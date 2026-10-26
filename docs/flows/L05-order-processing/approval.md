# Biên Bản Phê Duyệt Nghiệp Vụ & Kỹ Thuật (Cổng G3)
**Luồng L05: Xử lý đơn hàng & Thông báo (Vòng đời đơn, HPOS, tồn kho, email hóa đơn)**

---

## 1. Thông Tin Chung
- **Mã luồng:** L05
- **Mã task:** L05-G3-01
- **Flow Owner (Người thực hiện):** Nguyễn Minh Trí (Plugin Dev 2)
- **Product Owner (PO duyệt):** Nguyễn Tấn Phát
- **Scrum Master (SM duyệt):** Lê Nguyễn Quốc An
- **QA phụ trách:** Nguyễn Ngọc Thảo Uyên
- **Ngày nghiệm thu:** 20/10/2026
- **Trạng thái cổng G3:** ĐẠT (Passed)

---

## 2. Bảng Rà Soát 8 Tiêu Chí DoR (Definition of Ready)

| STT | Tiêu chí DoR | Tài liệu kiểm chứng | Đánh giá | Ghi chú kỹ thuật |
| :---: | :--- | :--- | :---: | :--- |
| **1** | **Flow Card đủ mục** | `docs/flows/L05-order-processing/flow-card.md` | **ĐẠT** | Đầy đủ mục tiêu, phạm vi nhận từ L04 và bàn giao L06/L07. |
| **2** | **Sơ đồ luồng chính đã vẽ và PO duyệt** | `docs/flows/L05-order-processing/state.drawio` | **ĐẠT** | Bản vẽ State Diagram thể hiện đầy đủ điểm bắt đầu, rẽ nhánh và điểm kết thúc. |
| **3** | **Trạng thái / State đã định nghĩa** | Flow Card mục 3 & State Machine | **ĐẠT** | 5 trạng thái chuẩn: `Pending payment`, `Processing`, `On-hold`, `Completed`, `Cancelled`. |
| **4** | **Dữ liệu, bảng, meta đã liệt kê** | CSDL HPOS Custom Tables | **ĐẠT** | Liệt kê đủ: `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta`. |
| **5** | **Endpoint / Hook đã chốt** | Flow Card mục 5 | **ĐẠT** | Chốt hook `woocommerce_order_status_changed`, `woocommerce_payment_complete`, class `Automattic\WooCommerce\Utilities\OrderUtil`. |
| **6** | **Tiêu chí chấp nhận $\ge$ 5 (Given-When-Then)** | Flow Card mục 6 | **ĐẠT** | Đủ 5 kịch bản: Tạo đơn, trừ kho tự động, trigger email, hoàn tồn kho khi hủy đơn, cấm ghi đè `wp_posts`. |
| **7** | **Ràng buộc kỹ thuật đã ghi** | Quy định kiến trúc hệ thống | **ĐẠT** | Không sửa core WordPress/WooCommerce; tuân thủ PSR-12; chạy độc lập trên Docker stack PHP 8.2 & MariaDB 10.11. |
| **8** | **Dữ liệu / kịch bản test mẫu** | Test Cases L05 | **ĐẠT** | Đã thiết lập kịch bản kiểm tra đơn hàng COD và đơn thanh toán cổng VNPay. |

---

## 3. Kết Luận & Quyết Định Phê Duyệt
1. **Kết quả đánh giá:** Luồng L05 đạt **8/8 tiêu chí DoR**.
2. **Quyết định:**
   - Chính thức phê duyệt nghiệm thu kỹ thuật cổng G3 cho luồng L05.
   - Cho phép chuyển trạng thái luồng L05 từ **CHƯA SẴN SÀNG** sang **SẴN SÀNG PROMPT**.
   - Cấp quyền cho kỹ sư Nguyễn Minh Trí tiến hành viết Prompt AI (Task `L05-G4-01`) và kích hoạt HPOS (Task `L05-G5-03`) tại Tuần 6.

---

## 4. Chữ Ký Xác Nhận
- **Flow Owner:** Nguyễn Minh Trí *(Đã ký)*
- **Product Owner:** Nguyễn Tấn Phát *(Đã duyệt)*
- **Scrum Master:** Lê Nguyễn Quốc An *(Đã duyệt)*