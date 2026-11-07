# L04-G1-01 — Nghiên cứu tích hợp cổng thanh toán (VNPay Sandbox, MoMo Sandbox, COD, VietQR)

- **Người thực hiện:** Trần Ngọc Minh Tuyền · **Duyệt:** Lê Nguyễn Quốc An · **Phạm vi:** chỉ nghiên cứu/tài liệu, chưa sửa code tích hợp.
- **Nguồn:** tài liệu chính thức (truy cập 28/10/2026) — VNPay `sandbox.vnpayment.vn/apis`, MoMo `developers.momo.vn/v3`, VietQR `api.vietqr.vn` / `vietqr.com`.
- **Thay thế/đính chính** các điểm sai trong `docs/PAYMENT_GATEWAYS.md` (xem mục 6).

## 0. Bảng so sánh nhanh

| | VNPay | MoMo | COD | VietQR |
|---|---|---|---|---|
| Cần tài khoản sandbox | Có (tự đăng ký, nhận mail) | Có (xem 2.1) | Không | Có (API token từ VietQR) |
| Mô hình | Redirect (GET) + Return URL + IPN | REST `POST /create` → `payUrl` + redirect + IPN | Offline, WooCommerce built-in | Sinh QR + webhook biến động số dư |
| Ký | HMAC-SHA512 | HMAC-SHA256 | — | Bearer token (+ `sign` tùy chọn) |
| Cần URL công khai khi dev localhost | Có (IPN) | Có (ipnUrl) | Không | Có nếu dùng webhook |
| Đơn vị tiền | VND × 100 | VND nguyên (1.000–50.000.000) | VND | VND nguyên |

## 1. VNPay Sandbox

**Đăng ký:** http://sandbox.vnpayment.vn/devreg/ → thông tin kết nối (`vnp_TmnCode`, `vnp_HashSecret`) gửi qua email đăng ký.
**Demo / thẻ test:** http://sandbox.vnpayment.vn/tryitnow/Home/CreateOrder · https://sandbox.vnpayment.vn/apis/vnpay-demo/

| Mục | Giá trị |
|---|---|
| Payment URL (GET) | `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html` |
| Truy vấn / hoàn tiền | `https://sandbox.vnpayment.vn/merchant_webapi/api/transaction` |
| Version | `2.1.0` |

**Luồng:** (1) backend dựng URL + `vnp_SecureHash` → redirect khách; (2) khách thanh toán; (3) VNPay redirect về `vnp_ReturnUrl` (chỉ hiển thị); (4) VNPay gọi **IPN URL** server-to-server — nơi cập nhật trạng thái đơn. Merchant phải gửi lại VNPay URL IPN khi thiết lập xong.

**Tham số tạo URL (chính):** `vnp_Version`, `vnp_Command=pay`, `vnp_TmnCode`, `vnp_Amount` (×100), `vnp_CreateDate` (`yyyyMMddHHmmss`), `vnp_CurrCode=VND`, `vnp_IpAddr`, `vnp_Locale` (vn/en), `vnp_OrderInfo` (không dấu, không ký tự đặc biệt), `vnp_OrderType` (mặc định `other`), `vnp_ReturnUrl`, `vnp_TxnRef` (duy nhất, không trùng trong ngày), `vnp_ExpireDate` (tùy chọn), `vnp_BankCode` (tùy chọn), `vnp_SecureHash`.

**Checksum (mẫu PHP chính thức):** lấy các tham số `vnp_*` bỏ `vnp_SecureHash` → `ksort` → nối `urlencode(key)=urlencode(value)` bằng `&` → `hash_hmac('sha512', $data, $vnp_HashSecret)`. Lỗi `97` = sai chữ ký.

**Quy trình IPN (theo tài liệu):** kiểm checksum → tìm đơn theo `vnp_TxnRef` → so khớp `vnp_Amount/100` với tổng đơn → kiểm trạng thái đơn hiện tại (chống xử lý lặp) → cập nhật → trả JSON `{"RspCode":"00","Message":"Confirm Success"}`. Mã RspCode IPN phía merchant theo code demo VNPay (00 thành công, 01 không thấy đơn, 02 đơn đã xác nhận, 04 sai số tiền, 97 sai chữ ký, 99 lỗi khác) — **cần đối chiếu lại với code demo PHP khi viết code**, trang đã tải không in đủ bảng này.
Thành công = `vnp_ResponseCode=00` **và** `vnp_TransactionStatus=00`.

