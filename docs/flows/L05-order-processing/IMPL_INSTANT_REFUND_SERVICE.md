# Đặc tả Triển khai `InstantRefundService` (Hoàn tiền tức thì 3 bước – Store Credit)

Tài liệu này dùng để bàn giao ngữ cảnh đầy đủ cho đoạn chat mới nhằm triển khai **Nhánh 1: Quy trình Đổi trả & Hoàn tiền nhanh Store Credit 3 bước (tham chiếu Shopee Instant Refund)** trong `kleer-plugin` và tích hợp gửi email qua `kleer-mailer`.

> **Gợi ý prompt mở đầu ở đoạn chat mới:**
> *"Hãy đọc file `@docs/flows/L05-order-processing/IMPL_INSTANT_REFUND_SERVICE.md`, đối chiếu với `Plugin.php`, `CartComboService.php` và `kleer-mailer/includes/class-order-email.php`, sau đó triển khai `InstantRefundService`, tích hợp email Store Credit và viết unit test CLI đầy đủ."*

---

## 1. Phạm vi & Bối cảnh Đồ án

* **Chức năng:** Chức năng 6 – Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước (`L07`, phụ trách trong báo cáo: Nguyễn Ngọc Thảo Uyên; kết nối với Chức năng 5 – Vòng đời đơn hàng HPOS & Hóa đơn Email QR của Nguyễn Minh Trí).
* **Phạm vi triển khai (BẮT BUỘC):**
  * Chỉ triển khai **Nhánh 1 (Instant Refund 3 bước $\rightarrow$ Phát hành Store Credit Coupon `KLR-CREDIT-...`)**.
  * **KHÔNG triển khai** Nhánh 2 (hoàn tiền chuyển khoản ngân hàng chuẩn / VNPay Refund API — nhánh này đã đưa vào Chương 6 *Hướng phát triển* trong [BAO_CAO_DO_AN_KLEER.md](../../BAO_CAO_DO_AN_KLEER.md)).
