# FLOW CARD: L09 - HẠ TẦNG, TRIỂN KHAI & HIỆU NĂNG

- **Mã luồng:** L09
- **Nhóm:** Nền tảng
- **Mục tiêu:** Đóng gói Docker đồng nhất, deploy Cloud VPS domain HTTPS (Rubric 5.1) và tăng tốc với Redis (Rubric 5.3).
- **Flow Owner:** Lê Nguyễn Quốc An
- **PO duyệt:** Nguyễn Tấn Phát
- **QA:** Nguyễn Ngọc Thảo Uyên
- **Trạng thái:** Phần code/config hoàn tất · VPS chờ có domain
- **Thư mục tài liệu:** `docs/flows/L09-infra-devops/`

---

## 1. Docker (đóng gói đồng nhất)

| Thành phần | Trạng thái |
|---|---|
| `Dockerfile.php` | PHP 8.2 FPM + 12 extension gồm `redis`, `intl`, `gd`, `soap`, `mbstring`, `zip` |
| `docker-compose.yml` | 5 service: nginx, php, mariadb, redis, phpmyadmin — tất cả có healthcheck |
| `nginx.conf` | Bản HTTP cho local/CI, không cần chứng thư |
| `deploy/nginx.https.conf.template` | Bản HTTPS cho production |
| `docker-compose.prod.yml` | Override thêm TLS + certbot tự gia hạn |

**Nguyên tắc bất di bất dịch:** cấu hình local **không bao giờ** yêu cầu chứng thư SSL. Nhờ vậy
`docker compose up` và CI luôn chạy được trên máy không có domain.

### Lệnh dùng hằng ngày

```bash
cp .env.example .env      # làm 1 lần
# sửa .env: password + WordPress Salt
docker compose up -d
docker compose ps         # cả 5 container phải healthy
```

---

## 2. HTTPS trên VPS (Rubric 5.1)

### 2.1. Yêu cầu trước khi bắt đầu

- VPS có Docker + Docker Compose v2.
- Domain đã trỏ A record về IP VPS **và đã lan giải xong**.
- Cổng **80** và **443** mở trên firewall/security group.

### 2.2. Deploy

```bash
git clone https://github.com/QuocAn0303/KLEER.git
cd KLEER
./deploy/deploy.sh shop.kleer.vn
```

Script tự động: render config nginx theo domain → tạo `.env.production` với password ngẫu nhiên
→ kiểm tra DNS có khớp IP VPS không → build → lấy chứng thư Let's Encrypt → khởi động → báo cáo kết quả.

Chạy lại nhiều lần đều an toàn (chứng thư còn hạn sẽ không bị lấy lại).

### 2.3. Kết quả cần đạt

| Kiểm tra | Kết quả mong đợi |
|---|---|
| `http://<domain>/health` | `301` (redirect sang HTTPS) |
| `https://<domain>/health` | `200` |
| Chứng thư | Let's Encrypt, tự gia hạn mỗi 12 giờ |
| `ssl_protocols` | Chỉ TLS 1.2 + 1.3 |
| HSTS | `max-age=31536000; includeSubDomains` |

### 2.4. Ghi chup bằng chứng cho Rubric

```bash
echo | openssl s_client -connect shop.kleer.vn:443 -servername shop.kleer.vn 2>/dev/null \
  | openssl x509 -noout -dates -issuer
```

---

## 3. Redis + HPOS (Rubric 5.3)

### 3.1. Object cache

- `wp-content/object-cache.php` — drop-in chuẩn của WordPress, tự nạp khi file tồn tại.
- `Kleer\Support\Cache\KleerCache` — lớp cache chuẩn hóa, đếm hit/miss phục vụ đo hiệu năng.

**Cấu hình** (trong `.env`):

```dotenv
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DATABASE=0
REDIS_PREFIX=kleer:
REDIS_TIMEOUT=1.0
```

**Cache theo nhóm** — xóa cache sản phẩm không làm mất cache Quiz:

| Nhóm | Chứa gì |
|---|---|
| `kleer_products` | Danh sách/chi tiết sản phẩm |
| `kleer_quiz` | Kết quả Quiz theo session |
| `kleer_api` | Response REST API |

### 3.2. Invalidation tự động

`Kleer\Support\Cache\CacheInvalidation` gắn vào các hook `save_post_product`,
`woocommerce_update_product`, `deleted_post`, `updated_option`… để dữ liệu không bị cache cũ.

### 3.3. HPOS

`Kleer\Support\Cache\WooCommerceCompatibility` khai báo `custom_order_tables` + cart/checkout
blocks qua `FeaturesUtil::declare_compatibility()` trên hook `before_woocommerce_init`.
Không khai báo thì WooCommerce hiện cảnh báo "incompatible".

### 3.4. Cân bằng tải (khuyến nghị)

Redis đang chạy `--maxmemory 256mb --maxmemory-policy allkeys-lru`. `allkeys-lru` phù hợp cho
cache vì khi đầy bộ nhớ thì **loại bỏ khóa ít dùng nhất**, không bao giờ báo lỗi ghi.

### 3.5. Đo hiệu năng

```bash
# Hit rate hiện tại
docker exec kleer-php php -r '
  require "/var/www/html/wp-content/plugins/kleer-plugin/kleer-plugin.php";
  $c = new Kleer\Support\Cache\KleerCache(getenv("REDIS_HOST"), 6379, 1.0, false);
  print_r($c->stats());
'

# Load test (cần chạy trên stack có WordPress thật)
docker run --rm -i --network kleer_kleer-net \
  -v "$PWD/tests/load:/scripts" \
  -e BASE_URL=http://kleer-nginx grafana/k6 run /scripts/kleer-load.js
```

Kịch bản k6 in ra p50/p95/p99 và tự cảnh báo nếu `/wp-json` trả 404 (tức là thiếu WordPress core).

---

## 4. Còn lại

- [ ] Có domain + VPS → chạy `./deploy/deploy.sh <domain>`.
- [ ] Chụp output mục 2.4 làm bằng chứng Rubric 5.1.
- [ ] Bật HPOS trong WooCommerce → Settings → Advanced → Features.
- [ ] Chạy load test trên VPS và lưu số liệu p95 làm bằng chứng Rubric 5.3.
