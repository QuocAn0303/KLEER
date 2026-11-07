# PRM-L04-01 — Gateway VNPay Sandbox (WC_Payment_Gateway)

- **Mã prompt:** `PRM-L04-01` · **Task soạn:** `L04-G4-01` · **Task dùng:** `L04-G5-02` (Tích hợp VNPay Sandbox)
- **Người viết:** Trần Ngọc Minh Tuyền (Integration Dev) · **Người duyệt:** Lê Nguyễn Quốc An
- **Phiên bản:** v1.0 dự thảo — **chờ G3**: chưa có `docs/flows/L04-checkout-payment/approval.md`, PO/SM chưa xác nhận 8/8 DoR cho L04. Không dùng prompt này để sinh code cho tới khi G3 được duyệt.
- **Agent/Model dự kiến:** Claude Code / Codex
- **Tài liệu đính kèm khi chạy:** `docs/flows/L04-checkout-payment/sequence.md`, `docs/flows/L04-checkout-payment/L04_Checkout_VNPay_Sequence.drawio`, `docs/flows/L04-checkout-payment/payment-gateway-research.md`, `docs/flows/L05-order-processing/flow-card.md`. *(Flow Card L04 chưa tồn tại trong repo — dùng `sequence.md` làm đặc tả; xem mục "Cần người review".)*

> Phần dưới đường kẻ là nội dung prompt, dán nguyên văn cho AI agent (và dán vào tab Prompt Log, cột "Nội dung prompt").

---

