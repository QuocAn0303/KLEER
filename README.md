# KLEER — Cửa hàng skincare unisex

KLEER là dự án website thương mại điện tử mỹ phẩm unisex thuộc học phần **EC312 — Thiết kế hệ thống Thương mại Điện tử**. Dự án sử dụng WordPress và WooCommerce, kết hợp giao diện riêng, bài trắc nghiệm tư vấn routine chăm sóc da, giỏ hàng combo và các tiện ích thanh toán, email đơn hàng.

## Mục lục

- [Chức năng](#chức-năng)
- [Kiến trúc và công nghệ](#kiến-trúc-và-công-nghệ)
- [Chạy dự án ở local](#chạy-dự-án-ở-local)
- [Cấu hình môi trường](#cấu-hình-môi-trường)
- [REST API](#rest-api)
- [Email thử nghiệm](#email-thử-nghiệm)
- [Kiểm thử và vận hành](#kiểm-thử-và-vận-hành)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Tài liệu liên quan](#tài-liệu-liên-quan)
- [Đóng góp](#đóng-góp)

## Chức năng

| Thành phần | Chức năng trong mã nguồn |
| --- | --- |
| **KLEER Theme** | Trang chủ, danh mục và chi tiết sản phẩm, bộ lọc catalog, giao diện Skin Quiz và thêm routine vào giỏ hàng. |
| **KLEER Plugin** | API tư vấn routine, taxonomy sản phẩm, kiểm tra JSON đầu vào, CORS, cache và nghiệp vụ giỏ hàng combo. |
| **Skin Quiz** | API hỗ trợ v1 (5 câu) và v4 (6 câu); giao diện hiện gửi `version=v4`. Gợi ý dựa trên sản phẩm WooCommerce và thuộc tính chăm sóc da. |
| **Combo routine** | Thêm nhiều sản phẩm vào giỏ, giảm 15% trên phần sản phẩm routine khi có ít nhất 3 bước khác nhau, hiển thị cảnh báo xung đột hoạt chất. |
| **KLEER Payments** | COD, chuyển khoản ngân hàng (BACS) và mã VietQR theo đơn hàng. Cấu hình tài khoản nhận tiền trong WooCommerce. |
| **KLEER Mailer** | SMTP và email hóa đơn HTML có QR tra cứu đơn hàng khi đơn chuyển sang `processing` hoặc `on-hold`, kèm cơ chế hạn chế gửi trùng. |
| **Hạ tầng** | Docker Compose, Nginx, PHP-FPM, MariaDB, Redis object cache; Mailpit tùy chọn cho email local. |

Repo có mã nguồn liên quan VNPay và tài liệu nghiên cứu thanh toán; sự hiện diện của các file này không xác nhận cổng đã được cấu hình hoặc nghiệm thu. Xem [tài liệu thanh toán](docs/PAYMENT_GATEWAYS.md) trước khi tích hợp.

## Kiến trúc và công nghệ

```text
Trình duyệt → Nginx → PHP-FPM → WordPress + WooCommerce
                                   ├── kleer-theme: giao diện
                                   ├── kleer-plugin: API và nghiệp vụ
                                   ├── kleer-payments: thanh toán
                                   ├── kleer-mailer: email đơn hàng
                                   ├── MariaDB: dữ liệu
                                   └── Redis: object cache
```

`kleer-plugin` tổ chức theo các tầng `Endpoints → Controllers → Services`, cùng `Models`, `Repositories`, `Contracts` và `Support`. Chi tiết tại [Plugin Architecture](docs/PLUGIN_ARCHITECTURE.md).

| Service | Image / runtime trong cấu hình | Cổng trên máy local mặc định |
| --- | --- | --- |
| `nginx` | `nginx:1.25-alpine` | `80` → container `80` |
| `php` | Build từ `Dockerfile.php`, `php:8.2-fpm-alpine` | Nội bộ `9000` |
| `mariadb` | `mariadb:10.11` | `3307` → container `3306` |
| `redis` | `redis:7-alpine` | Nội bộ `6379` |
| `phpmyadmin` | `phpmyadmin/phpmyadmin:latest` | `8080` → container `80` |
| `mailpit` (tùy chọn) | `axllent/mailpit:latest` | UI `8025`; SMTP nội bộ `1025` |

Các service chính dùng mạng `kleer-net`. MariaDB và Redis lưu dữ liệu trong named volume; mã nguồn được bind mount từ thư mục dự án.

## Chạy dự án ở local

### 1. Chuẩn bị

- Git, Docker Engine hoặc Docker Desktop đang chạy, Docker Compose v2.
- PowerShell nếu dùng `setup.ps1` trên Windows.
- Python 3 và các gói trong `requirements.txt` chỉ cần khi chạy công cụ Python tương ứng. Giao diện hiện dùng PHP/CSS/JavaScript trực tiếp, không có bước build npm.

```powershell
git clone https://github.com/QuocAn0303/KLEER.git
cd KLEER
Copy-Item .env.example .env
docker version
docker compose version
```

Trên Linux/macOS, thay `Copy-Item .env.example .env` bằng `cp .env.example .env`. Nếu đã có `.env`, giữ lại cấu hình hiện có.

### 2. Kiểm tra WordPress và cấu hình

Repo có các file WordPress nhưng `.gitignore` loại trừ `wp-settings.php`, `wp-config-sample.php` và `wp-config.php`. Sau khi clone, kiểm tra đủ WordPress core; bổ sung các file thiếu từ **bộ WordPress cùng phiên bản với `wp-includes/version.php`**, giữ nguyên thư mục `wp-content` của dự án.

`setup.ps1` chỉ tải WordPress khi thiếu `index.php`, nên không tự sửa trường hợp core thiếu một phần. Script chỉ tạo `wp-config.php` khi file này chưa có và đã có `wp-config-sample.php`.

Chỉnh `.env` trước khi khởi động:

- Đặt `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` đồng nhất với các biến `WORDPRESS_DB_NAME`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`.
- Trong Docker, database host là `mariadb:3306`; `localhost:3307` dành cho công cụ kết nối từ máy host.
- Nếu cổng 80 đang được dùng, đổi `NGINX_PORT` (ví dụ `8081`) và sử dụng URL tương ứng.

**Cấu hình WordPress:** khi cài thủ công, sao chép `wp-config-sample.php` thành `wp-config.php`, điền thông tin database khớp `.env`, tiền tố bảng và các khóa/salt riêng. Nếu đã có `wp-config.php`, kiểm tra các giá trị trong file trước khi chạy.

Image PHP hiện dùng PHP-FPM thuần, không có entrypoint của image WordPress để tự áp dụng `WORDPRESS_CONFIG_EXTRA`. Vì vậy, các biến `WORDPRESS_*`, URL hoặc salt trong `.env` không tự cập nhật `wp-config.php`; cần bảo đảm file này đọc biến môi trường hoặc được cấu hình tương ứng.

### 3. Khởi động

Windows có thể dùng script hỗ trợ:

```powershell
powershell -ExecutionPolicy Bypass -File setup.ps1
```

Hoặc chạy Docker Compose trực tiếp sau khi hoàn tất cấu hình:

```sh
docker compose up -d --build
docker compose ps
```

Lần khởi tạo MariaDB với volume trống, Docker chạy `init-scripts/01-init.sql` để nạp snapshot dữ liệu có trong repo. Quá trình này có thể mất thời gian. SQL không tự chạy lại trên volume đã có dữ liệu.

### 4. Hoàn tất trong WordPress

1. Mở [website local](http://localhost) và [trang quản trị](http://localhost/wp-admin/), thay cổng nếu đã đổi `NGINX_PORT`. Nếu database chưa có WordPress, hoàn tất trình cài đặt.
2. Cài và kích hoạt **WooCommerce** nếu chưa có. Plugin bên thứ ba này không được Git theo dõi; có thư mục trên máy của thành viên khác không có nghĩa bản clone mới đã có plugin.
3. Kích hoạt **KLEER Plugin**, **KLEER Payments**, **KLEER Mailer**; chọn theme **KLEER** tại Appearance → Themes.
4. Kiểm tra các trang Shop, Cart, Checkout, My Account và trang dùng template Quiz; lưu lại Permalinks nếu đường dẫn đẹp trả 404.
5. Kiểm tra sản phẩm, tồn kho, taxonomy và ảnh. `wp-content/uploads` không được Git theo dõi (trừ `.gitkeep`), nên cần bản media tương ứng nếu dùng snapshot database.
6. Cấu hình phương thức thanh toán và SMTP theo tài liệu liên kết bên dưới.

Tài khoản quản trị phụ thuộc database đang sử dụng; README không đặt tài khoản đăng nhập mặc định. Có thể quản lý database tại [phpMyAdmin](http://localhost:8080) bằng thông tin đã cấu hình trong `.env`.

## Cấu hình môi trường

| Nhóm | Biến chính | Ghi chú |
| --- | --- | --- |
| HTTP | `NGINX_PORT`, `NGINX_HOST`, `PHPMYADMIN_PORT` | Mặc định website cổng 80, phpMyAdmin cổng 8080. |
| Database | `MYSQL_*`, `WORDPRESS_DB_*` | Đồng bộ với `wp-config.php`; thay mật khẩu trong `.env` không tự đổi user đã tồn tại trong volume. |
| WordPress | `WORDPRESS_TABLE_PREFIX`, `WORDPRESS_DEBUG`, `WP_HOME`, `WP_SITEURL`, các khóa/salt | Kiểm tra cách `wp-config.php` áp dụng cấu hình. |
| Redis | `REDIS_HOST`, `REDIS_PORT`, `REDIS_DATABASE`, `REDIS_PREFIX`, `REDIS_TIMEOUT` | Cấu hình kết nối cho object cache; drop-in ở `wp-content/object-cache.php`. |
| REST CORS | `KLEER_CORS_ALLOWED_ORIGINS`, `KLEER_CORS_ALLOW_CREDENTIALS` | Khai báo origin frontend; khi dùng credentials phải chỉ định origin cụ thể. |

Xem đầy đủ tại [`.env.example`](.env.example). Không commit `.env`, `wp-config.php`, mật khẩu SMTP hoặc khóa cổng thanh toán.

## REST API

| Method | Route | Mục đích |
| --- | --- | --- |
| `GET` | `/wp-json/kleer/v1/health` | Kiểm tra API của plugin. |
| `POST` | `/wp-json/kleer/v1/skin-quiz` | Nhận bài quiz và trả routine. |
| `POST` | `/wp-json/kleer/v1/recommend` | Alias cho chức năng tư vấn. |

Khi dùng permalink mặc định, gọi qua `/?rest_route=/kleer/v1/...`. Ví dụ PowerShell với payload mẫu **v1**:

```powershell
curl.exe -i 'http://localhost/?rest_route=/kleer/v1/health'
curl.exe -i 'http://localhost/?rest_route=/kleer/v1/recommend' -H 'Content-Type: application/json' --data-binary '@tools/quiz/valid-submission.json'
```

API mặc định v1; chọn v4 bằng query `version=v4`, với payload đúng bộ 6 câu. Không đặt version trong JSON body. Client có cookie đăng nhập cần gửi `X-WP-Nonce` hợp lệ; gọi từ origin khác cần cấu hình CORS.

Kết quả phụ thuộc WooCommerce và catalog thật. v1 có thể trả `503` khi thiếu sản phẩm cho các bước bắt buộc; v4 có thể trả `missing_steps`/`warnings`. Xem [hướng dẫn API](docs/api/README.md), [OpenAPI JSON](docs/api/openapi.json) và [Postman collection](docs/api/KLEER-Routine.postman_collection.json). Điều chỉnh `base_url` theo cổng local; một số công cụ trong `tools/quiz` hiện dùng cố định cổng `8081`.

## Email thử nghiệm

Bật Mailpit:

```sh
docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d mailpit
```

Thêm cấu hình sau vào `wp-config.php`, trước khi nạp `wp-settings.php`, rồi kích hoạt KLEER Mailer:

```php
define('KLEER_SMTP_HOST', 'mailpit');
define('KLEER_SMTP_PORT', 1025);
define('KLEER_SMTP_SECURE', '');
define('KLEER_MAIL_FROM', 'no-reply@kleer.local');
define('KLEER_MAIL_FROM_NAME', 'KLEER');
```

Xem email tại [Mailpit](http://localhost:8025). Tham khảo [SMTP và email hóa đơn](docs/flows/L05-order-processing/email-smtp.md) để cấu hình chi tiết.

## Kiểm thử và vận hành

Sau khi các container đã chạy, thực hiện các bộ kiểm thử CLI có sẵn:

```sh
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/run_tests.php
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/verify_php_ini.php
docker compose exec -T php php wp-content/plugins/kleer-plugin/tests/verify_cache.php
docker compose exec -T php php wp-content/plugins/kleer-payments/tests/test-qr-payload.php
docker compose exec -T php php wp-content/plugins/kleer-mailer/tests/test-qr-encoder.php
```

Bài kiểm tra cache cần Redis đang hoạt động. Kiểm thử HTTP và luồng mua hàng cần WordPress, WooCommerce, sản phẩm và cấu hình tương ứng; tham khảo [Test Plan](docs/TEST_PLAN.md). Repo có kịch bản tải k6 tại `tests/load/kleer-load.js` và công cụ Python tại `tools/`.

Các lệnh vận hành thường dùng:

```sh
docker compose ps
docker compose logs --tail=100 nginx php mariadb
docker compose restart php nginx
docker compose down
```

`docker compose down` giữ named volume; tùy chọn `-v` xóa cả volume database và Redis, chỉ dùng khi chủ động muốn xóa dữ liệu local và đã sao lưu.

Workflow [Docker Build & Deploy](.github/workflows/docker.yml) khai báo build, kiểm tra cấu hình, PHP/plugin/cache, smoke test Nginx và quét bảo mật. `/health` của Nginx chỉ xác nhận tầng web server, khác với endpoint health của plugin.

Triển khai HTTPS có [script dành cho VPS Linux](deploy/deploy.sh), [Compose production](docker-compose.prod.yml) và template Nginx trong `deploy/`. Cần chuẩn bị domain, `.env.production`, cấu hình WordPress và chứng thư theo script; không dùng override production để khởi động local khi chưa có chứng thư.

## Cấu trúc thư mục

```text
KLEER/
├── .github/workflows/         # Workflow CI
├── .env.example              # Mẫu biến môi trường
├── docker-compose*.yml       # Stack local, Mailpit, override production
├── Dockerfile.php            # PHP-FPM và các extension
├── nginx.conf, php.ini       # Cấu hình web server và PHP
├── setup.ps1                 # Script hỗ trợ thiết lập Windows
├── init-scripts/             # Snapshot SQL nạp khi volume DB trống
├── deploy/                   # Script HTTPS và template Nginx
├── wp-admin/, wp-includes/   # Các file WordPress core trong workspace
├── wp-content/
│   ├── themes/kleer-theme/   # Theme của dự án
│   ├── plugins/
│   │   ├── kleer-plugin/     # Quiz, API, combo, taxonomy, cache
│   │   ├── kleer-payments/   # Thanh toán và VietQR
│   │   └── kleer-mailer/     # SMTP, email hóa đơn và QR
│   ├── object-cache.php      # Redis object-cache drop-in
│   └── uploads/              # Media local, không đưa vào Git
├── data/                     # Dữ liệu phục vụ dự án
├── tools/                    # Import, crawler, automation, kiểm tra quiz
├── tests/load/               # Kịch bản kiểm thử tải
└── docs/                     # Đặc tả, API, luồng nghiệp vụ, bằng chứng kiểm thử
```

## Tài liệu liên quan

- [Đặc tả hệ thống thương mại điện tử](docs/KLEER_ECOMMERCE_SPECIFICATION.md)
- [Hướng dẫn Docker](HUONG_DAN_DOCKER.md)
- [Kiến trúc plugin](docs/PLUGIN_ARCHITECTURE.md)
- [API và tích hợp routine](docs/api/README.md)
- [Thanh toán](docs/PAYMENT_GATEWAYS.md)
- [SMTP và email hóa đơn](docs/flows/L05-order-processing/email-smtp.md)
- [Hiệu năng và cache](docs/PERFORMANCE.md)
- [Kế hoạch kiểm thử](docs/TEST_PLAN.md)
- [Quy trình xử lý đơn hàng](docs/bpmn/fulfillment/fulfillment-notes.md)

Một số tài liệu ghi nhận thiết kế hoặc kết quả ở thời điểm trước. Khi tích hợp, đối chiếu với mã nguồn, schema và cấu hình hiện tại; không xem mô tả kế hoạch là xác nhận tính năng đã nghiệm thu.

## Đóng góp

1. Đồng bộ nhánh `develop`, tạo nhánh riêng cho thay đổi.
2. Giữ logic nghiệp vụ trong plugin; giao diện trong `kleer-theme`.
3. Chạy các kiểm thử liên quan và cập nhật tài liệu khi thay đổi API hoặc cấu hình.
4. Tạo Pull Request vào `develop`, mô tả thay đổi và kết quả kiểm tra để nhóm review trước khi merge.

Trao đổi lỗi hoặc đề xuất qua [Issues của dự án](https://github.com/QuocAn0303/KLEER/issues).
