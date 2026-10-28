# L05-G5-02 — Mẫu email hóa đơn khách hàng (Customer Invoice)

- **Người thực hiện:** Trần Ngọc Minh Tuyền · **Duyệt:** Lê Nguyễn Quốc An
- **File:** `wp-content/plugins/kleer-mailer/templates/invoice.php` (HTML) · dữ liệu: `Order_Email::build_data()` · xem thử: `tests/preview-invoice.php`
- **Kế thừa:** hook, SMTP và QR tra cứu của L05-G5-01 (`email-smtp.md`); vòng đời đơn theo `state.drawio`.

## 1. Thiết kế (phong cách Unisex)

| Yếu tố | Quyết định |
|---|---|
| Màu | Trung tính: đen `#111`, kem `#f3f1ec`, xám đá `#8a8780`. Trạng thái: xanh rêu `#3f5a47` (Đã xác nhận), vàng nâu `#8a6a1f` (Chờ thanh toán). Không dùng hồng/xanh dương "phân giới tính". |
| Chữ | Helvetica Neue / Arial; wordmark **KLEER** in hoa giãn chữ 8px; nhãn mục in hoa cỡ 11px giãn 2px |
| Bố cục | Một cột 600px: băng thương hiệu đen → nhãn trạng thái → tiêu đề → 3 ô (Mã đơn / Ngày đặt / Thanh toán) → danh sách sản phẩm → tổng tiền → (khối chuyển khoản) → ghi chú → Giao đến + **QR tra cứu** → nút → chân trang |
| Sản phẩm | Ảnh thu nhỏ 62px, tên, thuộc tính biến thể, SKU, đơn giá × số lượng, thành tiền |
| Tổng tiền | Tạm tính, giảm giá (nếu có), vận chuyển (tên phương thức; 0 → "Miễn phí"), **Tổng cộng** cỡ 24px |
| Hai biến thể | `processing` (nhãn "Đã xác nhận") và `on-hold` (nhãn "Chờ thanh toán", kèm khối thông tin chuyển khoản) |

Ảnh sản phẩm và QR được **nhúng inline** (`cid:`, tối đa 6 ảnh sản phẩm, mỗi ảnh ≤ 40 KB) nên hiển thị được cả khi site chạy localhost và không cần người nhận bấm "tải ảnh từ xa".

## 2. Kỹ thuật tương thích

- Bố cục hoàn toàn bằng `<table role="presentation">`, thuộc tính `width/bgcolor/align` kèm inline style (Outlook dùng engine Word).
- **Outlook desktop:** bảng "ma" `<!--[if mso]>` rộng cố định 600px (Outlook bỏ qua `max-width`), nút **VML** (`v:rect`) thay cho nút `<a>`, `o:OfficeDocumentSettings`, `mso-table-lspace/rspace`, `mso-hide:all` cho preheader.
- **Gmail mobile:** `max-width:600px; width:100%` + media query `≤600px` xếp cột dọc, nút full-width. Không dùng flex/grid/position.
- Không dùng ảnh nền, web font, JavaScript hay biểu mẫu. Có preheader và bản text thuần (`multipart/alternative`).

## 3. Kết quả kiểm tra thật

| Kiểm tra | Kết quả |
|---|---|
| Gửi qua đúng đường mã `Order_Email::send()` (SMTP Mailpit): 3 ảnh inline (QR + 2 sản phẩm), phần text | ✅ |
| Dung lượng HTML ≈ 12 KB (Gmail cắt thư HTML > 102 KB) | ✅ |
| Render 375px (mobile) và 800px (desktop), cả 2 biến thể: cột xếp dọc, không tràn ngang, nút full-width, QR giữa | ✅ (ảnh chụp, kiểm bằng mắt) |
| **Mailpit HTML check** (dữ liệu caniemail, 187 phép thử, gồm Outlook Windows 2007–2024 + Outlook.com/Mac/iOS/Android + Gmail web/iOS/Android): | **≈ 95 % hỗ trợ đầy đủ, ≈ 4 % một phần, 0,7 % không hỗ trợ** |
| Các mục "không hỗ trợ" còn lại | chỉ ảnh hưởng phần trang trí: `outline` (reset), `overflow/max-height/opacity/display:none` (chỉ để ẩn preheader, đã có `mso-hide`), `white-space` (đã thêm `nowrap` cho cột giá), `<style>` trên Gmail web mobile bản cũ (xem dưới) |
| Gửi thật 1 đơn COD qua Gmail SMTP với mẫu mới (đơn #46) | ✅ đã gửi, **chưa xác nhận hiển thị trong Gmail** |

## 4. Chưa kiểm được

1. **Outlook desktop thật và Gmail Mobile thật** chưa được mở: Mailpit HTML check là phân tích tĩnh dựa trên dữ liệu caniemail, ảnh chụp dùng Chromium. Cần bạn mở thư đơn #46 trong Gmail (điện thoại) và, nếu có, Outlook Windows để xác nhận mắt thường. Thử nhanh Outlook bằng Litmus/Email on Acid nếu cần chứng nhận.
2. **Gmail bản web trên điện thoại với tài khoản không phải Google** không đọc thẻ `<style>`, nên media query không chạy; thư vẫn **co giãn theo chiều ngang** (`width:100%`) nhưng 3 ô đầu và khối QR sẽ không xếp dọc.
3. Chế độ tối (dark mode) khai báo `color-scheme: light`; một số app (Gmail/Outlook mobile) vẫn có thể tự đảo màu.
4. Thư của tài khoản Gmail đang cấu hình vẫn có thể vào **Thư rác** — xem ghi chú ở `email-smtp.md` (link `localhost`, tự gửi cho chính mình).
5. Chữ "Thanh toan khi nhan hang (COD)" lấy từ plugin thanh toán (không dấu) — cần đổi ở plugin đó nếu muốn tiếng Việt chuẩn.

## 5. Cách xem thử

```bash
docker compose -f docker-compose.yml -f docker-compose.mail.yml up -d mailpit
docker exec kleer-php php wp-cli.phar --allow-root eval-file wp-content/plugins/kleer-mailer/tests/preview-invoice.php processing
docker exec kleer-php php wp-cli.phar --allow-root eval-file wp-content/plugins/kleer-mailer/tests/preview-invoice.php on-hold
# Mở http://localhost:8025 → thư mới nhất → tab "HTML Check" / thu nhỏ khung xem để thử responsive
```
Script tạo đơn mẫu (2 sản phẩm có ảnh, biến thể, SKU, vận chuyển, ghi chú) và ép SMTP về Mailpit, không gửi thư thật.