# BỐI CẢNH
Dự án KLEER: website TMĐT mỹ phẩm Unisex trên WordPress/WooCommerce, chạy Docker (nginx, php 8.2-fpm, mariadb 10.11, redis; container PHP tên `kleer-php`).
Theme: wp-content/themes/kleer-theme/. Plugin thanh toán đã có: **wp-content/plugins/kleer-payments/** (namespace `Kleer_Payments`, file `includes/class-*.php`, đã có COD + BACS/VietQR). Không sửa WordPress Core, không sửa WooCommerce, không dùng Elementor/WPBakery.
Chuẩn code: PHP theo PSR-12 (giữ phong cách WordPress hiện có của plugin: tab, `defined( 'ABSPATH' ) || exit;`), PHP 8.1+, JS ES6+. HPOS đã bật (L05): **không** đọc/ghi `wp_posts`/`wp_postmeta` trực tiếp cho đơn hàng, chỉ dùng API `WC_Order`/`wc_get_orders`. Redis object cache đang chạy. Mọi thay đổi qua Pull Request vào nhánh `develop`.

# LUỒNG VÀ NHIỆM VỤ
Luồng: L04 – Checkout & Thanh toán (COD, VietQR, VNPay Sandbox).
Nhiệm vụ cụ thể (1 việc, 1 đầu ra): tạo gateway `Kleer_Payments\VNPay_Gateway` **kế thừa `WC_Payment_Gateway`** (id `kleer_vnpay`) tích hợp VNPay Sandbox theo mô hình Redirect + Return URL + IPN, gồm ký/kiểm tra chữ ký **HMAC-SHA512**, tạo URL thanh toán, xử lý IPN, và đưa đơn sang **Processing (Đang xử lý)** khi thanh toán thành công.

# ĐẦU VÀO
- Sơ đồ tuần tự (nguồn sự thật cho luồng và thứ tự kiểm tra): `docs/flows/L04-checkout-payment/sequence.md` (Mermaid) và `.drawio` cùng thư mục.
- Vòng đời trạng thái đơn: `docs/flows/L05-order-processing/flow-card.md` (Pending payment → Processing / Cancelled; `Processing` trừ kho + gửi email hóa đơn).
- Tài liệu VNPay đã tổng hợp: `docs/flows/L04-checkout-payment/payment-gateway-research.md` (mục 1). **Nếu mâu thuẫn với `docs/PAYMENT_GATEWAYS.md` thì theo `payment-gateway-research.md`** (bản cũ sai URL hoàn tiền, `vnp_CurrCurrency`, mã lỗi).
- Hằng số đã chốt (khai trong `wp-config.php` của từng máy, **không commit**):
  - `KLEER_VNPAY_TMN_CODE` — `vnp_TmnCode` (8 ký tự)
  - `KLEER_VNPAY_HASH_SECRET` — `vnp_HashSecret`
  - (tùy chọn) `KLEER_VNPAY_PAYMENT_URL` — mặc định `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`
- Endpoint do WooCommerce cấp qua `WC()->api_request_url()`:
  - Return URL: `?wc-api=kleer_vnpay_return` → action `woocommerce_api_kleer_vnpay_return`
  - IPN URL: `?wc-api=kleer_vnpay_ipn` → action `woocommerce_api_kleer_vnpay_ipn`
  - Khi dev localhost, IPN cần URL công khai (ngrok/cloudflared); Return URL có thể là `http://localhost/...`.
- Tham số `vnp_*` gửi đi: `vnp_Version=2.1.0`, `vnp_Command=pay`, `vnp_TmnCode`, `vnp_Amount` (**VND × 100, số nguyên, không dấu phân cách**), `vnp_CreateDate`/`vnp_ExpireDate` (`yyyyMMddHHmmss`, múi giờ `Asia/Ho_Chi_Minh`, hết hạn mặc định +15 phút), `vnp_CurrCode=VND`, `vnp_IpAddr`, `vnp_Locale=vn`, `vnp_OrderInfo` (tiếng Việt **không dấu**, không ký tự đặc biệt, ≤255), `vnp_OrderType=other`, `vnp_ReturnUrl`, `vnp_TxnRef`, `vnp_SecureHash`.
- **Quy tắc ký (VNPay, PHP mẫu chính thức):** lấy các tham số `vnp_*` trừ `vnp_SecureHash`/`vnp_SecureHashType`, **bỏ tham số rỗng**, `ksort`, nối `urlencode(key)=urlencode(value)` bằng `&`, `hash_hmac('sha512', $chuoi, HASH_SECRET)`. Dùng `urlencode` (không `rawurlencode`) cả khi ký lẫn khi dựng query.
- **Vector kiểm thử cố định** (secret giả `DUMMYSECRET0123456789ABCDEFGHIJKL`, chỉ dùng cho test):
  - Tham số: `vnp_Version=2.1.0, vnp_Command=pay, vnp_TmnCode=TESTTMN1, vnp_Amount=5000000, vnp_CreateDate=20261028103000, vnp_CurrCode=VND, vnp_IpAddr=127.0.0.1, vnp_Locale=vn, vnp_OrderInfo="Thanh toan don hang 123", vnp_OrderType=other, vnp_ReturnUrl=http://localhost/?wc-api=kleer_vnpay_return, vnp_TxnRef=123R1, vnp_ExpireDate=20261028104500, vnp_BankCode=""` (rỗng → bị loại)
  - Chuỗi ký: `vnp_Amount=5000000&vnp_Command=pay&vnp_CreateDate=20261028103000&vnp_CurrCode=VND&vnp_ExpireDate=20261028104500&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+don+hang+123&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2Flocalhost%2F%3Fwc-api%3Dkleer_vnpay_return&vnp_TmnCode=TESTTMN1&vnp_TxnRef=123R1&vnp_Version=2.1.0`
  - `vnp_SecureHash` kỳ vọng: `fa470b1281d16f6dd459339662dc1cee3f6a2b0e6e59c817e6694de6fee9682690d5768c7bc847cde030663a516c4ac01f61638d806b84777d63664de2cae9ee`
- Mã IPN `RspCode` phía merchant: `00` thành công; `02` đơn đã xác nhận (VNPay dừng); `01` không thấy đơn; `04` sai số tiền; `97` sai chữ ký; `99` lỗi khác (VNPay retry với 01/04/97/99/timeout). Phản hồi JSON `{"RspCode":"..","Message":".."}`.
- `vnp_ResponseCode`: `00` thành công; `24` khách hủy; `11` hết hạn; `51` không đủ số dư; `97` sai chữ ký. Thành công thật = `vnp_ResponseCode=00` **và** `vnp_TransactionStatus=00`.

# ĐẦU RA MONG ĐỢI
Danh sách file (đường dẫn đầy đủ):
1. **Tạo** `wp-content/plugins/kleer-payments/includes/class-vnpay-signature.php` — class `Kleer_Payments\VNPay_Signature`, **thuần PHP, không gọi hàm WordPress** (để test chạy không cần WP):
   - `public static function build_hash_data( array $params ): string` — lọc `vnp_*`, bỏ `vnp_SecureHash`/`vnp_SecureHashType`, bỏ giá trị rỗng, `ksort`, nối bằng `urlencode`.
   - `public static function sign( array $params, string $secret ): string` — HMAC-SHA512 dạng hex.
   - `public static function verify( array $params, string $secret ): bool` — lấy `vnp_SecureHash` từ `$params`, so sánh bằng `hash_equals()` (không dùng `==`).
   - `public static function build_url( string $base_url, array $params, string $secret ): string`.
2. **Tạo** `wp-content/plugins/kleer-payments/includes/class-vnpay-gateway.php` — `Kleer_Payments\VNPay_Gateway extends \WC_Payment_Gateway`. File chỉ được `require` **sau khi** WooCommerce sẵn sàng (trong `Kleer_Payments::init()`), vì `WC_Payment_Gateway` chưa tồn tại lúc plugin nạp. Các phương thức bắt buộc:
   - `__construct()` — `id='kleer_vnpay'`, `method_title`, `method_description`, `has_fields=false`, `supports=['products']`, gọi `init_form_fields()` và `init_settings()`, đăng ký:
     `add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] )`,
     `add_action( 'woocommerce_api_kleer_vnpay_ipn', [ $this, 'check_ipn_response' ] )`,
     `add_action( 'woocommerce_api_kleer_vnpay_return', [ $this, 'handle_return' ] )`.
   - `public function init_form_fields(): void` — chỉ các trường: `enabled`, `title` (mặc định "Thanh toán qua VNPay"), `description`, `expire_minutes` (mặc định 15, 5–60), `debug` (bật log). **TUYỆT ĐỐI không có trường nhập TmnCode/HashSecret** trong form admin; thay bằng một trường kiểu thông báo chỉ hiển thị "Đã cấu hình trong wp-config.php" hoặc "Chưa cấu hình" (không in giá trị, không in độ dài).
   - `public function is_available(): bool` — `parent::is_available()` **và** hai hằng số tồn tại, không rỗng **và** `get_woocommerce_currency() === 'VND'`; thiếu thì trả `false` (ẩn gateway ở checkout), không Fatal/Warning.
   - `public function process_payment( $order_id ): array` — lấy `wc_get_order()`; từ chối nếu đơn không tồn tại hoặc đã trả tiền; tạo `vnp_TxnRef` theo mục "Quyết định" bên dưới và lưu vào order meta `_kleer_vnpay_txn_ref` (qua `$order->update_meta_data()` + `save()`); dựng URL bằng `VNPay_Signature::build_url()`; thêm order note ("Chuyển khách sang VNPay, TxnRef …"); **không** trừ kho, **không** `payment_complete`, **không** `empty_cart` ở bước này; trả `[ 'result' => 'success', 'redirect' => $url ]`. Lỗi → `wc_add_notice()` + `[ 'result' => 'failure' ]`.
   - `public function check_ipn_response(): void` — xử lý IPN theo đúng thứ tự trong `sequence.md` nhóm C: (1) verify checksum → `97`; (2) tìm đơn bằng `wc_get_orders( [ 'meta_key' => '_kleer_vnpay_txn_ref', 'meta_value' => $txn_ref, 'limit' => 1 ] )` → `01`; (3) so `(int) $vnp_Amount === (int) round( $order->get_total() ) * 100` → `04`; (4) đơn không còn `pending` → `02`; (5) cập nhật: `00`+`00` → `$order->payment_complete( $vnp_TransactionNo )` rồi kiểm tra trạng thái là `processing` (ghi order note nếu không); `24` → `update_status( 'cancelled' )` (WooCommerce hoàn kho theo L05); mã khác → `update_status( 'failed' )` + order note mã lỗi; lưu meta `_kleer_vnpay_transaction_no`, `_kleer_vnpay_response_code`, `_kleer_vnpay_bank_code`, `_kleer_vnpay_pay_date`; (6) trả `{"RspCode":"00","Message":"Confirm Success"}`. Mọi ngoại lệ → `99`. Phải: xóa output buffer trước khi echo, `header( 'Content-Type: application/json' )`, HTTP 200, `exit` — không để notice/HTML lọt vào body. **Chống xử lý song song:** dùng khóa nguyên tử `add_option( 'kleer_vnpay_lock_' . $order_id, time(), '', 'no' )` (thất bại = đang xử lý → trả `02`), luôn `delete_option` trong `finally`; kiểm tra lại trạng thái đơn sau khi giữ khóa.
   - `public function handle_return(): void` — Return URL: verify checksum, **chỉ đọc** trạng thái đơn, **không** ghi trạng thái. `processing`/`completed` → `WC()->cart->empty_cart()` + redirect `$order->get_checkout_order_received_url()`; `cancelled` → `wc_add_notice` + redirect `$order->get_checkout_payment_url()` (nút thanh toán lại); chữ ký sai → thông báo lỗi chung, redirect giỏ hàng; còn `pending` (IPN chưa tới) → thông báo "đang chờ xác nhận" + redirect trang cảm ơn.
   - Helper private: `get_tmn_code()`, `get_hash_secret()` (đọc **chỉ** từ hằng số), `build_order_info( WC_Order $order ): string` (`remove_accents()` rồi lọc `[A-Za-z0-9 ]`, cắt 255), `get_client_ip(): string` (`WC_Geolocation::get_ip_address()`, mặc định `127.0.0.1`), `log( string $msg, string $level = 'info' )` (`wc_get_logger()`, source `kleer-vnpay`, chỉ ghi khi bật `debug`).
3. **Sửa tối thiểu** `wp-content/plugins/kleer-payments/kleer-payments.php` (require 2 file mới theo đúng thứ tự: signature ngay, gateway trong `init()` sau khi kiểm tra WooCommerce) và `includes/class-gateways.php` (thêm `Kleer_Payments\VNPay_Gateway` vào `woocommerce_payment_gateways`; **giữ nguyên** `gateway_definition()` của VietQR và `apply_gateway_defaults()` của COD/BACS).
4. **Tạo** `wp-content/plugins/kleer-payments/tests/test-vnpay-signature.php` — chạy bằng PHP thuần, in PASS/FAIL từng ca, exit code ≠ 0 khi có ca fail (theo phong cách `tests/test-qr-payload.php`): đúng vector cố định ở mục Đầu vào; `verify` đúng/sai khi đổi 1 ký tự; tham số rỗng bị loại; thứ tự đầu vào khác nhau cho cùng kết quả; `hash_equals` cho giá trị khác độ dài không báo lỗi.
5. **Không** tạo thêm file ngoài danh sách trên.

Cách chạy thử trong Docker (ghi lại kết quả thật của từng lệnh trong báo cáo):
- `docker exec kleer-php php /var/www/html/wp-content/plugins/kleer-payments/tests/test-vnpay-signature.php`
- `docker exec kleer-php php -l /var/www/html/wp-content/plugins/kleer-payments/includes/class-vnpay-gateway.php` (và các file còn lại)
- Cấu hình thủ công (người chạy làm, không commit): thêm vào `wp-config.php` **local** trước dòng `/* That's all, stop editing! */`:
  `define( 'KLEER_VNPAY_TMN_CODE', '<TmnCode từ email VNPay>' );`
  `define( 'KLEER_VNPAY_HASH_SECRET', '<HashSecret từ email VNPay>' );`
  rồi bật gateway tại WooCommerce → Settings → Payments → VNPay; mở tunnel `ngrok http 80`; khai IPN URL `https://<ngrok>/?wc-api=kleer_vnpay_ipn` trên dashboard VNPay Sandbox.
