# Bàn giao L01-G5-05 — Routine Query Engine và REST API v4

Ngày: 03/11/2026. Phần công việc của Bùi Hiếu Nhân. Trạng thái bàn giao: **hoàn thành code và kiểm thử v4 trên môi trường riêng; PR gửi nhóm trưởng review/merge**.

Người dùng đã xác nhận dùng v4 trong trao đổi ngày 03/11. Xác nhận này được ghi nhận làm phạm vi triển khai; không tự thay chữ ký hoặc biên bản G3 của PO/SM.

## Nội dung có thể trình bày với nhóm trưởng

“Em đã sửa bộ đọc sản phẩm Quiz v4 để đọc hết catalog thay vì dừng ở 100 sản phẩm; đồng bộ cờ hoạt chất với CSV và giảm truy vấn taxonomy bằng cách đọc theo lô. Em bổ sung bộ kiểm thử repository, payload và Postman riêng cho v4, cùng công cụ kiểm API và benchmark. Trên môi trường Docker test độc lập, API qua 9/9 ca HTTP, toàn bộ 7.500 tổ hợp dùng catalog WooCommerce test không bị thiếu bước. Lần đo 20 request bắt đầu với catalog cache rỗng đạt tối đa 274,18 ms, trung vị các lượt sau 74,42 ms. Database nhóm đã được đối chiếu trước/sau và không đổi các bản ghi kinh doanh. Còn bước An/Trí triển khai WooCommerce/dữ liệu đã chốt lên site nhóm và PO/SM xác nhận nghiệm thu.”

## 1. Vấn đề trước khi sửa

- Reader v4 mặc định limit=100 và không đọc tiếp trang sau.
- CSV bàn giao dùng meta _kleer_strong_active nhưng reader đọc _kleer_has_strong_actives; cờ thực tế bị bỏ qua khi import nguyên schema CSV.
- Mỗi sản phẩm đọc riêng ba taxonomy; số query tăng theo số SKU.
- Sản phẩm gắn đủ cả 5 loại da chưa được quy về all_skin_types, dẫn đến có thể nhận nhầm +1 điểm “riêng loại da”.
- Benchmark có sẵn chỉ dùng payload v1, mặc định kiểm đúng 4 sản phẩm, chưa dùng được để nghiệm thu v4.
- Site nhóm localhost:8081 hiện có kleer-plugin nhưng không có WooCommerce, không có sản phẩm và đang dùng twentytwentyfive. Không thể lấy HTTP 503 ở môi trường này làm bằng chứng API gợi ý đã đạt.

## 2. Những thay đổi trong repo

| File | Thay đổi / lý do |
| --- | --- |
| wp-content/plugins/kleer-plugin/src/Repositories/WooCommerceProductRepository.php | Đọc toàn bộ các trang bằng WooCommerce API, thứ tự ID ổn định; chỉ lấy simple/publish/instock. Đọc taxonomy một lô và prime meta. Lọc giá không hợp lệ, hidden/search-only và thiếu bước. |
| Cùng repository | Đọc _kleer_strong_active trước; chỉ fallback key cũ khi key chuẩn chưa có. Chuỗi 0/false không bật cờ. Gộp đủ 5 loại da thành all_skin_types. Ưu tiên term canonical pa_*; có thể đọc term legacy khi chưa có canonical mà không ghi/migrate dữ liệu. |
| Cùng repository | Dùng cache catalog_live_v3_* để bỏ cache cũ, TTL 60 giây trong nhóm kleer_products. Lỗi đọc trang/term trả availability error, không cache catalog đọc dở. |
| wp-content/plugins/kleer-plugin/tests/run_v4_repository_tests.php | 27 assertions cô lập: vượt trang 100, meta chuẩn/cũ, boolean, all-skin, loại sản phẩm không hợp lệ, batch terms/cache, lỗi trang/term và engine chọn được SKU ngoài trang đầu. |
| tools/quiz/valid-submission-v4.json | Payload đủ 6 câu với mã q1..q6; version nằm ở query. |
| tools/quiz/check-v4-http.py | 9 ca HTTP: dầu mụn, nhạy cảm 6 bước, thiếu/trùng câu, option sai, consent false, nonce sai, Origin bị chặn, version không hỗ trợ. Không tạo đơn hoặc sửa tồn kho. |
| tools/quiz/benchmark.py | Thêm --version, --payload, --allow-missing-steps; kiểm ID/giá/bước/version; không đánh giá lỗi nhanh hoặc thiếu số mẫu là đạt; phân biệt complete/accepted và ghi lượt đầu, warm median, max. Xuất tiếng Việt được trong PowerShell. |
| tools/quiz/test_benchmark.py | 4 nhóm unit test kiểm chất lượng phép đo: routine 2 bước v4 hợp lệ; dữ liệu trống/trùng/sai phiên bản/sai bước; thiếu bước cần được chấp nhận rõ và có warning. |
| tools/quiz/verify-v4-runtime.php | CLI đọc catalog WooCommerce, số truy vấn, taxonomy và kiểm 7.500 tổ hợp trong process. Không kích hoạt plugin hoặc ghi sản phẩm/đơn hàng. |
| docs/api/KLEER-Routine-v4.postman_collection.json | Collection 9 request riêng v4, có assertions; để An/QA import và chạy lại. |
| docs/api/README.md | Hướng dẫn v4 mới, lệnh chạy và giới hạn; phân biệt hồ sơ lịch sử 26/10 với kết quả mới. |
| docs/tasks/NHAN_L01_G5_05_V4_HANDOVER_2026-11-03.md | Báo cáo bàn giao này. |

