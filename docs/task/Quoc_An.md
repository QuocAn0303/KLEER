## 4\. THIẾT KẾ HỆ THỐNG (SYSTEM DESIGN)

*(Phụ trách: Lê Nguyễn Quốc An – Thiết kế biểu đồ trên Draw.io và bàn giao qua Google Docs)*

**⚠️ QUY ĐỊNH BÀN GIAO CHO CHƯƠNG 4 (LÊ NGUYỄN QUỐC AN):**

* Toàn bộ sơ đồ Kiến trúc hệ thống (Mục 4.1) và sơ đồ Cơ sở dữ liệu ERD (Mục 4.2) **bắt buộc vẽ trên Draw.io (`.drawio`)**, xuất file ảnh `.png` sắc nét và bàn giao qua Google Docs cho Nhóm trưởng (Nguyễn Tấn Phát).  
* Dưới đây là toàn bộ nội dung thuyết minh kiến trúc và đặc tả các bảng CSDL chuẩn hóa 3NF \+ ánh xạ HPOS đã được thiết lập sẵn làm khung chuẩn.

---

### 4.1. Kiến trúc hệ thống (System Architecture)

Hệ thống thương mại điện tử **KLEER** được thiết kế bằng cách kết hợp hai mô hình kiến trúc hiện đại nhằm đảm bảo cả tính đóng gói triển khai lẫn khả năng mở rộng mã nguồn:

1. **Kiến trúc Phân tầng Vật lý 3 tầng (3-Tier Deployment Architecture)** trên nền tảng ảo hóa container **Docker Compose**.  
2. **Kiến trúc Nhân thu nhỏ kết hợp Phân lớp Hướng đối tượng (Microkernel / Plugin-based \+ Layered OOP Architecture)** ở tầng ứng dụng.

#### 4.1.1. Kiến trúc Triển khai Vật lý 3 Tầng (3-Tier Docker Architecture)

* **Tên file Draw.io cần vẽ:** `L09_System_Architecture_3Tier.drawio` (và `.png`)

