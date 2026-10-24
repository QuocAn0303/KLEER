# BIÊN BẢN PHÊ DUYỆT CỔNG G3 (GATE 3 APPROVAL)
## LUỒNG L01 — QUIZ CHẨN ĐOÁN DA & GỢI Ý ROUTINE CHĂM SÓC DA UNISEX

- **Mã task:** `L01-G3-01`
- **Mã luồng:** `L01` (Quiz chẩn đoán da & Phác đồ Routine)
- **Cổng kiểm soát:** `G3` — Nghiệm thu nghiệp vụ & kỹ thuật (Definition of Ready for Prompting)
- **Chủ sở hữu luồng (Flow Owner):** Bùi Hiếu Nhân — *Plugin Dev 1 (Quiz Backend, Taxonomy, REST)*
- **Người chủ trì thẩm định & phê duyệt:**
  - **Nguyễn Tấn Phát** — *Product Owner & Business Analyst (PO & BA)*
  - **Lê Nguyễn Quốc An** — *Scrum Master & DevOps (SM & DevOps)*
- **Ngày thẩm định:** 10/10/2026 (Tuần 03 — Sprint 1)
- **Hạn chót task:** 11/10/2026
- **Trạng thái phê duyệt:** ✅ **CHÍNH THỨC THÔNG QUA (8/8 TIÊU CHÍ DoR ĐẠT)**
- **Quyết định:** Chuyển trạng thái luồng `L01` sang **SẴN SÀNG PROMPT (READY FOR PROMPT - CỔNG G4)**

---

## 1. Mục đích biên bản

Biên bản này ghi nhận kết quả rà soát, thẩm định toàn diện tính nhất quán, khả thi và mức độ hoàn thiện về mặt nghiệp vụ lẫn kỹ thuật đối với luồng **L01 (Smart Skin Quiz)** trước khi chuyển giao sang Cổng G4 (Soạn Prompt cho AI agent sinh mã nguồn).

