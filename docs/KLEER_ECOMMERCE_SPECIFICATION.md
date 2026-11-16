# TÀI LIỆU NGỮ CẢNH HỆ THỐNG & ĐẶC TẢ TÍNH NĂNG DỰ ÁN TMĐT KLEER

## 1. TỔNG QUAN DỰ ÁN & BỐI CẢNH KỸ THUẬT

### 1.1. Thông tin chung
* **Tên thương hiệu & Đề tài:** **KLEER** — Hệ thống TMĐT B2C kinh doanh mỹ phẩm chăm sóc da Unisex (Unisex Skincare), tích hợp Quiz chẩn đoán da cá nhân hóa phác đồ điều trị và cơ chế an toàn hoạt chất.
* **Môn học:** Thiết kế Hệ thống Thương mại Điện tử (EC312) — Giảng viên hướng dẫn: ThS. Trịnh Trọng Tín.
* **Đối tượng người dùng mục tiêu:** Thế hệ Gen Z và người bận rộn yêu thích phong cách sống tối giản, khoa học, minh bạch về thành phần.
* **Quy mô dữ liệu:** $\ge 500\text{ SKU}$ mỹ phẩm thật, chuẩn hóa bảng thuộc tính và phân loại da liễu.

### 1.2. Kiến trúc Hạ tầng & Tech Stack (3-Tier Architecture)
* **Hạ tầng & Container:** Chạy đồng nhất trên **Docker Compose** với 5 services độc lập:
  * `nginx`: Reverse Proxy tiếp nhận HTTPS/SSL, phân phối static assets và đệm cache.
  * `php` (PHP 8.2-FPM): Xử lý nhân logic WordPress 6.4 + WooCommerce.
  * `mariadb` (MariaDB 10.11): Cơ sở dữ liệu quan hệ, cấu hình `innodb_buffer_pool_size = 512M`.
  * `redis`: In-memory cache phục vụ **Redis Object Cache** nhằm tối ưu I/O đĩa cứng.
  * `phpmyadmin`: Quản trị cơ sở dữ liệu môi trường phát triển nội bộ.
* **Tầng Theme (`wp-content/themes/kleer-theme/`):**
  * Thiết kế theo phong cách *Minimalist Earthy Tone*, Mobile-First Responsive (Desktop 1440px / Mobile 375px).
  * **Quy tắc bất biến:** Tuyệt đối **KHÔNG dùng Page Builder** (Elementor, WPBakery). 100% sử dụng PHP template thuần, SCSS/CSS theo chuẩn BEM và JavaScript (Vanilla JS ES6+).
