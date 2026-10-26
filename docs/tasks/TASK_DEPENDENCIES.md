# Phụ thuộc các task của Bùi Hiếu Nhân

Cập nhật 26/10/2026 sau xác nhận trực tiếp của Nhân.

- G3 chưa được PO Phát hoặc SM An phê duyệt. Các ghi nhận cũ về đã ký/đã hoàn thành G3 không còn được dùng làm bằng chứng.
- Docker hiện chưa có WooCommerce hay sản phẩm. Không tự nhập dữ liệu thay người phụ trách L08.
- Mã task tối ưu theo bảng hiện tại là **L09-G5-06**, không phải L05-G5-04.

| Task | Phần tự chuẩn bị | Điều kiện còn thiếu |
|---|---|---|
| L01-G3-01 | Hồ sơ chờ duyệt và danh sách quyết định | Phát/An duyệt, sơ đồ hiện hành, đủ 8/8 DoR |
| L01-G4-01 | Prompt v2 và Prompt Log | G3 được duyệt, review prompt |
| L01-G5-03 | Sửa ma trận trong code, kiểm thử 384 tổ hợp | PO phê duyệt ma trận và quy tắc profile/tie-break |
| L08-G5-02 | Code đăng ký 3 taxonomy | WooCommerce, kiểm tra menu Admin thực tế |
| L01-G5-05 | Query adapter đọc DB thật, endpoint và validation | Kho hợp lệ, Postman 200, HTTP dưới 300 ms |
| L01-G5-06 | Fallback chỉ chọn sản phẩm an toàn, lỗi 503 khi thiếu | Trí bàn giao fallback từng bước, PO chốt ngoại lệ, QA nghiệm thu |
| L09-G5-06 | 4 SELECT theo lô, cache và script đo | Baseline 15 nếu vẫn giữ DoD đó, EXPLAIN/index trên dữ liệu thật, benchmark HTTP |
| L10-G8-01 | OpenAPI, Postman và hướng dẫn tích hợp | Gói tài liệu đã sẵn sàng; review hợp đồng khi G3 được chốt |

Hướng dẫn từng bước: `docs/tasks/NHAN_NEXT_STEPS.md`. Bằng chứng thực thi: `docs/evidence/nhan/`. Không cập nhật task của thành viên khác hoặc tự đánh dấu DoR/biên bản đã duyệt.

Sau đồng bộ develop: giữ v4 6 câu, schema và frontend mới; tài liệu/384 tổ hợp legacy áp dụng v1. Registry chuẩn pa_* theo code của nhóm, không tạo taxonomy trùng. PO cần chốt phiên bản nghiệm thu.
