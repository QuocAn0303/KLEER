# ĐỀ XUẤT CHỐT — STT 1: TÊN THƯ MỤC & REST NAMESPACE

- **Vấn đề cần chốt:** Sổ tay dùng `unisex-theme` / `unisex-quiz-engine`, còn repo dùng `kleer-theme` / `kleer-plugin`.
- **Người đề xuất:** Lê Nguyễn Quốc An (đã được giao chốt trước `2026-11-01`)
- **Hạn chốt:** `2026-11-01`
- **Mốc:** phải chốt **trước khi** Nhân (L01) bắt đầu viết endpoint ở Tuần 07.
- **Trạng thái:** ✅ **ĐÃ CHỐT — giữ nguyên `kleer/*`**

---

## 1. Bối cảnh thực tế trong repo

Kiểm tra trực tiếp trên nhánh `develop`:

| Hạng mục | Repo đang dùng | Sổ tay ghi |
|---|---|---|
| Plugin | `wp-content/plugins/kleer-plugin/` | `unisex-quiz-engine` |
| Theme | `wp-content/themes/kleer-theme/` | `unisex-theme` |
| Root namespace PHP | `Kleer\` | — |
| REST namespace | `kleer/v1` | `unisex/v1`? |
| File `docs/PLUGIN_ARCHITECTURE.md` | đã chốt `kleer/v1` ở mục 9 | — |

**Code đã đóng cứng `kleer/v1`:** `wp-content/plugins/kleer-plugin/src/Endpoints/HealthEndpoints.php:28`
đăng ký route `register_rest_route('kleer/v1', '/health', ...)`.

**Đánh giá:** Phần mã đã viết (đã merge vào cả `main` và `develop`) **đồng nhất dùng `kleer`** về
thư mục, PHP namespace lẫn REST namespace. Đổi tên bây giờ sẽ phải sửa code đã chạy.

---

## 2. Phương án đề xuất: Giữ `kleer/*` (khuyến nghị)

| Tiêu chí | Đánh giá |
|---|---|
| Chi phí đổi | **Thấp** — 0 dòng code production phải sửa |
| Rủi ro gãy endpoint đang chạy | **Không có** |
| Khớp với STT 7 (đã chọn **KLEER** làm thương hiệu thương mại, hạn `2026-10-11`) | **Khớp** |
| Khớp với tên repo / GitHub (`QuocAn0303/KLEER`) | **Khớp** |
| Tính nhất quán với `Task08_30_Skincare.csv`, `docs/PLUGIN_ARCHITECTURE.md` | **Khớp** |

Lập luận: STT 7 đã chốt thương hiệu là **KLEER**. Nếu thương hiệu là KLEER thì tên thư mục và
REST namespace là `kleer-*` / `kleer/v1` là hệ quả tất nhiên. Giữ nguyên giúp dự án nhất quán
từ thương hiệu → repo → thư mục → namespace → URL.

---

## 3. Phương án thay thế và cái giá

### Phương án B — Đổi sang `unisex-*`

Cần làm đồng thời:
- Đổi tên thư mục `kleer-plugin` → `unisex-quiz-engine`, `kleer-theme` → `unisex-theme`.
- Đổi root PHP namespace `Kleer\` → `Unisex\` (ảnh hưởng **mọi** file trong `src/`).
- Đổi REST namespace `kleer/v1` → `unisex/v1` ⇒ **đổi URL công khai**, Frontend đã viết sẽ phải sửa.
- Cập nhật `docs/PLUGIN_ARCHITECTURE.md`, `nginx.conf`, `.env.example`, `docker-compose.yml`.

**Ước lượng:** ~1–2 ngày công sửa + rủi ro gãy PR đang review.

### Phương án C — Namespace REST riêng cho Quiz (ví dụ `kleer/quiz/v1`)

Không khuyến nghị: tạo thêm một quy ước lúc còn chưa có endpoint nào, làm phân mảnh API.

---

## 4. Nếu vẫn muốn dùng tên "Unisex"

Phương án ít tổn phiều nhất: giữ `kleer` cho **thương hiệu/Khách hàng**, dùng "Unisex" chỉ làm
**mô tả phân khúc** trong nội dung marketing — ví dụ mô tả sản phẩm là "Unisex Skincare".
Không cần đụng vào tên thư mục/kỹ thuật.

---

## 5. QUYẾT ĐỊNH ĐÃ CHỐT

> **Chốt STT 1:** Giữ nguyên thư mục `kleer-plugin` / `kleer-theme`, PHP namespace `Kleer\`,
> REST namespace `kleer/v1`. Sổ tay cập nhật theo repo, không sửa repo theo sổ tay.
> Sổ tay ghi `unisex-theme` / `unisex-quiz-engine` được hiểu là **tên gọi dự án trong giai đoạn
> thiết kế ban đầu**, đã bị thay thế khi chốt thương hiệu KLEER (STT 7).
>
> - **Quyết định:** Lê Nguyễn Quốc An — `2026-10-04`
> - **Căn cứ:** STT 7 đã chốt thương hiệu **KLEER** (`2026-10-11` hạn); code đã merge vào
>   cả `main` và `develop` đều dùng `kleer/*` nhất quán.
> - **Phạm vi áp dụng:** tất cả endpoint L01–L10. Endpoint mới **bắt buộc** dùng `kleer/v1`.

### Việc cần làm sau khi chốt

- [x] Cập nhật `docs/PLUGIN_ARCHITECTURE.md` mục 9 và 10 ghi rõ quyết định.
- [x] Nhân (L01) và các luồng sau dùng `kleer/v1`.
- [ ] PO (Nguyễn Tấn Phát) xác nhận trong sheet `Cần chốt` (STT 1) để đóng hạn `2026-11-01`.

### Lưu ý vận hành

Vì namespace không đổi, **Frontend đã viết trước đó không cần sửa URL**. Ngược lại, nếu
một thành viên đã viết endpoint theo `unisex/v1` thì phải đổi lại `kleer/v1` trước khi merge.
