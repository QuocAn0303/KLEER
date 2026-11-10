# MA TRẬN KỊCH BẢN KIỂM THỬ (TEST CASES) - TASK L03-G7-01
- **Module:** Phân hệ Giỏ hàng & Thanh toán (Cart & Checkout)
- **Tester:** Nguyễn Ngọc Thảo Uyên (QA & Automation Tester)
- **Mục tiêu:** Kiểm tra các trường hợp tính sai thuế, phí ship, mã giảm giá âm tiền, và đặt hàng thiếu thông tin bắt buộc[cite: 5].

---

## 1. Danh sách Kịch bản Kiểm thử (Test Cases)

| Mã TC | Tên Kịch bản / Thao tác | Loại Test | Kết quả mong đợi (Expected Result) | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-L03-01** | Bỏ trống thông tin bắt buộc khi đặt hàng (Tên, SĐT, Địa chỉ) | Validation | Hệ thống bôi đỏ viền các trường bị thiếu, chặn tạo đơn hàng và hiển thị thông báo lỗi yêu cầu nhập đủ thông tin. | Chờ test |
| **TC-L03-02** | Nhập sai định dạng thông tin (SĐT chứa chữ cái, sai format Email) | Validation | Hệ thống bắt lỗi định dạng không hợp lệ, chặn nút Đặt hàng. | Chờ test |
| **TC-L03-03** | Áp dụng mã giảm giá làm tổng tiền bị âm | Logic/Edge | Hệ thống báo lỗi "Mã giảm giá không hợp lệ" hoặc tự động giới hạn mức giảm, không cho phép tổng thanh toán < 0đ. | Chờ test |
| **TC-L03-04** | Áp dụng mã giảm giá đã hết hạn hoặc không tồn tại | Logic | Hệ thống hiển thị thông báo lỗi màu đỏ và không trừ tiền vào tổng đơn. | Chờ test |
| **TC-L03-05** | Tính phí ship động theo địa lý (Nội thành vs Ngoại thành) | Logic | Thay đổi địa chỉ giao hàng làm thay đổi chính xác mức phí ship (VD: 25k hoặc 35k) cộng vào tổng đơn. | Chờ test |
| **TC-L03-06** | Tính toán lại tổng tiền cuối cùng (Total Amount) | Logic | Cột Tổng thanh toán (Total) được tính toán chính xác = Subtotal + Thuế + Phí Ship - Tiền giảm giá. | Chờ test |

---

## 2. Tiêu chí nghiệm thu DoD (Definition of Done)
- [ ] Báo cáo kiểm thử Checkout hoàn thiện[cite: 5].
- [ ] Hệ thống bắt lỗi chính xác 100% các trường hợp validation form[cite: 5].s