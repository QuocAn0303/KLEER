# BÁO CÁO RÀ SOÁT VÀ PHÊ DUYỆT NGHIỆP VỤ, KỸ THUẬT HPOS (CỔNG G3)
**Luồng L05: Xử lý đơn hàng & Lưu trữ HPOS (Order Processing & HPOS Lifecycle)**

---

## 1. THÔNG TIN CHUNG
- **Mã luồng:** L05
- **Mã task:** L05-G3-01
- **Người thực hiện (Flow Owner):** Nguyễn Minh Trí (Plugin Dev 2)
- **Product Owner (PO):** Nguyễn Tấn Phát
- **Scrum Master (SM):** Lê Nguyễn Quốc An
- **QA phụ trách:** Nguyễn Ngọc Thảo Uyên
- **Môi trường hệ thống:** WordPress, WooCommerce 9.3.3, PHP 8.2, MariaDB 10.11, Docker Stack.
- **Ngày lập báo cáo:** 04/11/2026
- **Trạng thái cổng G3:** **CHỜ PO & SM XÁC NHẬN CHÍNH THỨC (PENDING APPROVAL)**

---

## 2. PHẠM VI KIỂM TRA
Rà soát toàn bộ tài liệu đặc tả, bản vẽ kiến trúc, mã nguồn plugin mở rộng (`kleer-mailer`), kịch bản kiểm thử và cấu hình hạ tầng liên quan đến luồng L05, bao gồm:
1. Document Flow Card: `docs/flows/L05-order-processing/flow-card.md`
2. Bản vẽ State Diagram: `docs/flows/L05-order-processing/state.drawio`
3. Tài liệu kỹ thuật Email Hóa đơn & SMTP: `docs/flows/L05-order-processing/email-smtp.md`
4. Tài liệu Mẫu Invoice: `docs/flows/L05-order-processing/invoice-template.md`
5. Cấu trúc CSDL WooCommerce High-Performance Order Storage (HPOS).

---

## 3. BẢNG RÀ SOÁT 8 TIÊU CHÍ DoR (DEFINITION OF READY)

| STT | Tiêu chí DoR | Tài liệu / Dẫn chứng kiểm chứng | Đánh giá | Chi tiết rà soát & Bằng chứng thực tế |
| :---: | :--- | :--- | :---: | :--- |
| **1** | **Flow Card đầy đủ các mục** | `docs/flows/L05-order-processing/flow-card.md` | **ĐẠT** | Đã định nghĩa rõ mục tiêu, phạm vi nhận dữ liệu từ L04 (Checkout) và bàn giao L06 (Vận chuyển) / L07 (Báo cáo). |
| **2** | **Sơ đồ luồng chính đã vẽ và PO duyệt** | `docs/flows/L05-order-processing/state.drawio` | **ĐẠT** | Sơ đồ thể hiện đầy đủ điểm bắt đầu, rẽ nhánh xử lý COD/BACS/VNPay, các hook chuyển trạng thái và điểm kết thúc. |
| **3** | **Trạng thái & Điều kiện chuyển trạng thái** | Flow Card (Mục 3) & `state.drawio` | **ĐẠT** | Định nghĩa đủ 5 trạng thái chuẩn: `Pending payment`, `Processing`, `On-hold`, `Completed`, `Cancelled`. |
| **4** | **Dữ liệu, Bảng, Meta & Taxonomy** | Kiến trúc HPOS Custom Tables | **ĐẠT** | Xác định 4 bảng chuyên biệt: `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta`. |
| **5** | **Endpoint / Hook kỹ thuật đã chốt** | Flow Card (Mục 5) | **ĐẠT** | Chốt hook `woocommerce_order_status_changed`, `woocommerce_payment_complete`, class `Automattic\WooCommerce\Utilities\OrderUtil`. |
| **6** | **Tiêu chí chấp nhận (Given-When-Then) $\ge$ 5** | Flow Card (Mục 6) | **ĐẠT** | Đủ 5 kịch bản: (1) Tạo đơn COD, (2) Trừ tồn kho tự động, (3) Trigger email hóa đơn, (4) Hủy đơn hoàn kho, (5) Cấm ghi đè `wp_posts`. |
| **7** | **Ràng buộc kỹ thuật đã ghi** | Quy định hệ thống | **ĐẠT** | Không sửa core WordPress/WooCommerce; tuân thủ PSR-12; chạy trên Docker PHP 8.2 & MariaDB 10.11. |
| **8** | **Dữ liệu & Kịch bản test mẫu** | `email-smtp.md` & `invoice-template.md` | **ĐẠT** | Có script test tự động `preview-invoice.php` tạo đơn hàng giả lập COD/BACS kiểm thử trên Mailpit Docker. |