- E2E thủ công: đặt đơn → chuyển sang VNPay → thẻ NCB test `9704198526191432198`, tên `NGUYEN VAN A`, ngày phát hành `07/15`, OTP `123456` → kỳ vọng đơn `Processing`.
- Nếu một lệnh không chạy được (ví dụ chưa có tunnel, chưa có credentials) thì **nói rõ chưa chạy**, không giả lập kết quả.

# QUYẾT ĐỊNH ĐÃ CHỐT TRONG PROMPT
- `vnp_TxnRef` = `{order_id}R{attempt}` (chữ-số, ví dụ `123R1`; `attempt` tăng mỗi lần tạo URL mới, lưu ở meta `_kleer_vnpay_attempt`), để khách thanh toán lại không trùng mã trong ngày. IPN tìm đơn qua meta `_kleer_vnpay_txn_ref`, **không** parse chuỗi. (`sequence.md` ghi `vnp_TxnRef = order_id`; cần cập nhật sơ đồ nếu phương án này được duyệt.)
- `24` → `cancelled`; mã lỗi khác (kể cả `11`) → `failed`. Đề xuất của người soạn, chờ L05/SM xác nhận.
- Cấu hình bí mật nằm ở `wp-config.php` (theo Prompt Rule của L04-G5-02), **không** dùng `.env`/database/option. (`payment-gateway-research.md` mục 5 từng ghi `.env`; ưu tiên `wp-config.php`.)