* **Tầng Plugin (`wp-content/plugins/kleer-plugin/`):**
  * Kiến trúc OOP phân tầng độc lập theo PSR-4 và PSR-12: `Kleer\` namespace.
  * Phân tách module: `Endpoints/`, `Controllers/`, `Services/`, `Repositories/`, `Config/`, `Taxonomies/`.
  * REST API Namespace: `kleer/v1`.
  * **Quy tắc kỷ luật:** Không sửa mã nguồn lõi WordPress Core hoặc WooCommerce Core. Không commit file nhạy cảm (`.env`, secrets, API keys).
### 1.3. Cơ sở Tham chiếu Hệ thống (System & Industry References)
Nhằm đảm bảo tính thực tiễn và tính học thuật (tránh việc giả định chủ quan), toàn bộ giải pháp của KLEER được tham chiếu và đối sánh từ các hệ thống uy tín:
* **Mô hình Bán lẻ & Chuẩn hóa Catalog:** Tham chiếu hệ thống bán lẻ mỹ phẩm **Hasaki.vn** (chuẩn hóa danh mục, thông tin sản phẩm, chính sách phân phối tại Việt Nam).
* **Quy trình Chẩn đoán & Tư vấn Routine:** Tham chiếu mô hình Personalized Skincare của **Curology (USA)** (cấu trúc bộ câu hỏi chẩn đoán da, ma trận phân loại và gợi ý phác đồ điều trị được Việt hóa).
* **Cơ sở dữ liệu Hoạt chất & Cảnh báo An toàn:** Tham chiếu tiêu chuẩn quốc tế từ **INCIDecoder** và **Paula's Choice Ingredient Dictionary** (danh mục hoạt chất đối kháng, nồng độ an toàn cho da nhạy cảm).
* **Xu hướng Thị trường Unisex:** Tham chiếu báo cáo nghiên cứu hành vi người tiêu dùng Gen Z & Nam giới trong chăm sóc cá nhân (Kantar Worldpanel & Mintel SEA).
---

## 2. NỖI ĐAU THỊ TRƯỜNG & CHIẾN LƯỢC MÔ HÌNH KINH DOANH

### 2.1. Nỗi đau của khách hàng (Customer Pain Points)
1. **Rào cản nhận thức về thành phần (Ingredient Knowledge Barrier):** Mỹ phẩm hiện đại tràn ngập các hoạt chất hóa học phức tạp (AHA, BHA, Retinol, Niacinamide, Tretinoin...). Khách hàng phổ thông và nam giới rất khó tự xác định mình cần chất gì.
2. **Bộ lọc TMĐT truyền thống bị dàn trải (Catalog-First):** Các sàn chỉ cho lọc giá, danh mục, thương hiệu; không thể trả lời câu hỏi: *"Tôi da dầu mụn ẩn và nhạy cảm thì cần kết hợp những sản phẩm nào với nhau?"*.
3. **Hiểm họa kích ứng do xung đột hoạt chất:** Khách hàng tự ghép sản phẩm rời rạc dễ thoa chồng các chất đối kháng (như AHA/BHA nồng độ cao cùng Retinol), gây bỏng rát, kích ứng và mất niềm tin.
4. **Hiện tượng Showrooming:** Khách hàng làm quiz hoặc tra cứu ở một nền tảng rồi sang sàn TMĐT khác mua lẻ từng món giá rẻ.

### 2.2. Đòn bẩy giữ chân & thúc đẩy chuyển đổi của KLEER
* **Guided Selling:** Thay vì bán từng món đồ riêng rẽ, KLEER bán một **"Giải pháp Routine hoàn chỉnh"** được may đo chính xác qua bài Quiz chẩn đoán.
* **Dynamic Routine Bundling:** Giảm giá tự động **5%** trên tổng đơn và miễn phí vận chuyển khi mua trọn Routine do Quiz đề xuất (2–6 sản phẩm), khiến tổng chi phí combo tại KLEER rẻ hơn việc mua lẻ chịu nhiều lần ship trên sàn.
* **1-Click Add Routine to Cart:** Triệt tiêu ma sát hành vi (friction), gom toàn bộ các món tương thích (2–6 sản phẩm) vào giỏ chỉ bằng 1 thao tác click duy nhất.
* **Skin Compatibility & Safety Guarantee:** Cam kết an toàn thành phần với hệ thống cảnh báo xung đột hoạt chất tự động và chính sách đổi trả/bảo hiểm kích ứng da liễu.

---

## 3. "XƯƠNG SỐNG" DỮ LIỆU: 3 THUỘC TÍNH SẢN PHẨM WOOCOMMERCE & CHUẨN HÓA CƠ SỞ DỮ LIỆU

Mọi thuật toán của KLEER đều dựa trên ba thuộc tính sản phẩm toàn cục của WooCommerce: `pa_skin_type`, `pa_skin_concern`, `pa_routine_step`. `TaxonomyRegistry.php` khởi tạo/đồng bộ các thuộc tính này qua WooCommerce API. Không đăng ký bộ custom taxonomy riêng trùng tên hoặc tạo song song các taxonomy không có tiền tố `pa_`:

```
┌────────────────────────────────────────────────────────────────────────┐
│                 3 WOOCOMMERCE PRODUCT TAXONOMIES                       │
├────────────────────┬────────────────────┬──────────────────────────────┤
│ 1. pa_skin_type    │ 2. pa_skin_concern │ 3. pa_routine_step           │
│    (Loại da)       │    (Vấn đề da)     │    (Bước trong chu trình)    │
├────────────────────┼────────────────────┼──────────────────────────────┤
│ • sensitive (NC)   │ • acne (Mụn)       │ • makeup_remover (Tẩy trang) │
│ • dry (Khô)        │ • dark_spots       │ • cleanser (Sữa rửa mặt)     │
│ • combination (HH) │   (Thâm/nám)       │ • toner (Toner)              │
│ • oily (Dầu)       │ • aging (Lão hóa)  │ • serum (Serum)              │
│ • normal (Thường)  │ • pores (Lỗ chân   │ • moisturizer (Dưỡng ẩm)     │
│                    │   lông to)         │ • sunscreen (Chống nắng)     │
└────────────────────┴────────────────────┴──────────────────────────────┘
```

* **Quy tắc toàn vẹn:**
  * Tên taxonomy chuẩn trong WooCommerce/DB là `pa_skin_type`, `pa_skin_concern`, và `pa_routine_step`. Các tên `skin_type`, `skin_concern`, `routine_step` là khóa logic/API; nếu một luồng dùng các khóa này thì phải ánh xạ sang taxonomy `pa_*` tương ứng. Không đăng ký hoặc tạo thêm bộ taxonomy không có tiền tố `pa_` song song.
  * `pa_skin_type` có đúng 5 term (kể cả `normal`). Sản phẩm "Mọi loại da" được gắn đủ 5 term. Thứ tự trên cũng là thứ tự ưu tiên khi hòa điểm Quiz.
  * `pa_skin_concern` có đúng 4 term. Giá trị `none` ("Không có vấn đề nổi bật") chỉ là lựa chọn trong Quiz, **không phải term** và không gắn lên sản phẩm; sản phẩm không có vấn đề đặc trị thì để trống `pa_skin_concern`.
  * `pa_routine_step` có đúng 6 term như bảng trên. Slug `treatment` không còn dùng (đổi thành `serum`).
  * Mỗi sản phẩm trong kho dữ liệu $\ge 500\text{ SKU}$ **bắt buộc chỉ thuộc DUY NHẤT một `pa_routine_step`**.
  * Hoạt chất chủ đạo lưu ở **một nơi duy nhất**: thuộc tính/taxonomy `pa_active_ingredient` (ví dụ: `retinol`, `aha`, `bha`, `pha`, `vitamin_c`, `niacinamide`...). Đây là nguồn cho bộ lọc L02 và cho quét xung đột ở giỏ hàng L03. Crawler L08 ghi vào cột `Attribute: pa_active_ingredient`.
  * Cờ dẫn xuất `_kleer_strong_active = 1/0`: bằng 1 nếu sản phẩm chứa một trong danh sách hoạt chất mạnh (BHA/Salicylic Acid, AHA, PHA, Retinol, Vitamin C ≥ 5%; xem 4B). Dùng cho cờ nhạy cảm của Quiz. Nếu trang nguồn không ghi nồng độ thì phân loại theo sự có mặt của hoạt chất và để người duyệt dữ liệu rà lại.
  * Sản phẩm lành tính dành cho mọi loại da và không có hoạt chất mạnh được đánh dấu trường postmeta: `_kleer_gentle_fallback = 1` (còn lại là 0).

---

## 4. CHI TIẾT CÁC TÍNH NĂNG CỐT LÕI (CORE FEATURE SPECIFICATIONS)

---

### TÍNH NĂNG 1: SMART DIAGNOSTIC QUIZ & CÁ NHÂN HÓA ROUTINE (LUỒNG L01)

#### A. Trải nghiệm người dùng (Frontend SPA UX)
* **Giao diện:** Single-Page Application (SPA) xây dựng bằng HTML5 semantic và Vanilla JS ES6 (`kleer-theme`), tải mượt mà không bao giờ reload trang.
* **Micro-interactions:** Hiệu ứng chuyển slide giữa các câu hỏi dưới $100\text{ ms}$, có thanh tiến trình động (Progress Bar), hỗ trợ quay lại câu trước và tự động lưu trạng thái tạm thời trên client.
* **Màn hình kết quả:** Phân tích hồ sơ da, hiển thị Routine gồm 2 đến 6 sản phẩm tương thích (theo thứ tự Sáng / Tối), kèm giá gốc, giá combo giảm 10% (xem mục 2.2) và nút bấm **"Thêm trọn bộ vào giỏ hàng"**.

#### B. Thuật toán Tính điểm Trọng số (Weighted Tag Matching - Logic v4)
* **Cơ sở tham chiếu nghiệp vụ:** Bộ câu hỏi và ma trận tính điểm được nghiên cứu, kế thừa và Việt hóa từ quy trình tư vấn da cá nhân hóa của **Curology (curology.com)** — nền tảng D2C skincare hàng đầu thế giới:
  * Lược bỏ các bước kê đơn thuốc cần bác sĩ da liễu Hoa Kỳ, chuyển hóa thành bài trắc nghiệm mỹ phẩm không kê đơn (OTC) phù hợp với cơ địa người Việt Nam.
  * Tái cấu trúc thành 6 câu hỏi đa chiều (v4) đánh giá: Lượng bã nhờn, phản ứng kích ứng, vấn đề bệnh lý da, độ dài routine mong muốn và thói quen chống nắng/trang điểm.
* **API Endpoint:** `POST /wp-json/kleer/v1/skin-quiz` (Namespace: `kleer/v1`, xử lý qua `SkinQuizController`). Thời gian phản hồi: $< 300\text{ ms}$.
* **Quy tắc chấm điểm (Quiz Decision Matrix):**
  1. **Q1–Q3 (Xác định Loại da):** Các câu hỏi về lượng dầu vùng chữ T, độ căng rát sau rửa mặt. Mỗi lựa chọn cộng điểm vào các vector: `sensitive`, `dry`, `combination`, `oily`, `normal`.
     * *Quy tắc hòa điểm:* `sensitive` > `dry` > `combination` > `oily` > `normal`.
     * *Cờ nhạy cảm:* Bật (`is_sensitive = true`) khi tổng điểm `sensitive >= 4`.
  2. **Q4 (Xác định Vấn đề da):** Chọn 1 vấn đề chủ đạo (`acne`, `dark_spots`, `aging`, `pores`, `none`).
  3. **Q5 (Độ dài Routine mong muốn):** Chọn số sản phẩm $N$ từ $2$ đến $6$ (mỗi sản phẩm một bước). Số sản phẩm thực tế có thể ít hơn $N$ nếu bước bị bỏ (Q6) hoặc không có SKU phù hợp.
  4. **Q6 (Thói quen chống nắng/trang điểm):** Nếu khách không trang điểm và không dùng kem chống nắng (lựa chọn 6C), hệ thống bỏ cả `sunscreen` lẫn `makeup_remover` (vì Tẩy trang luôn đi kèm Chống nắng), **ngoại trừ** khi vấn đề chính là `dark_spots` hoặc `aging`. Khách 6C không thuộc ngoại lệ chỉ còn tối đa 4 sản phẩm (`cleanser`, `toner`, `serum`, `moisturizer`).
  5. **Hạng ưu tiên giữ bước (Step Hierarchy):**
     * `cleanser` (1) $\rightarrow$ `sunscreen` (2) $\rightarrow$ `makeup_remover` (2.1) $\rightarrow$ Bước vấn đề đẩy (2.5) $\rightarrow$ `serum` (3) $\rightarrow$ `toner` (4) $\rightarrow$ `moisturizer` (5). Routine lấy $N$ bước có hạng nhỏ nhất; bước nào không có SKU thì bỏ, không thay bằng bước khác.
     * *Logic đẩy bước theo vấn đề:* `acne` ưu tiên đẩy `toner` (nhưng nếu da nhạy cảm thì ưu tiên đẩy `serum`); `pores` ưu tiên `toner`; `dark_spots`/`aging` ưu tiên `serum`.
  6. **Thuật toán chọn SKU cho từng bước (2 giai đoạn):**
     * *Giai đoạn 1 — Lọc cứng:* chỉ giữ SKU thỏa **tất cả**: (a) đúng `pa_routine_step` của bước; (b) có term `pa_skin_type` trùng loại da chính (SKU "Mọi loại da" có đủ 5 term nên luôn qua); (c) còn hàng; (d) nếu `is_sensitive = true` thì `_kleer_strong_active = 0`.
     * *Giai đoạn 2 — Xếp hạng:*
     $$\text{Product Score} = 2 \times (\text{Khớp } skin\_concern) + 1 \times (\text{Dành riêng cho } skin\_type)$$
     "Khớp `skin_concern`" = SKU có term `pa_skin_concern` trùng vấn đề chính của khách (bằng 0 nếu khách chọn `none`). "Dành riêng cho `skin_type`" = SKU **không** phải "Mọi loại da" (không gắn đủ 5 term `pa_skin_type`); chỉ cộng +1 một lần, dù SKU gắn riêng cho 2 loại da trở lên.
     * *Quy tắc hòa điểm sản phẩm:* Chọn sản phẩm có giá bán thấp hơn (`_price` thấp hơn). Nếu tiếp tục bằng nhau, chọn `product_id` thấp hơn.
     * *Danh sách hoạt chất mạnh bị loại khi `is_sensitive = true`:* BHA/Salicylic Acid, AHA, PHA, Retinol, Vitamin C ≥ 5%. Khác với cặp xung đột ở giỏ hàng (xem Tính năng 3), danh sách này dùng riêng cho Quiz.

#### C. Cơ chế Dự phòng An toàn (Safe Fallback Mechanism)
* Nếu một bước routine không tìm thấy sản phẩm khớp hoàn hảo hoặc sản phẩm hết hàng trong kho:
  1. Tự động truy vấn sản phẩm mang cờ `_kleer_gentle_fallback = 1` (sản phẩm an toàn cho mọi loại da) để bù vào bước đó.
  2. Bắn hook nội bộ `kleer_quiz_fallback_used` (không chứa dữ liệu cá nhân).
  3. Nếu toàn bộ kho không có bất kỳ sản phẩm nào thỏa mãn, ném `RoutineUnavailableException`, API trả về mã HTTP 503 chuẩn kèm thông báo thân thiện. **Cam kết tuyệt đối: Không trả về routine rỗng, không sinh lỗi trang trắng (White Screen of Death).**

---

### TÍNH NĂNG 2: BỘ LỌC AJAX ĐA TIÊU CHÍ & CATALOG MỸ PHẨM CHUẨN UX (LUỒNG L02)

#### A. Bộ lọc Ajax Đa tiêu chí Tốc độ cao ($< 200\text{ ms}$)
* Cho phép khách hàng tìm kiếm và sàng lọc tức thì trên kho $\ge 500\text{ SKU}$ kết hợp đồng thời nhiều tiêu chí:
  * **Loại da (`skin_type`):** Dầu, Khô, Hỗn hợp, Nhạy cảm, Thường.
  * **Vấn đề da (`skin_concern`):** Mụn, Thâm, Lão hóa, Lỗ chân lông.
  * **Hoạt chất chủ đạo (`pa_active_ingredient`):** BHA, AHA, PHA, Retinol, Niacinamide, Vitamin C, Ceramide...
  * **Khoảng giá:** Slider mức giá theo VNĐ.
  * **Thương hiệu (`Brand`):** Các hãng mỹ phẩm thật thu thập được.
* **Cơ chế kỹ thuật:** Sử dụng `wp_ajax_` kết hợp `tax_query` chuẩn hóa đa bảng quan hệ (`wp_terms`, `wp_term_relationships`). Đồng bộ trạng thái bộ lọc vào URL bằng **HTML5 History API** (`pushState`) giúp khách hàng có thể copy và chia sẻ trực tiếp link kết quả đã lọc mà không tải lại trang.

#### B. Trang Chi tiết Sản phẩm (PDP - Single Product Page)
* Kế thừa phong cách bố cục thông tin minh bạch tham khảo chuẩn từ Hasaki.vn:
  * Gallery ảnh trượt chuẩn tỷ lệ 1:1 định dạng WebP.
  * Nhãn Taxonomy nổi bật (Badge loại da, bước routine).
  * Khối phân tích bảng thành phần chi tiết (Full INCI list).
  * Accordion đóng/mở hướng dẫn sử dụng và vị trí trong routine chăm sóc da.

---

### TÍNH NĂNG 3: GIỎ HÀNG THÔNG MINH, 1-CLICK ADD & CẢNH BÁO XUNG ĐỘT HOẠT CHẤT (LUỒNG L03)

#### A. Cảnh báo Xung đột Hoạt chất (Ingredient Conflict Warning – Điểm sáng tạo)
* **Cơ sở khoa học & Dữ liệu tham chiếu:**
  * Bảng danh mục hoạt chất và cơ chế tương tác hoá mỹ phẩm được xây dựng dựa trên nguồn dữ liệu mở của **INCIDecoder (incidecoder.com)** kết hợp từ điển thành phần của **Paula's Choice**.
  * Quy tắc xung đột được chuẩn hóa để loại trừ rủi ro bỏng rát khi kết hợp sai hoạt chất mạnh (như BHA/AHA kết hợp Retinol/Tretinoin nồng độ cao trong cùng một buổi).
* **Ý nghĩa:** Giải quyết rủi ro bỏng rát/kích ứng cho khách hàng, đóng vai trò như một "chuyên gia da liễu ảo" giám sát đơn hàng.
* **Cơ chế hoạt động:**
  1. **Trigger:** Kích hoạt tự động thông qua hook `woocommerce_cart_calculate_fees` và `woocommerce_check_cart_items` mỗi khi giỏ hàng có sự thay đổi.
  2. **Quét mảng hoạt chất:** Duyệt qua toàn bộ `product_id` trong giỏ (`WC()->cart->get_cart()`), đọc thuộc tính `pa_active_ingredient` của từng sản phẩm.
  3. **Đối chiếu quy tắc (Conflict Rules):** Kiểm tra sự hiện diện đồng thời của các cặp chất kỵ nhau. Cụ thể:
     $$\mathbf{Pair\ 1:} \quad \{\text{AHA / BHA}\} \quad \cap \quad \{\text{Retinol}\}$$
  4. **Hiển thị cảnh báo (Warning Banner):** Nếu phát hiện xung đột, hệ thống trả về thông báo trong JSON fragment của Mini-Cart và trang Checkout. Banner màu hổ phách/cam cảnh báo nguy cơ kích ứng khi dùng chung trong một buổi tối và khuyên khách hàng nên sử dụng xen kẽ cách ngày.
  5. **Tính chất UX:** Cảnh báo mang tính chất thông thái (Advisory Warning), **không khóa cứng** nút bấm thanh toán nếu khách hàng có chủ đích mua cho người khác hoặc dùng lệch buổi.

#### B. 1-Click Add Routine to Cart & Dynamic Bundle Discount
* **1-Click Add:** Module AJAX đẩy đồng loạt mảng 2–6 `product_id` từ kết quả Quiz vào giỏ hàng trong 1 request HTTP duy nhất và trượt mở Mini-Cart từ cạnh phải.
* **Chiết khấu Routine Bundle:** Can thiệp qua hook giỏ hàng, tự động nhận diện nếu giỏ hàng hội tụ đủ các bước của Routine do Quiz đề xuất $\rightarrow$ Tự động trừ trực tiếp khoản chiết khấu 10% (xem mục 2.2) vào tổng tiền đơn hàng, hiển thị rõ ràng số tiền khách hàng vừa tiết kiệm được mà không cần nhập coupon thủ công.

---

### TÍNH NĂNG 4: CỔNG THANH TOÁN ĐA KÊNH & EMAIL HÓA ĐƠN THÔNG MINH (LUỒNG L04 & L05)

#### A. Tích hợp Cổng thanh toán (Rubric 5.2)
* **VNPay Sandbox:** Tự viết class kế thừa `WC_Payment_Gateway`. Tạo URL thanh toán có tính toán chữ ký số bảo mật chuẩn `HMAC SHA512`, xử lý luồng `Return URL` và `IPN Callback` tự động cập nhật đơn hàng sang trạng thái "Đang xử lý" (Processing).
* **VietQR động:** Tự động sinh mã QR chuyển khoản ngân hàng chuẩn Napas tại trang Cảm ơn (Thank You page), nhúng chính xác số tiền đơn hàng và mã đơn vào nội dung chuyển khoản.
* **COD:** Phương thức thanh toán khi nhận hàng truyền thống.

#### B. Xử lý Đơn hàng HPOS & Email Hóa đơn Thông minh
* **WooCommerce HPOS (Rubric 5.3):** Kích hoạt lưu trữ đơn hàng trên bảng phẳng chuyên biệt (`wp_wc_orders`, `wp_wc_order_operational_data`), cách ly hoàn toàn dữ liệu đơn hàng khỏi bảng `wp_posts`, chống nghẽn DB.
* **Email Hóa đơn HTML:** Gửi qua SMTP ngay khi tạo đơn. Mẫu email được tùy biến chuẩn responsive, nhúng bảng tóm tắt phác đồ Routine (hướng dẫn thứ tự thoa Sáng/Tối) và mã QR tra cứu tình trạng vận đơn.

---

### TÍNH NĂNG 5: PIPELINE CÀO DỮ LIỆU & NÉN MEDIA $\ge 500\text{ SKU}$ (LUỒNG L08)

* **Bộ Web Scraper tự động (Python BeautifulSoup / Playwright - Rubric 5.4):**
  * Thu thập dữ liệu thực tế từ các website uy tín: Hasaki.vn, Cocoon Vietnam, Paula's Choice.
  * Bóc tách các trường: SKU, Tên sản phẩm, Giá bán thực tế (`Regular price`), Thương hiệu thật, Bảng thành phần INCI, Hướng dẫn sử dụng, URL ảnh gốc.
* **Tiêu chuẩn Nén Media Tự động:**
  * Script xử lý ảnh tự động cắt ảnh vuông tỷ lệ **1:1**.
  * Chuyển đổi định dạng sang **`.webp`**.
  * Nén tối ưu dung lượng **$\le 120\text{ KB}$/ảnh** trước khi import vào hệ thống.
* **Chuẩn hóa CSV & Gán nhãn Taxonomy:**
    * Sinh mã SKU chuẩn không trùng lặp, ổn định khi cào lại: `KLR-{hash8}`, trong đó `hash8` là 8 ký tự hex đầu của SHA-1 chuỗi chuẩn hóa `thương_hiệu|tên_sản_phẩm|dung_tích` (chữ thường, bỏ dấu tiếng Việt, bỏ ký tự đặc biệt, dung tích viết dạng `50ml`, `30g`). Cùng sản phẩm khác dung tích thì khác SKU. Nếu hai sản phẩm khác nhau trùng mã thì thêm hậu tố `-2`, `-3`. Chỉ cào từ Hasaki.vn.
  * Gán taxonomy `pa_skin_type` và `pa_routine_step` (đúng 1 giá trị cho mỗi taxonomy) cho 100% bản ghi. `pa_skin_concern` có thể để trống nếu sản phẩm không có vấn đề đặc trị. Giá trị phải thuộc đúng danh sách term ở mục 3. Tên cột CSV/import có thể dùng khóa logic không có `pa_` nếu importer ánh xạ rõ ràng sang taxonomy chuẩn WooCommerce.
  * Gán `_kleer_gentle_fallback = 1` cho sản phẩm lành tính dành cho mọi loại da và không có hoạt chất mạnh; mỗi `routine_step` nên có ít nhất 1 sản phẩm như vậy để Safe Fallback luôn có đường dự phòng. Gán `pa_active_ingredient` và `_kleer_strong_active` theo mục 3.

---

### TÍNH NĂNG 6: TỐI ƯU HẠ TẦNG HIỆU NĂNG & TỰ ĐỘNG HÓA KÉP (LUỒNG L09 & L10)

#### A. Tối ưu Hiệu năng Cơ sở dữ liệu & Caching (Rubric 5.1 & 5.3)
* **Redis Object Cache:** Đệm toàn bộ transient và kết quả query SQL của taxonomy terms trên RAM.
  * Giảm số lượng truy vấn SQL từ 15–18 queries xuống còn 2–4 queries cho mỗi request Quiz/Catalog.
  * Tỷ lệ Cache Hit Ratio đạt $\ge 80\%$.
  * Tốc độ tải trang trên thiết bị di động đạt mục tiêu $< 2\text{ giây}$.

#### B. Hệ thống Tự động hóa Kép (Dual-Automation - Rubric 5.5 & 5.6)
Để đáp ứng tuyệt đối tiêu chuẩn Rubric "hai cách làm độc lập, không trùng lặp", hệ thống triển khai 2 kịch bản tự động hóa cho cùng 3 nghiệp vụ quản trị:
1. *Nghiệp vụ 1:* Duyệt đơn hàng mới sang trạng thái "Đã hoàn thành" (Completed).
2. *Nghiệp vụ 2:* Tìm và chuyển một sản phẩm chỉ định vào thùng rác / xóa sản phẩm.
3. *Nghiệp vụ 3:* Cập nhật số lượng tồn kho sản phẩm thành giá trị mới.

```
                  ┌───────────────────────────────────────────────┐
                  │             3 NGHIỆP VỤ QUẢN TRỊ              │
                  │   1. Duyệt đơn  │  2. Xóa SP  │  3. Sửa kho   │
                  └───────┬───────────────────────────────┬───────┘
                          │                               │
             Phương thức 1 (Rubric 5.5)      Phương thức 2 (Rubric 5.6)
                          ▼                               ▼
               [Selenium Web Automation]       [Python REST API Script]
               • Điều khiển GUI trình duyệt   • Giao tiếp Headless qua HTTP
               • Mô phỏng thao tác click chuột • Endpoint /wp-json/wc/v3/
               • Phụ thuộc DOM, CSS Selector   • Tốc độ mili-giây, độc lập UI
