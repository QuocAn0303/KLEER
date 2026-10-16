#!/usr/bin/env bash
# ============================================
# KLEER - Reset tai khoan admin chuan cho ca team (Linux/macOS/VPS/CI)
# Chay voi: bash tools/reset-admin.sh
# ============================================
#
# Dua MOI database local ve dung 1 tai khoan duy nhat:
#   Username: admin
#   Password: admin
#
# Database nam trong Docker volume, KHONG nam trong Git nen khong the push.
# Chay script nay sau `git pull` + `docker compose up -d` de dong bo.
#
# Yeu cau: container php + mariadb dang chay, WordPress da duoc cai dat.
set -euo pipefail

# Git Bash tren Windows tu convert moi tham so dang /... thanh path Windows
# truoc khi goi docker.exe -> duong dan trong container (/tmp, /var/www) bi
# bien dang. Tat co che do khi script chay duoi MSYS2/Git Bash.
# Tren Linux/macOS bien nay khong co tac dung phu.
export MSYS_NO_PATHCONV=1

WP="docker compose exec -T php php /tmp/wp-cli.phar --path=/var/www/html --allow-root"

echo "== KLEER reset-admin =="

# 1. Nap wp-cli vao container neu chua co
echo "[1/5] Kiem tra wp-cli trong container php..."
docker compose exec -T php sh -c "test -x /tmp/wp-cli.phar || (curl -sfL -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x /tmp/wp-cli.phar)"

# 2. WordPress da duoc cai dat chua?
echo "[2/5] Kiem tra WordPress da cai dat..."
if ! $WP core is-installed >/dev/null 2>&1; then
  echo "[ERROR] WordPress chua duoc cai dat (DB con trong)."
  echo "        Hay mo http://localhost de cai dat lan dau, hoac chay:"
  echo "        docker compose exec php php /tmp/wp-cli.phar core install --path=/var/www/html --allow-root --url=http://localhost --title=KLEER --admin_user=admin --admin_password=admin --admin_email=admin@kleer.local --skip-email"
  exit 1
fi

# 3. Tao moi hoac reset mat khau admin -> admin
echo "[3/5] Dong bo tai khoan admin / admin..."
if $WP user get admin --field=ID >/dev/null 2>&1; then
  $WP user update admin --user_pass=admin --role=administrator --user_email=admin@kleer.local
  echo "      Da reset mat khau admin -> admin."
else
  $WP user create admin admin@kleer.local --role=administrator --user_pass=admin
  echo "      Da tao moi user admin."
fi

ADMIN_ID="$($WP user get admin --field=ID | tr -d '\r[:space:]')"

# 4. Xoa moi tai khoan khac, chuyen bai viet ve cho admin
echo "[4/5] Xoa cac tai khoan thua (giu lai duy nhat admin)..."
OTHERS="$($WP user list --field=user_login | tr -d '\r' | grep -vx 'admin' || true)"
if [ -n "$OTHERS" ]; then
  echo "$OTHERS" | while IFS= read -r u; do
    [ -z "$u" ] && continue
    echo "      Xoa user thua: $u"
    # shellcheck disable=SC2086
    $WP user delete "$u" --reassign="$ADMIN_ID" --yes
  done
else
  echo "      Khong co tai khoan thua."
fi

# 5. Xoa cache + bao danh sach cuoi
echo "[5/5] Xoa cache va chot danh sach..."
$WP cache flush >/dev/null 2>&1 || true
$WP user list --fields=ID,user_login,roles

echo ""
echo "[OK] Xong! Dang nhap tai http://localhost/wp-login.php voi admin / admin"
