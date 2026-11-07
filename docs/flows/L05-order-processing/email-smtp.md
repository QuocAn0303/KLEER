# L05-G5-01 — SMTP + email hóa đơn tự động (kleer-mailer)

- **Người thực hiện:** Trần Ngọc Minh Tuyền · **Duyệt:** Lê Nguyễn Quốc An
- **Mã nguồn:** `wp-content/plugins/kleer-mailer/` · hộp thư thử nghiệm: `docker-compose.mail.yml` (Mailpit)
- **Bản vẽ tham chiếu:** `docs/flows/L05-order-processing/state.drawio` (đơn chuyển `Processing` thì gửi email; `On-hold` dành cho BACS chờ đối soát).

## 1. Đã làm

| Hạng mục | Cách làm |
|---|---|
| SMTP | `phpmailer_init` → `isSMTP()`. Mọi thông số đọc từ **hằng số trong `wp-config.php`**, không lưu DB. Có preset `gmail`, `sendgrid`. |
| Hook | `woocommerce_order_status_processing` (chính); `woocommerce_order_status_on-hold` (phụ, BACS chờ chuyển khoản) |
| Nội dung | HTML responsive (bảng + inline style + media query ≤600px), có ảnh **QR tra cứu đơn** nhúng inline (`cid:`), có bản text thuần (`multipart/alternative`) |
| QR | Mã hóa bằng **PHP thuần** (`QR_Encoder`, byte mode, mức M, version 1–10) rồi xuất PNG bằng GD; không thêm thư viện. Nội dung QR = link `order-received` kèm order key |
| Chống gửi trùng | Meta `_kleer_invoice_email_{processing|on-hold}`; tắt 2 email khách mặc định của WooCommerce (`customer_processing_order`, `customer_on_hold_order`). Email "đơn mới" cho admin giữ nguyên |
| Lỗi | Email khách rỗng/sai → ghi order note, không gửi; `wp_mail` thất bại → order note + log `kleer-mailer`, **không** đánh dấu đã gửi (cho phép gửi lại), không Fatal |

## 2. Cấu hình (`wp-config.php`, không commit)

**Dev (Docker, Mailpit — đang dùng):**
```php
define( 'KLEER_SMTP_HOST', 'mailpit' );
define( 'KLEER_SMTP_PORT', 1025 );
define( 'KLEER_SMTP_SECURE', '' );
define( 'KLEER_MAIL_FROM', 'no-reply@kleer.local' );
define( 'KLEER_MAIL_FROM_NAME', 'KLEER' );
```
Chạy hộp thư: `docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d mailpit` → xem thư tại http://localhost:8025.

**Gmail (App Password):** bật xác minh 2 bước → tạo App Password 16 ký tự tại myaccount.google.com/apppasswords.
```php
define( 'KLEER_SMTP_PROVIDER', 'gmail' );              // smtp.gmail.com:587 STARTTLS
define( 'KLEER_SMTP_USER', '<tài khoản Gmail>' );
define( 'KLEER_SMTP_PASS', '<App Password>' );
define( 'KLEER_MAIL_FROM', '<đúng tài khoản Gmail đó>' ); // Gmail ghi đè From khác với tài khoản
```
**SendGrid:** tạo API key quyền *Mail Send*, xác thực Single Sender hoặc Domain.
```php
define( 'KLEER_SMTP_PROVIDER', 'sendgrid' );           // smtp.sendgrid.net:587, user = "apikey"
define( 'KLEER_SMTP_PASS', '<SendGrid API key>' );
define( 'KLEER_MAIL_FROM', '<địa chỉ đã xác thực trên SendGrid>' );
```
Tùy chọn: `KLEER_MAIL_KEEP_WC_EMAILS = true` để giữ email mặc định của WooCommerce song song.

## 3. Kết quả kiểm thử thật (WooCommerce 9.3.3, Mailpit trong Docker)

| Kiểm tra | Kết quả |
|---|---|
| Đặt đơn COD qua HTTP → khách nhận email "Xác nhận đơn hàng #24" qua SMTP `mailpit:1025` (+1 email "đơn mới" cho admin, **không** có email WC trùng) | ✅ |
| Đặt đơn BACS → email "Đã nhận đơn hàng #25 – chờ thanh toán" kèm thông tin chuyển khoản | ✅ |
| MIME: `multipart/alternative` có phần text; ảnh QR `image/png` inline, HTML tham chiếu `cid:kleer-order-qr` | ✅ |
| **Giải mã ảnh QR lấy từ chính email** (OpenCV) = đúng link tra cứu đơn | ✅ |
| `QR_Encoder`: 16 độ dài (1–213 byte, version 1–10) đều giải mã được bằng OpenCV; 34 kiểm tra PHP | ✅ |
| Responsive: render ở 375px và 900px (ảnh chụp), không tràn ngang, QR hiển thị, bảng sản phẩm vừa khung | ✅ |
| Gửi lặp: chuyển `processing` lần 2 không gửi lại; email rỗng; `wp_mail` lỗi; BACS on-hold; cấu hình SMTP | ✅ 9/9 |

## 4. Chưa đạt được / cần người làm

1. **"Không rơi vào spam" chưa chứng minh được.** Chỉ kiểm được trong Docker với Mailpit. Cần: (a) tài khoản Gmail App Password hoặc SendGrid API key thật (không dán vào chat/doc, đặt trong `wp-config.php`), (b) gửi thử tới hộp thư Gmail/Outlook thật, (c) nếu dùng tên miền riêng: SPF + DKIM + DMARC trỏ về nhà cung cấp.
2. **Đã làm sẵn để giảm điểm spam:** From = tài khoản SMTP, Envelope-Sender = From, Message-ID cùng tên miền với From, có text thuần, charset UTF-8, tiêu đề không viết hoa/dấu `!`, không ảnh lớn, không link rút gọn.
3. **Link/QR tra cứu dùng `WP_HOME`** (`http://localhost`). Khi triển khai, đặt `WP_HOME`/`WP_SITEURL` đúng tên miền công khai, nếu không điện thoại quét QR sẽ không mở được.
4. Chuỗi tên phương thức thanh toán lấy từ plugin thanh toán (hiện không dấu: "Thanh toan khi nhan hang (COD)").
5. Với đơn VNPay: email gửi khi IPN chuyển đơn sang `processing` (đã tích hợp qua hook, nhưng IPN thật chưa chạy được — xem L04-G5-02).
