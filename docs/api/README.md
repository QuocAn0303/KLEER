# Tích hợp KLEER Routine API

Người chuẩn bị: Bùi Hiếu Nhân. Phiên bản 0.2.0, ngày 26/10/2026. Gói này mô tả hành vi code hiện tại; G3 và quy tắc nghiệp vụ còn chờ PO/SM phê duyệt. Chưa có kết quả nghiệm thu API 200 trên sản phẩm thật hoặc tiêu chí HTTP dưới 300 ms.

## Phiên bản và tích hợp develop

Bản develop mới có cả v1 legacy 5 câu và v4 draft 6 câu. Mặc định v1; query `version=v4` chọn bản v4, không đặt version trong JSON body. Giữ nguyên thuật toán, các ca v4 và giao diện của nhóm. Bộ 384 tổ hợp ở đây chỉ kiểm tra v1. PO chưa xác nhận phiên bản nghiệm thu chính thức.

v1 dùng adapter 4 SELECT và yêu cầu đủ bước an toàn, thiếu kho trả 503. v4 giữ reader WooCommerce API và hợp đồng `missing_steps`/`warnings`, có thể trả danh sách thiếu/rỗng khi có WooCommerce nhưng kho chưa đủ SKU. Cả hai không dùng ID fixture trong production; thiếu WooCommerce trả 503. Không dùng cam kết v1 để suy diễn kết quả v4. `openapi.json` được sinh từ schema chung mới nhất; `openapi.yaml` là tài liệu đã có từ upstream, cần review đồng bộ khi PO chốt contract.

## Các file sử dụng

- `openapi.json`: đặc tả OpenAPI 3.1, import vào Swagger Editor hoặc công cụ hỗ trợ OpenAPI 3.1.
- `KLEER-Routine.postman_collection.json`: 7 request cùng assertions kiểm tra HTTP, bước routine và thời gian phản hồi.
- `../../tools/quiz/valid-submission.json`: request mẫu đầy đủ để thử cURL/Postman.
- Schema chính giữ tại `wp-content/plugins/kleer-plugin/schemas/`. Chạy `python tools/quiz/build-api-docs.py` từ gốc repo khi cần đồng bộ đặc tả sau khi schema thay đổi.

## Gọi endpoint

Hai route cùng chức năng: `POST /wp-json/kleer/v1/recommend` và `POST /wp-json/kleer/v1/skin-quiz`. Khi WordPress dùng permalink mặc định, gọi `POST /?rest_route=/kleer/v1/recommend`; URL này đang được collection sử dụng để không cần thay cấu hình website.

```powershell
curl.exe -i 'http://localhost:8081/?rest_route=/kleer/v1/recommend' -H 'Content-Type: application/json' --data-binary '@tools/quiz/valid-submission.json'
```

Client gửi JSON với `session_id`, `consent.policy_accepted=true`, `consent.marketing_opt_in` và đủ 5 câu trả lời. Dùng các `question_id` và lựa chọn trong `QuizQuestions::getQuestions()`; không gửi nhãn tiếng Việt thay slug. Mỗi câu có đúng một lựa chọn. Client tự tạo session UUID; API không lưu lịch sử/consent hoặc gửi marketing.

Server client không bắt buộc có Origin hoặc cookie. Browser khác origin phải nằm trong `KLEER_CORS_ALLOWED_ORIGINS`. Browser đang đăng nhập phải gửi nonce `wp_create_nonce('wp_rest')` qua `X-WP-Nonce`. Nonce gửi sai bị từ chối, kể cả khi gọi ẩn danh. API public không cần khóa bí mật; nonce bảo vệ cookie và không phải chứng thực người dùng cho tính năng khác.

```javascript
const response = await fetch('/?rest_route=/kleer/v1/recommend', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' }, // thêm X-WP-Nonce khi có cookie đăng nhập
  body: JSON.stringify(payload)
});
const result = await response.json();
if (!response.ok) {
  // 400: highlight data.errors[].pointer; 503: hiển thị thông báo và nút thử lại.
  throw new Error(result.message || 'Không thể tạo routine');
}
// Dùng result.data.recommended_products theo đúng thứ tự trả về.
```

## Response và lỗi

HTTP 200 có `status=ok`, `data.submission_id` (mã đối soát xác định từ session, không phải ID lịch sử database), `data.skin_profile` và danh sách `recommended_products`. Mỗi sản phẩm có ID WooCommerce thật, tên, giá VND nguyên, bước và lý do. Routine minimal: cleanser, moisturizer, sunscreen; intensive có treatment trước moisturizer. Không dùng ID trong fixtures của test để thêm giỏ hàng.

Da nhạy cảm dùng `<primary_type>_sensitive`, ví dụ `dry_sensitive`, theo ca nghiệm thu dự thảo. Loại da và vấn đề ưu tiên được thể hiện trong nhãn/concerns; quy tắc này còn chờ PO chốt. Giá lấy từ `_price`, chưa phải tổng đơn sau thuế, phí hoặc khuyến mãi.

