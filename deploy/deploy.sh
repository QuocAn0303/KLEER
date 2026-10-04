#!/usr/bin/env bash
# ============================================
# KLEER - Script deploy len VPS (Rubric 5.1: HTTPS)
# ============================================
#
# Chay tren may chu Linux co Docker + Docker Compose v2.
# Script nao khong chay theo duong dan tuyet doi, nen duoc goi tu thu muc goc repo.
#
#   ./deploy/deploy.sh shop.kleer.vn
#
# Chay lai nhieu lan deu an toan: script chi lay chung thu neu chua co, va
# renew chung thu khong can goi lai.

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${PROJECT_ROOT}"

readonly NGINX_TEMPLATE="${SCRIPT_DIR}/nginx.https.conf.template"
readonly NGINX_RENDERED="${SCRIPT_DIR}/nginx.https.conf"
readonly PROD_ENV="${PROJECT_ROOT}/.env.production"

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m!!\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mXX\033[0m %s\n' "$*" >&2; exit 1; }

# ============================================
# 1. Tham so
# ============================================
DOMAIN="${1:-}"

if [[ -z "${DOMAIN}" ]]; then
    DOMAIN="$(grep -E '^KLEER_SERVER_NAME=' .env 2>/dev/null | cut -d= -f2- || true)"
    [[ -n "${DOMAIN}" ]] || die "Chua truyen domain. Vi du: ./deploy/deploy.sh shop.kleer.vn"
fi

command -v docker >/dev/null 2>&1 || die "Chua cai Docker."
docker compose version >/dev/null 2>&1 || die "Chua cai Docker Compose v2."

log "Domain: ${DOMAIN}"

# ============================================
# 2. Render config nginx HTTPS
# ============================================
[[ -f "${NGINX_TEMPLATE}" ]] || die "Khong tim thay ${NGINX_TEMPLATE}"

sed "s|__SERVER_NAME__|${DOMAIN}|g" "${NGINX_TEMPLATE}" > "${NGINX_RENDERED}"

if grep -q '__SERVER_NAME__' "${NGINX_RENDERED}"; then
    die "Render nginx.https.conf that bai, con placeholder chua duoc thay the."
fi
log "Da render ${NGINX_RENDERED}"

# ============================================
# 3. Tao .env.production
# ============================================
if [[ ! -f "${PROD_ENV}" ]]; then
    warn "Chua co .env.production. Tao file moi tu .env.example."
    log "QUAN TRONG: doi password + WordPress Salt truoc khi len production."
    cp .env.example "${PROD_ENV}"

    SECRET_PASSWORD="$(openssl rand -base64 24)"
    salt_url="https://api.wordpress.org/secret-key/1.1/salt/"

    sed -i "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=${SECRET_PASSWORD}|" "${PROD_ENV}"
    sed -i "s|^MYSQL_PASSWORD=.*|MYSQL_PASSWORD=${SECRET_PASSWORD}|" "${PROD_ENV}"
    sed -i "s|^MYSQL_USER=.*|MYSQL_USER=kleer_user|" "${PROD_ENV}"
    sed -i "s|^WP_SITEURL=.*|WP_SITEURL=https://${DOMAIN}|" "${PROD_ENV}"
    sed -i "s|^WP_HOME=.*|WP_HOME=https://${DOMAIN}|" "${PROD_ENV}"
    sed -i "s|^NGINX_HOST=.*|NGINX_HOST=${DOMAIN}|" "${PROD_ENV}"
    sed -i "s|^WP_ENV=.*|WP_ENV=production|" "${PROD_ENV}"
    sed -i "s|^NGINX_PORT=.*|NGINX_PORT=80|" "${PROD_ENV}"
    sed -i "s|^KLEER_CORS_ALLOWED_ORIGINS=.*|KLEER_CORS_ALLOWED_ORIGINS=https://${DOMAIN}|" "${PROD_ENV}"
    sed -i "s|^WORDPRESS_DEBUG=.*|WORDPRESS_DEBUG=false|" "${PROD_ENV}"

    # WordPress Salt lay tu api.wordpress.org
    if command -v curl >/dev/null 2>&1; then
        curl -fsSL "${salt_url}" >> "${PROD_ENV}"
    else
        warn "Khong co curl. Tu lay salt tai ${salt_url} va them vao cuoi ${PROD_ENV}."
    fi

    chmod 600 "${PROD_ENV}"
    log "Da tao ${PROD_ENV}. KIEM TRA LAI NO TRUOC KHI TIEP TUC."
else
    log "Dung ${PROD_ENV} hien co."
fi

# Chan deploy neu con placeholder mac dinh
if grep -q 'put_your_unique_phrase_here\|root_password_here\|kleer_password_here' "${PROD_ENV}"; then
    die "Con gia tri placeholder trong .env.production. Hay thay password va WordPress Salt truoc."
fi

# ============================================
# 4. Kiem tra DNS
# ============================================
log "Kiem tra DNS..."
PUBLIC_IP="$(curl -fsS https://api.ipify.org 2>/dev/null || echo '')"
RESOLVED_IP="$(getent hosts "${DOMAIN}" 2>/dev/null | awk '{print $1}' | head -n1 || true)"

if [[ -n "${PUBLIC_IP}" && -n "${RESOLVED_IP}" && "${PUBLIC_IP}" != "${RESOLVED_IP}" ]]; then
    die "DNS chua dung: ${DOMAIN} -> ${RESOLVED_IP}, nhung may chu la ${PUBLIC_IP}. Cap nhat A record truoc."
fi
[[ -n "${PUBLIC_IP}" ]] && log "Public IP: ${PUBLIC_IP}"
[[ -n "${RESOLVED_IP}" ]] && log "Domain tro ve: ${RESOLVED_IP}"

# ============================================
# 5. Build + khoi dong
# ============================================
log "Build image..."
docker compose --env-file "${PROD_ENV}" -f docker-compose.yml -f docker-compose.prod.yml build

log "Khoi dong services (cong 80 de lay chung thu)..."
docker compose --env-file "${PROD_ENV}" -f docker-compose.yml -f docker-compose.prod.yml up -d --wait mariadb redis php

# ============================================
# 6. Lay chung thu
# ============================================
log "Lay chung thu Let's Encrypt cho ${DOMAIN}..."

if docker compose --env-file "${PROD_ENV}" -f docker-compose.yml -f docker-compose.prod.yml \
     run --rm --entrypoint "certbot certonly --webroot -w /var/www/certbot -d ${DOMAIN} --non-interactive --agree-tos --register-unsafely-without-email --keep-until-expiring" certbot; then
    log "Chung thu san co."
else
    die "Lay chung thu that bai. Kiem tra: DNS da tro ve may chu chua? Cong 80 da mo chua?"
fi

# ============================================
# 7. Khoi dong nginx voi HTTPS
# ============================================
log "Khoi dong nginx..."
docker compose --env-file "${PROD_ENV}" -f docker-compose.yml -f docker-compose.prod.yml up -d

log "Cho container on dinh..."
sleep 10
docker compose --env-file "${PROD_ENV}" -f docker-compose.yml -f docker-compose.prod.yml ps

# ============================================
# 8. Kiem tra
# ============================================
echo
log "Kiem tra ket qua:"
log "  HTTP  -> HTTP $(curl -s -o /dev/null -w '%{http_code}' "http://${DOMAIN}/health") (ky vong 301)"
log "  HTTPS -> HTTP $(curl -sk -o /dev/null -w '%{http_code}' "https://${DOMAIN}/health") (ky vong 200)"

if ! curl -sf "https://${DOMAIN}/health" >/dev/null 2>&1; then
    warn "HTTPS chua tra 200. Xem log: docker compose -f docker-compose.yml -f docker-compose.prod.yml logs nginx"
fi

echo
log "Xong. Truy cap: https://${DOMAIN}"
log "Gia han SSL tu dong moi 12 gio qua container certbot."
