# PROMPT PRM-L01-01: QUIZ ENGINE & WEIGHTED MATCHING REST API
- **Mã Prompt:** `PRM-L01-01`
- **Mã task liên quan:** `L01-G4-01`, `L01-G5-03`, `L01-G5-05`, `L01-G5-06`
- **Luồng:** `L01` (Quiz chẩn đoán da & Phác đồ Routine)
- **Tác giả:** Bùi Hiếu Nhân (Plugin Dev 1)
- **Người duyệt:** Lê Nguyễn Quốc An (Scrum Master & DevOps)
- **Ngày lập:** 10/10/2026

---

# 1. Bối cảnh & Yêu cầu kỹ thuật
Dự án KLEER: website TMĐT mỹ phẩm Unisex trên WordPress/WooCommerce, chạy Docker (nginx, php 8.2-fpm, mariadb 10.11, redis).
Theme: `wp-content/themes/kleer-theme/`. Plugin: `wp-content/plugins/kleer-plugin/`. Không sửa WordPress Core.
Chuẩn code: PHP theo PSR-12, tuân thủ OOP phân tầng (Endpoints, Controllers, Services, Models), kiến trúc Clean Architecture.

# 2. Nhiệm vụ cụ thể
1. **Config:** Định nghĩa mảng 5 câu hỏi cốt lõi, thang điểm trọng số (Weighted Tag Matching) ánh xạ tới 3 Custom Taxonomies (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`).
2. **Service:** Viết class `QuizEngineService` xử lý thuật toán cộng dồn điểm, xác định hồ sơ da (`skin_profile`), đề xuất phác đồ 3–4 bước (Cleanser, Treatment, Moisturizer, Sunscreen) và cơ chế Safe Fallback không bao giờ trả về kết quả rỗng.
3. **Controller:** Viết class `SkinQuizController` sử dụng trait `ValidatesJsonPayload` để kiểm tra tính hợp lệ của request theo `quiz-submission.schema.json` và định dạng response theo `quiz-submission-response.schema.json`.
4. **Endpoints:** Viết class `SkinQuizEndpoints` đăng ký route `POST /wp-json/kleer/v1/skin-quiz` và alias `POST /wp-json/kleer/v1/recommend`.
5. **Plugin Bootstrap:** Đăng ký endpoint trong `src/Plugin.php`.
6. **Tests:** Bổ sung unit tests vào `tests/run_tests.php` kiểm tra tính toán điểm số, fallback an toàn và schema validation.

# 3. Tiêu chí chấp nhận (Given-When-Then)
- **Happy Path 1:** Khách hàng da dầu mụn chọn intensive routine -> API trả `skin_profile.code = 'oily_acne'`, 4 bước sản phẩm chuẩn, không có lỗi.
- **Happy Path 2:** Khách hàng da khô nhạy cảm chọn minimal routine -> API trả `skin_profile.code = 'dry_sensitive'`, 3 bước sản phẩm dịu nhẹ.
- **Resilience Case:** Kho không có sản phẩm treatment khớp chính xác -> Safe Fallback tự động chọn sản phẩm dịu nhẹ cho mọi loại da, không bao giờ trả mảng rỗng.
- **Validation Case:** Payload sai format hoặc thiếu trường required -> Controller bắt lỗi HTTP 400 kèm mã `kleer_invalid_payload` và JSON pointer.
- **Registration Case:** Route `/wp-json/kleer/v1/skin-quiz` và `/recommend` được đăng ký thành công qua `rest_api_init`.