---

## 4. KẾT QUẢ RÀ SOÁT CHUYÊN SÂU 9 HẠNG MỤC KỸ THUẬT

### 4.1. Rà soát Flow Card L05
- **Mục tiêu:** Quản lý vòng đời đơn hàng, tự động trừ tồn kho, tối ưu hiệu năng CSDL với HPOS và gửi email hóa đơn.
- **Tính đầy đủ:** Đã ghi nhận rõ ranh giới nhận đơn từ L04 (Khách bấm Đặt hàng) và chuyển tiếp cho L06/L07 khi đơn đạt trạng thái `Processing` / `Completed`.

### 4.2. Rà soát State Diagram L05
- Bản vẽ `state.drawio` định nghĩa chi tiết các luồng rẽ nhánh:
  - Đơn COD $\rightarrow$ Chuyển trực tiếp sang `Processing`.
  - Đơn Chuyển khoản (BACS) $\rightarrow$ Chuyển sang `On-hold` chờ kế toán đối soát.
  - Đơn Cổng thanh toán (VNPay) $\rightarrow$ Ở trạng thái `Pending payment` đến khi nhận tín hiệu Callback/IPN thành công mới chuyển sang `Processing`.

### 4.3. Kiểm tra 5 trạng thái đơn hàng
1. **Pending payment:** Đơn vừa khởi tạo, chưa ghi nhận thanh toán.
2. **Processing:** Thanh toán thành công hoặc COD hợp lệ. Đã kiểm thử kích hoạt tự động trừ tồn kho (`reduce_order_stock`) và gửi email hóa đơn.
3. **On-hold:** Tồn kho đã giảm, đơn tạm giữ chờ đối soát chuyển khoản.
4. **Completed:** Đơn giao thành công, đóng vòng đời nghiệp vụ.
5. **Cancelled:** Đơn bị hủy; tự động hoàn lại số lượng tồn kho (`restore_order_stock`).

### 4.4. Cơ chế chuyển trạng thái đơn hàng
Cơ chế chuyển trạng thái dựa trên các hook chuẩn của WooCommerce, đảm bảo tính đóng gói và không can thiệp trực tiếp vào câu lệnh SQL update trạng thái.

### 4.5. Thời điểm trừ tồn kho (Stock Reduction)
- **Kích hoạt:** Khi đơn hàng chuyển sang `Processing` hoặc `On-hold`.
- **Phục hồi tồn kho:** Tự động khôi phục khi đơn hàng bị chuyển sang `Cancelled` hoặc `Failed`.

### 4.6. Cơ chế gửi email hóa đơn (`kleer-mailer`)
- Mã nguồn nằm tại `wp-content/plugins/kleer-mailer/`.
- Tự động bắt hook `woocommerce_order_status_processing` và `woocommerce_order_status_on-hold`.
- **Đặc tính kỹ thuật:**
  - Sử dụng SMTP qua hằng số khai báo trong `wp-config.php` (`KLEER_SMTP_HOST`, `KLEER_SMTP_PORT`).
  - Mẫu email HTML responsive 600px, tương thích Outlook desktop và Gmail mobile.
  - Nhúng mã QR tra cứu đơn hàng trực tiếp dạng inline (`cid:kleer-order-qr`) bằng thư viện PHP thuần `QR_Encoder`, không phụ thuộc API bên ngoài.
  - Có cơ chế chống gửi trùng bằng meta key `_kleer_invoice_email_{status}`.

### 4.7. Kiến trúc HPOS & Các bảng chuyên biệt
- Đã bật và nghiệm thu chế độ High-Performance Order Storage (Custom Tables).
- Toàn bộ dữ liệu đơn hàng ghi nhận tại 4 bảng: `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta`.
- **Đã xác minh:** 100% đơn hàng mới không tạo bản ghi `post_type = 'shop_order'` trong bảng `wp_posts`.