* **Mô tả các thành phần trên sơ đồ:**

  * **Tầng 1 – Presentation & Web Proxy Tier (`nginx` container):** Đóng vai trò Reverse Proxy tiếp nhận toàn bộ kết nối HTTP/HTTPS từ trình duyệt khách hàng (Desktop/Mobile), phân phối trực tiếp các tài nguyên tĩnh (ảnh `.webp`, CSS, JS) và chuyển tiếp các yêu cầu động qua giao thức FastCGI tới tầng xử lý ứng dụng.  
  * **Tầng 2 – Application & Business Logic Tier (`php:8.2-fpm` container):** Vận hành nhân nền tảng WordPress 6.x & WooCommerce 8.x cùng 4 module tách biệt hoàn toàn:  
    * `kleer-theme`: Tầng giao diện người dùng (PHP Templates thuần, CSS BEM, Vanilla JS ES6 SPA cho Skin Quiz và AJAX Filter – tuyệt đối không dùng Page Builder nặng nề).  
    * `kleer-plugin`: Tầng xử lý nghiệp vụ cốt lõi theo chuẩn PSR-4 (`Kleer\` namespace), cung cấp REST API `/wp-json/kleer/v1/skin-quiz`, thuật toán chấm điểm Routine v4, giỏ hàng Combo 15%, cảnh báo xung đột hoạt chất, HPOS và hoàn tiền Store Credit.  
    * `kleer-payments`: Plugin độc lập tích hợp cổng thanh toán VNPay Sandbox (`HMAC-SHA512`) và trình tạo mã QR chuyển khoản động Napas VietQR.  
    * `kleer-mailer`: Plugin độc lập xử lý gửi Email hóa đơn HTML đính kèm mã QR và Email cấp mã Store Credit qua giao thức SMTP.  
  * **Tầng 3 – Data & Caching Tier (`mariadb:10.11` & `redis` containers):**  
    * **MariaDB 10.11:** Lưu trữ cơ sở dữ liệu quan hệ với InnoDB Engine, vận hành kiến trúc bảng phẳng **WooCommerce HPOS** (`wp_wc_orders*`) cách ly dữ liệu đơn hàng khỏi bảng bài viết.  
    * **Redis In-Memory Cache:** Đệm kết quả truy vấn sản phẩm và các thuộc tính Taxonomy (`KleerCache`), giảm tải truy vấn SQL trực tiếp xuống đĩa cứng.

#### 4.1.2. Kiến trúc Phân lớp Phần mềm trong `kleer-plugin` (Layered OOP Architecture)

* **Tên file Draw.io cần vẽ:** `L09_Layered_Plugin_Architecture.drawio` (và `.png`)  
* **Nguyên tắc dòng phụ thuộc một chiều (Unidirectional Dependency Flow):** **Luồng phụ thuộc:** `HTTP/AJAX Request → Endpoints → Controllers → Services → Contracts (Interfaces) ← Repositories → Models / Database`

| Tầng kiến trúc (Layer) | Thư mục mã nguồn | Trách nhiệm cốt lõi (Responsibility) |
| :---- | :---- | :---- |
| **1\. Presentation Layer** | `wp-content/themes/kleer-theme/` | Hiển thị giao diện HTML5/CSS BEM, xử lý tương tác SPA Skin Quiz (`quiz.js`), bộ lọc AJAX (`catalog-filter.js`) và giỏ hàng (`routine-cart.js`). |
| **2\. Routing / Endpoint Layer** | `kleer-plugin/src/Endpoints/` | Đăng ký các route REST API (`register_rest_route` dưới namespace `kleer/v1`), kiểm tra quyền truy cập (`permission_callback`) và chính sách CORS. |
| **3\. Controller Layer** | `kleer-plugin/src/Controllers/` | Tiếp nhận `WP_REST_Request`, kiểm tra tính hợp lệ của dữ liệu đầu vào bằng JSON Schema (`ValidatesJsonPayload`), gọi tầng Service và định dạng phản hồi `WP_REST_Response`. |
| **4\. Service / Domain Layer** | `kleer-plugin/src/Services/` | Chứa toàn bộ quy tắc nghiệp vụ cốt lõi: `QuizEngineService` (chấm điểm da & chọn Routine), `CartComboService` (giảm giá 15% & quét xung đột hoạt chất), `HposService`, `InstantRefundService`. |
| **5\. Repository / Data Access Layer** | `kleer-plugin/src/Repositories/` | Hiện thực hóa các `Contracts` (`ProductRepositoryInterface`), thực thi truy vấn dữ liệu sản phẩm, Taxonomy và đơn hàng HPOS kết hợp bộ nhớ đệm `KleerCache`. |

---

### 4.2. Thiết kế cơ sở dữ liệu (Database Design)

Để vừa đảm bảo tính chặt chẽ của lý thuyết chuẩn hóa cơ sở dữ liệu quan hệ (**Normalization 1NF – 2NF – 3NF**), vừa phản ánh trung thực cơ chế lưu trữ hiệu năng cao **WooCommerce HPOS (High-Performance Order Storage)** đang chạy trong thực tế, thiết kế CSDL của KLEER được trình bày ở **2 tầng liên kết chặt chẽ**:

1. **Tầng 1 – Mô hình Dữ liệu Quan hệ Luận lý Chuẩn hóa 3NF (Logical 3NF ERD):** Biểu diễn 8 thực thể nghiệp vụ cốt lõi của bài toán KLEER với đầy đủ Khóa chính (`PK`), Khóa ngoại (`FK`) và ràng buộc toàn vẹn.  
2. **Tầng 2 – Ánh xạ Vật lý sang Kiến trúc Bảng phẳng WooCommerce HPOS & Taxonomy Schema (Physical Schema Mapping):** Giải trình cách lưu trữ thực tế trong MariaDB giúp loại bỏ điểm nghẽn của bảng `wp_postmeta` truyền thống.

#### 4.2.1. Tầng 1: Mô hình ERD Luận lý Chuẩn hóa 3NF (Logical 3NF Schema)

* **Tên file Draw.io cần vẽ:** `L09_Database_ERD_3NF_and_HPOS.drawio` (và `.png`)  
* **Giải trình tính chuẩn hóa (Normalization):**  
  * **Đạt chuẩn 1NF:** Mọi thuộc tính trong 8 bảng đều chứa giá trị nguyên tố (Atomic). Các thuộc tính đa trị của sản phẩm (như một sản phẩm phù hợp với nhiều *Loại da*, giải quyết nhiều *Vấn đề da*, hoặc chứa nhiều *Hoạt chất INCI*) được tách hoàn toàn ra bảng trung gian `Product_Taxonomies` thay vì lưu chuỗi ngăn cách bằng dấu phẩy.  
  * **Đạt chuẩn 2NF:** Mọi thuộc tính không khóa trong các bảng có khóa chính ghép (`Product_Taxonomies`, `Order_Items`) đều phụ thuộc hàm đầy đủ vào toàn bộ khóa chính (ví dụ: `unit_price` và `quantity` trong `Order_Items` phụ thuộc vào cả `order_id` và `product_id`, còn `product_name` và `regular_price` gốc được tách về bảng `Products`).  
  * **Đạt chuẩn 3NF:** Không tồn tại phụ thuộc bắc cầu (Transitive Dependency) giữa các thuộc tính không khóa. Thông tin chi tiết khách hàng tách ở bảng `Users`, thông tin danh mục thuộc tính da liễu tách ở `Taxonomy_Terms`, thông tin giao dịch thanh toán tách ở `Payment_Transactions`, và mã hoàn tiền nhanh tách ở bảng `Store_Credits`.

##### Đặc tả 8 Bảng Quan hệ Luận lý Cốt lõi của KLEER:

**1\. Bảng `Users` (Người dùng / Khách hàng & Quản trị viên)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `user_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh duy nhất của người dùng |
| `email` | `VARCHAR(100)` | UNIQUE, NOT NULL | Địa chỉ email đăng nhập & nhận hóa đơn / Store Credit |
| `password_hash` | `VARCHAR(255)` | NOT NULL | Mật khẩu đã băm bảo mật |
| `full_name` | `VARCHAR(150)` | NOT NULL | Họ và tên người dùng |
| `phone` | `VARCHAR(20)` | NULL | Số điện thoại liên hệ giao hàng |
| `role` | `ENUM('customer','admin')` | NOT NULL | Vai trò phân quyền trên hệ thống |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm tạo tài khoản |

**2\. Bảng `Products` (Sản phẩm Chăm sóc Da – ≥ 500 SKU)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `product_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh sản phẩm |
| `sku` | `VARCHAR(50)` | UNIQUE, NOT NULL | Mã SKU chuẩn hóa (`KLR-{hash8}`) |
| `product_name` | `VARCHAR(255)` | NOT NULL | Tên thương mại của sản phẩm |
| `regular_price` | `DECIMAL(12,2)` | NOT NULL | Giá bán niêm yết (VNĐ) |
| `stock_quantity` | `INT` | NOT NULL, DEFAULT 0 | Số lượng tồn kho thực tế |
| `stock_status` | `ENUM('instock','outofstock')` | NOT NULL | Trạng thái còn hàng / hết hàng |
| `full_inci_list` | `TEXT` | NULL | Bảng thành phần danh pháp quốc tế INCI đầy đủ |
| `is_strong_active` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Cờ hoạt chất mạnh (`_kleer_strong_active = 1/0` phục vụ lọc da nhạy cảm) |
| `is_gentle_fallback` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Cờ sản phẩm lành tính dự phòng (`_kleer_gentle_fallback = 1/0`) |

**3\. Bảng `Taxonomy_Terms` (Từ điển Thuộc tính Da liễu & Hoạt chất)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `term_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh giá trị thuộc tính |
| `taxonomy_group` | `VARCHAR(50)` | NOT NULL | Nhóm thuộc tính (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`, `pa_brand`) |
| `term_slug` | `VARCHAR(100)` | NOT NULL | Mã chuẩn hóa (VD: `oily`, `sensitive`, `acne`, `cleanser`, `serum`, `retinol`, `bha`...) |
| `term_name` | `VARCHAR(150)` | NOT NULL | Tên hiển thị tiếng Việt (VD: *Da dầu*, *Da nhạy cảm*, *Sữa rửa mặt*...) |

**4\. Bảng `Product_Taxonomies` (Bảng liên kết Đa-Đa giữa Sản phẩm và Thuộc tính Da liễu)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `product_id` | `BIGINT UNSIGNED` | **PK, FK1** (`Products.product_id`) | Mã sản phẩm |
| `term_id` | `BIGINT UNSIGNED` | **PK, FK2** (`Taxonomy_Terms.term_id`) | Mã thuộc tính da liễu / bước routine / hoạt chất được gán |

**5\. Bảng `Orders` (Đơn hàng – Lưu trữ trên cấu trúc phẳng HPOS)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `order_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã đơn hàng |
| `customer_id` | `BIGINT UNSIGNED` | **FK** (`Users.user_id`), NULL | Mã tài khoản khách hàng (NULL nếu mua dạng Guest kèm email) |
| `billing_email` | `VARCHAR(100)` | NOT NULL | Email người mua nhận hóa đơn QR & Store Credit |
| `shipping_address` | `VARCHAR(500)` | NOT NULL | Địa chỉ nhận hàng đầy đủ |
| `order_status` | `VARCHAR(40)` | NOT NULL | Trạng thái đơn (`pending`, `on-hold`, `processing`, `completed`, `cancelled`, `return-requested`, `return-approved`, `refunded`) |
| `subtotal_amount` | `DECIMAL(12,2)` | NOT NULL | Tổng tiền hàng trước giảm giá |
| `bundle_discount_amount` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0 | Số tiền giảm giá **15% Combo Routine** (khi đủ ≥ 3 bước) |
| `shipping_fee` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0 | Phí vận chuyển theo khu vực |
| `total_amount` | `DECIMAL(12,2)` | NOT NULL | Tổng số tiền thanh toán cuối cùng |
| `return_reason` | `VARCHAR(255)` | NULL | Lý do khách yêu cầu đổi trả (nếu có) |
| `return_tracking_code` | `VARCHAR(100)` | NULL | Mã vận đơn khách gửi trả hàng để kích hoạt Instant Refund |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm đặt hàng |

**6\. Bảng `Order_Items` (Chi tiết từng dòng sản phẩm trong Đơn hàng)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `order_item_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã dòng chi tiết đơn hàng |
| `order_id` | `BIGINT UNSIGNED` | **FK1** (`Orders.order_id`) | Mã đơn hàng chứa sản phẩm |
| `product_id` | `BIGINT UNSIGNED` | **FK2** (`Products.product_id`) | Mã sản phẩm được mua |
| `quantity` | `INT UNSIGNED` | NOT NULL | Số lượng đặt mua |
| `unit_price` | `DECIMAL(12,2)` | NOT NULL | Đơn giá tại thời điểm đặt hàng |
| `line_total` | `DECIMAL(12,2)` | NOT NULL | Thành tiền của dòng sản phẩm |

**7\. Bảng `Payment_Transactions` (Giao dịch Thanh toán COD / VietQR / VNPay)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `transaction_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã giao dịch nội bộ |
| `order_id` | `BIGINT UNSIGNED` | **FK** (`Orders.order_id`) | Mã đơn hàng liên kết |
| `payment_method` | `ENUM('cod','vietqr','vnpay')` | NOT NULL | Phương thức thanh toán khách chọn |
| `gateway_txn_ref` | `VARCHAR(100)` | NULL | Mã giao dịch tham chiếu trên cổng VNPay (`vnp_TransactionNo`) |
| `amount` | `DECIMAL(12,2)` | NOT NULL | Số tiền giao dịch |
| `signature_verified` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Cờ xác nhận chữ ký số `HMAC-SHA512` hợp lệ |
| `payment_status` | `ENUM('pending','paid','failed')` | NOT NULL | Trạng thái thanh toán |
| `paid_at` | `DATETIME` | NULL | Thời điểm xác nhận thanh toán thành công |

**8\. Bảng `Store_Credits` (Mã Hoàn tiền nhanh Store Credit – Nhánh 1 Instant Refund)**

| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :---- | :---- | :---: | :---- |
| `credit_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh bản ghi Store Credit |
| `coupon_code` | `VARCHAR(64)` | UNIQUE, NOT NULL | Mã Store Credit phát hành (`KLR-CREDIT-{ORDER_ID}-{HASH}`) |
| `source_order_id` | `BIGINT UNSIGNED` | **FK** (`Orders.order_id`), UNIQUE | Đơn hàng gốc phát sinh hoàn tiền nhanh |
| `customer_email` | `VARCHAR(100)` | NOT NULL | Email khách hàng được phép sử dụng mã này |
| `credit_amount` | `DECIMAL(12,2)` | NOT NULL | Mệnh giá hoàn tiền (= 100% `total_amount` của đơn gốc) |
| `is_redeemed` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Trạng thái đã sử dụng (`0`: Chưa dùng, `1`: Đã dùng cho đơn mới) |
| `issued_at` | `DATETIME` | NOT NULL | Thời điểm phát hành ngay khi khách nhập mã vận đơn trả hàng |

---

#### 4.2.2. Tầng 2: Bảng Ánh xạ Vật lý sang WooCommerce HPOS & WordPress Schema

Trong môi trường thực thi MariaDB 10.11 của KLEER, các bảng luận lý 3NF ở trên được ánh xạ trực tiếp vào hệ thống bảng phẳng **WooCommerce HPOS** (`HposService.php`) và hệ thống bảng Taxonomy chuẩn như sau:

| Bảng Luận lý 3NF | Bảng Vật lý trong MariaDB (WooCommerce HPOS / WP) | Cơ chế lưu trữ & Lợi ích hiệu năng |
| :---- | :---- | :---- |
| `Users` | `wp_users` & `wp_usermeta` | Lưu thông tin định danh tài khoản, mật khẩu băm và phân quyền người dùng. |
| `Products` | `wp_posts` (`post_type='product'`) & `wp_wc_product_meta_lookup` | Sử dụng bảng phẳng `wp_wc_product_meta_lookup` để đánh chỉ mục (Index) trực tiếp cho `sku`, `min_price`, `max_price`, `stock_status`, `stock_quantity` giúp lọc nhanh mà không cần join nhiều lần vào `wp_postmeta`. |
| `Taxonomy_Terms` & `Product_Taxonomies` | `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships` | Hệ thống 3 bảng quan hệ chuẩn hóa của WordPress lưu toàn bộ 4 trục thuộc tính (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`), có đánh Index trên `(object_id, term_taxonomy_id)`. |
| `Orders` & `Payment_Transactions` | **`wp_wc_orders`**, **`wp_wc_order_addresses`**, **`wp_wc_order_operational_data`**, **`wp_wc_orders_meta`** | **Kiến trúc HPOS chuyên biệt:** Tách hoàn toàn đơn hàng ra khỏi `wp_posts`. Bảng `wp_wc_orders` lưu phẳng `id`, `status`, `total_amount`, `customer_id`, `billing_email`, `payment_method`, `transaction_id`; `wp_wc_order_addresses` lưu tách biệt địa chỉ `billing`/`shipping`; `wp_wc_orders_meta` lưu `_kleer_return_reason` và `_kleer_return_tracking_code`. |
| `Order_Items` | `wp_woocommerce_order_items` & `wp_wc_order_product_lookup` | Lưu chi tiết từng dòng sản phẩm và khoản phí giảm giá `Giảm 15% Combo Routine` (`order_item_type = 'fee'`). |
| `Store_Credits` | `wp_posts` (`post_type='shop_coupon'`) & `wp_postmeta` | Lưu mã Store Credit với `discount_type = 'fixed_cart'`, `coupon_amount`, `customer_email` và `usage_limit = 1`, tương thích 100% với bộ tính tiền tại trang Checkout của WooCommerce. |

---

### 4.3. Thiết kế giao diện, trải nghiệm người dùng (UI/UX Design)

*(Phụ trách tổng hợp: Lê Nguyễn Quốc An – Phối hợp thiết kế Figma cùng Lâm Trúc Uyên & Huỳnh Ngọc Nhã Uyên)*

#### 4.3.1. Hệ thống Quy chuẩn Thiết kế (Design System & Tokens)

* **Phong cách chủ đạo:** *Minimalist Clinical & Earthy Tone* — truyền tải thông điệp khoa học, minh bạch thành phần và thân thiện với mọi đối tượng người dùng.  
* **Bảng màu (Color Palette):**  
  * Màu nền chính (*Background Primary*): Trắng kem ấm (`#FAF8F5`) giảm mỏi mắt khi đọc bảng thành phần INCI dài.  
  * Màu nhấn thương hiệu (*Brand Primary*): Xanh rêu đậm thảo mộc (`#2C4A3E`) tạo cảm giác an toàn, chuẩn da liễu.  
  * Màu cảnh báo xung đột hoạt chất (*Warning Banner*): Vàng hổ phách (`#FFF3CD`, viền `#D97706`) thu hút sự chú ý nhưng không gây hoang mang như màu đỏ báo lỗi.  
* **Typography & Bố cục:** Sử dụng font chữ không chân hiện đại (*Inter / Plus Jakarta Sans*), thiết kế **Mobile-First Responsive** trên 2 khung chuẩn: **Desktop (1440px)** và **Mobile (375px)**.

#### 4.3.2. Danh mục 6 Màn hình Giao diện Cốt lõi cần chèn vào Google Docs

1. **Màn hình 1 – Trang chủ (Home Page):** Hero Banner dẫn thẳng vào bài *Skin Quiz*, khu vực giới thiệu quy trình 3 bước chọn Routine và danh sách sản phẩm nổi bật theo từng bước dưỡng.  
2. **Màn hình 2 – Giao diện SPA Skin Quiz & Kết quả Routine:** Thanh tiến trình động (Progress Bar 1/6 → 6/6), thẻ câu hỏi trực quan, và màn hình kết quả hiển thị hồ sơ da kèm nút **"Thêm trọn bộ vào giỏ hàng (Giảm 15%)"**.  
3. **Màn hình 3 – Trang Danh mục (Catalog) & Bộ lọc AJAX:** Lưới sản phẩm tỷ lệ ảnh vuông 1:1 kèm Sidebar bộ lọc đa tiêu chí (*Loại da, Vấn đề da, Bước Routine, Hoạt chất INCI, Giá*).  
4. **Màn hình 4 – Trang Chi tiết Sản phẩm (PDP – Tham chiếu Hasaki & INCIDecoder):** Hiển thị rõ Badge phân loại da, vị trí bước trong Routine, và hộp thông tin **Bảng thành phần INCI chi tiết**.  
5. **Màn hình 5 – Trang Giỏ hàng Thông minh (Cart Page):** Hiển thị dòng chiết khấu tự động **`Giảm 15% Combo Routine`** và **Thẻ cảnh báo xung đột hoạt chất (AHA/BHA \+ Retinol)**.  
6. **Màn hình 6 – Trang Thanh toán (Checkout), Mã VietQR động & Giao diện Đổi trả Instant Refund:** Form đặt hàng tinh gọn, màn hình quét mã QR Napas động / cổng VNPay Sandbox, và khu vực **Yêu cầu trả hàng / Nhập mã vận đơn nhận ngay mã Store Credit**.