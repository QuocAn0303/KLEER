# Flow Card L05: Xử lý Đơn hàng & Lưu trữ HPOS (Order Processing & HPOS Lifecycle)

## 1. Thông tin chung
- **Mã luồng:** L05
- **Tên luồng:** Xử lý đơn hàng & Thông báo (Vòng đời đơn, HPOS, tồn kho, email hóa đơn)
- **Flow Owner:** Nguyễn Minh Trí (Plugin Dev 2)
- **Người duyệt:** Nguyễn Tấn Phát (PO), Lê Nguyễn Quốc An (Scrum Master)
- **Cổng:** G1 (Đặc tả nghiệp vụ)

## 2. Mục tiêu & Phạm vi
Quản lý toàn bộ vòng đời đơn hàng từ khi tạo đơn đến khi sẵn sàng giao, tự động trừ tồn kho, tối ưu hiệu năng lưu trữ cơ sở dữ liệu với WooCommerce HPOS (High-Performance Order Storage - Rubric 5.3) và kích hoạt gửi email hóa đơn.
- **Bắt đầu khi:** Khách hàng bấm đặt hàng tại cổng Checkout (nhận từ luồng L04).
- **Kết thúc khi:** Đơn hàng chuyển trạng thái Sẵn sàng giao / Đang xử lý (bàn giao cho luồng L06 Vận chuyển và L07 Báo cáo).

## 3. Vòng đời 5 trạng thái đơn hàng chuẩn (WooCommerce Order States)
1. **Pending payment (Chờ thanh toán):** Đơn hàng vừa khởi tạo, đang chờ xác nhận thanh toán (COD hoặc cổng thanh toán).
2. **Processing (Đang xử lý):** Thanh toán thành công hoặc đơn COD hợp lệ.
   - **Hành động hệ thống:** Tự động kích hoạt cơ chế trừ tồn kho sản phẩm (`reduce_order_stock`).
   - **Thông báo:** Kích hoạt hook gửi email hóa đơn tự động cho khách hàng.
3. **On-hold (Tạm giữ):** Tồn kho đã giảm nhưng cần nhân viên xác nhận thủ công (ví dụ: chuyển khoản ngân hàng chờ đối soát).
4. **Completed (Hoàn thành):** Đơn hàng đã đóng gói, giao thành công đến tay người mua.
5. **Cancelled (Đã hủy):** Khách hủy đơn hoặc quá hạn thanh toán; tự động hoàn lại số lượng tồn kho (`restore_order_stock`).

## 4. Kiến trúc lưu trữ CSDL chuyên biệt (WooCommerce HPOS)
Chuyển đổi hoàn toàn cơ chế lưu trữ từ `wp_posts` và `wp_postmeta` truyền thống sang các bảng chuyên biệt:
- `wp_wc_orders`: Lưu dữ liệu định danh, ID khách hàng, trạng thái, tổng giá trị, loại tiền tệ.
- `wp_wc_order_addresses`: Lưu địa chỉ thanh toán (billing) và giao hàng (shipping).
- `wp_wc_order_operational_data`: Lưu cờ xử lý, trạng thái thanh toán, ngày tạo/cập nhật.
- `wp_wc_orders_meta`: Lưu các metadata mở rộng tùy chỉnh của KLEER.

## 5. Hook & Trigger kỹ thuật cốt lõi
- Hook chuyển trạng thái đơn: `woocommerce_order_status_changed`
- Hook xử lý trừ kho tự động: `woocommerce_payment_complete` hoặc `woocommerce_order_status_processing`
- Hook gửi email thông báo: `woocommerce_order_status_pending_to_processing_notification`
- Lớp can thiệp HPOS: `Automattic\WooCommerce\Utilities\OrderUtil`

## 6. Tiêu chí chấp nhận (Given - When - Then)
- **Kịch bản 1 (Tạo đơn thành công):**
  - *Given:* Khách hàng có 2 sản phẩm trong giỏ hàng.
  - *When:* Bấm xác nhận đặt đơn hàng COD.
  - *Then:* Đơn hàng được tạo trong bảng `wp_wc_orders` với trạng thái `Processing`.
- **Kịch bản 2 (Trừ kho tự động):**
  - *Given:* Sản phẩm A còn 10 món trong kho.
  - *When:* Đơn hàng mua 2 sản phẩm A chuyển sang `Processing`.
  - *Then:* Tồn kho sản phẩm A giảm chính xác còn 8 món.
- **Kịch bản 3 (Trigger Email):**
  - *Given:* Đơn hàng hợp lệ vừa chuyển sang `Processing`.
  - *When:* Hệ thống ghi nhận trạng thái.
  - *Then:* Kích hoạt gửi email xác nhận và hóa đơn chi tiết về địa chỉ email của khách.
- **Kịch bản 4 (Hủy đơn hoàn kho):**
  - *Given:* Đơn hàng đang ở trạng thái `Processing` hoặc `On-hold`.
  - *When:* Đơn hàng chuyển sang trạng thái `Cancelled`.
  - *Then:* Tồn kho của các sản phẩm trong đơn được phục hồi lại đúng số lượng ban đầu.
- **Kịch bản 5 (Không ghi đè wp_posts):**
  - *Given:* Hệ thống đã kích hoạt HPOS chế độ Custom Tables.
  - *When:* Đơn hàng mới được tạo.
  - *Then:* 100% dữ liệu đơn hàng nằm ở các bảng `wp_wc_*`, không tạo thêm bản ghi dạng `post_type = 'shop_order'` trong `wp_posts`.