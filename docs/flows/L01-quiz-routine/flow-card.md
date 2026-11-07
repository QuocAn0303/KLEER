# FLOW CARD: L01 - QUIZ CHẨN ĐOÁN DA & GỢI Ý ROUTINE CHĂM SÓC DA UNISEX

- **Mã luồng:** `L01`
- **Tên luồng:** Quiz chẩn đoán da & Đề xuất Routine chăm sóc da Unisex cá nhân hóa
- **Mã task liên quan:** `L01-G1-01`, `L01-G2-01`, `L01-G3-01`, `L01-G4-01`, `L01-G5-01..06`, `L01-G6-01`, `L01-G7-01`, `L01-G8-01`
- **Chủ sở hữu luồng (Flow Owner):** Bùi Hiếu Nhân (Plugin Dev 1 - Quiz Backend, Taxonomy, REST)
- **Phối hợp:** Huỳnh Ngọc Nhã Uyên (Theme Dev 2 - Frontend SPA), Lê Nguyễn Quốc An (Scrum Master & DevOps), Nguyễn Tấn Phát (PO & BA), Nguyễn Ngọc Thảo Uyên (QA)
- **Trạng thái:** DỰ THẢO — CHỜ PO PHÁT VÀ SM AN DUYỆT G3
- **Xác nhận ngày 26/10/2026:** Nhân cho biết G3 chưa được phê duyệt. Các trọng số và ca nghiệm thu bên dưới là đầu vào dự thảo để chuẩn bị kỹ thuật, chưa phải quyết định PO.
- **Thư mục tài liệu:** `docs/flows/L01-quiz-routine/`

---

> Phạm vi bản Flow Card này là v1 legacy 5 câu. develop còn có bản nháp v4 6 câu với version=v4; giữ nguyên thuật toán và missing_steps/warnings của v4, không dùng 384 ca v1 để tuyên bố nghiệm thu v4. PO cần chốt bản dùng chính thức.

## 1. Mục tiêu & Giá trị nghiệp vụ (Business Objective)

Hệ thống cung cấp trải nghiệm **Smart Skin Quiz (SPA không tải lại trang)** giúp khách hàng (nam, nữ và mọi giới tính) tự chẩn đoán tình trạng da của mình chỉ trong 60 giây và nhận ngay **phác đồ chăm sóc da khoa học 3–4 bước (Personalized Routine)** kèm danh sách sản phẩm cụ thể từ thương hiệu KLEER.

### Giá trị mang lại:
1. **Xóa bỏ rào cản chọn mỹ phẩm:** Khách hàng Unisex thường bối rối trước hàng ngàn loại mỹ phẩm; Quiz đóng vai trò "chuyên viên tư vấn ảo" hướng dẫn theo nhu cầu thực tế.
2. **Tăng tỷ lệ chuyển đổi (CR) và AOV (Average Order Value):** Tự động tạo Combo Routine hoàn chỉnh, liên kết trực tiếp với tính năng "1-Click Add Routine to Cart" (Luồng `L03`) giảm 15% khi mua trọn bộ.
3. **An toàn & Khoa học:** Đảm bảo sản phẩm được đề xuất tương thích theo loại da và vấn đề da, loại trừ xung đột hoạt chất.

---

## 2. Chu trình người dùng (User Journey)

```text
[Trang chủ / Banner CTA] 
        │ (Click "Bắt đầu chẩn đoán da")
        ▼
[Màn hình Quiz SPA] (Vanilla JS, 0 reload)
   ├─ Q1: Phản ứng bề mặt da sau rửa mặt (Loại da)
   ├─ Q2: Tình trạng đổ dầu & lỗ chân lông (Bã nhờn)
   ├─ Q3: Tần suất nhạy cảm, kích ứng (Độ nhạy cảm)
   ├─ Q4: Vấn đề ưu tiên cải thiện (Mụn / Thâm / Lão hóa / Cấp ẩm)
   └─ Q5: Mục tiêu Routine (Tối giản 3 bước vs Chuyên sâu 4 bước)
        │ (Submit Answers + Consent)
        ▼
[API POST /wp-json/kleer/v1/skin-quiz]
   ├─ CORS Check & Nonce Verify
   ├─ JSON Schema Validation (quiz-submission.schema.json)
   ├─ Weighted Tag Matching Engine
   └─ Fallback Resolver (nếu thiếu sản phẩm)
        │ (JSON Response)
        ▼
[Màn hình Kết quả Routine]
   ├─ Hồ sơ da khách hàng (Skin Profile Badge)
   ├─ Phác đồ 3–4 bước chuẩn:
   │    1. Làm sạch (Cleanser)
   │    2. Đặc trị (Treatment/Serum)
   │    3. Dưỡng ẩm (Moisturizer)
   │    4. Bảo vệ (Sunscreen)
   ├─ Hướng dẫn sử dụng Sáng / Tối
   └─ Nút CTA: "Thêm trọn bộ Routine vào giỏ hàng - Tiết kiệm 15%" (Chuyển sang L03)
```