# TIÊU CHÍ CHẤP NHẬN (Given–When–Then)
1. **Ký đúng:** Given tham số của vector cố định và secret giả, When gọi `VNPay_Signature::sign()`, Then kết quả bằng đúng `fa470b12…2cae9ee` và `verify()` trả `true`; đổi 1 ký tự bất kỳ thì `false`.
2. **Tạo URL & redirect:** Given đơn Pending 50.000đ, gateway đã cấu hình hằng số, When `process_payment()`, Then trả `result=success`, `redirect` bắt đầu bằng URL sandbox, chứa `vnp_Amount=5000000`, `vnp_TxnRef` dạng `{id}R1`, `vnp_SecureHash` 128 ký tự hex; đơn vẫn `pending`, kho chưa trừ.
3. **IPN thành công:** Given đơn Pending, IPN hợp lệ có `vnp_ResponseCode=00` và `vnp_TransactionStatus=00`, số tiền khớp, When `check_ipn_response()`, Then đơn → `processing`, kho trừ đúng, lưu `_kleer_vnpay_transaction_no`, trả `{"RspCode":"00","Message":"Confirm Success"}`.
4. **Khách hủy:** Given đơn Pending, IPN hợp lệ có `vnp_ResponseCode=24`, When xử lý IPN, Then đơn → `cancelled`, kho không bị trừ/được hoàn, trả `RspCode 00`; Return URL hiển thị thông báo hủy và đường dẫn thanh toán lại.
5. **Ngoại lệ — chữ ký sai:** Given IPN có `vnp_SecureHash` bị sửa hoặc thiếu, When xử lý, Then trả `RspCode 97`, trạng thái đơn không đổi, không tra cứu đơn, log có cảnh báo nhưng không chứa HashSecret.
6. **Ngoại lệ — sai số tiền / không thấy đơn:** Given IPN ký đúng nhưng `vnp_Amount` khác tổng đơn × 100 (hoặc `vnp_TxnRef` không tồn tại), When xử lý, Then trả `RspCode 04` (hoặc `01`), đơn không đổi.
7. **Ngoại lệ — IPN lặp / song song:** Given đơn đã `processing` (hoặc hai IPN cùng lúc), When IPN đến lần 2, Then trả `RspCode 02`, không trừ kho lần hai, không gửi email lần hai.
8. **Ngoại lệ — thiếu cấu hình:** Given chưa khai `KLEER_VNPAY_TMN_CODE` hoặc `KLEER_VNPAY_HASH_SECRET` (hoặc tiền tệ khác VND), When mở trang Checkout/Settings, Then gateway không hiển thị ở checkout, trang admin báo "Chưa cấu hình", không có PHP Warning/Fatal.
9. **Return URL không ghi trạng thái:** Given khách quay về Return URL với `vnp_ResponseCode=00` nhưng IPN chưa tới, When `handle_return()`, Then đơn vẫn `pending`, khách thấy thông báo chờ xác nhận; khi IPN đến sau đó đơn mới chuyển `processing`.
10. **Bảo mật dữ liệu:** Given toàn bộ mã mới, When tìm kiếm trong repo (`grep`), Then không có HashSecret/TmnCode thật, không có số thẻ/OTP lưu trong DB hoặc log, không in `vnp_HashSecret` ở bất kỳ phản hồi/notice nào.

