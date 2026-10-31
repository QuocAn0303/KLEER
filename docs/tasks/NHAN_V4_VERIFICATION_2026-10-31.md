# Bàn giao kiểm chứng Quiz v4 — Bùi Hiếu Nhân

Ngày: 31/10/2026. Nhánh làm việc: `develop`; baseline đã đồng bộ: `5a9be4a3bc397887620ab624c453cd186e051442`.

## Kết quả

| Nội dung | Bằng chứng thực thi |
| --- | --- |
| Cấu hình nguồn | 6 câu, 27 lựa chọn, 5 loại da, N=2..6 |
| Kiểm chứng toàn bộ v4 | 7.500/7.500 tổ hợp khớp mô hình tham chiếu |
| Ca mẫu PO trong sheet | 11/11 khớp ID SKU mẫu nhập tay và hồ sơ da |
| Dữ liệu trả lời sai | 6/6 ca bị từ chối |
| Tổng phép kiểm tra của verifier v4 | 67.600, không có lỗi |
| Hồi quy suite cũ | 298 assertions, không có lỗi; phần 384 tổ hợp vẫn chỉ là v1 |
| Kiểm tra khả năng phát hiện sai lệch | Bản sao source đổi điểm 3A từ 4 thành 5 làm verifier trả exit 1, phát hiện 2.026 phép kiểm tra sai; source chuẩn và code không bị đổi |

Kết quả máy đọc: `docs/evidence/nhan/v4-verification-2026-10-31.json`. Có thời điểm chạy UTC, SHA-256 của snapshot, verifier và hai file cấu hình/service được kiểm tra. Thời gian CLI không được dùng để chứng minh HTTP dưới 300 ms.

Nguồn: [Quiz Decision Matrix](https://docs.google.com/spreadsheets/d/14cv_y-ps5W_KFH9b2cHPd8E1U7MneYuCR12XN9Wqj1Q/edit). Snapshot raw của 5 tab nằm tại `tools/quiz/fixtures/nhan-v4-source-2026-10-31.json`; expected không lấy từ `QuizQuestions.php`.

## Phạm vi kết luận

Đã chuẩn bị xong phần kỹ thuật của L01-G4-01 (prompt v4) và bổ sung bằng chứng kiểm tra L01-G5-03 (logic weighted matching v4). Có thể chuyển hai task sang **đang duyệt** để Phát/An review. Không tự ký G3 hoặc xác nhận 8/8 DoR.

Người dùng đã chọn phiên bản v4 trong trao đổi ngày 30/10/2026. Quyết định phiên bản không được diễn giải thành PO/SM đã phê duyệt toàn bộ ma trận, các giả định hoặc bản tích hợp thật.

Với 30 SKU mẫu, **5.700/7.500 tổ hợp thiếu ít nhất một bước**; số routine có dưới 2 sản phẩm trong tập mẫu là 0. Catalog rỗng bổ sung trong test trả danh sách rỗng và cảnh báo đúng theo code hiện tại. Vì vậy, logic khớp ma trận không đồng nghĩa đạt DoD cũ “mọi tổ hợp luôn có routine đầy đủ”. Chưa đóng task fallback L01-G5-06.

Ba cách diễn giải được ghi rõ trong verifier và cần PO xác nhận:

1. Lọc eligibility theo loại da trước khi chấm sản phẩm, như giả định QA A-01 và 11 ca mẫu.
2. “Riêng loại da” là sản phẩm không gắn đủ cả 5 loại; các hàng nguồn gắn đủ 5 loại được ánh xạ sang `all_skin_types`.
3. Nếu cả điểm và giá bằng nhau thì chọn ID thấp hơn để ổn định; nguồn hiện chỉ nêu phá hòa theo giá.

## Phần cần phối hợp tiếp

| Task | Còn thiếu |
| --- | --- |
| L01-G3-01 | Phát/An duyệt G3, xác nhận 8/8 DoR và các giả định v4 |
| L08-G5-02 | Kiểm tra ba thuộc tính trong WooCommerce Admin thật |
| L01-G5-05 | Request v4 HTTP 200 trên ID thật, JSON đúng và benchmark dưới 300 ms |
| L01-G5-06 | Nghiệm thu thiếu SKU/fallback v4 trên bản sao dữ liệu và thống nhất DoD |
| L09-G5-06 | Baseline 15 query, đo/EXPLAIN v4 và HTTP thật; bốn SELECT của adapter v1 không phải bằng chứng v4 |

Docker Desktop daemon chưa chạy khi kiểm tra ngày 31/10, nên đợt này không bootstrap WordPress, không thử API/menu, không sửa database, không import dữ liệu và không đổi tồn kho. Không có bằng chứng mới để đóng các task môi trường/hiệu năng.

Figma câu 5 hiện chỉ cho chọn 2/3/4 bước; chưa thấy câu 6 trong các khung Quiz đã rà soát. Sơ đồ Drive còn dùng 5 câu/3–4 bước. Các đầu vào này cần người phụ trách đồng bộ theo v4, không sửa thay theme trong đợt này.

## File mới và mức ảnh hưởng

- `docs/prompts/PRM-L01-01-v4.md`: prompt v4 riêng, giữ nguyên prompt v1/lịch sử.
- `tools/quiz/fixtures/nhan-v4-source-2026-10-31.json`: snapshot nguồn để chạy lại offline.
- `tools/quiz/verify-v4-matrix.php`: CLI riêng, không được plugin tự nạp; không HTTP/DB/file writes.
- `docs/evidence/nhan/v4-verification-2026-10-31.json`: kết quả thực thi.
- Tài liệu bàn giao này.

Không sửa mã runtime trong plugin/theme, `Plugin.php`, taxonomy, schema, repository, API controller hay module thanh toán/email của team. Bộ thay đổi bàn giao chỉ gồm 5 file mới của Nhân; trước commit, `git diff` với HEAD ở các phạm vi runtime là rỗng. Bàn giao trực tiếp trên `develop` theo quy ước nhóm; các sửa đổi local có sẵn được giữ ngoài commit.

Các thay đổi local có sẵn ở `.gitignore`, `setup.ps1` và hai file WordPress chưa được theo dõi đã được bảo toàn. `.gitignore` giữ thêm whitelist `kleer-mailer` từ bản team. Bản sao file trước đồng bộ tại `../.kleer-backups/nhan-before-sync-20261031-7716957b/`; Git stash `1e2763093f79c820a8018038450de32c4d0a3239` vẫn được giữ. Đây là backup file thay đổi local, không phải backup database/volume.

## Cách chạy lại

```powershell
& 'C:\xampp\php\php.exe' tools/quiz/verify-v4-matrix.php
& 'C:\xampp\php\php.exe' wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php
```

Verifier chỉ in JSON; exit 0 khi khớp, exit 1 khi lệch. Không cần kết nối Google Drive để chạy lại. Snapshot 1..30 dùng ID mẫu; không truyền các ID đó vào giỏ hàng WooCommerce thật.