---

## 3. Bộ câu hỏi chuẩn & Ma trận trọng số (Quiz Decision Matrix)

### 3.1. Danh sách 5 câu hỏi cốt lõi

| Mã câu hỏi (`question_id`) | Câu hỏi hiển thị | Lựa chọn (`answer`) | Trọng số ánh xạ (Scoring Weights) |
|---|---|---|---|
| `q1_skin_feeling` | Cảm giác bề mặt da sau khi rửa mặt 30 phút mà chưa thoa gì? | `tight_dry`: Căng rát, khô khốc<br>`greasy_all`: Bóng nhờn toàn bộ mặt<br>`oily_tzone`: Nhờn vùng chữ T, khô 2 bên má<br>`comfortable`: Dễ chịu, không khô không nhờn | `dry` +3<br>`oily` +3<br>`combination` +3<br>`normal` +3 |
| `q2_pore_sebum` | Tình trạng lỗ chân lông và dầu thừa của bạn như thế nào? | `large_pores`: Lỗ chân lông to, nhanh đổ dầu<br>`normal_pores`: Lỗ chân lông nhỏ, ít bóng dầu<br>`tzone_only`: Chỉ thấy rõ lỗ chân lông ở mũi/trán<br>`flaky_rough`: Da thường xuyên bong tróc, ráp | `oily` +2, `acne` +1<br>`normal` +2, `dry` +1<br>`combination` +2<br>`dry` +3 |
| `q3_sensitivity` | Da bạn có dễ bị đỏ rát, châm chích khi đổi thời tiết/sản phẩm? | `very_often`: Thường xuyên đỏ rát, ngứa<br>`sometimes`: Thỉnh thoảng khi dùng chất lạ<br>`rarely_never`: Rất hiếm hoặc chưa bao giờ | `sensitive` +4<br>`sensitive` +2<br>`sensitive` +0 |
| `q4_main_concern` | Vấn đề da bạn muốn tập trung cải thiện nhất hiện nay? | `acne_blemish`: Mụn viêm, mụn ẩn, mụn đầu đen<br>`dark_spots`: Thâm mụn, sạm nám, da không đều màu<br>`dehydration`: Da thiếu nước, thiếu ẩm, xỉn màu<br>`anti_aging`: Nếp nhăn li ti, da kém săn chắc | `acne` +4<br>`dark_spots` +4<br>`hydration` +4<br>`aging` +4 |
| `q5_routine_goal` | Bạn mong muốn quy trình chăm sóc da thế nào? | `minimal`: Tối giản, nhanh gọn (3 bước)<br>`intensive`: Chuyên sâu, hiệu quả cao (4 bước) | `steps: 3` (Cleanser, Moisturizer, Sunscreen)<br>`steps: 4` (Cleanser, Treatment, Moisturizer, Sunscreen) |

### 3.2. Thuật toán tổng hợp điểm (Scoring Algorithm)

```php
// Tổng hợp vector điểm từ payload trả lời
$scores = [
    'skin_type' => ['oily' => 0, 'dry' => 0, 'combination' => 0, 'normal' => 0],
    'sensitivity' => 0,
    'concerns' => ['acne' => 0, 'dark_spots' => 0, 'hydration' => 0, 'aging' => 0],
];
```

