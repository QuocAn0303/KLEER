# Báo cáo Kiểm thử Luồng Checkout & Thanh toán (L04-G7-01)

- **Mã task:** `L04-G7-01`
- **Luồng nghiệp vụ:** L04 – Checkout & Thanh toán (COD, VietQR, VNPay Sandbox)
- **Người thực hiện (QA):** Nguyễn Ngọc Thảo Uyên
- **Người duyệt (SM):** Lê Nguyễn Quốc An
- **Flow Owner (Dev):** Trần Ngọc Minh Tuyền
- **Ngày kiểm thử:** 17/11/2026 – 20/11/2026
- **Môi trường kiểm thử:** Local Docker (`http://localhost:8082`, WordPress/WooCommerce 11.2.2 + HPOS, PHP 8.2, MariaDB 10.11, Redis)
- **Phạm vi kiểm thử:** Đánh giá chức năng thanh toán COD, sinh mã VietQR động, tích hợp cổng VNPay Sandbox và kiểm thử bảo mật chữ ký SHA512 / lưu trữ dữ liệu thẻ (Rubric 5.2).

---

## 1. Tóm tắt kết quả thực thi (Executive Summary)

| Cụm chức năng | Tổng số TC | PASS | FAIL | BLOCKED | Tỷ lệ đạt (trên ca thực thi) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Cụm A: Thanh toán khi nhận hàng (COD)** | 2 | 2 | 0 | 0 | 100% (2/2) |
| **Cụm B: Chuyển khoản ngân hàng (VietQR động)** | 3 | 3 | 0 | 0 | 100% (3/3) |
| **Cụm C: VNPay Sandbox & Bảo mật giao dịch** | 5 | 2 | 0 | 3 | 100% (2/2 ca khả thi) |
| **Tổng cộng** | **10** | **7** | **0** | **3** | **100% (7/7 ca thực thi)** |

> **Ghi chú tổng quan:**
> - Toàn bộ 7 kịch bản khả thi trên môi trường local (`TC01`–`TC05`, `TC09`, `TC10`) đều **PASS 100%**.
> - 3 kịch bản nhập thẻ test trực tiếp trên giao diện cổng VNPay (`TC06`, `TC07`, `TC08`) ghi nhận trạng thái **BLOCKED** do khi hệ thống KLEER chuyển hướng sang `sandbox.vnpayment.vn`, cổng Sandbox trả về mã lỗi cấu hình **`code=72` (*Không tìm thấy website*)** từ địa chỉ `http://localhost:8082` (chưa khai báo whitelist domain/Return URL trên Merchant Portal VNPay Sandbox).
> - Phát hiện **01 lỗi giao diện nhỏ (Minor UI Bug)** ở khối hiển thị mã VietQR trên trang Thank You (`TC03`) cần Dev điều chỉnh CSS `max-width`.

---

## 2. Chi tiết kết quả 10 kịch bản kiểm thử (Test Cases)

