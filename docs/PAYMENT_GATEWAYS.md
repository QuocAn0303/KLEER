# Tài liệu kỹ thuật tích hợp cổng thanh toán (môi trường Test)

> Phạm vi: VNPay Sandbox, Momo Sandbox, COD, VietQR — áp dụng cho WooCommerce (WordPress/Docker, KLEER).

---

## 1. VNPay Sandbox

### 1.1 Thông tin kết nối
| Thông tin | Giá trị / Ghi chú |
|---|---|
| URL thanh toán (Payment URL) | `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html` |
| URL tra soát / hoàn trả | `https://sandbox.vnpayment.vn/merchant_webapi/merchant.html` |
| `vnp_TmnCode` | Cấp khi đăng ký sandbox |
| `vnp_HashSecret` | Khóa bí mật ký HMAC-SHA512 |
| Đăng ký sandbox | https://sandbox.vnpayment.vn/devreg |
| Tài liệu API | https://sandbox.vnpayment.vn/apis/ |
| Dashboard tra giao dịch | https://sandbox.vnpayment.vn/merchantv2/Transaction/PaymentSearch.htm |

### 1.2 Mô hình kết nối: Redirect + IPN (không phải REST thuần)
1. **Backend** tạo query string `vnp_*`, ký `vnp_SecureHash`, redirect trình duyệt khách sang Payment URL.
2. Khách nhập OTP trên trang VNPay.
3. VNPay redirect về **`vnp_ReturnUrl`** (kênh hiển thị cho người dùng — **không dùng để ghi nhận đơn**).
4. VNPay gọi **IPN URL** (server-to-server) để xác nhận kết quả — **chỉ IPN mới được phép cập nhật trạng thái đơn hàng**.

### 1.3 Quy tắc ký checksum (điểm dễ sai nhất)
- Lấy mọi tham số `vnp_*` trừ `vnp_SecureHash`, loại bỏ tham số rỗng.
- Sắp xếp theo alphabet của key → nối `key=value&key=value...` (đã URL-encode).
- `HMAC-SHA512(chuỗi, vnp_HashSecret)` → `vnp_SecureHash`.
- Sai thứ tự / sai encode → VNPay trả **mã lỗi 97 (chữ ký không hợp lệ)**.

### 1.4 Tham số bắt buộc
- `vnp_Amount` = số tiền × 100 (50.000đ → `5000000`, không thập phân).
- `vnp_TxnRef` — mã đơn duy nhất trong ngày.
- `vnp_OrderInfo` — mô tả đơn.
- `vnp_CreateDate` / `vnp_ExpireDate` — `yyyyMMddHHmmss`, giờ GMT+7.
- `vnp_Command=pay`, `vnp_Version=2.1.0`, `vnp_Locale=vn|en`, `vnp_CurrCurrency=VND`, `vnp_IpAddr`.

### 1.5 Chạy trên localhost (dự án KLEER)
- Web nội bộ: `http://localhost` (NGINX_PORT, mặc định 80; `localhost:8080` là phpMyAdmin).
- **IPN yêu cầu server công khai** → dùng tunnel: `ngrok http 80` hoặc `cloudflared tunnel --url http://localhost:80`, khai IPN URL `https://xxxx.ngrok-free.app/...` trên dashboard sandbox.
- Return URL có thể là `http://localhost/...` (trình duyệt của khách tự quay về).
- Ghi chú chính thức VNPay: *website cần mở ra internet để máy chủ VNPay gửi IPN*.

### 1.6 Mã lỗi thường gặp
| Code | Ý nghĩa |
|---|---|
| 00 | Giao dịch thành công |
| 97 | Chữ ký không hợp lệ (kiểm tra thứ tự/encode/HashSecret) |
| 91 | Giao dịch không tồn tại / đã hết hạn |
| 95 | Giao dịch đã được xử lý |
| 24 | Khách hủy giao dịch |

### 1.7 Thẻ test
Lấy trong mục *Thông tin thẻ test* tại `sandbox.vnpayment.vn/apis` (Visa/Master/ATM test card + OTP sandbox).

---

## 2. Momo Sandbox

### 2.1 Thông tin kết nối
| Thông tin | Giá trị |
|---|---|
| Test endpoint | `https://test-payment.momo.vn/v2/gateway/api/create` |
| Payment page test | `https://test-payment.momo.vn/v2/gateway/pay?...` |
| Tài liệu | https://developers.momo.vn/v3/docs/payment/... |
| Cấp thông tin test | Đăng ký hồ sơ doanh nghiệp trên portal Momo → bộ `partnerCode`, `accessKey`, `secretKey` riêng cho môi trường Test |

### 2.2 Luồng One-Time Payment (`captureWallet`)
1. Backend `POST /v2/gateway/api/create` với: `partnerCode`, `accessKey` (ký `signature` = HMAC-SHA256 của `accessKey + amount + ... + secretKey`), `amount` (tối thiểu 1.000đ, tối đa 50.000.000đ), `orderId`, `redirectUrl`, `ipnUrl`, `requestType=captureWallet`.
2. Response trả `payUrl`/`deeplink`/`qrCodeUrl` → redirect khách sang đó.
3. Momo trả về qua `redirectUrl` (user) + gọi `ipnUrl` (server, `resultCode=0` là thành công).
4. Timeout gọi API tối thiểu 30s.

