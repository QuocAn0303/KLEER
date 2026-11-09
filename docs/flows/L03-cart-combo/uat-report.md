# BIÊN BẢN NGHIỆM THU UAT LUỒNG L03 — GIỎ HÀNG, CHIẾT KHẤU 15% COMBO & CẢNH BÁO HOẠT CHẤT

- **Mã công việc:** `L03-G8-01` (Cổng G8 — Nghiệm thu UAT theo BPMN)
- **Luồng nghiệp vụ:** `L03` — Giỏ hàng & Combo Routine (`docs/flows/L03-cart-combo/`)
- **Người thực hiện:** Huỳnh Ngọc Nhã Uyên (Theme Dev 2 — Quiz Frontend, Cart/Checkout UX)
- **Người phê duyệt:** Nguyễn Tấn Phát (Product Owner) & Lê Nguyễn Quốc An (Scrum Master)
- **Ngày nghiệm thu:** 09/11/2026
- **Môi trường kiểm thử:** Docker Local (`http://localhost/`, Nginx 1.25, PHP 8.2.35, MariaDB 10.11, Redis 7, WooCommerce 11.2.2) — nhánh `develop` (commit `452bc8e`, bao gồm commit `efe7b51` triển khai `CartComboService.php`)
- **Dữ liệu kiểm thử:** 513 SKU thực tế đã nạp từ `data/woocommerce_500_skus.csv`
- **Tài liệu & đầu vào đối chiếu:**
  1. Sơ đồ BPMN 2.0 (v2): `docs/flows/L03-cart-combo/L03_Cart_Combo_Discount_BPMN.drawio` (*L03-G5-01 — BPMN 2.0 | 1-Click Routine → Giỏ hàng → Combo → Cảnh báo hoạt chất → Checkout*)
  2. Đặc tả Flow Card: `docs/flows/L03-cart-combo/flow-card.md`
  3. Mã nguồn đối chiếu:
     - Backend: `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php`
     - Frontend: `wp-content/themes/kleer-theme/assets/js/routine-cart.js`, `wp-content/themes/kleer-theme/assets/js/quiz.js`, `wp-content/themes/kleer-theme/inc/quiz-assets.php`

---

## 1. Xác nhận Đầu vào & Trạng thái Thực thi trên nhánh `develop`

1. **Đầu vào BPMN (`L03_Cart_Combo_Discount_BPMN.drawio`):** Đầy đủ chuẩn BPMN 2.0 (2 swimlane *KHÁCH HÀNG* và *HỆ THỐNG*, 11 tác vụ, 4 cổng XOR `g_ok`, `g_combo`, `g_conf`, `g_choice`, cùng 3 chú thích Business Rules về Combo 15%, Ma trận xung đột hoạt chất và Nguồn tham khảo).
2. **Trạng thái mã nguồn trên `develop`:**
   - Logic của luồng L03 đã được triển khai trên nhánh `develop` tại lớp dịch vụ `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php` (được khởi tạo trong `Plugin.php` dòng 152–153) và script `wp-content/themes/kleer-theme/assets/js/routine-cart.js` (nạp trong `functions.php` dòng 95–106).
   - Bộ kiểm thử UAT gồm 10 kịch bản dưới đây được thực thi trực tiếp trên trình duyệt Chrome đối chiếu từng nút quyết định trên sơ đồ BPMN L03.

---

## 2. Ma trận Truy vết các Nút BPMN 2.0 sang 10 Kịch bản UAT

