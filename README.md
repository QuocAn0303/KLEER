# KLEER — Unisex Skincare E-commerce Platform (Lead Generation & Sales)

KLEER là hệ thống thương mại điện tử (B2C) chuyên biệt ngành mỹ phẩm chăm sóc da unisex, được xây dựng trên **WordPress + WooCommerce**, kết hợp kiến trúc plugin riêng (`kleer-plugin`), theme riêng (`kleer-plugin`), cổng thanh toán (`kleer-payments`) và hệ thống email (`kleer-mailer`). Dự án thuộc học phần **EC312 — Thiết kế hệ thống Thương mại Điện tử**, trường Đại học Công nghệ Thông tin – ĐHQG TP.HCM.

## 🎯 Mục tiêu kinh doanh & Lead Generation

KLEER được thiết kế để **kéo lead và chuyển đổi bán hàng** thông qua:

| Mục tiêu | Cơ chế trong hệ thống |
|----------|----------------------|
| **Thu hút khách hàng mới** | Skin Quiz (trắc nghiệm chẩn đoán da) — khách nhập thông tin da, nhận routine cá nhân hóa, để lại email để xem kết quả |
| **Giữ chân khách hàng** | Store Credit (hoàn tiền nhanh) khi đổi trả — mã crédit khuyến khích mua lại |
| **Tăng giá trị đơn hàng (AOV)** | Giảm giá 15% Combo Routine khi giỏ hàng có từ 3 bước skincare trở lên |
| **Giảm rủi ro mua hàng** | Cảnh báo xung đột hoạt chất (AHA/BHA + Retinol), chính sách đổi trả 7 ngày, email hóa đơn kèm mã QR tra cứu |
| **Tư vấn cá nhân hóa** | Thuật toán chấm điểm da v4 đề xuất routine 2–6 bước phù hợp loại da, vấn đề da, mức độ nhạy cảm |

---

## 📋 Mục lục

