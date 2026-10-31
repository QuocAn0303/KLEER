# L01 G3 Biên bản chờ phê duyệt

Cập nhật 26/10/2026. Người chuẩn bị: Bùi Hiếu Nhân. Nhân xác nhận PO Phát và SM An **chưa duyệt G3**. Tài liệu này không phải bằng chứng đã ký hay đã đạt 8/8 DoR.

## Hồ sơ cần duyệt

- Flow Card và ma trận dự thảo: `docs/flows/L01-quiz-routine/flow-card.md`.
- Sơ đồ `L01_Quiz_Logic_Flowchart.drawio`: cần Huỳnh Ngọc Nhã Uyên bàn giao đường dẫn bản hiện hành.
- Prompt kỹ thuật dự thảo: `docs/prompts/PRM-L01-01-quiz-engine.md`.
- Hợp đồng API: `docs/api/openapi.json`, `docs/api/README.md`.
- Kiểm thử độc lập: `wp-content/plugins/kleer-plugin/tests/run_nhan_tests.php`.

Sau đồng bộ develop còn có bản v4 6 câu. PO cần xác nhận phiên bản dùng nghiệm thu; 384 tổ hợp đang kiểm tra bản v1 5 câu, không đại diện cho v4.

## Các quyết định đang chờ

| Nội dung | Người cần xác nhận | Hiện trạng |
|---|---|---|
| 5 câu hỏi, tất cả trọng số và ngưỡng sensitivity >= 3 | Nguyễn Tấn Phát | Chờ duyệt |
| Mã da nhạy cảm `<loại_da>_sensitive`, gồm `dry_sensitive` | Nguyễn Tấn Phát | Chờ duyệt |
| Thứ tự xử lý bằng điểm oily, dry, combination, normal | Nguyễn Tấn Phát | Chờ duyệt |
| Bộ fallback dịu nhẹ cho từng bước và ngoại lệ kho rỗng HTTP 503 | Nguyễn Tấn Phát | Chờ duyệt |
| Sơ đồ và ít nhất 5 ca Given When Then | PO Phát và Huỳnh Ngọc Nhã Uyên | Chờ bàn giao và duyệt |
| Namespace, schema, taxonomy, CORS và ranh giới plugin | Lê Nguyễn Quốc An cùng Trần Ngọc Minh Tuyền | Chờ review kỹ thuật |
| Đủ 8/8 DoR trên bảng nhóm | PO Phát và SM An | Chưa xác nhận |

## Xác nhận

| Vai trò | Họ tên | Kết quả | Ngày và bằng chứng |
|---|---|---|---|
| Người chuẩn bị | Bùi Hiếu Nhân | Đã chuẩn bị bản kỹ thuật | 26/10/2026 |
| PO | Nguyễn Tấn Phát | Chờ phản hồi | Chưa có |
| SM | Lê Nguyễn Quốc An | Chờ phản hồi | Chưa có |

Chỉ cập nhật G3 và DoR thành đã duyệt sau khi nhận xác nhận thật. Không tự ký thay hoặc dùng kết quả unit test làm bằng chứng phê duyệt nghiệp vụ.