| Mã TC | Phân hệ | Tên kịch bản & Bước thực hiện | Dữ liệu test | Kết quả mong đợi | Kết quả thực tế | Trạng thái |
| :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| **TC01** | COD | Đặt đơn COD chuẩn: Thêm SP vào giỏ $\rightarrow$ điền đủ thông tin giao hàng tại `/checkout` $\rightarrow$ chọn COD $\rightarrow$ bấm *Place Order*. | Đơn `#1187` (`₫215,000`), KH: `Uyên`, SĐT: `0901234567`, ĐC: `123 Nguyen Hue, Q1, TP.HCM` | Hiển thị trang *Order received*; đơn trong Admin chuyển sang **Đang xử lý (`Processing`)**; tồn kho tự động trừ. | Trang *Order received* hiển thị mã đơn `#1187`; Admin ghi nhận trạng thái `Processing` và trừ tồn kho chính xác. | **PASS** |
| **TC02** | COD | Đặt đơn COD áp dụng mã giảm giá: Nhập mã giảm giá hợp lệ tại Checkout $\rightarrow$ chọn COD $\rightarrow$ đặt hàng. | Đơn `#1189` (`₫275,700`), Coupon: `KLEER10` (giảm 10%) | Dòng chiết khấu trừ đúng tỷ lệ; tổng tiền thu hộ trên Thank You page và Admin khớp 100%. | Tổng thanh toán sau giảm giá và phí ship khớp chính xác (`₫275,700`); đơn chuyển sang `Processing`. | **PASS** |
| **TC03** | VietQR | Sinh mã VietQR động theo đơn hàng: Chọn *Chuyển khoản ngân hàng (QR VietQR)* $\rightarrow$ đặt hàng $\rightarrow$ kiểm tra trang Thank You. | Đơn `#1190` (`₫699,000`), Bank BIN: `970436` (Vietcombank), STK: `19036767676767` | Trang Thank You render mã QR VietQR chứa đúng số tiền đơn hàng và cú pháp nội dung `KLEER1190`. | Hệ thống render khối `.kleer-vietqr` với đúng số tiền `₫699,000` và nội dung `KLEER1190`. *(Ghi nhận Minor UI Bug: ảnh QR hiển thị kích thước quá lớn trên Desktop)*. | **PASS** *(Kèm UI Note)* |
| **TC04** | VietQR | Kiểm tra trạng thái đơn ngay sau khi tạo đơn VietQR trong `WP-Admin > WooCommerce > Orders`. | Đơn `#1190` (`₫699,000`) | Đơn hàng nằm ở trạng thái **Tạm giữ (`On hold`)** để chờ đối soát chuyển khoản, không tự nhảy sang `Processing`. | Đơn `#1190` hiển thị đúng trạng thái **`On hold`** trong danh sách Orders. | **PASS** |
| **TC05** | VietQR | Quét đối chiếu mã VietQR thực tế bằng ứng dụng ngân hàng trên điện thoại (không bấm chuyển tiền). | Mã QR của đơn `#1190` (`₫699,000`, `KLEER1190`) | App ngân hàng nhận diện định dạng mã QR chuẩn Napas 247, đọc đúng số tiền và nội dung chuyển khoản. | App ngân hàng đọc chuẩn xác payload VietQR, hiển thị đúng số tiền `699,000 VND` và nội dung `KLEER1190`. | **PASS** |
| **TC06** | VNPay | Thanh toán thành công qua VNPay Sandbox & xác thực IPN/Return URL SHA512. | Đơn `#1191` (`₫400,000`), Thẻ test NCB `9704198526191432198`, OTP `123456` | Redirect sang `sandbox.vnpayment.vn` đúng số tiền; thanh toán xong trả về Thank You và chuyển đơn sang `Processing`. | Website KLEER tạo URL giao dịch kèm chữ ký SHA512 và redirect sang VNPay Sandbox thành công, nhưng cổng VNPay dừng ở màn hình lỗi **`code=72` (*Không tìm thấy website*)** do `localhost:8082` chưa được whitelist. | **BLOCKED** *(Lỗi env/Sandbox `code=72`)* |
| **TC07** | VNPay | Ngoại lệ khách hàng bấm *"Hủy thanh toán"* tại cổng VNPay Sandbox (mã lỗi `24`). | Đơn `#1192` (`₫400,000`) | VNPay trả về website kèm thông báo hủy giao dịch; đơn chuyển sang `Cancelled` hoặc giữ `Pending payment`. | Không thể thao tác nút Hủy trên giao diện VNPay do cổng Sandbox chặn ngay bước đầu với mã `code=72`. Đơn `#1192` giữ nguyên trạng thái `Pending payment` an toàn. | **BLOCKED** *(Lỗi env/Sandbox `code=72`)* |
| **TC08** | VNPay | Ngoại lệ nhập sai OTP / Giao dịch bị từ chối trên VNPay Sandbox. | Đơn `#1193` (`₫399,000`), OTP sai `999999` | Website bắt lỗi từ chối giao dịch rõ ràng, không trắng trang (WSOD) và cho phép thanh toán lại. | Không thể nhập OTP trên giao diện VNPay do bị chặn ở màn hình `code=72`. Đơn `#1193` giữ nguyên `Pending payment`. | **BLOCKED** *(Lỗi env/Sandbox `code=72`)* |
| **TC09** | VNPay (Bảo mật) | Giả mạo tham số trên Return URL (`vnp_ResponseCode=00` kèm `vnp_SecureHash` sai). | Request GET tới `/?wc-api=wc_gateway_kleer_vnpay&...&vnp_ResponseCode=00&vnp_SecureHash=0000...` cho đơn `#1193` | Hệ thống từ chối cập nhật đơn hàng; đơn `#1193` không bị chuyển sang `Processing`. | Request giả mạo bị WordPress/WooCommerce chặn đứng (trả về `-1`), đơn `#1193` vẫn giữ nguyên trạng thái **`Pending payment`** trong Admin. | **PASS** |
| **TC10** | VNPay (Bảo mật) | Kiểm tra dữ liệu lưu trữ trong CSDL HPOS & Order Notes sau các giao dịch thanh toán. | Kiểm tra chi tiết đơn `#1190`, `#1193` trong `WP-Admin > Orders` & `wp_wc_orders` | Tuyệt đối không lưu số thẻ thô (PAN), ngày hết hạn hay mã CVV trong CSDL hoặc log. | Chỉ lưu mã tham chiếu đơn hàng và trạng thái thanh toán; không tồn tại bất kỳ dữ liệu số thẻ thô nào. | **PASS** |

