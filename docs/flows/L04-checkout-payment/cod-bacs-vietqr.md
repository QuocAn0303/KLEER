# L04-G5-01 — COD + Chuyển khoản (BACS) với VietQR tự sinh

- **Người thực hiện:** Trần Ngọc Minh Tuyền · **Duyệt:** Lê Nguyễn Quốc An
- **Mã nguồn:** `wp-content/plugins/kleer-payments/` (COD/BACS/QR do plugin này cấp; plugin ban đầu của Quốc An, task này sửa lỗi và hoàn thiện cấu hình).
- **DoD:** khách chọn được COD, hoặc quét VietQR thành công ở trang Thank You.

## 1. Kết quả kiểm thử thật (WooCommerce 9.3.3, shortcode checkout, localhost)

| Kiểm tra | Kết quả |
|---|---|
| Checkout liệt kê `bacs`, `cod`, `kleer_vnpay` | ✅ |
| Đặt đơn COD qua HTTP (đơn #23) | ✅ thành công, đơn `processing`, **không** có khối QR |
| Đặt đơn BACS qua HTTP (đơn #22) | ✅ thành công, đơn `on-hold`, Thank You hiện khối QR + bảng thông tin chuyển khoản |
| Quét QR hiển thị trên trang (giải mã ảnh SVG bằng jsQR) | ✅ ra đúng payload `00020101021238540010A00000072701240006970436011001234567890208QRIBFTTA53037045405500005802VN62120808KLEER 22630449CD` |
| CRC-16 của payload thật | ✅ khớp (tính lại độc lập bằng Python) |
| `test-qr-payload.php` | ✅ 41/41 (gồm vector chính thức NAPAS `F4E5` và vietqr_gen `34A0`) |
| `test-qr-render.js` | ✅ 11/11 |
| `php -l` mọi file plugin | ✅ |

**Chưa kiểm được:** quét bằng app ngân hàng thật. Tài khoản đang dùng là **placeholder** (Vietcombank `970436`, STK `0123456789`); cần số tài khoản thật của KLEER để quét thử bằng điện thoại.

## 2. Lỗi tìm thấy trong bản VietQR cũ và cách sửa

Bản cũ tự test (40/40 PASS) nhưng chỉ kiểm tra chính nó, không đối chiếu với đặc tả NAPAS. Đối chiếu với đặc tả NAPAS 247 (ví dụ chính thức `...6304F4E5`) thì payload cũ **không quét được trên app ngân hàng**:

| # | Bản cũ | Đúng theo NAPAS 247 | Sửa |
|---|---|---|---|
| 1 | Tag `26` với GUID tự đặt `9704360000` | Tag `38`, AID `A000000727`, dịch vụ `QRIBFTTA` | Viết lại `QR_Payload::build()` |
| 2 | **Không có số tài khoản** trong QR (chỉ BIN + "mã vùng" + loại TK) | `38/01` = `00` BIN + `01` số tài khoản | Thêm số tài khoản, bỏ "mã vùng"/"loại TK" |
| 3 | CRC-16/XMODEM, init `0x0000` | CRC-16/CCITT-FALSE, init `0xFFFF` | Đổi `crc16()`; test kiểm chuẩn `123456789 → 29B1` |
| 4 | Nội dung chuyển khoản chỉ nằm ở tag 62/01 (mã đơn) | Nội dung CK nằm ở `62/08` để app ngân hàng điền sẵn | Dùng `62/08` |
| 5 | Hướng dẫn: BIN `970436` = VietinBank | `970436` = **Vietcombank**; VietinBank = `970415` | Sửa mô tả trường |
| 6 | Mục "KLEER VietQR" trong WooCommerce → Payments **không tồn tại** (mảng trả về qua `woocommerce_payment_gateways` bị WooCommerce bỏ qua), nên không có chỗ nhập số tài khoản | Cần lớp gateway thật để có trang cấu hình | Thêm `includes/class-vietqr-settings.php` (không hiện ở checkout) |
| 7 | COD bật nhưng **không hiện ở checkout**: `enable_for_virtual = no` và shop chưa có phương thức vận chuyển → giỏ hàng bị coi là không cần ship | COD phải hiện | `apply_gateway_defaults()` đặt `enable_for_virtual = yes` |

## 3. Cấu hình

`WooCommerce → Settings → Payments`:
- **Thanh toán khi nhận hàng (COD)** — bật, tiêu đề tiếng Việt.
- **Chuyển khoản ngân hàng (QR VietQR)** (BACS) — bật.
- **KLEER VietQR (QR theo đơn hàng)** — nhập: Số tài khoản (4–19 số), Bank BIN (6 số), Tên chủ TK, Tên ngân hàng, Tiền tố nội dung CK (mặc định `KLEER`, nội dung = `KLEER {mã đơn}`, ≤25 ký tự).

Thiếu số tài khoản hoặc BIN sai định dạng → **không** vẽ QR (thay vì vẽ mã sai).

Với DB đã kích hoạt plugin từ trước, `apply_gateway_defaults()` chỉ chạy khi kích hoạt. Muốn áp dụng thay đổi (COD luôn hiện): bật "Enable for virtual orders" ở cài đặt COD, hoặc chạy lại hàm trên.

## 4. Giới hạn và việc cần quyết

- **Không tự đối soát.** Đơn BACS ở `on-hold`, admin phải xác nhận tay. Câu chữ trên Thank You *"Hệ thống sẽ đối soát đơn hàng ngay khi nhận được tiền"* **sai so với thực tế** — nên sửa thành "nhân viên sẽ xác nhận".
- **Checkout Blocks:** hook Thank You của Blocks đã được plugin xử lý qua `render_block`, nhưng **chưa kiểm** vì phiên test dùng checkout shortcode (gateway VNPay cổ điển cũng không hiện ở Blocks — xem mục review của PRM-L04-01).
- **Không có dấu tiếng Việt** trong chữ hiển thị của plugin (cố tình viết không dấu); nếu cần tiếng Việt chuẩn, đổi chuỗi và thêm file dịch.
- COD với `enable_for_virtual = yes` cũng hiện cho đơn không cần ship; chấp nhận được vì KLEER chỉ bán hàng vật lý. L06 (vận chuyển) có thể đặt lại sau khi có phương thức ship.