| Mã nút BPMN | Tên bước trên sơ đồ BPMN L03 (v2) | Thành phần thực thi trong code | Kịch bản UAT kiểm chứng |
|---|---|---|---|
| `c_click` | Nhấn "Thêm trọn bộ Routine vào giỏ hàng" (1-click) | `quiz-assets.php` (`cartEnabled`), `quiz.js` (`kleer:addRoutineToCart`) → `routine-cart.js` (`addRoutineToCart`) | UAT-01 |
| `s_valid` + `g_ok` | Nhận AJAX: xác thực nonce CSRF, kiểm tra tồn kho & trạng thái từng SP → *Hợp lệ và đủ hàng?* | `CartComboService::handleBulkAddRoutineAjax()` (`wp_verify_nonce`, `is_purchasable`, `is_in_stock`) | UAT-01, UAT-02, UAT-03 |
| `s_err` → `end_err` | Trả lỗi + thông báo (hết hàng / nonce sai / timeout); **không thêm SP nào**, giỏ giữ nguyên | Nhánh `wp_send_json_error` (403/400) trong `handleBulkAddRoutineAjax()` | UAT-02, UAT-03 |
| `s_add` | Thêm đồng thời các SP vào WC Cart; gắn meta `routine_id`, `routine_step` | `$cart->add_to_cart($id, 1)` trong `handleBulkAddRoutineAjax()` | UAT-01 |
| `s_calc` + `g_combo` | Tính lại giỏ: đếm số bước Routine hợp lệ (cùng `routine_id`) → *Đủ 3–4 bước hợp lệ?* | `CartComboService::applyRoutineBundleDiscount()` (`woocommerce_cart_calculate_fees`) | UAT-04, UAT-05, UAT-06 |
| `s_disc` | Áp giảm Combo 15%; hiển thị số tiền tiết kiệm | `$cart->add_fee('Giảm 15% Combo Routine Unisex', -$discountAmount)` | UAT-04, UAT-05 |
| `s_nodisc` | Gỡ giảm giá Combo (nếu có); giữ giá gốc + thông báo gỡ ưu đãi | `removeExistingDiscount()` + tính lại phí mỗi lần giỏ thay đổi | UAT-07 |
| `s_scan` + `g_conf` | Quét `pa_active_ingredient` của toàn bộ SP trong giỏ → *Có cặp hoạt chất xung đột?* | `CartComboService::checkIngredientConflicts()` (`productHasAcid`, `productHasRetinoid`) | UAT-08, UAT-09 |
| `s_warn` | Hiển thị Alert Card (soft warning): AHA/BHA + Retinol… | `CartComboService::renderConflictNotice()` (`woocommerce_before_cart`, `woocommerce_before_checkout_form`) | UAT-08, UAT-09, UAT-10 |
| `s_render` → `c_view` | Cập nhật mini-cart fragments & hiển thị trang `/cart/` | `WC_AJAX::get_refreshed_fragments()` + `routine-cart.js` | UAT-01, UAT-04, UAT-05 |
| `g_choice` → `c_edit` / `end_ok` | Khách chọn: *Chỉnh sửa giỏ* (vòng lặp về `s_calc`) hoặc *Tiếp tục thanh toán* (sang L04 khi có cảnh báo) | Trang `/cart/` & `/checkout/` | UAT-07, UAT-10 |

---

## 3. Kết quả Thực thi 10 Kịch bản Nghiệm thu UAT trên Trình duyệt (DoD)