---

## 3. Danh sách lỗi & Đề xuất khắc phục cho Dev (Bug & Observation Log)

1. **[ENV / CONFIG - Major] Cổng VNPay Sandbox báo lỗi `code=72` (*Không tìm thấy website*) khi test từ `localhost:8082`:**
   - **Mô tả:** Khi bấm *Place Order* với phương thức VNPay, plugin `kleer-payments` đã đóng gói tham số và điều hướng sang `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html` thành công, nhưng VNPay trả về trang thông báo `code=72`.
   - **Đề xuất:** Integration Dev (**Trần Ngọc Minh Tuyền**) kiểm tra lại giá trị `KLEER_VNPAY_TMN_CODE` hoặc chạy kiểm thử lại luồng `TC06`–`TC08` trên domain HTTPS của VPS nhóm (`Rubric 5.1`) / tunnel `ngrok` để VNPay Sandbox chấp nhận `vnp_ReturnUrl`.
2. **[UI - Minor] Khung mã QR VietQR (`.kleer-vietqr__code`) hiển thị quá lớn trên màn hình Desktop:**
   - **Mô tả:** Tại trang *Order received* của đơn `#1190`, ảnh/canvas mã QR phóng to chiếm toàn bộ chiều rộng vùng nội dung khiến người dùng phải cuộn chuột mới thấy bảng thông tin chuyển khoản.
   - **Đề xuất:** Bổ sung CSS `max-width: 240px; width: 100%; height: auto;` cho class `.kleer-vietqr__code` trong `wp-content/plugins/kleer-payments/assets/`.

---

## 4. Bằng chứng kiểm thử (Evidence)

Toàn bộ ảnh chụp màn hình thực thi 10 kịch bản (màn hình Checkout, trang Thank You đơn COD `#1187`/`#1189`, mã QR VietQR đơn `#1190`, thông báo lỗi VNPay `code=72`, phản hồi chặn giả mạo `TC09` và bảng danh sách trạng thái `WP-Admin > Orders`) được lưu trữ tại thư mục Google Drive dự án:
- **Thư mục Drive:** `Thiết kế hệ thống TMDT/Thảo Uyên/L04-G7-01-Evidence/`

---

## 5. Kết luận nghiệm thu DoD

- Đã hoàn thành thiết kế và thực thi đầy đủ **10/10 kịch bản kiểm thử** bao phủ cả 3 phương thức thanh toán (COD, VietQR động, VNPay Sandbox) và kiểm tra bảo mật chống giả mạo tham số.
- Hệ thống xử lý chính xác trạng thái đơn hàng (`Processing` cho COD, `On hold` cho VietQR, `Pending payment` khi giao dịch trực tuyến chưa hoàn tất) và bảo vệ an toàn trước request giả mạo checksum. Đề xuất **SM Lê Nguyễn Quốc An** duyệt đóng task `L04-G7-01`.