### 4.8. Kiểm tra hook `woocommerce_order_status_changed`
- Bắt sự kiện chuyển trạng thái tổng quát để ghi log nghiệp vụ, cập nhật bảng operational data và trigger các dịch vụ liên quan.

### 4.9. Tính tương thích với WooCommerce HPOS
- Sử dụng class chuẩn `Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()` để kiểm tra trạng thái HPOS trước khi thực thi lệnh.

---

## 5. CÁC VẤN ĐỀ CHƯA THỐNG NHẤT & RỦI RO KỸ THUẬT

### 5.1. Rủi ro Kỹ thuật & Hạ tầng
1. **IPN VNPay chưa hoạt động thực tế:**
   - *Hiện trạng:* Đơn VNPay chuyển trạng thái `Processing` phụ thuộc vào IPN Callback từ cổng thanh toán. Do môi trường Dev chạy Localhost, IPN thật chưa kết nối được (đang chờ xử lý ở task `L04-G5-02`).
   - *Tác động:* Cần giả lập IPN khi test luồng L05 với VNPay.
2. **Khả năng rơi vào Spam của Email Hóa đơn:**
   - *Hiện trạng:* Mới chỉ kiểm thử thành công 100% trên Mailpit (`docker-compose.mail.yml`). Khi cấu hình SMTP thật (Gmail App Password / SendGrid), cần bổ sung bản ghi SPF, DKIM, DMARC cho tên miền chính thức.
3. **Mã QR tra cứu chứa URL Localhost:**
   - *Hiện trạng:* URL nhúng trong mã QR đang lấy theo `WP_HOME` (`http://localhost`). Khi triển khai Production, điện thoại quét QR sẽ không mở được nếu chưa đổi `WP_HOME` sang Domain công khai.

### 5.2. Đề xuất chỉnh sửa / Cần PO & SM chốt
- **Đề xuất 1:** Chấp nhận kiểm thử luồng L05 trên Docker Mailpit cho giai đoạn G3/G4; việc kiểm thử Spam email thật sẽ chuyển sang Môi trường Staging (G5).
- **Đề xuất 2:** Giữ nguyên cơ chế tự động trừ kho tại `Processing`/`On-hold` và phục hồi kho tại `Cancelled`.

---

## 6. KẾT LUẬN & ĐỀ XUẤT CHUYỂN GIAI ĐOẠN

1. **Đánh giá tổng quan:** Luồng L05 đã hoàn thiện đầy đủ tài liệu đặc tả, bản vẽ State Diagram, kiến trúc CSDL HPOS, plugin gửi email `kleer-mailer` và bộ test case mẫu.
2. **Kết luận DoR:** Đạt **8/8 tiêu chí DoR**.
3. **Khuyến nghị:**
   - **ĐỦ ĐIỀU KIỆN KỸ THUẬT DỂ CHUYỂN SANG GIAI ĐOẠN CỔNG G4 (SẮN SÀNG PROMPT / LẬP TRÌNH BỔ SUNG).**
   - Cần PO Nguyễn Tấn Phát và SM Lê Nguyễn Quốc An ký duyệt chính thức vào khung xác nhận dưới đây để hoàn tất thủ tục bàn giao task `L05-G3-01`.

---

## 7. KHUNG KÝ DUYỆT XÁC NHẬN

> **Lưu ý:** Chữ ký dưới đây xác nhận luồng L05 đã đạt 8/8 tiêu chí DoR và chính thức được phê duyệt chuyển sang giai đoạn phát triển G4/G5.

| Vai trò | Hoạt động | Họ và Tên | Trạng thái / Chữ ký | Ngày ký |
| :--- | :--- | :--- | :---: | :---: |
| **Flow Owner** | Người lập báo cáo | Nguyễn Minh Trí | *Đã trình báo cáo* | 04/11/2026 |
| **Product Owner (PO)** | Phê duyệt Nghiệp vụ | Nguyễn Tấn Phát | **[ CHỜ KÝ / APPROVED ]** | ...../...../2026 |
| **Scrum Master (SM)** | Phê duyệt Kỹ thuật & DoR | Lê Nguyễn Quốc An | **[ CHỜ KÝ / APPROVED ]** | ...../...../2026 |