**Không thay trọng số hoặc thuật toán QuizQuestions/QuizEngineService, không đổi contract route mặc định v1, không sửa source theme/core hoặc module thanh toán/email.** Các thay đổi local .gitignore, setup.ps1 và hai file core chưa theo dõi có sẵn được giữ nguyên. Giai đoạn kiểm thử ban đầu chưa commit/push. Theo yêu cầu bàn giao ngày 03/11, bộ thay đổi được phát hành trên nhánh feature/nhan-l01-g5-05-v4, mở PR vào develop và ghi nhận Hoàn thành ở đúng task L01-G5-05, kèm phạm vi kiểm thử. Không ký thay G3 hoặc ghi rằng PR đã merge.

## 3. Môi trường kiểm thử và dữ liệu

- URL server nhóm: http://localhost:8081. Giữ cấu hình plugin/theme/database nguồn.
- Server kiểm thử riêng: http://127.0.0.1:8082. Compose project kleer-nhan-v4; database kleer_nhan_v4_test; các volume riêng test-db/test-wordpress và Redis riêng.
- WordPress 7.1.2, WooCommerce 11.2.1 tải từ https://wordpress.org/plugins/woocommerce/, PHP 8.2.34 trong container.
- Source CSV lấy đúng develop 227edfb6abdd8cb3a9fe7208369e7bf4f85bebc9: 528 dòng; 30 dòng thuộc 15 SKU trùng được loại khỏi **bản nhập test** để không tự quyết định bản nào đúng.
- Đã nhập 498 sản phẩm không trùng; 92 sản phẩm chưa có routine_step nên reader bỏ qua. Catalog hợp lệ dùng cho Quiz: **406 sản phẩm**, đủ cả 6 bước, 183 sản phẩm có cờ hoạt chất mạnh, ID lớn nhất 508.
- Không sửa CSV gốc, không tự gán bước cho 92 sản phẩm thiếu bước. 498 sản phẩm test **không được diễn giải là đã nghiệm thu yêu cầu dữ liệu >=500 SKU sạch của L08**.
- Không tải/gán ảnh sản phẩm trong đợt import riêng này; không tuyên bố nghiệm thu chất lượng ảnh/catalog UI.
- Bản test chặn email và outbound WordPress HTTP, tắt WP-Cron để không phát thư/tác vụ nền từ bản sao.
- Theme KLEER chỉ được bật trên bản test; tạo trang page_id=509 để kiểm UI→API. Site nhóm vẫn giữ theme và plugin như ban đầu.

## 4. Kết quả trước/sau và kiểm thử

### Đọc catalog trên cùng database test

| Chỉ số | Reader gốc | Reader sau sửa |
| --- | --- | --- |
| Sản phẩm đã publish trong DB test | 498 | 498 |
| Sản phẩm vào catalog Quiz | 91 (chỉ xét 100 SKU đầu) | 406 |
| Sản phẩm nhận cờ hoạt chất mạnh | 0 | 183 |
| Query của lần đọc catalog đo được | 309 | 35 |
| Query đọc lại khi cache warm (đo riêng) | Chưa đo | 0 |