- [Tổng quan hệ thống](#tổng-quan-hệ-thống)
- [Chức năng & Lead Generation](#chức-năng--lead-generation)
- [Kiến trúc & công nghệ](#kiến-trúc--công-nghệ)
- [Thiết kế cơ sở dữ liệu](#thiết-cơ-sở-dữ-liệu)
- [REST API & Tích hợp](#rest-api--tích-hợp)
- [Chạy dự án ở local](#chạy-dự-án-ở-local)
- [Cấu hình môi trường](#cấu-hình-môi-trường)
- [Hướng dẫn phát triển cho AI Agent](#hướng-dẫn-phát-triển-cho-ai-agent)
- [Kiểm thử và vận hành](#kiểm-thử-và-vận-hành)
- [Triển khai sản xuất](#triển-khai-sản-xuất)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Tài liệu tham khảo](#tài-liệu-tham-khảo)
- [Đóng góp](#đóng-góp)

---

## 🏗️ Tổng quan hệ thống

### Mô hình kinh doanh
- **B2C (Business-to-Consumer)** bán lẻ mỹ phẩm chăm sóc da
- **Guided Selling**: Dẫn dắt khách hàng từ chẩn đoán da → routine cá nhân hóa → mua hàng → đổi trả/hoàn tiền
- **Tham khảo thực tế**: Curology (skin quiz), INCIDecoder (thành phần INCI), Hasaki (danh mục mỹ phẩm VN), Shopee (hoàn tiền nhanh)

### Hai mô hình kiến trúc kết hợp
1. **3-Tier Deployment Architecture** (Docker Compose): Nginx (presentation/proxy) → PHP-FPM (application) → MariaDB + Redis (data/cache)
2. **Microkernel / Plugin-based + Layered OOP** (tầng ứng dụng): `kleer-plugin` tách biệt hoàn toàn business logic khỏi theme, cho phép thay đổi giao diện mà không làm gián đoạn nghiệp vụ.

### Danh mục plugin & module
| Plugin/Module | Vị trí | Vai trò |
|--------------|--------|---------|
| `kleer-theme` | `wp-content/themes/kleer-theme/` | Presentation Layer — HTML/CSS BEM, Vanilla JS ES6 SPA (Skin Quiz, AJAX Filter, Routine Cart), không dùng Page Builder |
| `kleer-plugin` | `wp-content/plugins/kleer-plugin/` | Core Business Logic — REST API `/wp-json/kleer/v1/*`, Routine v4, Combo 15%, cảnh báo hoạt chất, HPOS, Store Credit |
| `kleer-payments` | `wp-content/plugins/kleer-payments/` | Tích hợp VNPay Sandbox (HMAC-SHA512), VietQR Napas động, COD |
| `kleer-mailer` | `wp-content/plugins/kleer-mailer/` | SMTP Email hóa đơn HTML + mã QR, email mã Store Credit |

---

## ⚡ Chức năng & Lead Generation

### 1. Skin Quiz — Chẩn đoán da &Lead Capture
- **6 câu hỏi** (v4) thu thập: loại da, vấn đề da, mức độ nhạy cảm, thói quen sinh hoạt
- **Thuật toán chấm điểm v4** đề xuất Routine 2–6 bước, loại bỏ hoạt chất mạnh nếu da nhạy cảm
- **Kết quả hiển thị** hồ sơ da + sản phẩm khuyến nghị + nút *"Thêm trọn bộ vào giỏ hàng (Giảm 15%)"*
- **Lead capture**: Email khách hàng được lưu để xem kết quả quiz, dùng cho email marketing sau này

### 2. Catalog & AJAX Filter
- Lưới sản phẩm ảnh vuông 1:1
- Bộ lọc đa tiêu chí không tải lại trang: Loại da, Vấn đề da, Bước Routine, Hoạt chất INCI, Giá, Thương hiệu
- URL history (pushState) cho SEO

### 3. Product Detail Page (PDP)
- Badge phân loại da, vị trí bước trong Routine
- Hộp bảng thành phần INCI chi tiết (tham chiếu INCIDecoder)
- Thông tin bước dưỡng trong Routine

### 4. Smart Cart — Combo Routine & Cảnh báo hoạt chất
- **1-Click Add Routine Bundle**: Thêm nhiều sản phẩm cùng lúc
- **Giảm giá 15% Combo Routine**: Tự động áp dụng khi giỏ có từ 3 bước Routine hợp lệ
- **Cảnh báo xung đột hoạt chất**: AHA/BHA + Retinoids dùng cùng lúc → cảnh báo da liễu (không chặn checkout)

### 5. Checkout & Thanh toán
- 3 phương thức: COD, VietQR (mã QR Napas động), VNPay Sandbox (HMAC-SHA512)
- IPN (Server-to-Server) cho VNPay xác nhận trạng thái đơn hàng

### 6. Order Management & Instant Refund
- Quản lý đơn hàng trên bảng phẳng **WooCommerce HPOS** (`wp_wc_orders*`)
- **Hoàn tiền nhanh Nhánh 1**: Khách nhập mã vận đơn trả hàng → nhận ngay mã Store Credit (100% đơn gốc)
- Gửi email hóa đơn HTML kèm mã QR tra cứu đơn hàng

### 7. Store Credit — Retention Engine
- Mã crédito: `KLR-CREDIT-{ORDER_ID}-{HASH}`
- Giá trị = 100% `total_amount` của đơn gốc
- `usage_limit = 1`, chỉ khách hàng có email phù hợp mới sử dụng

---

## 🏗️ Kiến trúc & Công nghệ

### 3-Tier Deployment Architecture (Docker Compose)

```
Trình duyệt/Mobile
      │
      ▼
┌─────────────┐
│  nginx      │ Tier 1: Presentation & Web Proxy (Reverse Proxy, FastCGI, Static assets)
│  :8082      │
└─────────────┘
      │ FastCGI
      ▼
┌─────────────┐
│  php:8.2-fpm│ Tier 2: Application & Business Logic (WordPress 6.x + WooCommerce 8.x)
│  :9000      │   ├── kleer-theme (Presentation)
│             │   ├── kleer-plugin (Core Business + REST API)
│             │   ├── kleer-payments (VNPay, VietQR, COD)
│             │   └── kleer-mailer (SMTP Email)
└─────────────┘
      │ SQL / Cache
      ▼
┌─────────────┬─────────────┐
│  MariaDB    │   Redis     │ Tier 3: Data & Caching
│  :3307      │   :6379     │   WooCommerce HPOS tables + KleerCache
└─────────────┴─────────────┘
```

### Layered OOP Architecture (trong `kleer-plugin`)

**Luồng phụ thuộc một chiều:**
```
HTTP/AJAX Request → Endpoints → Controllers → Services → Contracts (Interfaces) ← Repositories → Models/Database
```

| Tầng | Thư mục | Trách nhiệm |
|------|---------|-------------|
| Presentation | `kleer-theme/` | HTML5/CSS BEM, Vanilla JS ES6 SPA (quiz.js, catalog-filter.js, routine-cart.js) |
| Routing/Endpoints | `kleer-plugin/src/Endpoints/` | `register_rest_route` namespace `kleer/v1`, permission_callback, CORS |
| Controller | `kleer-plugin/src/Controllers/` | `WP_REST_Request`, JSON Schema validation (`ValidatesJsonPayload`), gọi Service, trả `WP_REST_Response` |
| Service/Domain | `kleer-plugin/src/Services/` | `QuizEngineService`, `CartComboService`, `HposService`, `InstantRefundService` |
| Repository | `kleer-plugin/src/Repositories/` | Hiện thực `Contracts`, truy vấn dữ liệu HPOS + `KleerCache` |
| Contracts | `kleer-plugin/src/Contracts/` | `ProductRepositoryInterface`, `QuizProductRepositoryInterface`, etc. |

### Các Service chính
| Service | Chức năng |
|---------|-----------|
| `QuizEngineService` | Thuật toán chấm điểm da v4, chọn Routine, sản phẩm phù hợp, gentle fallback |
| `CartComboService` | Giảm giá 15% Combo (≥3 bước), quét xung đột hoạt chất |
| `HposService` | Đồng bộ đơn hàng HPOS, khai báo compatibility |
| `InstantRefundService` | Hoàn tiền nhanh, tạo mã Store Credit, gửi email |

---

## 🗄️ Thiết kế Cơ sở Dữ Liệu

### 8 bảng luận lý chuẩn hóa 3NF

| Bảng | PK | FK | Mô tả |
|------|-----|-----|-------|
| `Users` | `user_id` | — | Khách hàng & Quản trị viên |
| `Products` | `product_id` | — | Sản phẩm chăm sóc da (≥500 SKU) |
| `Taxonomy_Terms` | `term_id` | — | Từ điển thuộc tính da liễu & hoạt chất |
| `Product_Taxonomies` | `product_id, term_id` | `product_id`, `term_id` | Bảng liên kết Đa-Đa sản phẩm ↔ thuộc tính |
| `Orders` | `order_id` | `customer_id` → Users | Đơn hàng (HPOS flat table) |
| `Order_Items` | `order_item_id` | `order_id`, `product_id` | Chi tiết dòng sản phẩm trong đơn hàng |
| `Payment_Transactions` | `transaction_id` | `order_id` | Giao dịch COD / VietQR / VNPay |
| `Store_Credits` | `credit_id` | `source_order_id` → Orders | Mã Hoàn tiền nhanh (Instant Refund) |

### Ánh xạ vật lý sang WooCommerce HPOS

| Bảng luận lý | Bảng vật lý MariaDB | Cơ chế |
|-------------|---------------------|--------|
| `Users` | `wp_users`, `wp_usermeta` | Định danh, mật khẩu băm, phân quyền |
| `Products` | `wp_posts` (product), `wp_wc_product_meta_lookup` | Index trực tiếp sku, price, stock_status |
| `Taxonomy_Terms` + `Product_Taxonomies` | `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships` | 4 trục: pa_skin_type, pa_skin_concern, pa_routine_step, pa_active_ingredient |
| `Orders` + `Payment_Transactions` | `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta` | HPOS tách đơn hàng khỏi `wp_posts` |
| `Order_Items` | `wp_woocommerce_order_items`, `wp_wc_order_product_lookup` | Chi tiết dòng + khoản phí giảm 15% |
| `Store_Credits` | `wp_posts` (shop_coupon), `wp_postmeta` | `discount_type='fixed_cart'`, `usage_limit=1` |

### Redis Cache (KleerCache)
- Nhóm: `kleer_products`, `kleer_quiz`, `kleer_api`
- Cấu hình: `maxmemory 256mb`, `maxmemory-policy allkeys-lru`, `appendonly yes`

---

## 🔌 REST API & Tích hợp

### Danh mục Endpoint

| Method | Route | Mục đích | Auth |
|--------|-------|----------|------|
| `GET` | `/wp-json/kleer/v1/health` | Kiểm tra API hoạt động | Không |
| `POST` | `/wp-json/kleer/v1/skin-quiz` | Nhận bài quiz, trả routine & sản phẩm | Có (nonce) |
| `POST` | `/wp-json/kleer/v1/recommend` | Alias cho tư vấn routine | Có (nonce) |

### Ví dụ gọi API (PowerShell)

```powershell
# Health check
curl.exe -i 'http://localhost:8082/?rest_route=/kleer/v1/health'

# Gửi bài quiz v4
curl.exe -i 'http://localhost:8082/?rest_route=/kleer/v1/skin-quiz?version=v4' `
  -H 'Content-Type: application/json' `
  -H 'X-WP-Nonce: <nonce>' `
  --data-binary '@tools/quiz/valid-submission-v4.json'
```

### Lưu ý API
- **v1**: 5 câu hỏi, có thể trả `503` nếu thiếu sản phẩm
- **v4**: 6 câu hỏi, có thể trả `missing_steps` / `warnings`; chọn bằng query `?version=v4`
- Client từ origin khác cần cấu hình `KLEER_CORS_ALLOWED_ORIGINS` trong `.env`
- Client có cookie đăng nhập cần gửi header `X-WP-Nonce`

Tài liệu chi tiết: [docs/api/README.md](docs/api/README.md), [OpenAPI JSON](docs/api/openapi.json), [Postman Collection](docs/api/KLEER-Routine-v4.postman_collection.json)

---

## 🖥️ Chạy dự án ở Local

### 1. Chuẩn bị

| Công cụ | Yêu cầu |
|---------|---------|
| Git | Đã cài |
| Docker Engine / Desktop | Đang chạy |
| Docker Compose v2 | Đã cài |
| PowerShell | Nếu dùng `setup.ps1` trên Windows |
| Python 3 | Chỉ cần khi chạy công cụ Python (`requirements.txt`) |

```powershell
git clone https://github.com/QuocAn0303/KLEER.git
cd KLEER
Copy-Item .env.example .env
docker version
docker compose version
```

### 2. Kiểm tra WordPress Core

Repo có các file WordPress nhưng `.gitignore` loại trừ `wp-config.php`. Sau khi clone:
- Kiểm tra `wp-config.php` đã tồn tại (sau khi copy từ `.env.example` hoặc tạo thủ công)
- Nếu thiếu WordPress core, bổ sung từ phiên bản tương ứng với `wp-includes/version.php`
- Giữ nguyên thư mục `wp-content/` của dự án (chứa `kleer-theme`, `kleer-plugin`, etc.)

### 3. Cấu hình `.env`

| Biến | Mô tả | Ví dụ |
|------|-------|-------|
| `NGINX_PORT` | Cổng website local | `8082` |
| `PHPMYADMIN_PORT` | Cổng phpMyAdmin | `8083` |
| `MYSQL_DATABASE` | Tên database | `kleer_db` |
| `MYSQL_USER` | User database | `kleer_user` |
| `MYSQL_PASSWORD` | Mật khẩu database | `kleer_password_here_change_me` |
| `WORDPRESS_DB_HOST` | Host DB trong Docker | `mariadb:3306` |
| `WP_HOME`, `WP_SITEURL` | URL WordPress | `http://localhost:8082` |
| `KLEER_CORS_ALLOWED_ORIGINS` | Origin frontend được phép | `http://localhost:8082,http://localhost:3000` |

### 4. Khởi động

```powershell
# Windows (tùy chọn)
powershell -ExecutionPolicy Bypass -File setup.ps1

# Hoặc chạy trực tiếp
docker compose up -d --build
docker compose ps
```

Container sẵn sàng khi tất cả hiển thị `healthy`.

### 5. Hoàn tất trong WordPress

1. Mở [http://localhost:8082](http://localhost:8082) và [http://localhost:8082/wp-admin/](http://localhost:8082/wp-admin/)
2. Hoàn tất cài đặt WordPress nếu database trống
3. Cài & kích hoạt **WooCommerce** (plugin bên thứ ba, không được Git theo dõi)
4. Kích hoạt **KLEER Plugin**, **KLEER Payments**, **KLEER Mailer**
5. Chọn theme **KLEER** tại Appearance → Themes
6. Kiểm tra trang Shop, Cart, Checkout, My Account; lưu Permalinks nếu đường dẫn đẹp trả 404
7. Cấu hình phương thức thanh toán và SMTP theo tài liệu liên kết

### 6. Truy cập phpMyAdmin
[http://localhost:8083](http://localhost:8083) — thông tin từ `.env` (`kleer_user` / `kleer_password_here_change_me`, database `kleer_db`)

---

## 🤖 Hướng dẫn Phát triển cho AI Agent

Nếu bạn là AI Agent được giao tiếp nối hoặc phát triển tính năng mới, hãy đọc kỹ phần này trước khi viết code.

### Nguyên tắc cốt lõi
1. **Logic nghiệp vụ nằm trong `kleer-plugin`**, không nằm trong theme functions.php
2. **Giao diện nằm trong `kleer-theme`**, không dùng Page Builder (Elementor, WPBakery...)
3. **Tuân theo Layered OOP**: Endpoints → Controllers → Services → Contracts ← Repositories → Models/DB
4. **Không sửa trực tiếp WordPress core** (`wp-admin`, `wp-includes`)

### Cách thêm Endpoint REST API mới
1. Tạo file trong `kleer-plugin/src/Endpoints/`
2. Đăng ký route trong `register_rest_route` dưới namespace `kleer/v1`
3. Thêm `permission_callback` kiểm tra nonce/quyền
4. Tạo Controller trong `kleer-plugin/src/Controllers/` nếu logic phức tạp
5. Thêm validation qua `ValidatesJsonPayload` trait

### Cách thêm Service nghiệp vụ mới
1. Tạo class trong `kleer-plugin/src/Services/`
2. Định nghĩa Interface trong `kleer-plugin/src/Contracts/` (nếu cần repository)
3. Kết nối Repository và Service qua Constructor Injection
4. Gọi Service từ Controller, không gọi trực tiếp Repository từ Controller

### Cách thêm bảng CSDL mới
1. Thêm bảng vào schema 3NF (nếu là bảng luận lý)
2. Tạo Repository implements Contract tương ứng
3. Ánh xạ sang bảng vật lý nếu cần (HPOS, wp_posts, wp_terms, etc.)
4. Cập nhật tài liệu tại `docs/`

### Cấu hình email test (Mailpit)
```sh
docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d mailpit
```
Thêm vào `wp-config.php` (trước `wp-settings.php`):
```php
define('KLEER_SMTP_HOST', 'mailpit');
define('KLEER_SMTP_PORT', 1025);
define('KLEER_SMTP_SECURE', '');
define('KLEER_MAIL_FROM', 'no-reply@kleer.local');
define('KLEER_MAIL_FROM_NAME', 'KLEER');
```
Xem email tại [http://localhost:8025](http://localhost:8025)

### Cách tối ưu cho Marketing & Sales
1. **Skin Quiz**: Tùy chỉnh câu hỏi và thuật toán chấm điểm tại `kleer-plugin/src/Config/QuizQuestions.php` và `QuizEngineService.php`
2. **Combo Routine**: Điều chỉnh ngưỡng giảm giá và bước yêu cầu tại `CartComboService.php`
3. **Store Credit**: Điều chỉnh giá trị crédito và thời hạn sử dụng tại `InstantRefundService.php`
4. **Cảnh báo hoạt chất**: Cập nhật danh sách hoạt chất kỵ nhau tại `CartComboService::scanIngredientConflicts()`
5. **Email marketing**: Sửa template email tại `kleer-mailer/templates/invoice.php`

### Quy tắc commit
- Làm việc trên nhánh `develop`
- Giữ logic nghiệp vụ trong plugin, giao diện trong theme
- Chạy kiểm thử liên quan trước khi commit
- Cập nhật tài liệu khi thay đổi API hoặc cấu hình

---

## 🧪 Kiểm thử và Vận hành

### Chạy kiểm thử CLI

```sh
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/run_tests.php
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/verify_php_ini.php
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/verify_cache.php
docker compose exec -T php php wp-content/plugins/kleer-payments/tests/test-qr-payload.php
docker compose exec -T php php wp-content/plugins/kleer-mailer/tests/test-qr-encoder.php
```

- Kiểm thử cache cần Redis hoạt động
- Kiểm thử HTTP và luồng mua hàng cần WordPress, WooCommerce, sản phẩm đã cấu hình
- Xem [Test Plan](docs/TEST_PLAN.md) và [kịch bản tải k6](tests/load/kleer-load.js)

### Lệnh vận hành thường dùng

```sh
docker compose ps
docker compose logs --tail=100 nginx php mariadb
docker compose restart php nginx
docker compose down
```

- `docker compose down` giữ named volume
- `docker compose down -v` xóa cả volume database và Redis (chỉ dùng khi đã sao lưu)

### CI/CD
Workflow [`.github/workflows/docker.yml`](.github/workflows/docker.yml) thực hiện:
- Build Docker image
- Kiểm tra cấu hình
- Kiểm tra PHP / plugin / cache
- Smoke test Nginx
- Quét bảo mật

---

## 🚀 Triển khai Sản xuất

### Yêu cầu trước khi triển khai
- VPS có Docker + Docker Compose v2
- Domain đã trỏ A record về IP VPS
- Cổng 80 và 443 mở trên firewall

### Quy trình triển khai
```bash
git clone https://github.com/QuocAn0303/KLEER.git
cd KLEER
./deploy/deploy.sh shop.kleer.vn
```

Script tự động:
1. Render config Nginx theo domain
2. Tạo `.env.production` với mật khẩu ngẫu nhiên
3. Kiểm tra DNS khớp IP VPS
4. Build container
5. Lấy chứng thư Let's Encrypt (tự gia hạn mỗi 12 giờ)
6. Khởi động và báo cáo kết quả

### Kết quả cần đạt
| Kiểm tra | Kết quả mong đợi |
|----------|-----------------|
| `http://<domain>/health` | `301` (redirect sang HTTPS) |
| `https://<domain>/health` | `200` |
| Chứng thư | Let's Encrypt, tự gia hạn |
| `ssl_protocols` | Chỉ TLS 1.2 + 1.3 |
| HSTS | `max-age=31536000; includeSubDomains` |

Tài liệu chi tiết: [docs/flows/L09-infra-devops/flow-card.md](docs/flows/L09-infra-devops/flow-card.md)

---

## 📁 Cấu trúc Thư mục

```
KLEER/
├── .github/workflows/          # CI/CD workflow
├── .env.example                # Mẫu biến môi trường
├── docker-compose*.yml         # Stack local, Mailpit, override production
├── Dockerfile.php              # PHP-FPM + extensions (redis, intl, gd, soap, mbstring, zip)
├── nginx.conf                  # Cấu hình Nginx (local HTTP)
├── php.ini                     # Cấu hình PHP
├── setup.ps1                   # Script hỗ trợ thiết lập Windows
├── HUONG_DAN_DOCKER.md         # Hướng dẫn Docker chi tiết
├── init-scripts/               # Snapshot SQL nạp khi volume DB trống
│   └── 01-init.sql
├── deploy/                     # Script HTTPS và template Nginx production
│   ├── deploy.sh
│   ├── nginx.https.conf
│   └── nginx.https.conf.template
├── wp-admin/, wp-includes/     # WordPress core (các file trong workspace)
├── wp-content/
│   ├── themes/kleer-theme/     # Theme dự án (PHP BEM, Vanilla JS ES6)
│   │   ├── assets/css/         # CSS BEM (global, catalog, product_detail, home)
│   │   ├── assets/js/          # Vanilla JS (quiz.js, catalog-filter.js, routine-cart.js)
│   │   ├── assets/images/      # Ảnh sản phẩm, hero, ingredients
│   │   ├── front-page.php      # Trang chủ
│   │   ├── archive-product.php # Danh mục sản phẩm
│   │   ├── single-product.php  # Chi tiết sản phẩm
│   │   ├── functions.php       # Khởi tạo theme, enqueue assets
│   │   └── woocommerce/        # Template overrides WooCommerce
│   ├── plugins/
│   │   ├── kleer-plugin/       # Core business logic (Endpoints, Controllers, Services, Repositories, Contracts)
│   │   ├── kleer-payments/     # VNPay Sandbox, VietQR Napas, COD
│   │   └── kleer-mailer/       # SMTP email hóa đơn + QR, Store Credit email
│   ├── object-cache.php        # Redis object-cache drop-in
│   └── uploads/                # Media local (không đưa vào Git)
├── data/                       # Dữ liệu sản phẩm CSV (≥500 SKU)
├── tools/                      # Import, crawler, automation, kiểm tra quiz
├── tests/load/                 # Kịch bản kiểm thử tải (k6)
└── docs/                       # Đặc tả, API, luồng nghiệp vụ, bằng chứng kiểm thử
    ├── task/Quoc_An.md         # Task thiết kế hệ thống (Chương 4)
    ├── PLUGIN_ARCHITECTURE.md  # Kiến trúc plugin chi tiết
    ├── KLEER_ECOMMERCE_SPECIFICATION.md  # Đặc tả thương mại điện tử
    ├── PAYMENT_GATEWAYS.md     # Tích hợp thanh toán
    ├── BAO_CAO_DO_AN_KLEER.md  # Báo cáo đồ án
    ├── api/                    # OpenAPI, Postman collection
    ├── flows/                  # Luồng nghiệp vụ (checkout, order, fulfillment)
    └── evidence/               # Bằng chứng kiểm thử
```

---

## 📚 Tài liệu Tham khảo

| Tài liệu | Mô tả |
|----------|-------|
| [Đặc tả thương mại điện tử](docs/KLEER_ECOMMERCE_SPECIFICATION.md) | Tổng đặc tả hệ thống |
| [Kiến trúc plugin](docs/PLUGIN_ARCHITECTURE.md) | Layered OOP, dependency rules, endpoint catalog |
| [Thiết kế hệ thống (Quốc An)](docs/task/Quoc_An.md) | Chương 4: Kiến trúc 3-Tier, ERD 3NF, UI/UX |
| [API & Routine](docs/api/README.md) | Hướng dẫn gọi REST API |
| [Thanh toán](docs/PAYMENT_GATEWAYS.md) | VNPay Sandbox, VietQR, COD |
| [Email SMTP](docs/flows/L05-order-processing/email-smtp.md) | Cấu hình SMTP, hóa đơn HTML |
| [Hiệu năng](docs/PERFORMANCE.md) | Redis cache, HPOS, load test |
| [Kế hoạch kiểm thử](docs/TEST_PLAN.md) | Test cases, UAT report |
| [Xử lý đơn hàng](docs/bpmn/fulfillment/fulfillment-notes.md) | Luồng xử lý fulfillment |

> **Lưu ý:** Một số tài liệu ghi nhận thiết kế tại thời điểm trước. Khi tích hợp, hãy đối chiếu với mã nguồn, schema và cấu hình hiện tại; không xem mô tả kế hoạch là xác nhận tính năng đã nghiệm thu.

---

## 🤝 Đóng Góp

1. Làm việc trên nhánh `develop`
2. Giữ logic nghiệp vụ trong plugin; giao diện trong `kleer-theme`
3. Chạy kiểm thử liên quan và cập nhật tài liệu khi thay đổi API hoặc cấu hình
4. Tạo commit và push lên `develop`, mô tả thay đổi và kết quả kiểm tra để nhóm review

Trao đổi lỗi hoặc đề xuất qua [Issues của dự án](https://github.com/QuocAn0303/KLEER/issues).

---

**Phụ trách thiết kế hệ thống (Chương 4):** Lê Nguyễn Quốc An — Scrum Master & DevOps  
**Nhóm trưởng (PO & BA):** Nguyễn Tấn Phát  
**Môn học:** EC312 — Thiết kế hệ thống Thương mại Điện tử  
**Năm học:** 2026–2027