Việc thẩm định căn cứ trên 3 trụ cột tài liệu đầu vào:
1. **Flow Card Luồng L01:** Đặc tả chi tiết tại [`docs/flows/L01-quiz-routine/flow-card.md`](file:///c:/Users/Lenovo/Documents/STUDY/HK%201%2026%20-%2027/EC312%20-%20Thi%E1%BA%BFt%20k%E1%BA%BF%20h%E1%BB%87%20th%E1%BB%91ng%20TM%C4%90T/Project/project/KLEER/docs/flows/L01-quiz-routine/flow-card.md).
2. **Sơ đồ luồng logic (Quiz Flowchart):** Bản vẽ logic rẽ nhánh câu hỏi và luồng dữ liệu [`L01_Quiz_Logic_Flowchart.drawio`](file:///c:/Users/Lenovo/Documents/STUDY/HK%201%2026%20-%2027/EC312%20-%20Thi%E1%BA%BFt%20k%E1%BA%BF%20h%E1%BB%87%20th%E1%BB%91ng%20TM%C4%90T/Project/project/KLEER/docs/flows/L01-quiz-routine/).
3. **Ma trận trọng số câu hỏi (Quiz Decision Matrix):** Bảng gán điểm trọng số 5 câu hỏi cốt lõi ánh xạ trực tiếp sang 3 Custom Taxonomies của WooCommerce.

---

## 2. Bảng đánh giá 8/8 Tiêu chí Definition of Ready (DoR)

| STT | Tiêu chí DoR | Yêu cầu chuẩn | Kết quả thẩm định | Trạng thái |
|:---:|:---|:---|:---|:---:|
| **DoR 1** | **Mục tiêu & Phạm vi nghiệp vụ (Business Scope)** | Xác định rõ giá trị đem lại, đối tượng người dùng Unisex, hành trình 60 giây không reload trang, liên kết luồng Giỏ hàng `L03`. | Đã định nghĩa hoàn chỉnh trong Flow Card L01 Mục 1 & 2. Tích hợp trực tiếp nút "Thêm trọn bộ Routine" (1-Click) sang giỏ hàng WooCommerce. | **ĐẠT** |
| **DoR 2** | **Flow Card nghiệp vụ chi tiết (Business Flow Card)** | Phân định rõ Trigger, Happy Path, Alternate Path, Fallback Path, các trạng thái UX/UI của màn hình kết quả. | Tài liệu `docs/flows/L01-quiz-routine/flow-card.md` mô tả chuẩn xác chu trình 5 bước câu hỏi và cấu trúc trả kết quả. | **ĐẠT** |
| **DoR 3** | **Sơ đồ luồng & Kiến trúc tương tác (Flowchart & Architecture)** | Bản vẽ sơ đồ khối rẽ nhánh câu hỏi và sơ đồ tuần tự API REST giữa SPA Frontend và Backend Plugin. | Đã rà soát `L01_Quiz_Logic_Flowchart.drawio` và chu trình request lifecycle phù hợp với `docs/PLUGIN_ARCHITECTURE.md`. | **ĐẠT** |
| **DoR 4** | **Ma trận quyết định & Thuật toán tính điểm (Decision Matrix & Algorithm)** | Bộ câu hỏi cốt lõi, công thức cộng dồn trọng số, ánh xạ ra 4 nhóm da chính và 4 vấn đề da mục tiêu. | Bộ 5 câu hỏi chuẩn hóa kèm trọng số điểm chi tiết. Thuật toán phân loại vector điểm da liễu rõ ràng, không có trường hợp bất định. | **ĐẠT** |
| **DoR 5** | **Ánh xạ CSDL & Custom Taxonomies (Data Mapping)** | Ánh xạ đồng nhất với 3 Custom Taxonomies: `pa_skin_type`, `pa_skin_concern`, `pa_routine_step` và danh mục sản phẩm. | 3 Taxonomies đã được chuẩn hóa khớp với CSDL sản phẩm (`Task08_30_Skincare.csv`), phủ kín 30 sản phẩm mẫu của thương hiệu KLEER. | **ĐẠT** |
| **DoR 6** | **Hợp đồng API & Schema Validation (API Contract & Schemas)** | Hợp đồng JSON rõ ràng, xác thực dữ liệu 2 đầu, phân định mã lỗi HTTP, CORS whitelist và CSRF nonce. | Sử dụng hợp đồng JSON Schema duy nhất (`quiz-submission.schema.json` và `quiz-submission-response.schema.json`), endpoint `POST /wp-json/kleer/v1/skin-quiz`, namespace `kleer/v1` chốt theo STT 1. | **ĐẠT** |
| **DoR 7** | **Cơ chế Fallback an toàn (Safe Fallback & Resilience)** | 100% tổ hợp câu trả lời ngẫu nhiên đều nhận được Routine hợp lệ; không bao giờ bị kết quả rỗng hay trang trắng. | Thuật toán 3 tầng ưu tiên (Exact Match -> Partial Match -> Safe Fallback Gentle/All Skin Types) bảo đảm luôn trả về đủ 3 hoặc 4 sản phẩm. | **ĐẠT** |
| **DoR 8** | **Ràng buộc kỹ thuật & Tiêu chí nghiệm thu (Technical Constraints & BDD)** | Tuân thủ OOP phân tầng (`Kleer\Endpoints`, `Kleer\Controllers`, `Kleer\Services`), PSR-12, phản hồi < 300ms, kịch bản Given-When-Then. | Cung cấp đủ 4 ca kiểm thử BDD mẫu, thời gian phản hồi cam kết < 300ms với bộ đệm `KleerCache` nhóm `kleer_quiz`. | **ĐẠT** |

**Tổng kết tiêu chí:** **8/8 ĐẠT (100%)**

---

## 3. Rà soát tính nhất quán đa luồng (Cross-Flow Consistency Review)

Ban thẩm định đã đối chiếu chéo luồng `L01` với các luồng phụ thuộc liên quan trong dự án:

1. **Khớp nối với Luồng L01 Hạ tầng tích hợp (`L01-G6-01`):**
   - Đã kiểm tra tính tương thích giữa `SkinQuizController` dự kiến và trait `ValidatesJsonPayload`.
   - Xác nhận cơ chế CORS Whitelist và kiểm tra CSRF Nonce đã sẵn sàng tại tầng `Kleer\Support\Cors\CorsService`.
   - Xác nhận quyết định chốt STT 1: Giữ nguyên REST namespace `kleer/v1` và thư mục `wp-content/plugins/kleer-plugin/`.

2. **Khớp nối với Luồng L03 Giỏ hàng & Combo Routine (`L03-G1-01`):**
   - Màn hình kết quả của Quiz trả về danh sách `recommended_products` có chứa các `id` sản phẩm và trường `routine_step`.
   - Dữ liệu này khớp hoàn toàn với đầu vào của tính năng "1-Click Add Routine to Cart" của Luồng L03 để kích hoạt tự động giảm giá 15% Combo Routine.

3. **Khớp nối với Danh mục sản phẩm ban đầu (`Task08_30_Skincare.csv`):**
   - Kiểm tra 30 sản phẩm hiện tại: Đã phủ đủ 4 nhóm bước (`Cleanser`, `Toner`, `Serum/Treatment`, `Sunscreen`) cho cả 4 loại da (`Dầu`, `Khô`, `Hỗn hợp`, `Nhạy cảm`).
   - Dòng sản phẩm dịu nhẹ (Cetaphil, CeraVe Hydrating, Simple, Vichy 89) đóng vai trò chốt chặn hoàn hảo cho Cơ chế Safe Fallback.

---

## 4. Kế hoạch hành động tiếp theo (Cổng G4 — Tuần 04)

Với việc Cổng G3 được phê duyệt chính thức, luồng L01 đủ điều kiện chuyển giao sang Cổng G4:

1. **Task L01-G4-01 (Tuần 04):** Soạn Prompt cho AI agent sinh mã nguồn Quiz Engine và weighted matching L01.
   - **Mã Prompt:** `PRM-L01-01` (ghi vào tab Prompt Log).
   - **Nội dung:** Yêu cầu AI agent sinh class `SkinQuizEndpoints`, `SkinQuizController`, và `QuizEngineService` kế thừa kiến trúc đã định nghĩa tại `docs/PLUGIN_ARCHITECTURE.md`.
2. **Task L01-G5-03 (Tuần 04):** Lập trình thuật toán Weighted Tag Matching và module cấu hình mảng câu hỏi PHP.
3. **Task L01-G5-05 (Tuần 06):** Lập trình Routine Query Engine và đăng ký REST API Endpoint chính thức.

---

## 5. Chữ ký xác nhận và Phê duyệt

| Vai trò | Họ và tên | Chữ ký / Xác nhận | Ngày phê duyệt |
|---|---|:---:|:---:|
| **Plugin Dev 1 (Người thực hiện L01)** | **Bùi Hiếu Nhân** | *Đã ký (Bùi Hiếu Nhân)* | 10/10/2026 |
| **Product Owner & BA (Người duyệt G3)** | **Nguyễn Tấn Phát** | *Đã phê duyệt (Nguyễn Tấn Phát)* | 10/10/2026 |
| **Scrum Master & DevOps** | **Lê Nguyễn Quốc An** | *Đã xác nhận kỹ thuật (Lê Nguyễn Quốc An)* | 10/10/2026 |

> **KẾT LUẬN CHÍNH THỨC:**  
> Luồng **L01 — Quiz chẩn đoán da & Phác đồ Routine** được **CHẤP THUẬN THÔNG QUA CỔNG G3**.  
> Trạng thái luồng chuyển thành: **SẴN SÀNG PROMPT (READY FOR PROMPT)**.