1. **Xác định Loại da chính (`primary_skin_type`):**
   - Loại da có điểm cao nhất trong nhóm `oily`, `dry`, `combination`, `normal`.
   - Nếu `sensitivity >= 3`, gắn cờ `is_sensitive = true`.
2. **Xác định Vấn đề chính (`primary_concern`):**
   - Vấn đề có điểm cao nhất trong nhóm `concerns`.
3. **Mã hồ sơ da (`skin_profile`) đề xuất chờ PO chốt:**
   - Da không nhạy cảm: `<loại_da>_<vấn_đề>`; da nhạy cảm: `<loại_da>_sensitive` để khớp ca nghiệm thu 2 (`dry_sensitive`). Vấn đề ưu tiên vẫn nằm trong nhãn và danh sách `concerns`.
   - Khi bằng điểm, ưu tiên theo thứ tự khai báo: oily, dry, combination, normal. Đây là quy tắc xác định của bản triển khai dự thảo, cần PO chấp thuận.

---

## 4. Ánh xạ Custom Taxonomies WooCommerce

Hệ thống sử dụng 3 Custom Taxonomies của WooCommerce để lọc sản phẩm:

| Taxonomy Slug | Tên Taxonomy | Terms phổ biến | Mục đích trong Quiz |
|---|---|---|---|
| `pa_skin_type` | Loại da | `oily`, `dry`, `combination`, `sensitive`, `all_skin_types` | Khớp với loại da của khách hàng |
| `pa_skin_concern` | Vấn đề da | `acne`, `dark_spots`, `aging`, `hydration`, `soothing` | Khớp với phác đồ điều trị |
| `pa_routine_step` | Bước Routine | `cleanser`, `treatment`, `moisturizer`, `sunscreen` | Chia đều 4 bước phác đồ |

---

## 5. Cơ chế Fallback an toàn (Safe Fallback Mechanism)

Để đảm bảo **100% không bao giờ trả về kết quả rỗng (empty array)** hoặc gây lỗi màn hình trắng:

1. **Ưu tiên 1 (Exact Match):** Sản phẩm khớp chính xác cả 3 điều kiện: `pa_routine_step` + `pa_skin_type` + `pa_skin_concern`.
2. **Ưu tiên 2 (Partial Match):** Nếu không có sản phẩm khớp cả vấn đề da, truy vấn sản phẩm khớp: `pa_routine_step` + `pa_skin_type` + `pa_skin_concern: soothing / all`.
3. **Ưu tiên 3 (Safe Fallback):** Nếu vẫn không tìm thấy, hệ thống tự động gán sản phẩm dịu nhẹ quốc dân: `pa_routine_step` + `pa_skin_type: all_skin_types` (ví dụ: Sữa rửa mặt Cetaphil Gentle, Dưỡng chất Vichy Mineral 89, Kem chống nắng Cell Fusion C).
4. **Điều kiện để nghiệm thu:** Kho phải có ít nhất một sản phẩm dịu nhẹ, còn hàng, đã xuất bản cho mỗi bước. Khi đủ dữ liệu, trả đủ **3 hoặc 4 sản phẩm** theo `q5_routine_goal`. Kho rỗng hoặc thiếu sản phẩm an toàn trả lỗi JSON `503 kleer_catalog_incomplete`, không trả HTTP 200 với danh sách thiếu, không dùng ID mẫu. Ngoại lệ này cần PO duyệt trước khi đóng task fallback.

### Tương thích tên taxonomy trong triển khai