* **Ngôn ngữ & Chuẩn code hiện hành của repo:**
  * PHP **>= 8.2**, bắt buộc `declare(strict_types=1);` và `defined('ABSPATH') || exit;`.
  * Autoloader PSR-4 trong [kleer-plugin.php](../../../wp-content/plugins/kleer-plugin/kleer-plugin.php): prefix `Kleer\` ánh xạ vào `wp-content/plugins/kleer-plugin/src/`.
  * Do đó `InstantRefundService` **phải** dùng `namespace Kleer\Services;` (lưu ý: `HposService.php` cũ đang dùng `Kleer\Plugin\Services` nên không tự nạp qua PSR-4; không lặp lại lỗi namespace này).

---

## 2. Các File Tham chiếu Trước khi Code

1. [kleer-plugin.php](../../../wp-content/plugins/kleer-plugin/kleer-plugin.php) — Autoloader `Kleer\` và lifecycle `plugins_loaded`.
2. [Plugin.php](../../../wp-content/plugins/kleer-plugin/src/Plugin.php) — Nơi khởi tạo và gọi `->register()` cho các service (theo đúng mẫu `$this->cartComboService`).
3. [CartComboService.php](../../../wp-content/plugins/kleer-plugin/src/Services/CartComboService.php) — Mẫu chuẩn của một Service trong `kleer-plugin` (cách định nghĩa constants, hook `wp_ajax_*`, sanitize input, verify nonce, render WooCommerce notices).
4. [class-order-email.php](../../../wp-content/plugins/kleer-mailer/includes/class-order-email.php) — Nơi gửi email hóa đơn HTML (`Kleer_Mailer\Order_Email`), cần bổ sung method gửi email cấp mã Store Credit với cơ chế chống gửi trùng bằng order meta.
5. [run_tests.php](../../../wp-content/plugins/kleer-plugin/tests/run_tests.php) — Mẫu viết unit test PHP chạy trực tiếp qua CLI (`php ...`) không cần kết nối DB WordPress thật.
6. [BAO_CAO_DO_AN_KLEER.md](../../BAO_CAO_DO_AN_KLEER.md) (Mục `3.1.6`, `3.3.6`, `3.4.1 (#6)`, `3.5.6`, `3.6.6`, `4.2`) — Khớp 100% tên lớp, tên trạng thái, tên meta key và quy tắc nghiệp vụ đã chốt trong báo cáo.

---

## 3. Quy tắc Kỹ thuật & Lưu trữ HPOS

* **Bắt buộc tương thích WooCommerce HPOS:**
  * Không bao giờ dùng `get_post_meta()`, `update_post_meta()` hay `wp_update_post()` trực tiếp cho đơn hàng.
  * Luôn thao tác qua WooCommerce Order CRUD API (`wc_get_order($orderId)`, `$order->get_status()`, `$order->get_meta()`, `$order->update_meta_data()`, `$order->update_status()`, `$order->add_order_note()`, `$order->save()`).
* **Metadata keys chuẩn trên `WC_Order` (lưu vào `wp_wc_orders_meta`):**
  * `_kleer_return_reason` (`string`: mã lý do, ví dụ `damaged_shipping`, `wrong_item`, `skin_irritation`)
  * `_kleer_return_description` (`string`: mô tả chi tiết của khách)
  * `_kleer_return_requested_at` (`string`: ISO-8601 / `Y-m-d H:i:s` UTC hoặc `current_time('mysql')`)
  * `_kleer_return_carrier` (`string`: đơn vị vận chuyển gửi trả, ví dụ `SPX`, `GHN`, `GHTK`, `ViettelPost`)
  * `_kleer_return_tracking_code` (`string`: mã vận đơn trả hàng)
  * `_kleer_return_tracking_submitted_at` (`string`)
  * `_kleer_store_credit_coupon_code` (`string`: mã coupon đã phát hành, dùng làm cờ idempotency)
  * `_kleer_store_credit_issued_at` (`string`)
  * `_kleer_store_credit_email_sent` (`string`: cờ chống gửi trùng email trong `kleer-mailer`)

---

## 4. Đặc tả Chi tiết Nghiệp vụ 3 Bước

### 4.1. Đăng ký Trạng thái Đơn hàng Tùy chỉnh (Custom Order Statuses)
* Hook `init` (`register_post_status`) và filter `wc_order_statuses`:
  1. `wc-return-requested` — Nhãn: `Yêu cầu trả hàng` (Bước 1)
  2. `wc-return-approved` — Nhãn: `Chờ gửi trả hàng` (Bước 2)
* Đảm bảo trạng thái hiển thị trong trang quản trị Orders (hỗ trợ cả Legacy và HPOS screen) để Admin có thể chuyển trạng thái qua lại giữa `return-requested` $\rightarrow$ `return-approved` (duyệt) hoặc `return-requested` $\rightarrow$ `completed` (từ chối).

### 4.2. Bước 1 – Khách hàng gửi Yêu cầu Trả hàng (`handleCustomerReturnRequest`)
* **Điều kiện hợp lệ (Pre-conditions & Guards):**
  1. Kiểm tra CSRF Nonce (`wp_verify_nonce`) hợp lệ.
  2. Kiểm tra quyền sở hữu đơn hàng (`verifyOrderOwnership(WC_Order $order, ?string $orderKey = null)`):
     * Nếu đã đăng nhập: `$order->get_customer_id() === get_current_user_id()` (hoặc `current_user_can('manage_woocommerce')`).
     * Nếu là Guest xem qua `order_key` trên URL My Account / View Order: đối chiếu `$order->get_order_key() === $orderKey`.
  3. Trạng thái hiện tại của đơn phải là `completed` (chấp nhận có/không tiền tố `wc-` khi chuẩn hóa).
  4. **Kiểm tra thời hạn $\le 7$ ngày (EF-01):** Tính số ngày từ `$order->get_date_completed()` (fallback sang `get_date_created()` nếu `date_completed` null) tới thời điểm hiện tại phải $\le 7 \times 86400$ giây. Nếu quá 7 ngày $\rightarrow$ từ chối (`throw` / trả về lỗi rõ ràng).
  5. Kiểm tra `reason` thuộc danh mục hợp lệ (`damaged_shipping`, `wrong_item`, `skin_irritation`, hoặc map nhãn tiếng Việt) và `description` được sanitize bằng `sanitize_textarea_field`.
* **Hành động:**
  * Lưu `_kleer_return_reason`, `_kleer_return_description`, `_kleer_return_requested_at`.
  * Ghi order note cho Admin dễ theo dõi lý do khách gửi.
  * Gọi `$order->update_status('return-requested', ...)` và `$order->save()`.

### 4.3. Bước 2 – Quản trị viên Duyệt / Từ chối Yêu cầu
* Admin xử lý trực tiếp tại màn hình chỉnh sửa đơn hàng WooCommerce (chuyển trạng thái sang `return-approved` để duyệt, hoặc trả về `completed` kèm order note nếu từ chối theo AF-01).
* Có thể bổ sung Meta Box nhỏ trên màn hình Admin Order (`add_meta_boxes` hỗ trợ cả `woocommerce_page_wc-orders` của HPOS và `shop_order`) hiển thị rõ: Lý do đổi trả, Mô tả, Thời gian yêu cầu, Mã vận đơn trả hàng, và Mã Store Credit đã cấp (nếu có).

### 4.4. Bước 3 – Khách nhập Mã vận đơn $\rightarrow$ Kích hoạt Instant Refund Tự động (`handleReturnTrackingSubmitted` & `issueStoreCreditCoupon`)
* **Điều kiện hợp lệ (Guards):**
  1. Kiểm tra Nonce và quyền sở hữu đơn hàng như Bước 1.
  2. Trạng thái hiện tại của đơn phải là `return-approved`.
  3. **Kiểm tra định dạng Mã vận đơn (EF-02):** Chuẩn hóa trim + uppercase (tùy chọn giữ nguyên chữ/số), validate regex `/^[A-Za-z0-9\-]{6,64}$/` (tối thiểu 6 ký tự chữ/số, không khoảng trắng/ký tự lạ). Nếu rỗng hoặc sai định dạng $\rightarrow$ báo lỗi, không cho qua.
* **Hành động tức thì (không cần chờ hàng về kho):**
  1. Lưu `_kleer_return_carrier`, `_kleer_return_tracking_code`, `_kleer_return_tracking_submitted_at`.
  2. Gọi `issueStoreCreditCoupon(WC_Order $order): string`:
     * **Idempotency:** Nếu `$order->get_meta('_kleer_store_credit_coupon_code')` đã có giá trị khác rỗng $\rightarrow$ trả về luôn mã cũ, tuyệt đối không tạo coupon thứ hai.
     * Sinh mã duy nhất: `KLR-CREDIT-{ORDER_ID}-{HASH}` (ví dụ `{HASH}` = 6 ký tự hex/alphanumeric viết hoa sinh ngẫu nhiên an toàn).
     * Khởi tạo `WC_Coupon`:
       * `set_code($couponCode)`
       * `set_discount_type('fixed_cart')`
       * `set_amount((float) $order->get_total())` (100% tổng giá trị đơn hàng)
       * `set_usage_limit(1)`
       * `set_email_restrictions([$order->get_billing_email()])` (ràng buộc cứng email khách hàng)
       * `set_description(...)`
       * Lưu meta trên coupon: `_kleer_source_order_id`, `_kleer_store_credit_issued_at`
       * Gọi `$coupon->save()`.
     * Lưu `_kleer_store_credit_coupon_code` và `_kleer_store_credit_issued_at` vào `$order`.
  3. Cập nhật trạng thái đơn hàng sang `refunded` kèm order note ghi rõ mã vận đơn & mã Store Credit vừa phát hành (`$order->update_status('refunded', ...)` + `$order->save()`).
  4. Phát WordPress action hook:
     ```php
     do_action('kleer_instant_refund_issued', $order->get_id(), $couponCode, $order);
     ```

---

## 5. Giao diện Người dùng (Order Detail UI) & Tích hợp Email

### 5.1. Giao diện trên Trang Chi tiết Đơn hàng (`woocommerce_order_details_after_order_table`)
Hiển thị box **Đổi trả & Hoàn tiền nhanh Store Credit** tùy theo trạng thái đơn của khách (tương thích trang *My Account $\rightarrow$ View Order* và *Order Received / View Order*):
* **`completed` & còn hạn $\le 7$ ngày:** Form Bước 1 (Select lý do đổi trả, Textarea mô tả, Nút *"Yêu cầu Trả hàng/Hoàn tiền"*). Nếu quá 7 ngày thì ẩn form và hiển thị thông báo đã hết hạn đổi trả 7 ngày (EF-01).
* **`return-requested`:** Thông báo trạng thái *"Yêu cầu trả hàng đang chờ Quản trị viên xét duyệt"* + tóm tắt lý do đã gửi.
* **`return-approved`:** Form Bước 3 mở khóa ô chọn Đơn vị vận chuyển + ô nhập **Mã vận đơn gửi trả** (tối thiểu 6 ký tự) + nút *"Xác nhận đã gửi hàng & Nhận Store Credit ngay"*.
* **`refunded` (có `_kleer_store_credit_coupon_code`):** Card nổi bật hiển thị **Mã Store Credit (`KLR-CREDIT-...`)**, mệnh giá 100% đơn hàng, email áp dụng, hướng dẫn dán mã ở trang Giỏ hàng/Checkout và nút sao chép nhanh.

> **Lưu ý UX:** Submit form qua `admin-post.php` (hoặc `wp_ajax_*`) rồi redirect lại đúng URL trang chi tiết đơn hàng kèm `wc_add_notice()` (hoặc query param thông báo) để người dùng thấy phản hồi tức thì mà không cần viết thêm build step JS phức tạp. Nếu thuận tiện, có thể tách thêm lớp `InstantRefundEndpoints` / `InstantRefundController` ở `kleer/v1` để test API bằng Postman/curl, nhưng UI form + `InstantRefundService` là ưu tiên chính.

### 5.2. Tích hợp `kleer-mailer`
Trong [class-order-email.php](../../../wp-content/plugins/kleer-mailer/includes/class-order-email.php):
* Đăng ký listener:
  ```php
  add_action('kleer_instant_refund_issued', [$this, 'on_store_credit_issued'], 20, 3);
  ```
* Thêm method `send_store_credit_email(int $order_id, string $coupon_code): bool` (khớp chữ ký trong `BAO_CAO_DO_AN_KLEER.md` mục `3.4.1`):
  * Kiểm tra cờ `_kleer_store_credit_email_sent` trên `$order` để tránh gửi trùng.
  * Kiểm tra email hợp lệ (`is_email($order->get_billing_email())`).
  * Gửi email HTML thông báo hoàn tiền nhanh 100% qua `wp_mail()` chứa rõ: Mã đơn hàng gốc, Mã vận đơn trả hàng, Mã Store Credit `KLR-CREDIT-...`, Mệnh giá (VND), Email được phép dùng, và nút CTA quay lại cửa hàng mua sắm.
  * Đánh dấu `_kleer_store_credit_email_sent` và ghi order note khi gửi thành công.

---

## 6. Cấu trúc File Dự kiến Tạo / Cập nhật

1. **[Tạo mới]** `wp-content/plugins/kleer-plugin/src/Services/InstantRefundService.php`
   * Namespace: `Kleer\Services`
   * Các hằng số và method công khai bám sát thiết kế Class Diagram (`register()`, `registerCustomOrderStatuses()`, `isEligibleForReturn()`, `handleCustomerReturnRequest()`, `handleReturnTrackingSubmitted()`, `issueStoreCreditCoupon()`, `renderCustomerReturnPanel()`, `renderAdminReturnMetaBox()`...).
2. **[Cập nhật]** `wp-content/plugins/kleer-plugin/src/Plugin.php`
   * Thêm property `private ?InstantRefundService $instantRefundService = null;`, getter `instantRefundService(): ?InstantRefundService`, khởi tạo và gọi `register()` trong `Plugin::register()`.
3. **[Cập nhật]** `wp-content/plugins/kleer-mailer/includes/class-order-email.php`
   * Bổ sung hook `kleer_instant_refund_issued`, method `on_store_credit_issued()` và `send_store_credit_email(int $order_id, string $coupon_code)`.
4. **[Tạo mới]** `wp-content/plugins/kleer-plugin/tests/run_instant_refund_tests.php`
   * Bộ unit test CLI tự mock các hàm WooCommerce (`WC_Order`, `WC_Coupon`, `wc_get_order`, `wp_verify_nonce`, `do_action`...) chạy không cần DB.

---

## 7. Tiêu chí Nghiệm thu & Test Cases Bắt buộc

Sau khi viết code xong, chạy `php -l` kiểm tra cú pháp toàn bộ file đã sửa/tạo và chạy bộ test `php wp-content/plugins/kleer-plugin/tests/run_instant_refund_tests.php` (cộng với `run_tests.php` hiện có để đảm bảo không hồi quy):

1. **TC-IR-01 (Bước 1 Happy Path):** Đơn `completed` trong vòng $\le 7$ ngày, đúng chủ sở hữu & nonce $\rightarrow$ chuyển sang `return-requested`, lưu đủ `_kleer_return_reason`, `_kleer_return_description`, `_kleer_return_requested_at`.
2. **TC-IR-02 (EF-01 Quá hạn 7 ngày):** Đơn `completed` từ 8 ngày trước $\rightarrow$ `isEligibleForReturn()` trả về `false`, `handleCustomerReturnRequest()` từ chối và giữ nguyên trạng thái `completed`.
3. **TC-IR-03 (Guard trạng thái & quyền):** Đơn đang `processing` hoặc user khác không có `order_key` hợp lệ $\rightarrow$ từ chối thao tác.
4. **TC-IR-04 (EF-02 Mã vận đơn lỗi):** Đơn đang `return-approved`, khách gửi mã rỗng hoặc `< 6` ký tự (ví dụ `"123"`) hoặc có ký tự đặc biệt $\rightarrow$ từ chối, không đổi trạng thái, không tạo coupon.
5. **TC-IR-05 (Bước 3 Happy Path):** Đơn đang `return-approved`, khách nhập mã vận đơn hợp lệ (ví dụ `"SPXVN20261111"`) $\rightarrow$ đơn chuyển `refunded`, tạo `WC_Coupon` mã `KLR-CREDIT-{ID}-...` mệnh giá = 100% tổng đơn, `discount_type = fixed_cart`, `usage_limit = 1`, `email_restrictions = [billing_email]`, kích hoạt hook `kleer_instant_refund_issued`.
6. **TC-IR-06 (Idempotency):** Gọi `issueStoreCreditCoupon()` lần 2 trên đơn đã có `_kleer_store_credit_coupon_code` $\rightarrow$ trả về đúng mã cũ, không tạo coupon mới.
7. **TC-IR-07 (Mailer Idempotency):** `Order_Email::send_store_credit_email()` gửi 1 lần thành công và gắn meta `_kleer_store_credit_email_sent`, gọi lại lần 2 không gửi trùng.
