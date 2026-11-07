# Các bước hoàn thành task của Nhân

Cập nhật 26/10/2026. Chỉ đánh giá task của Bùi Hiếu Nhân; các đề nghị bên dưới là phối hợp đầu vào, không phải đánh giá công việc của người khác.

## Những phần đã tự thực hiện

- Kích hoạt riêng `kleer-plugin` trên WordPress local thành công, không có PHP Warning/Notice trong lần kiểm tra. Trạng thái active_plugins cũ được lưu ngoài repo tại `.kleer-backups/nhan-active-plugins-before-2026-10-26.json` trong thư mục project cha.
- Sau đồng bộ 5 commit mới từ develop, giữ `TaxonomyRegistry` dùng pa_skin_type/pa_skin_concern/pa_routine_step qua WooCommerce API; không thêm bộ taxonomy trùng. Chưa có WooCommerce thì registry chờ, menu thực tế chưa nghiệm thu.
- Sửa trọng số flaky_rough và mã `dry_sensitive` theo ca nghiệm thu dự thảo; kiểm tra độc lập toàn bộ 384 tổ hợp trên danh mục đầy đủ và danh mục chỉ có fallback dịu nhẹ.
- Kiểm tra đủ 5 câu hỏi, lựa chọn, consent, Origin và nonce; kiểm tra schema đầu vào/đầu ra. Thay ID mẫu bằng adapter đọc dữ liệu sản phẩm thật trong đường chạy production.
- Fallback bỏ lựa chọn bừa sản phẩm không tương thích. Nếu thiếu kho thì trả JSON 503 rõ ràng, không trả routine thiếu hoặc ID giả.
- Có adapter 4 SELECT theo lô và cache catalog tối đa 60 giây, cùng kiểm thử với database giả lập. Chưa khẳng định đạt số đo thật hoặc giảm từ 15 truy vấn.
- Có đặc tả OpenAPI 3.1, bộ Postman và hướng dẫn tích hợp bên thứ ba trong `docs/api/`.
- Sửa hồ sơ G3 thành chờ duyệt theo xác nhận trực tiếp của Nhân; không để chữ ký/xác nhận chưa có thật.

## Đồng bộ develop

Giữ nguyên Quiz v4 6 câu, giao diện và schema mới của nhóm. 384 tổ hợp ở đây chỉ áp dụng bản v1 5 câu; PO cần xác nhận v1/v4 nào dùng nghiệm thu. v1 thiếu kho an toàn trả 503; v4 giữ hợp đồng missing_steps/warnings khi WooCommerce có nhưng chưa đủ SKU. Thiếu WooCommerce trả 503 cho cả hai, không dùng catalog mẫu trong production.

## Bước 1 Xin duyệt G3

Gửi Phát và An `docs/flows/L01-quiz-routine/approval.md`, Flow Card, prompt và API contract. Nhờ Nhã Uyên bàn giao sơ đồ hiện hành. Phát chốt 5 câu hỏi, trọng số, quy tắc bằng điểm, mã da nhạy cảm, routine 3/4 bước và sản phẩm dịu nhẹ. Phát cần quyết định rõ ngoại lệ 503 khi kho không thể cung cấp đủ bước; nếu không chấp nhận, phải bảo đảm tồn kho dự phòng theo nghiệp vụ. An rà soát kỹ thuật. Sau xác nhận thật, người có trách nhiệm cập nhật 8/8 DoR; Nhân mới đóng L01-G3-01 và sử dụng kết quả làm đầu vào chính thức cho G4/G5.

## Bước 2 Bàn giao plugin và prompt

Code lifecycle/PSR-4 đã có và plugin đã được kích hoạt trên local. L01-G5-01 có bằng chứng runtime. Prompt v2 trong `docs/prompts/PRM-L01-01-quiz-engine.md` cần được ghi vào Prompt Log dưới tên Nhân, trạng thái dự thảo chờ G3. Sau G3, chỉnh prompt theo quyết định cuối và xin review để đóng L01-G4-01. Không tự sửa DoR hoặc trạng thái của người duyệt.

## Bước 3 Nhận môi trường WooCommerce

Nhờ An cung cấp môi trường WordPress có WooCommerce hoặc hướng dẫn cài trên môi trường nhóm; không cần gửi mật khẩu qua tài liệu này. Nhờ Trí bàn giao dữ liệu L08 có ID thật và đủ taxonomy. Không tự nhập sản phẩm thay Trí. Sau đó mở Products và xác nhận đủ 3 menu taxonomy để đóng L08-G5-02; lưu ảnh/bằng chứng thực tế. Plugin không tự tạo product post type thay WooCommerce.

## Bước 4 Chốt thuật toán

