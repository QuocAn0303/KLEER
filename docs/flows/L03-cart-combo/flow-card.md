# FLOW CARD: L03 - GIỎ HÀNG & COMBO ROUTINE

- **Mã luồng:** L03
- **Mục tiêu:** Quản lý giỏ hàng mượt mà, tự động giảm giá 10% khi mua trọn bộ Routine và cảnh báo xung đột hoạt chất chăm sóc da.

---

## 1. Cơ chế 1-Click Routine to Cart
- **Kích hoạt:** Khách hàng nhấn nút "Thêm trọn bộ Routine vào giỏ hàng" tại màn hình kết quả Quiz (L01).
- **Xử lý:** 
  - Frontend gửi AJAX request kèm danh sách `product_id` của 3–4 bước phác đồ (Làm sạch, Đặc trị, Dưỡng ẩm, Chống nắng).
  - Hệ thống tự động thêm đồng thời toàn bộ sản phẩm vào WooCommerce Cart mà không gây xung đột session.
  - Cập nhật Mini-cart fragments và chuyển hướng (redirect) khách hàng đến trang Giỏ hàng `/cart/`.

---

## 2. Quy tắc tự động giảm 10% Combo Routine (Dynamic Routine Bundle Discount)
- **Điều kiện áp dụng:**
  - Giỏ hàng phải chứa đồng thời đủ 3 đến 4 sản phẩm đại diện cho các bước cơ bản theo taxonomy skincare:
    1. Làm sạch (Cleanser)
    2. Đặc trị (Treatment/Serum)
    3. Dưỡng ẩm (Moisturizer)
    4. Chống nắng (Sunscreen - tùy chọn bước 4)
- **Công thức tính:**
  - Tự động áp dụng chiết khấu **15%** trên tổng giá trị của các sản phẩm thuộc combo.
  - Hiển thị trực quan dòng thông báo: *"Bạn đã tiết kiệm được [Số tiền giảm] khi mua trọn bộ Routine!"*.
- **Cơ chế gỡ giảm giá (Rollback):**
  - Nếu khách hàng xóa hoặc giảm số lượng khiến giỏ hàng không còn đủ tối thiểu 3 bước quy chuẩn, mã giảm combo sẽ lập tức bị hủy bỏ và tổng tiền tính lại về nguyên giá.

---

## 3. Cơ chế cảnh báo xung đột hoạt chất (Ingredient Conflict Warning)
- **Mục đích:** Đảm bảo an toàn da liễu cho khách hàng Unisex khi tự phối đồ skincare.
- **Quy tắc phát hiện kỵ nhau:**
  - Quét taxonomy `pa_active_ingredient` của các sản phẩm có trong giỏ hàng.
  - Cảnh báo khi xuất hiện cùng lúc các cặp hoạt chất kỵ nhau, tiêu biểu:
    - **AHA/BHA (Salicylic/Glycolic Acid)** kết hợp với **Retinol/Tretinoin**.
    - **Vitamin C (L-Ascorbic Acid)** nồng độ cao kết hợp với **Retinol** hoặc **Niacinamide** ở pH không tương thích.
- **Giao diện cảnh báo (UX):**
  - Xuất hiện một thẻ cảnh báo (Alert Card) màu vàng/cam nổi bật ngay trên bảng giỏ hàng.
  - Nội dung mẫu: *"Lưu ý da liễu: Routine của bạn đang chứa AHA/BHA và Retinol dùng chung thời điểm có thể gây kích ứng da. Hãy cân nhắc dùng xen kẽ sáng/tối hoặc cách ngày."*
  - Vẫn cho phép khách tiếp tục thanh toán (Soft warning) kèm khuyến cáo.