**Mã `vnp_ResponseCode` hay gặp:** 00 thành công · 07 nghi ngờ gian lận · 09 chưa đăng ký InternetBanking · 10 sai xác thực >3 lần · 11 hết hạn chờ · 12 thẻ bị khóa · 13 sai OTP · 24 khách hủy · 51 không đủ số dư · 95 VNPay từ chối · 97 sai chữ ký · 98 timeout · 99 lỗi khác.

**Thẻ test (nguồn: trang demo):**
- NCB nội địa thành công: `9704198526191432198` · NGUYEN VAN A · `07/15` · OTP `123456`
- NCB không đủ số dư `9704195798459170488` · chưa kích hoạt `9704192181368742` · bị khóa `9704193370791314` · hết hạn `9704194841945513`
- Quốc tế (CVC 123, hết hạn 12/26): Visa no-3DS `4456530000001005`, Visa 3DS `4456530000001096`, Master no-3DS `5200000000001005`
- Thẻ test có thể đổi theo thời gian — luôn xem lại trang demo trước khi test.

## 2. MoMo Sandbox

### 2.1 Tài khoản
Dùng cặp test của môi trường `test-payment.momo.vn`: `partnerCode`, `accessKey`, `secretKey`. Cách lấy (trang docs MoMo "Integration process" / portal business.momo.vn) **chưa xác minh được trong phiên này** — trang đăng ký cần đăng nhập. ⇒ Việc cần Tuyền làm tay, xem mục 5.

### 2.2 API One-Time Payment (`captureWallet`)
- `POST https://test-payment.momo.vn/v2/gateway/api/create` (JSON)
- Trường: `partnerCode`, `requestId` (duy nhất, dùng chống trùng/idempotency), `amount` (Long, 1.000–50.000.000 VND), `orderId` (≤200 ký tự, chỉ `0-9a-zA-Z` + `-_`), `orderInfo`, `redirectUrl`, `ipnUrl`, `requestType=captureWallet`, `extraData` (base64 JSON, mặc định `""`), `lang` (vi/en), `signature`, (`items`, `userInfo`, `autoCapture` tùy chọn).
- **Chuỗi ký (HMAC-SHA256 bằng `secretKey`)**, key sắp a→z:
  `accessKey=..&amount=..&extraData=..&ipnUrl=..&orderId=..&orderInfo=..&partnerCode=..&redirectUrl=..&requestId=..&requestType=..`
  (`accessKey` nằm **trong chuỗi ký**, không gửi như tham số riêng; secretKey chỉ là khóa HMAC.)
- Response: `resultCode` (0 = OK), `payUrl`, `deeplink`, `qrCodeUrl`, `signature`.
- **IPN/redirect nhận về:** `partnerCode, orderId, requestId, amount, orderInfo, orderType, transId, resultCode, message, payType, responseTime, extraData, signature` → tự tính lại HMAC-SHA256 (chuỗi gồm accessKey + các trường sort a→z) để verify; `resultCode=0` thành công. Danh sách resultCode: https://developers.momo.vn/v3/docs/payment/api/result-handling/resultcode/
- Phương thức thanh toán trên trang MoMo: ví MoMo, thẻ ATM, Visa/Master/JCB.

> Dữ liệu thẻ/OTP test của MoMo trong bản cũ (`9704 0000 0000 0018`, OTP `000000`) **chưa xác nhận được từ nguồn chính thức** — xác nhận lại ở trang test của MoMo trước khi dùng.

## 3. COD
- Có sẵn trong WooCommerce: *WooCommerce → Settings → Payments → Cash on delivery*; không cần sandbox/tunnel.
- Trạng thái mặc định: `processing` (đơn hàng thường) hoặc `on-hold`/`completed` theo cấu hình; cần quy ước: đơn COD `processing` → giao → xác nhận thu tiền → `completed`.
- Cần quyết định ở G1/L04: giới hạn COD theo khu vực/giá trị đơn, phí COD, xác nhận SĐT.

