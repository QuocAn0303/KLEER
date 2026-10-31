# PRM L01 01 Quiz Engine và Routine API

- Mã prompt: `PRM-L01-01`; task `L01-G4-01`.
- Người chuẩn bị: Bùi Hiếu Nhân; cập nhật 26/10/2026.
- Phiên bản: v2.1 dự thảo; **chờ G3, chưa có phê duyệt của PO/SM**.

## Bối cảnh

KLEER là website WordPress/WooCommerce trên PHP 8.2, MariaDB và Redis. Phần backend thuộc `wp-content/plugins/kleer-plugin/`; PHP namespace `Kleer\`, REST namespace `kleer/v1`. Không sửa WordPress core, theme hoặc plugin của thành viên khác. Không thêm thư viện, không commit .env/secret. Mọi thay đổi bàn giao qua PR vào develop; chỉ chuẩn bị code, không tự duyệt nghiệp vụ.

## Luồng và nhiệm vụ

Xây dựng backend L01 nhận 5 câu trả lời, tính điểm theo ma trận, đọc sản phẩm thật, trả routine 3 hoặc 4 bước. Chuẩn bị kỹ thuật theo bản dự thảo; liệt kê rõ các giả định cần PO chốt.

Sau đồng bộ develop: giữ nguyên v4 6 câu và giao diện mới đã có. Phần ma trận 5 câu và 384 tổ hợp trong prompt này áp dụng v1; không ghi đè v4 hoặc tự chốt v1/v4 thay PO. Controller tiếp tục đọc query version=v1/v4.

## Đầu vào

- Flow Card, ma trận và ca nghiệm thu: `docs/flows/L01-quiz-routine/flow-card.md`.
- Sơ đồ: chờ đường dẫn `L01_Quiz_Logic_Flowchart.drawio` của Huỳnh Ngọc Nhã Uyên, không giả định đã duyệt.
- Schema dùng chung: `schemas/quiz-submission.schema.json` và `schemas/quiz-submission-response.schema.json` trong plugin.
- Dữ liệu: bảng `{prefix}posts` (product/publish), `{prefix}postmeta` (`_price`, `_stock_status`, `_kleer_gentle_fallback`), `{prefix}term_relationships`, `{prefix}term_taxonomy`, `{prefix}terms`. Không hard-code prefix wp_. Không lưu câu trả lời, cookie hay consent vào database trong task này.
- Taxonomy chuẩn của develop: `pa_skin_type`, `pa_skin_concern`, `pa_routine_step`; adapter v1 đọc thêm dữ liệu legacy không có tiền tố nếu đã tồn tại. Không tạo song song hai bộ taxonomy. Mỗi sản phẩm có đúng một routine_step.

## Đầu ra mong đợi

1. `src/Config/QuizQuestions.php`: mảng PHP đúng tất cả trọng số; `flaky_rough` chỉ cộng dry +3; loại fixture catalog khỏi đường chạy thật.
2. `src/Services/QuizEngineService.php`: class tương ứng vai trò Quiz_Engine; cung cấp `calculate_routine(array $payload): array`, `calculateSkinProfile(array $answers): array`, `recommendRoutine(array $profile, int $steps, array $catalog): array` và kiểm tra 5 câu trả lời duy nhất, đúng lựa chọn.
3. `src/Repositories/RoutineProductRepository.php`: đọc ID, tên, giá và taxonomy từ database bằng tối đa 4 SELECT theo lô; bỏ sản phẩm hết hàng, ẩn, chưa có giá hoặc sai bước; hỗ trợ simple products. Không dùng ID fixture làm ID WooCommerce. Cache tối đa 60 giây trong nhóm kleer_products và xóa cache khi sản phẩm/term thay đổi.
4. `src/Controllers/SkinQuizController.php`: kiểm tra JSON Schema và consent, gọi service, kiểm tra response; trả 400 cho câu trả lời sai, 503 cho thiếu kho/WooCommerce, 500 chung cho lỗi nội bộ. Không lộ thông tin database.
5. `src/Endpoints/SkinQuizEndpoints.php`: đăng ký POST `/skin-quiz` và `/recommend` trong `rest_api_init`; khách ẩn danh được gọi; chặn Origin ngoài whitelist; kiểm tra X-WP-Nonce nếu được gửi và với người đang đăng nhập.
6. `src/Taxonomy/TaxonomyRegistry.php`, `src/Plugin.php`: giữ registry thuộc tính WooCommerce mới nhất; tạo thuộc tính qua WC API ở admin_init và gắn product ở init, không thêm registry trùng; lifecycle và PSR-4 không tạo Warning/Notice.
7. Safe fallback chỉ dùng sản phẩm thật được phân loại dịu nhẹ/phù hợp. Nếu thiếu bước thì ném RoutineUnavailableException, controller trả JSON 503; không trả routine thiếu hoặc sản phẩm không tương thích. Phát hook `kleer_quiz_fallback_used` không chứa dữ liệu cá nhân.
8. Kiểm thử trong `tests/run_nhan_tests.php`; OpenAPI, Postman và hướng dẫn tích hợp trong `docs/api/`; ghi giới hạn nghiệm thu trong tài liệu Nhân.

## Tiêu chí Given When Then

1. Given da dầu mụn và kho đủ, When gửi intensive, Then HTTP 200, oily_acne, đủ 4 bước đúng thứ tự và schema hợp lệ.
2. Given da khô nhạy cảm và kho dịu nhẹ đủ, When gửi minimal, Then dry_sensitive và đúng 3 bước không có treatment.
3. Given không có khớp chính xác nhưng đủ sản phẩm dự phòng, When chấm điểm, Then đủ routine bằng sản phẩm dịu nhẹ thật và phát sự kiện fallback.
4. Given thiếu/nhân đôi câu hỏi, UUID sai, lựa chọn không tồn tại hoặc consent false, When gửi, Then 400 cùng JSON Pointer, không truy vấn sản phẩm.
5. Given Origin không cho phép hoặc nonce gửi lên sai, When gửi, Then 403 trước khi chạy service.
6. Given kho rỗng/thiếu một bước hoặc WooCommerce chưa có, When gửi hợp lệ, Then 503 có mã lỗi ổn định, không dùng ID mẫu, không trả danh sách thiếu.
7. Given dữ liệu thật và môi trường nghiệm thu, When đo cold/warm request, Then báo số SELECT và toàn bộ thời gian HTTP; chỉ đạt tiêu chí dưới 300 ms khi HTTP 200 và routine đủ. Không suy diễn kết quả 15 xuống 4 nếu không có baseline 15.

## Chạy thử

`docker exec kleer-php php /var/www/html/wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php`

`docker exec kleer-php php /var/www/html/tools/quiz/verify-runtime.php`

`python tools/quiz/benchmark.py --base-url http://localhost:8081 --samples 20`

## Cần người review xác nhận

PO chốt ma trận, cách xử lý bằng điểm, mã da nhạy cảm và ngoại lệ 503. An/Minh Tuyền chốt contract/schema/taxonomy và điều kiện hiệu năng. Trí cung cấp dữ liệu thật, có ít nhất một sản phẩm fallback hợp lệ cho mỗi bước. Không ghi bất kỳ quyết định nào là đã duyệt khi chưa có xác nhận.