# KHÔNG ĐƯỢC LÀM
- Không sửa file ngoài phạm vi nêu trên; không sửa WordPress Core, WooCommerce, theme, `kleer-plugin` hoặc plugin của thành viên khác.
- Không commit `.env`, `wp-config.php`, bất kỳ secret nào (kể cả secret test thật); chỉ dùng placeholder `<...>` và secret giả trong test.
- Không đặt TmnCode/HashSecret trong database, option, form admin, JS, log hay response; không `var_dump`/`error_log` dữ liệu `$_GET` thô chứa chữ ký ở production.
- **Cấm lưu số thẻ, OTP, CVV thô** ở bất kỳ đâu (VNPay xử lý thẻ trên trang của họ; plugin không nhận dữ liệu thẻ).
- Không dùng `==` so sánh chữ ký (bắt buộc `hash_equals`); không dùng `md5`/`sha256`/`sha1` thay `HMAC-SHA512`.
- Không cập nhật trạng thái đơn từ Return URL; chỉ IPN được cập nhật.
- Không `sanitize_text_field()`/`esc_*` lên giá trị `$_GET` **trước khi** tính chữ ký (làm đổi dữ liệu, sai hash); chỉ `wp_unslash()`, sanitize khi hiển thị.
- Không truy vấn trực tiếp `wp_posts`/`wp_postmeta` cho đơn hàng (HPOS); không `DROP`/`ALTER` bảng.
- Không thêm thư viện/Composer package mới nếu chưa hỏi; không gọi API hoàn tiền/truy vấn giao dịch (ngoài phạm vi).
- Không tự đánh dấu "đạt" nếu chưa chạy; không bịa kết quả test hay output.