```

---

## 5. BẢN ĐỒ PHÂN LUỒNG NGHIỆP VỤ (FLOW REGISTRY)

| Mã Luồng | Tên Luồng | Phụ Trách (Flow Owner) | Vai Trò Chính | Đầu Vào Nhận Từ | Bàn Giao Cho |
| :---: | :--- | :--- | :--- | :---: | :---: |
| **L01** | Quiz chẩn đoán da & Routine cá nhân hóa | Bùi Hiếu Nhân | Plugin Dev 1 | **L08** (Taxonomy, Data) | L02, L03 |
| **L02** | Khám phá & Catalog sản phẩm (Lọc Ajax, PDP) | Lâm Trúc Uyên | Theme Dev 1 | **L08** (Data 500 SKU) | L03 |
| **L03** | Giỏ hàng, 1-Click Cart & Cảnh báo xung đột | Huỳnh Ngọc Nhã Uyên | Theme Dev 2 | **L01, L02** | L04 |
| **L04** | Checkout & Thanh toán (COD, VietQR, VNPay) | Trần Ngọc Minh Tuyền | Integration Dev | **L03** | L05 |
| **L05** | Xử lý đơn hàng, HPOS & Email Hóa đơn | Nguyễn Minh Trí | Plugin Dev 2 | **L04** | L06, L07, L10 |
| **L06** | Vận chuyển & Giao hàng (Shipping Zones) | Trần Ngọc Minh Tuyền | Integration Dev | **L05** | L07 |
| **L07** | Đổi trả 7 ngày & Kích ứng mỹ phẩm | Nguyễn Tấn Phát | PO & BA | **L06** | L05 |
| **L08** | Dữ liệu $\ge 500\text{ SKU}$ (Crawl, Nén ảnh, CSV) | Nguyễn Minh Trí | Plugin Dev 2 | Nguồn bên ngoài | **L01, L02** |
| **L09** | Hạ tầng Docker, Cloud VPS, SSL & Redis | Lê Nguyễn Quốc An | Scrum Master / DevOps | Repo mã nguồn | Toàn hệ thống |
| **L10** | Tự động hóa (Selenium + REST API Script) | Nguyễn Ngọc Thảo Uyên | QA & Automation Lead | **L05, L08** | Báo cáo / Hội đồng |
| **L00** | Quản trị dự án, Tài liệu Báo cáo Word & Slide | Lê Nguyễn Quốc An | Scrum Master / DevOps | Toàn hệ thống | Giảng viên |

---

## 6. NGUYÊN TẮC PHỐI HỢP & QUY TẮC BẢO VỆ CHẤT LƯỢNG MÃ NGUỒN

1. **Quy tắc phân định thư mục độc lập (Zero-Coupling):**
   * Theme Dev chỉ sửa file trong `/wp-content/themes/kleer-theme/`.
   * Plugin Dev chỉ sửa file trong `/wp-content/plugins/kleer-plugin/`.
   * Các script tự động hóa nằm tại `/tools/` (`/tools/crawler/`, `/tools/quiz/`, `/tools/automation/`).
2. **Phê duyệt cổng nghiệp vụ (G-Gates):**
   * Phải hoàn thành G1 (Nghiên cứu/Flow Card) $\rightarrow$ G2 (Bản vẽ UML/BPMN 2.0) $\rightarrow$ G3 (PO/SM phê duyệt) mới được phép soạn Prompt AI để sinh mã nguồn tại G4 và merge code tại G5/G6.