## 4. VietQR
Hai hướng, **cần chốt** (mục 5):
1. **QR tĩnh/động tự sinh** theo chuẩn VietQR/NAPAS (EMVCo), đối soát thủ công — không cần đối tác.
2. **API VietQR (api.vietqr.org / tài liệu api.vietqr.vn, vietqr.com):**
   - `POST /vqr/api/token_generate` — Basic Auth (tài khoản API do VietQR cấp) → access_token, hạn tối đa **300s**.
   - `POST /vqr/api/qr/generate-customer` — Bearer token; bắt buộc: `bankCode, bankAccount, userBankName, content (≤23 ký tự), qrType (0), amount (số nguyên), orderId (≤13 ký tự), transType ("C")`; tùy chọn `sign, urlLink, note, additionalData, terminalCode, serviceCode`.
   - Nhận biến động số dư: merchant tự dựng API **Get Token** + **Transaction Sync** (webhook) cho VietQR gọi vào; có Check Transaction & Refund API.
   - Môi trường test: có **API Test Callback** để giả lập giao dịch thành công (không cần chuyển tiền thật).
   - Ràng buộc: `orderId` ≤13 ký tự ⇒ cần map với mã đơn WooCommerce; nội dung chuyển khoản không dấu.
- Plugin WordPress VietQR chính thức: **chưa xác minh** trong phiên này; kiểm tra trước khi quyết định tự code hay dùng plugin.

## 5. Việc còn lại để đạt DoD ("Tài khoản VNPay/Momo Sandbox được kích hoạt, có TmnCode + HashSecret")
Phần này **không làm thay được** (cần email/thông tin cá nhân, đăng nhập portal):

| # | Việc | Ai | Trạng thái |
|---|---|---|---|
| 1 | Đăng ký http://sandbox.vnpayment.vn/devreg/ bằng email nhóm, lấy `vnp_TmnCode`, `vnp_HashSecret` | Tuyền | ⬜ |
| 2 | Lấy `partnerCode/accessKey/secretKey` test của MoMo | Tuyền | ⬜ |
| 3 | Lưu 5 giá trị vào `.env` (không commit); chia sẻ cho nhóm qua kênh an toàn, **không dán vào chat/doc** | Tuyền | ⬜ |
| 4 | Chốt hướng VietQR (tự sinh vs API vietqr) và đăng ký nếu chọn API | Tuyền + An | ⬜ |
| 5 | Chốt dùng ngrok/cloudflared cho IPN khi dev; khai IPN URL cho VNPay | Tuyền | ⬜ |
| 6 | Upload tài liệu lên Drive `Thiết kế hệ thống TMDT/Minh Tuyền/`, An duyệt | Tuyền | ⬜ |

## 6. Đính chính so với `docs/PAYMENT_GATEWAYS.md` (bản nháp cũ)
| Bản cũ | Đúng theo tài liệu chính thức |
|---|---|
| VNPay hoàn tiền: `.../merchant_webapi/merchant.html` | `.../merchant_webapi/api/transaction` |
| `vnp_CurrCurrency=VND` | `vnp_CurrCode=VND` |
| MoMo ký: "`accessKey + amount + ... + secretKey`" | HMAC-SHA256 bằng `secretKey` trên chuỗi `key=value&…` sort a→z (gồm `accessKey`) |
| Mã 91/95 VNPay: "không tồn tại / đã xử lý" | 95 = VNPay từ chối xử lý; 91/02 là RspCode phía IPN merchant, không nằm trong bảng `vnp_ResponseCode` đã đọc |
| Thẻ test VNPay: "xem trang apis" | Có thể ghi cụ thể (mục 1) |
| Thẻ MoMo `9704 0000 0000 0018 …`, OTP `000000` | Chưa xác minh — không dùng cho tới khi kiểm |
| VietQR token "~300s", plugin chính thức | 300s xác nhận; plugin chưa xác minh |

## 7. Checklist test sau khi có credentials (cho task G2+)
Thanh toán thành công · khách hủy (VNPay 24) · sai chữ ký (97) · sai số tiền · IPN gọi lặp (idempotent) · đóng tab trước return (đơn vẫn cập nhật qua IPN) · MoMo `resultCode≠0` · hết hạn chờ (11) · COD happy path · VietQR test callback.