# YÊU CẦU CUỐI
Liệt kê các giả định đã dùng và những chỗ cần người review kiểm tra kỹ, tối thiểu: (a) cách xử lý checkout dạng Blocks, (b) quyết định `vnp_TxnRef` và ánh xạ trạng thái `24`/`11`, (c) khóa chống IPN song song, (d) những lệnh/ca test **chưa chạy được** và lý do. Báo cáo kết quả bằng output thật của các lệnh ở mục "Cách chạy thử".

---

## Cần người review (ngoài prompt)
| # | Vấn đề | Ai quyết |
|---|---|---|
| 1 | G3 của L04 chưa có biên bản `approval.md` → prompt đang ở trạng thái dự thảo | PO Phát, SM An |
| 2 | Repo chưa có Flow Card L04 (`docs/flows/L04-checkout-payment/flow-card.md`); prompt dùng `sequence.md` thay thế | Tuyền / An |
| 3 | Mẫu Prompt chuẩn ghi plugin `kleer-plugin`, nhưng gateway thanh toán hiện nằm ở `kleer-payments`; prompt chọn `kleer-payments` | An |
| 4 | L09 đã khai báo tương thích `cart_checkout_blocks`. Nếu trang `/checkout/` dùng **block checkout**, gateway cổ điển sẽ không hiện ra; cần tích hợp `AbstractPaymentMethodType` (ngoài phạm vi prompt này) hoặc chuyển checkout sang shortcode | Tuyền / An |
| 5 | `vnp_TxnRef = {id}R{n}` và ánh xạ `24→cancelled`, `11→failed` khác/không có trong `sequence.md`/L05 | An, nhóm L05 |
| 6 | Credentials VNPay: file docx đã chứa secret ở dạng thường; không dán vào prompt/Prompt Log | Tuyền |
| 7 | Hai trường `Tên file bản vẽ` của G5-02 là "TỰ VẼ TỰ CODE" → dùng bản vẽ G2-01 (đã có) | — |