Đây là số đo của adapter trên catalog test; **không phải cam kết toàn request chỉ có 35 query hoặc đạt mục tiêu 15→4 của task tối ưu tuần 09**. Việc so trước/sau được thực hiện bằng CLI trên bản test, sau đó đã khôi phục code đã sửa.

### Kiểm thử logic và HTTP

- Verifier ma trận v4: **7.500/7.500**, 11/11 ca mẫu, 6/6 ca sai, 67.600 phép kiểm tra, 0 lỗi.
- Suite hồi quy của Nhân: **298 assertions, 0 lỗi**; sweep 384 tổ hợp × 2 catalog trong suite này vẫn là v1.
- Suite reader v4 mới: **27 assertions, 0 lỗi**.
- Bộ kiểm tra benchmark: **4 tests đạt**.
- Kiểm trên catalog WooCommerce test trong process: **7.500 tổ hợp, 0 lỗi schema/ID/giá/cờ nhạy cảm, 0 tổ hợp thiếu bước, 0 routine dưới 2 sản phẩm**.
- HTTP v4: **9/9 ca đạt** (200/400/403 đúng trường hợp). Các response 200 có đủ bước và ID WooCommerce thật của database test.
- Đã thao tác giao diện chọn 6 câu cho ca da dầu/mụn/4 sản phẩm; trang kết quả hiển thị đúng 4 sản phẩm WooCommerce và giá, không thay dữ liệu nhóm.
- CTA thêm routine vào giỏ hiện bị disable theo cấu hình theme có sẵn. Chưa nghiệm thu Quiz→Cart; phần này thuộc tích hợp L03/G6 và không được đánh dấu hoàn thành ở đây.
- Collection Postman đã tạo để bàn giao; các kết quả HTTP trên được chạy bằng Python, không ghi rằng đã thao tác ứng dụng Postman.

### Hiệu năng HTTP

Lần đo chính: benchmark-v4-cold.json, 03/11/2026 13:58:15 UTC (20:58:15 Asia/Bangkok):

| Chỉ số | Kết quả |
| --- | --- |
| Số mẫu | 20/20 |
| Phiên bản | v4; allow_missing_steps=false |
| Kết quả nghiệp vụ | 20/20 HTTP 200, routine đầy đủ |
| Request đầu, sau xóa riêng cache catalog test | 274,18 ms |
| Trung vị 19 request sau | 74,42 ms |
| Trung vị toàn bộ | 75,09 ms |
| Cao nhất | 274,18 ms |
| Tất cả dưới 300 ms | Đạt trong lần đo này |

“Cold” ở đây là **cache catalog rỗng trên server đã chạy**, không phải khởi động lạnh toàn bộ PHP/OPcache/container. Đây là bằng chứng trên môi trường test đã mô tả; không khẳng định mọi máy/website nhóm đều có độ trễ này.

### Điều kiện hạ tầng ảnh hưởng phép đo

Cấu hình Nginx có sẵn bật fastcgi_keep_conn on trong khi pool PHP chỉ có 5 worker và Nginx có nhiều worker. Trong lượt test ban đầu, sau vài request pool bị giữ bởi kết nối idle; ghi nhận cảnh báo pm.max_children, có request chờ hơn 50 giây. Kết nối localhost trên máy cũng có overhead khi thử IPv6 trước cổng chỉ bind IPv4.

**Chỉ trong Nginx test**, chuyển fastcgi_keep_conn off; benchmark dùng 127.0.0.1:8082. Không sửa nginx.conf hoặc restart Nginx/PHP của nhóm. An cần review việc cân đối FastCGI keepalive/PHP pool trước triển khai. Không coi kết quả sau điều chỉnh riêng này là số đo của server nhóm 8081.

## 5. Bảo toàn dữ liệu nhóm

Đã có backup trước thay đổi tại .kleer-backups/nhan-v4-20261103/shared-db-before.sql (ngoài repo). Không đính kèm SQL chứa thông tin tài khoản vào patch hoặc báo cáo công khai.

Đối chiếu trước/sau trên các bảng:
- wp_posts: 4/4 bản ghi, giống nhau.
- wp_postmeta: 2/2, giống nhau.
- wp_users: 1/1, giống nhau.
- wp_usermeta: 20/20, giống nhau.
- wp_terms, wp_term_taxonomy, wp_term_relationships: mỗi bảng 1/1, giống nhau.