Sau khi Phát duyệt ma trận, đối chiếu `QuizQuestions` và các kỳ vọng trong `run_nhan_tests.php` với đúng bản PO. Chạy:

```powershell
docker exec kleer-php php /var/www/html/wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php
```

Yêu cầu: không có lỗi/Warning và cả 384 tổ hợp khớp ma trận đã duyệt. Hiện kiểm thử đạt với bản dự thảo, nên chưa thay thế sự phê duyệt của PO để đóng L01-G5-03.

## Bước 5 Nghiệm thu dữ liệu và fallback

Trí gán đúng term slug tiếng Anh trong `docs/api/README.md`, giá hợp lệ, trạng thái publish/instock và một bước cho mỗi simple product. Phát xác nhận ít nhất một sản phẩm fallback thật, dịu nhẹ cho mỗi bước. Trên bản sao dữ liệu, nhờ QA thử mất exact match, thiếu một bước và kho rỗng. Không thay tồn kho thật của nhóm để chạy test. Với kho đủ fallback, mọi tổ hợp phải đủ 3/4 bước; với kho không thể đáp ứng, kiểm tra 503 theo ngoại lệ đã duyệt. Có đầy đủ bằng chứng mới đóng L01-G5-06.

## Bước 6 Tích hợp và thử API thật

Tuyền xác nhận host, schema, CORS/nonce và hỗ trợ client thứ ba. Nhã Uyên nối giao diện quiz với endpoint thật, ánh xạ câu hỏi frontend sang question_id/option backend và xử lý lỗi 400/403/503. Lâm Trúc Uyên phối hợp thống nhất slug với bộ lọc Catalog nếu bộ lọc dùng 3 taxonomy này. Phần sửa theme vẫn thuộc người phụ trách theme.

Import `docs/api/KLEER-Routine.postman_collection.json`. Đặt base_url theo host. Kiểm tra request 01/02 trả 200, đủ routine, ID tồn tại trong WooCommerce và dưới 300 ms; các request lỗi trả đúng mã. Dùng đường dẫn `/?rest_route=/kleer/v1/recommend` khi permalink mặc định; không bắt buộc sửa cấu hình website để gọi API. Đóng L01-G5-05 sau nghiệm thu đầy đủ.

## Bước 7 Đo SQL và HTTP

Nhờ An/Trí cung cấp kho đại diện và điều kiện cold/warm; QA cùng ghi bằng chứng. Chạy:

```powershell
docker exec kleer-php php /var/www/html/tools/quiz/verify-runtime.php
python tools/quiz/benchmark.py --base-url http://localhost:8081 --samples 20 --output docs/evidence/nhan/http-benchmark.json
```

SHOW INDEX hiện thấy các chỉ mục WordPress tiêu chuẩn. Dùng EXPLAIN trên 4 SELECT của adapter với kho thật; không thêm index trùng. Kiểm tra số query adapter riêng với toàn bộ số query bootstrap. Chỉ tính thời gian HTTP thành công 200, không dùng thời gian xử lý service hay lỗi 503 để chứng minh dưới 300 ms. Hiện chưa có baseline 15 query; nhờ An xác nhận baseline hoặc chỉnh DoD qua quyết định nhóm, không tự đổi chỉ tiêu. Có số đo thật mới đóng L09-G5-06.

## Bước 8 Bàn giao tài liệu API

L10-G8-01 có `docs/api/openapi.json`, Postman collection và hướng dẫn schema, route, lỗi, bảo mật, sản phẩm, cache, tích hợp và mở rộng adapter. Gửi Tuyền/An review khi hợp đồng được chốt; đồng bộ tài liệu nếu PO thay quy tắc. Tài liệu không tuyên bố API đã đạt hiệu năng hoặc đã có sản phẩm thật.

## Bước 9 Cập nhật đúng phần Nhân

Chỉ tick task có đủ DoD. L09-G1-01 đã được đánh dấu trước đó; L01-G5-01 và L10-G8-01 có thể ghi nhận đầu ra đã thực hiện trong đợt này. Các task khác giữ chưa hoàn thành cho đến khi có đủ đầu vào, phê duyệt và bằng chứng. Không cập nhật task của thành viên khác, không tự xác nhận phê duyệt G3 hoặc 8/8 DoR.

## Bằng chứng

- `docs/evidence/nhan/runtime.json`: trạng thái plugin, taxonomy, response và SHOW INDEX hiện tại.
- `docs/evidence/nhan/http-benchmark.json`: số đo HTTP hiện tại; lỗi 503 không được tính PASS.
- `docs/evidence/nhan/unit-tests.txt`: kết quả kiểm thử độc lập và hồi quy.
- `docs/api/`: đầu ra L10-G8-01 và công cụ bàn giao.