| HTTP | Code | Xử lý phía tích hợp |
|---|---|---|
| 400 | kleer_invalid_payload | Sửa payload/câu trả lời, xem data.errors[].pointer |
| 400 | kleer_consent_required / kleer_invalid_version | Cần consent true hoặc query version v1/v4 hợp lệ |
| 403 | kleer_cors_forbidden | Kiểm tra Origin đã được duyệt |
| 403 | rest_cookie_invalid_nonce | Lấy nonce hợp lệ hoặc gửi request ẩn danh không có cookie |
| 503 | kleer_woocommerce_unavailable | Cần môi trường có WooCommerce |
| 503 | kleer_catalog_incomplete | Thiếu bước an toàn, xem data.missing_steps; không vẽ routine thiếu |
| 503 | kleer_catalog_unavailable | Kho không đọc được; thử lại và báo người vận hành |
| 500 | kleer_quiz_engine_error | Hiển thị thông báo chung; không có chi tiết server trong response |

## Đầu vào sản phẩm cần Trí bàn giao

Phần mô tả adapter 4 SELECT bên dưới áp dụng v1. Registry chuẩn dùng TaxonomyRegistry của develop, không đăng ký thêm bộ taxonomy legacy; WooCommerce chưa có thì chờ, không tạo product post type thay thế. v4 giữ reader và các bước makeup_remover/toner/serum đã có trên upstream.

Chỉ lấy simple product đã publish, `_stock_status=instock`, `_price` hợp lệ, không bị exclude-from-catalog và có đúng một bước. Variable/grouped/external product chưa nằm trong adapter này. Bộ đọc dùng CPT storage hiện tại của WooCommerce, không được xem là tương thích mọi kho sản phẩm tương lai.

| Thuộc tính chuẩn develop | Legacy adapter v1 hỗ trợ | Term slug |
|---|---|---|
| pa_skin_type | skin_type | oily, dry, combination, normal, sensitive, all_skin_types |
| pa_skin_concern | skin_concern | acne, dark_spots, hydration, aging, soothing |
| pa_routine_step | routine_step | cleanser, treatment, moisturizer, sunscreen |

Mỗi bước cần ít nhất một sản phẩm dự phòng mà PO đã duyệt là dịu nhẹ, còn hàng. Phân loại bằng `_kleer_gentle_fallback=yes` hoặc đồng thời `skin_type=all_skin_types` và `skin_concern=soothing`. Không tự đoán độ dịu nhẹ từ tên sản phẩm. Slug tiếng Việt cần được người phụ trách dữ liệu ánh xạ đúng trước import; adapter không tự đổi term của luồng L08.

## Fallback v1 và khả năng mở rộng

Service tìm exact match rồi partial match dịu nhẹ rồi gentle fallback; da nhạy cảm chỉ nhận sản phẩm tagged sensitive hoặc dịu nhẹ. Không chọn bừa sản phẩm đầu tiên. Nếu không đủ bước, trả lỗi JSON 503, không thay bằng ID mẫu hay danh sách thiếu. Muốn nghiệm thu mục tiêu mọi tổ hợp có routine thì phải duy trì đủ sản phẩm dự phòng thật cho cả 4 bước và được PO chấp thuận.

Hook `kleer_quiz_fallback_used($step, $level)` cho phép logger/QA ghi nhận fallback; hook không chứa session hay câu trả lời. Nếu chưa có listener thì không có log bền vững được tạo tự động.

Config và service thuộc plugin, không phụ thuộc theme. Adapter database riêng tại `Repositories/RoutineProductRepository.php`; muốn hỗ trợ variation hoặc data store khác thì thay adapter và thêm kiểm thử phù hợp. `calculate_routine($payload)` và `processSubmission($payload)` dùng chung hành vi. Chỉ truyền catalog thủ công cho unit test; production mặc định luôn đọc DB.

## Kiểm thử và đo

```powershell
docker exec kleer-php php /var/www/html/wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php
docker exec kleer-php php /var/www/html/tools/quiz/verify-runtime.php
python tools/quiz/benchmark.py --base-url http://localhost:8081 --samples 20 --output docs/evidence/nhan/http-benchmark.json
```

Import collection vào Postman, đặt `base_url` theo môi trường. Request 01 và 02 chỉ PASS khi có sản phẩm thật và dưới 300 ms; 503 không được tính là PASS. Các case 400/403 có thể chạy khi kho chưa sẵn sàng. Thử kho rỗng/thiếu bước trên bản sao dữ liệu, không thay tồn kho của nhóm để kiểm thử.

Adapter v1 dùng 4 SELECT theo lô khi có sản phẩm; kho rỗng chỉ cần truy vấn đầu. Cache catalog trong nhóm `kleer_products` tối đa 60 giây; invalidation qua hook sản phẩm hiện có và hook term bổ sung. Thay đổi bằng SQL trực tiếp cần xóa cache hoặc chờ TTL. Không cache JSON response có mã phiên. Query count chỉ tính adapter, không bao gồm bootstrap WordPress; thời gian API phải đo toàn bộ HTTP, cả cold và warm.

Indexes có sẵn cần kiểm tra: posts.type_status_date, postmeta.post_id, term_relationships.PRIMARY/object_id, term_taxonomy.taxonomy/PRIMARY và terms.PRIMARY/slug. `verify-runtime.php` ghi SHOW INDEX. Dùng EXPLAIN trên đúng SQL và tập dữ liệu thật trước khi đề nghị thêm index; không tạo index trùng mặc định. Chưa có baseline 15 truy vấn, nên không thể tuyên bố giảm 15 xuống 4 chỉ dựa trên số SELECT của adapter.

Tham khảo cách đăng ký taxonomy và route: [WordPress register_taxonomy](https://developer.wordpress.org/reference/functions/register_taxonomy/) và [WordPress REST custom endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/).