Dùng so sánh từng tuple và SHA-256; kết quả all_business_rows_unchanged=true trong shared-data-integrity.json. Site nguồn không được cài/activate WooCommerce, chuyển theme, nhập/xóa sản phẩm, thay tồn kho hay đổi tài khoản. Không dùng backup file làm bằng chứng đã kiểm thử khôi phục toàn bộ hệ thống production; bản SQL đã được nhập thành công vào DB test riêng.

## 6. Cách chạy lại

Trong thư mục repo KLEER:

~~~powershell
& 'C:\xampp\php\php.exe' wp-content/plugins/kleer-plugin/tests/run_v4_repository_tests.php
& 'C:\xampp\php\php.exe' tools/quiz/verify-v4-matrix.php
& 'C:\xampp\php\php.exe' wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php
python -m unittest discover -s tools/quiz -p test_benchmark.py -v

python tools/quiz/check-v4-http.py --base-url http://127.0.0.1:8082 --output ../deliverables/kleer-nhan-v4-2026-11-03/http-v4-review.json
python tools/quiz/benchmark.py --base-url http://127.0.0.1:8082 --version v4 --samples 20 --output ../deliverables/kleer-nhan-v4-2026-11-03/benchmark-v4-review.json
docker exec kleer-nhan-v4-php-1 php /var/www/html/tools/quiz/verify-v4-runtime.php
~~~

Trang xem thử: http://127.0.0.1:8082/?page_id=509. Thao tác lựa chọn được lưu tạm trong LocalStorage của trình duyệt. Nếu mở đúng tab đã kiểm thử có thể thấy ngay kết quả; mở trang trên cửa sổ mới để thử lại.

Không chạy script seed-test-only.php trên database nhóm; script nằm ngoài repo và có guard DB/URL riêng. Môi trường test đang được giữ để nhóm review; không dùng docker compose down -v trên project nhóm.

## 7. Việc còn cần nhóm trưởng/An/Trí xác nhận

1. Review patch, chạy lại collection v4; ghi người review và môi trường được chấp nhận.
2. An chuẩn bị WooCommerce và dữ liệu thật trên site nhóm; hiện 8081 chưa có điều kiện này. Mọi thay đổi database chung cần qua quy trình của nhóm.
3. Trí/PO xử lý 15 SKU trùng và 92 dòng không có routine_step trong tập test; bàn giao catalog sạch theo đúng taxonomy/meta, đảm bảo chỉ tiêu L08 riêng.
4. An review FastCGI/PHP pool và đo lại đúng server nhóm trước khi nhận tiêu chí <300ms ở đó.
5. Cập nhật Flow Card/DoD/G3 theo v4 và xác nhận các quy tắc thiếu SKU; không ký thay PO/SM.
6. Theo yêu cầu của người dùng ngày 03/11, ghi Hoàn thành ở Tasks dòng 54 sau khi bàn giao Git/Drive, kèm chú thích hoàn thành kỹ thuật trên môi trường riêng. PR review/merge, triển khai site nhóm và xác nhận G3 vẫn do nhóm phụ trách.

## 8. Bằng chứng kèm theo

Bằng chứng được commit tại docs/evidence/nhan/v4-2026-11-03/ và lưu thêm trong deliverables/kleer-nhan-v4-2026-11-03:
- catalog-before.json: số đo reader gốc trên DB test.
- runtime-v4.json: catalog và sweep dữ liệu WooCommerce.
- http-v4-ipv4.json: 9 ca HTTP đạt.
- benchmark-v4-ipv4.json: 20 request sau chỉnh Nginx test; max 257,69ms.
- benchmark-v4-cold.json: lượt đo chính bắt đầu với cache catalog rỗng; max 274,18ms.
- http-v4.json: lượt đầu chậm trước điều chỉnh Nginx test, được giữ để minh bạch điều kiện.
- shared-data-integrity.json: so sánh bản ghi nhóm trước/sau.
- quiz-v4-result.jpg: ảnh giao diện kết quả.
- changed-files.json: danh sách/hash các file kỹ thuật bàn giao. Bản patch review được cung cấp trong gói Drive; source chính nằm trên nhánh feature của PR.

Bản ghi thay đổi giữ riêng phần code của nhiệm vụ; không chứa .env, SQL dump, credentials hoặc những thay đổi local có sẵn.