| Mã KB | Tên kịch bản UAT | Bước BPMN | Dữ liệu kiểm thử (ID / Tên SP / Giá) | Kỳ vọng theo BPMN L03 & Flow Card | Kết quả quan sát thực tế trên trình duyệt | Kết luận |
|---|---|---|---|---|---|---|
| **UAT-01** | 1-Click thêm trọn bộ Routine 4 bước từ màn hình kết quả Quiz | `c_click` → `s_valid` → `s_add` → `s_render` | Bộ 4 SP: ID `700` (*Gel Rửa Mặt Eucerin*, `318.000₫`) + `874` (*Serum GoodnDoc*, `451.000₫`) + `957` (*Kem Dưỡng Paula's Choice*, `755.000₫`) + `1041` (*KCN Cetaphil*, `273.000₫`) | 1. Nút *"Thêm trọn bộ Routine vào giỏ hàng"* trên trang `/quiz/` bấm được.<br>2. Thêm đồng thời đủ 4 SP vào giỏ, gắn meta `routine_id`/`routine_step`.<br>3. Tự động chuyển hướng sang `/cart/`. | - Trên UI `/quiz/`, nút bấm đang bị khóa (`disabled`) và hiện *"THÊM ROUTINE VÀO GIỎ HÀNG (SẮP RA MẮT)"* do cờ `kleer_quiz_cart_enabled` trong `quiz-assets.php` đang để `false`.<br>- Khi gọi hàm `kleerAddRoutineToCart([700, 874, 957, 1041])`, **cả 4 sản phẩm đều vào giỏ đầy đủ**, nhưng **trang không tự động chuyển sang `/cart/`** (phải tự truy cập qua link vì dòng redirect trong `routine-cart.js` dòng 102–103 đang bị comment) và cart item chưa gắn `routine_id`. | **PARTIAL** |
| **UAT-02** | Bảo mật CSRF: Gửi AJAX `kleer_add_routine_to_cart` với Nonce sai | `s_valid` → `g_ok (Không)` → `s_err` → `end_err` | `action = kleer_add_routine_to_cart`, `nonce = "sai_nonce_123"`, `product_ids = [700, 874, 957]` | Hệ thống từ chối request, trả mã lỗi HTTP `403`, không thêm sản phẩm nào vào giỏ | Trình duyệt nhận đúng **`HTTP Status: 403`** kèm JSON `{success: false, data: {message: "Nonce không hợp lệ."}}`, giỏ hàng giữ nguyên. | **PASS** |
| **UAT-03** | Nhánh lỗi sản phẩm không hợp lệ / hết hàng trong bộ Routine | `s_valid` → `g_ok (Không)` → `s_err` → `end_err` | Danh sách gồm 3 SP hợp lệ (`700, 874, 957`) + 1 ID không hợp lệ (`999999`) trên giỏ trống | BPMN quy định tại nút `s_err` → `end_err`: *"Trả lỗi + thông báo; **không thêm SP nào** → Kết thúc: chưa thêm vào giỏ, giỏ giữ nguyên"* | Hệ thống **vẫn tự động thêm 3 sản phẩm hợp lệ (`700, 874, 957`) vào giỏ** (tổng `1.524.000₫`) và trả `200 OK` thay vì hủy toàn bộ thao tác và báo lỗi. | **FAIL** |
| **UAT-04** | Tự động giảm 15% Combo khi giỏ đủ 3 bước (`Cleanser` + `Moisturizer` + `Sunscreen`) | `s_calc` → `g_combo (Có)` → `s_disc` | ID `700` (`318.000₫`) + `957` (`755.000₫`) + `1041` (`273.000₫`). Tạm tính = `1.346.000₫` | Tự động áp chiết khấu 15% (`-201.900₫`), tổng tiền còn `1.144.100₫` | Giỏ hàng tự động hiển thị đúng dòng **`Giảm 15% Combo Routine Unisex: -201.900 ₫`** và tổng thanh toán giảm còn **`1.144.100 ₫`** đúng như kỳ vọng. | **PASS** |
| **UAT-05** | Tự động giảm 15% Combo khi giỏ đủ 4 bước (`Cleanser` + `Serum` + `Moisturizer` + `Sunscreen`) & thông báo tiết kiệm | `s_calc` → `g_combo (Có)` → `s_disc` | ID `700` + `874` + `957` + `1041`. Tạm tính = `1.797.000₫` | 1. Tự động giảm 15% trên tổng 4 SP = **`-269.550₫`** (còn `1.527.450₫`).<br>2. Hiển thị trực quan dòng thông báo: *"Bạn đã tiết kiệm được 269.550đ khi mua trọn bộ Routine!"* | - Số tiền giảm tính **chính xác `-269.550 ₫`** trên tổng 4 sản phẩm.<br>- Tuy nhiên trên giao diện **không có câu thông báo** *"Bạn đã tiết kiệm được ... khi mua trọn bộ Routine!"* như đặc tả trong `flow-card.md` mục 2 (chỉ hiển thị dòng phí âm ở phần tổng tiền). | **PARTIAL** |
| **UAT-06** | Mua Combo 3 bước có bước Đặc trị (`Cleanser` + `Serum` + `Moisturizer`, không có KCN) | `s_calc` → `g_combo (Có)` → `s_disc` | Xóa KCN `1041`, giữ lại 3 bước: Sữa rửa mặt `700` (`318.000₫`) + Serum `874` (`451.000₫`) + Kem dưỡng `957` (`755.000₫`). Tạm tính = `1.524.000₫` | Đủ 3 bước cơ bản (Làm sạch + Đặc trị/Serum + Dưỡng ẩm) → vẫn phải giữ mức giảm 15% (`-228.600₫`, còn `1.295.400₫`) | **Bị mất dòng giảm giá 15%**, tổng tiền tính nguyên giá **`1.524.000 ₫`**.<br>**Nguyên nhân:** `CartComboService::ROUTINE_STEPS` chỉ khai báo `['cleanser', 'treatment', 'moisturizer', 'sunscreen']` mà thiếu `'serum'`, nên bước Serum (`874`) không được đếm vào số bước hợp lệ (chỉ đếm được 2 bước là `cleanser` và `moisturizer`). | **FAIL** |
| **UAT-07** | Cơ chế Rollback khi xóa sản phẩm xuống dưới 3 bước | `c_edit` → `s_calc` → `g_combo (Không)` → `s_nodisc` | Xóa tiếp để giỏ chỉ còn 2 sản phẩm (`< 3` bước) | 1. Hủy bỏ ngay chiết khấu 15%, tính lại về nguyên giá.<br>2. Hiển thị thông báo *"Ưu đãi Combo đã được gỡ do giỏ hàng không còn đủ điều kiện"* (theo BPMN Note `n1`) | - Dòng giảm giá 15% **được gỡ bỏ hoàn toàn**, tổng tiền quay về nguyên giá.<br>- Tuy nhiên **không hiện thông báo** *"Ưu đãi Combo đã được gỡ do giỏ hàng không còn đủ điều kiện"*. | **PARTIAL** |
| **UAT-08** | Cảnh báo xung đột hoạt chất cặp **AHA/BHA + Retinol** trên trang Giỏ hàng | `s_scan` → `g_conf (Có)` → `s_warn` | ID `1027` (*KCN Bioderma SPF30* chứa `BHA / Salicylic Acid`) + ID `940` (*Kem Dưỡng Angel's Liquid* chứa `Retinol / Retinoids`) | Xuất hiện thẻ cảnh báo (Alert Card) màu vàng/cam ngay trên bảng giỏ hàng `/cart/` | **Không thấy thẻ cảnh báo trên giao diện `/cart/`.**<br>**Nguyên nhân:** Hàm `renderConflictNotice()` trong `CartComboService.php` đang gắn vào classic hook `woocommerce_before_cart`, trong khi trang `/cart/` hiện dùng **WooCommerce Cart Block** (`wc-blocks.css`) nên hook cổ điển này không được gọi khi render giao diện. | **FAIL** |
| **UAT-09** | Cảnh báo xung đột hoạt chất cặp **Vitamin C + Retinol / Niacinamide** | `s_scan` → `g_conf (Có)` → `s_warn` | ID `1032` (*Sữa Chống Nắng Sunplay* chứa `Vitamin C`) + ID `940` (*Kem Dưỡng Angel's Liquid* chứa `Retinol` & `Niacinamide`) | Xuất hiện thẻ cảnh báo xung đột hoạt chất theo BPMN Note `n2` & `flow-card.md` mục 3 | **Không thấy thẻ cảnh báo trên giao diện `/cart/`.**<br>**Nguyên nhân kép:** (1) `CartComboService::checkIngredientConflicts()` mới chỉ cài đặt cặp `AHA/BHA + Retinoid`, chưa có logic quét `Vitamin C + Retinol/Niacinamide`; (2) Trang `/cart/` dùng WooCommerce Block nên không render hook cổ điển. | **FAIL** |
| **UAT-10** | Soft Warning & Chuyển tiếp sang trang Thanh toán (`Checkout`) | `s_warn` → `c_view` → `g_choice (Tiếp tục thanh toán)` → `end_ok` | Giỏ hàng đang chứa cặp sản phẩm xung đột hoạt chất → bấm chuyển sang `/checkout/` | 1. Không tự ý xóa sản phẩm khỏi giỏ, cho phép vào `/checkout/`.<br>2. Hiển thị lại cảnh báo mềm trên `/checkout/` và cho phép hoàn tất đặt hàng | - Hệ thống **vẫn cho vào trang `/checkout/` bình thường và giữ nguyên các sản phẩm trong giỏ** (đúng tính chất Soft Warning không khóa giỏ).<br>- Tuy nhiên trên `/checkout/` **không hiện thẻ cảnh báo màu cam** (do trang Checkout cũng dùng WooCommerce Block) và **chưa thể bấm đặt hàng** vì hiện thông báo đỏ: *"There are no payment methods available. Please contact us for help placing your order."* (chưa bật cổng thanh toán trong WooCommerce). | **PARTIAL** |

### Tổng hợp tỷ lệ khớp BPMN (DoD)
- **Đạt hoàn toàn (PASS):** **2/10** kịch bản (`UAT-02`, `UAT-04`)
- **Đạt một phần (PARTIAL):** **4/10** kịch bản (`UAT-01`, `UAT-05`, `UAT-07`, `UAT-10`)
- **Chưa đạt (FAIL):** **4/10** kịch bản (`UAT-03`, `UAT-06`, `UAT-08`, `UAT-09`)

---

## 4. Danh sách Lỗi (Defect Log) Cần Khắc phục

| Mã lỗi | Mức độ | Kịch bản bị ảnh hưởng | Mô tả lỗi & Nguyên nhân gốc trong code | Vị trí file cần sửa |
|---|---|---|---|---|
| **BUG-L03-01** | **Critical** | `UAT-06` | **Bước `serum` không được tính vào số bước Routine hợp lệ để giảm 15%:** Hằng số `ROUTINE_STEPS` chỉ khai báo `['cleanser', 'treatment', 'moisturizer', 'sunscreen']` trong khi toàn bộ 74 sản phẩm tinh chất trong CSV/DB mang slug `serum`. Khi khách mua bộ 3 bước *Cleanser + Serum + Moisturizer* (không có KCN), hệ thống chỉ đếm được 2 bước nên mất ưu đãi 15%. | `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php` (dòng 16, 181–186) |
| **BUG-L03-02** | **Critical** | `UAT-08`, `UAT-09`, `UAT-10` | **Thẻ cảnh báo xung đột hoạt chất không hiển thị trên giao diện `/cart/` và `/checkout/`:** Hàm `renderConflictNotice()` móc vào `woocommerce_before_cart` và `woocommerce_before_checkout_form` (chỉ chạy với Shortcode Cart/Checkout cổ điển), nhưng 2 trang này đang dùng **WooCommerce Blocks** (`woocommerce/cart`, `woocommerce/checkout`). | `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php` (dòng 49–50, 282–296) |
| **BUG-L03-03** | **Major** | `UAT-01` | **Nút 1-Click trên trang `/quiz/` bị khóa (`disabled`) và thiếu tự động chuyển hướng sang `/cart/`:** (1) Cờ `cartEnabled` trong `inc/quiz-assets.php` dòng 41 đang mặc định `false` (chưa có `add_filter('kleer_quiz_cart_enabled', '__return_true')`); (2) Dòng `window.location.href` trong `assets/js/routine-cart.js` dòng 102–103 đang bị comment. | `wp-content/themes/kleer-theme/inc/quiz-assets.php` (dòng 41); `wp-content/themes/kleer-theme/assets/js/routine-cart.js` (dòng 101–105) |
| **BUG-L03-04** | **Major** | `UAT-09` | **Chưa cài đặt quy tắc phát hiện xung đột cặp `Vitamin C + Retinol/Niacinamide`:** `checkIngredientConflicts()` mới chỉ kiểm tra `ACID_GROUP` và `RETINOID_GROUP`, thiếu nhóm `Vitamin C` theo đặc tả BPMN Note `n2` và `flow-card.md` mục 3. | `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php` (dòng 20–40, 239–280) |
| **BUG-L03-05** | **Major** | `UAT-03` | **API 1-Click không đảm bảo tính nguyên tử (all-or-nothing) khi có SP lỗi/hết hàng:** Theo BPMN (`g_ok -> Không -> s_err -> end_err`), nếu có SP không hợp lệ hoặc hết hàng thì không được thêm SP nào vào giỏ, nhưng `handleBulkAddRoutineAjax()` hiện vẫn thêm các SP hợp lệ còn lại. | `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php` (dòng 96–133) |
| **BUG-L03-06** | **Minor** | `UAT-05`, `UAT-07` | **Thiếu câu thông báo số tiền tiết kiệm và thông báo khi gỡ ưu đãi Combo:** Khi áp dụng giảm 15% chưa hiện câu *"Bạn đã tiết kiệm được [Số tiền] khi mua trọn bộ Routine!"*; khi xóa SP xuống dưới 3 bước chưa hiện thông báo *"Ưu đãi Combo đã được gỡ do giỏ hàng không còn đủ điều kiện"*. | `wp-content/plugins/kleer-plugin/src/Services/CartComboService.php` (dòng 200–206) |
| **BUG-L03-07** | **Major** *(Phụ thuộc L04)* | `UAT-10` | **Trang `/checkout/` báo *"There are no payment methods available"*:** Chưa kích hoạt cổng thanh toán (`COD`, `BACS`/VietQR, `VNPay`) trong cấu hình WooCommerce nên không thể hoàn tất nút Đặt hàng ở bước cuối. | Cấu hình WooCommerce Payment Gateways (luồng L04) |

---

## 5. Kết luận Nghiệm thu (UAT Sign-off)

- **Tiến độ công việc `L03-G8-01`:** **ĐÃ HOÀN THÀNH 100%** việc kiểm thử 10 kịch bản UAT từ Quiz sang Giỏ hàng & Thanh toán theo đúng sơ đồ BPMN L03 (`L03_Cart_Combo_Discount_BPMN.drawio`) và ghi nhận đầy đủ bằng chứng thực tế.
- **Đánh giá chất lượng bản build L03 hiện tại:** **ĐẠT MỘT PHẦN — CẦN SỬA LỖI (6/10 kịch bản có hoạt động tính năng cốt lõi nhưng chỉ 2/10 kịch bản khớp tuyệt đối 100% với BPMN)** do các lỗi `BUG-L03-01` đến `BUG-L03-06`.
- **Kiến nghị gửi PO Nguyễn Tấn Phát & SM Lê Nguyễn Quốc An:**
  1. Ghi nhận `L03-G8-01` đã hoàn thành biên bản nghiệm thu UAT tại `docs/flows/L03-cart-combo/uat-report.md`.
  2. Chuyển danh sách 6 lỗi `BUG-L03-01` → `BUG-L03-06` cho Dev phụ trách `CartComboService.php` và `kleer-theme` để hotfix trước khi đóng luồng L03.