### 2.3 Chạy trên localhost
- Giống VNPay: `ipnUrl` phải công khai → ngrok/cloudflared.
- **MoMo Test App**: gỡ app MoMo thật, cài app test; test wallet mật khẩu/OTP mặc định `000000`.
- Thẻ ATM test: `NGUYEN VAN A / 9704 0000 0000 0018 / 03/07` (thành công); `...0026` khóa thẻ; `...0034` thiếu số dư; `...0042` vượt hạn mức.

---

## 3. COD (Cash on Delivery — Thanh toán khi nhận hàng)

- **Không cần sandbox/tài khoản** — WooCommerce hỗ trợ sẵn: `WooCommerce → Settings → Payments → Cash on delivery` (COD), bật và sửa tiêu đề/mô tả.
- Flow: khách chọn COD → đơn `processing/on-hold` → shipper giao → xác nhận thu tiền (`processing` hoặc `completed`).
- Sống bằng hook `woocommerce_order_status_changed` / `woocommerce_cod_process_payment_order` để ghi log đối soát.
- Rủi ro: không xác minh được tiền trước → nên kết hợp xác nhận SĐT/OTP trước khi giao (plugin hoặc custom).
- Chạy được ngay trên localhost, không cần tunnel.

---

## 4. VietQR

### 4.1 Các mô hình
| Mô hình | Mô tả | Phù hợp |
|---|---|---|
| QR tĩnh | Sinh QR từ STK + ngân hàng + số tiền/nội dung (chuẩn EMVCo/Napas) | Đơn giản nhất, không cần API |
| QR động | Gọi API tạo QR cho từng đơn, có orderId trong nội dung | Cần đối soát tự động |
| Qua cổng trung gian (VietQR.io / VietQR.com / api.vietqr.vn) | Nhận webhook biến động số dư → xác nhận đơn | E-commerce cần tự động |
| Qua Napas/iPay của ngân hàng | Trực tiếp với ngân hàng (MB, BIDV...) | Cần hợp đồng |

### 4.2 Thông tin kết nối (dịch vụ trung gian, ví dụ vietqr.com / api.vietqr.vn)
1. `Get Token` → Bearer Token (hết hạn ~300s).
2. `Generate QR Code` (`qrType`: 1 = tĩnh, động theo đơn) → nhận QR image/base64.
3. Khách quét & chuyển khoản NAPAS 247.
4. Nhận biến động số dư qua webhook/`Transaction Sync` → `Check Transaction` (theo `orderId`) → xác nhận đơn.
5. `Refund` (ký `checkSum` bằng `secretKey`).

### 4.3 Lưu ý
- Nội dung chuyển khoản tối đa 19–23 ký tự, **tiếng Việt không dấu, không ký tự đặc biệt**.
- Trên WordPress có sẵn **plugin VietQR** (chính thức từ api.vietqr.vn) hỗ trợ tạo QR + thông báo biến động số dư + đối soát MB/BIDV — tích hợp nhanh nhất cho WooCommerce.
- Alternatives không mất phí: sinh QR tĩnh tự sinh theo spec Napas (thư viện `subiz/vietqr`) + duyệt đối soát thủ công/bảng kê ngân hàng.
- Không cần public URL nếu dùng plugin có push notification qua app; nếu webhook → cần tunnel khi chạy localhost.

---

## 5. Đăng ký tài khoản Sandbox — checklist

| Dịch vụ | Bước đăng ký | Trạng thái |
|---|---|---|
| VNPay | Đăng ký tại `sandbox.vnpayment.vn/devreg` (email + thông tin website) → nhận `vnp_TmnCode` + `vnp_HashSecret` qua email/portal | ⬜ Chờ đăng ký |
| Momo | Portal `developers.momo.vn` → Đăng ký hồ sơ doanh nghiệp → chọn mô hình Test → nhận `partnerCode` + `accessKey` + `secretKey` | ⬜ Chờ đăng ký |
| COD | Không cần — bật trong WooCommerce settings | ⬜ Chờ bật |
| VietQR | Đăng ký tài khoản tại trung gian (vietqr.com / api.vietqr.vn / vietqr.io) → lấy API token + tích hợp webhook | ⬜ Chờ đăng ký |

> Lưu ý bảo mật: toàn bộ `TmnCode/HashSecret/accessKey/secretKey` để ở `.env` (đã có `.env.example`), **không commit lên git**; `.gitignore` đã bỏ qua `.env`.

---

## 6. Kiểm thử chung trên localhost:8080 (KLEER)

1. `docker compose up -d` → web tại `http://localhost`, phpMyAdmin tại `http://localhost:8080`.
2. Bật tunnel: `ngrok http 80` → lấy URL công khai cho IPN (`VNPay`/`Momo`/`VietQR webhook`).
3. Nhập credentials sandbox vào `.env` → plugin đọc từ biến môi trường.
4. Checkout thử: mong đợi `vnp_ResponseCode=00` / `resultCode=0` → order chuyển `processing`, log IPN tại `wp-content/debug.log`.
5. Kịch bản test bắt buộc: thanh toán thành công, khách hủy (code 24), chữ ký sai (97), IPN gọi lại 2 lần (idempotent), khách đóng tab trước khi return (đơn vẫn phải cập nhật qua IPN).