Sau cập nhật develop, giữ registry `TaxonomyRegistry` của nhóm: dùng ba thuộc tính WooCommerce `pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, được tạo qua WooCommerce API ở admin_init. Không đăng ký thêm một bộ taxonomy trùng. Adapter v1 vẫn đọc được dữ liệu legacy skin_type/skin_concern/routine_step nếu đã tồn tại. WooCommerce chưa có thì registry chờ, không tạo product post type giả.

---

## 6. Hợp đồng API REST (API Contract)

- **Endpoint:** `POST /wp-json/kleer/v1/skin-quiz`
- **Quyết định Namespace:** `kleer/v1` (tuân thủ tài liệu STT 1 và `docs/PLUGIN_ARCHITECTURE.md`)
- **Headers bắt buộc:**
  - `Content-Type: application/json`
  - `X-WP-Nonce: <nonce>` (CSRF Protection khi đăng nhập)
  - `Origin: <origin-in-whitelist>`
- **Request Schema:** `wp-content/plugins/kleer-plugin/schemas/quiz-submission.schema.json`
- **Response Schema:** `wp-content/plugins/kleer-plugin/schemas/quiz-submission-response.schema.json`
- **Mục tiêu phản hồi:** `< 300ms` cho toàn bộ HTTP request thành công, chưa nghiệm thu. Catalog được cache qua `KleerCache` nhóm `kleer_products` tối đa 60 giây; không cache response chứa mã phiên.

---

## 7. Các trường hợp ngoại lệ & Bắt lỗi (Exception Handling)

| Mã lỗi HTTP | Mã lỗi Kleer | Nguyên nhân | Cách xử lý |
|---|---|---|---|
| `400 Bad Request` | `kleer_invalid_payload` | Payload thiếu trường hoặc sai định dạng schema | Trả JSON Pointer chỉ rõ vị trí lỗi để Frontend highlight |
| `403 Forbidden` | `kleer_cors_forbidden` | Origin không nằm trong whitelist cấu hình `.env` | Chặn kết nối từ trang không được phép |
| `403 Forbidden` | `rest_cookie_invalid_nonce` | CSRF Nonce hết hạn | Trả mã yêu cầu Frontend làm mới nonce |
| `503 Service Unavailable` | `kleer_catalog_incomplete` / `kleer_woocommerce_unavailable` / `kleer_catalog_unavailable` | Thiếu sản phẩm an toàn, thiếu WooCommerce hoặc không đọc được kho | Trả JSON có thông báo và missing_steps; không giả sản phẩm |
| `500 Internal Error`| `kleer_quiz_engine_error` | Lỗi nội bộ không dự kiến | Trả thông báo chung, không lộ chi tiết máy chủ |

---

## 8. Tiêu chuẩn chấp nhận (Acceptance Criteria - Given-When-Then)

### Ca 1: Khách hàng da dầu mụn chọn Routine 4 bước
- **Given:** Khách hàng chọn Q1=greasy_all, Q2=large_pores, Q3=rarely_never, Q4=acne_blemish, Q5=intensive.
- **When:** Nhấn nút "Hoàn thành chẩn đoán" gửi payload lên API.
- **Then:** API trả HTTP 200, `skin_profile.code = "oily_acne"`, danh sách gồm 4 sản phẩm (`cleanser`, `treatment`, `moisturizer`, `sunscreen`) với thành phần Salicylic Acid/Zinc PCA, tổng tiền tính đúng.

### Ca 2: Khách hàng da khô nhạy cảm chọn Routine 3 bước
- **Given:** Khách hàng chọn Q1=tight_dry, Q2=flaky_rough, Q3=very_often, Q4=dehydration, Q5=minimal.
- **When:** Gửi câu trả lời lên hệ thống.
- **Then:** API trả `skin_profile.code = "dry_sensitive"`, danh sách gồm 3 sản phẩm dịu nhẹ (không có bước `treatment`), có cảnh báo hoạt chất dịu nhẹ trong `disclaimer`.

### Ca 3: Dữ liệu payload không hợp lệ (Kiểm tra Schema)
- **Given:** Frontend gửi `session_id` sai định dạng UUID hoặc thiếu trường `consent.policy_accepted`.
- **When:** Gửi request lên API.
- **Then:** Backend chặn lại ngay tại tầng Controller, trả HTTP 400 kèm mã lỗi `kleer_invalid_payload` và mảng errors chỉ rõ JSON pointer.

### Ca 4: Kho sản phẩm thiếu mặt hàng khớp chính xác (Kích hoạt Fallback)
- **Given:** Database tạm thời hết sản phẩm Treatment cho da hỗn hợp thâm sạm.
- **When:** Thuật toán tính điểm chạy qua bộ lọc sản phẩm.
- **Then:** Hệ thống tự động kích hoạt Safe Fallback, gán sản phẩm dưỡng sáng dịu nhẹ cho mọi loại da, không trả về mảng rỗng, cờ fallback được ghi log.
