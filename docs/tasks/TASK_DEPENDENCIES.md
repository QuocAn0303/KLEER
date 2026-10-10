# BẢNG PHÂN TÍCH PHỤ THUỘC & ĐIỀU KIỆN CẦN CÓ CHO CÁC TASK TIẾP THEO
## Dự án: KLEER — Nền tảng TMĐT Skincare Unisex (WooCommerce)
**Người phụ trách:** Bùi Hiếu Nhân (*Plugin Dev 1 — Quiz Backend, Taxonomy, REST*)

---

## 1. Tổng quan tình trạng các task tiếp theo (Tuần 04 đến Tuần 09)

| Mã task | Tuần | Tên việc | Trạng thái phụ thuộc | Khả năng thực hiện |
|---|:---:|---|---|:---:|
| **L01-G4-01** | Tuần 04 | Soạn Prompt cho AI agent sinh mã nguồn Quiz Engine và weighted matching L01 | Phụ thuộc `L01-G3-01` (**Đã hoàn thành**) | **SẴN SÀNG THỰC HIỆN NGAY** |
| **L01-G5-03** | Tuần 04 | Thiết kế cấu trúc câu hỏi và lập trình thuật toán Weighted Tag Matching | Nhận đầu vào từ `L01-G4-01` và Flow Card L01 (**Đã đủ dữ liệu**) | **SẴN SÀNG THỰC HIỆN NGAY** |
| **L01-G5-05** | Tuần 06 | Lập trình Routine Query Engine và đăng ký REST API Endpoint chính thức | Kế thừa hạ tầng CORS/Schema `L01-G6-01` và `L01-G5-03` | **SẴN SÀNG THỰC HIỆN NGAY** |
| **L01-G5-06** | Tuần 08 | Xây dựng Cơ chế Fallback an toàn (Safe Fallback Mechanism) | Tích hợp trực tiếp trong `QuizEngineService` của luồng L01 | **SẴN SÀNG THỰC HIỆN NGAY** |
| **L05-G5-04** | Tuần 09 | Tối ưu hóa truy vấn SQL: Đặt chỉ mục (Index) CSDL & Đo lường API response | **Bắt buộc phụ thuộc vào luồng L05 và môi trường CSDL thực tế** | **PHẢI CHỜ (DEPENDENT)** |

---

## 2. Bảng chi tiết những gì cần có để thực hiện các task phụ thuộc

Dưới đây là đặc tả chi tiết các điều kiện tiên quyết, tài nguyên và sản phẩm từ các luồng khác cần có trước khi có thể triển khai các task phụ thuộc:

### 2.1. Task L05-G5-04: Tối ưu hóa truy vấn SQL & Đặt chỉ mục (Index) Custom Taxonomies (Tuần 09)

- **Mục tiêu task:** Đặt chỉ mục (Index) thích hợp cho các trường dữ liệu hay tìm kiếm trong Custom Taxonomies (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`); Đo lường giảm số truy vấn từ 15 xuống 4; Phản hồi API luôn `< 300ms`.
- **Người duyệt:** Lê Nguyễn Quốc An (Scrum Master & DevOps).

#### Bảng điều kiện tiên quyết cần có (Prerequisites Matrix):

| STT | Hạng mục cần có | Nguồn cung cấp / Người phụ trách | Mô tả chi tiết & Tiêu chuẩn chấp nhận | Tình trạng hiện tại |
|:---:|---|---|---|:---:|
| **1** | **Cơ sở dữ liệu MariaDB đang chạy chứa dữ liệu thực** | Lê Nguyễn Quốc An (*DevOps*) | Container MariaDB 10.11 trong Docker stack đang hoạt động (`docker compose up -d`), có chứa database WordPress/WooCommerce với ít nhất 30 sản phẩm thực tế từ file `Task08_30_Skincare.csv`. | ⏳ Chưa có môi trường DB chạy trên host |
| **2** | **Bộ bảng chuyên biệt WooCommerce HPOS** | Nguyễn Minh Trí (*Plugin Dev 2 — Task L05-G5-03, Tuần 06*) | Cấu hình HPOS đã kích hoạt thành công, chuyển đổi lưu trữ từ `wp_posts` sang các bảng chuyên biệt: `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`. | ⏳ Nguyễn Minh Trí chưa tới Tuần 06 |
| **3** | **Dữ liệu Custom Taxonomies đã được nạp (Seeded)** | Lâm Trúc Uyên (*Theme Dev 1*) / Nguyễn Tấn Phát (*PO*) | 3 Taxonomies (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`) đã được đăng ký và gán terms cụ thể cho các sản phẩm trong bảng `wp_term_relationships`, `wp_term_taxonomy`. | ⏳ Chưa seed dữ liệu vào DB thật |
| **4** | **Script kiểm thử tải & đo lường hiệu năng** | Lê Nguyễn Quốc An (*Task L09*) | Script k6 đo lường độ trễ p50/p95/p99 (`tests/load/kleer-load.js`) và công cụ theo dõi số câu truy vấn (Query Monitor / MySQL Slow Query Log). | ⚠️ Đã có k6 script nhưng cần stack chạy thật |
| **5** | **Quyền can thiệp cấu trúc CSDL (DDL Permissions)** | Lê Nguyễn Quốc An (*DevOps*) | File migration SQL hoặc script hook PHP `dbDelta()` / `ALTER TABLE` có quyền thêm Index trên các bảng MariaDB: `wp_term_relationships (object_id, term_taxonomy_id)`, `wp_postmeta (meta_key, meta_value)`. | ⏳ Chờ cấp quyền và kịch bản migration |

---

### 2.2. Điểm phụ thuộc liên luồng của Luồng L01 đối với các thành viên khác

Mặc dù phần Backend Core của Quiz (`L01-G5-03`, `G5-05`, `G5-06`) có thể lập trình độc lập bằng mô hình Clean Architecture & Mock Repositories, nhưng để **nghiệm thu End-to-End (E2E)** trên toàn hệ thống cần các đầu vào sau từ nhóm:

| Mã task liên quan | Thành viên phụ trách | Vai trò | Sản phẩm bàn giao cần để tích hợp E2E | Mức độ ảnh hưởng |
|---|---|---|---|:---:|
| **L01-G2-01** | Huỳnh Ngọc Nhã Uyên | Theme Dev 2 | File bản vẽ gốc `L01_Quiz_Logic_Flowchart.drawio` và Figma Prototype UI. | Trung bình (Backend đã chủ động chuẩn hóa 5 câu hỏi trong Flow Card L01) |
| **L01-G5-02** | Huỳnh Ngọc Nhã Uyên | Theme Dev 2 | Màn hình SPA Quiz Frontend (HTML5/Vanilla JS) gọi REST API. | Cao (Cần để ghép nối Frontend ↔ Backend ở Tuần 07 - `L01-G6-01`) |
| **L03-G5-01** | Huỳnh Ngọc Nhã Uyên | Theme Dev 2 | Nút "1-Click Add Routine to Cart" đón nhận mảng `recommended_products` từ Quiz. | Cao (Phục vụ trải nghiệm mua Combo giảm 15%) |
| **L01-G7-01** | Nguyễn Ngọc Thảo Uyên | QA & Automation | Bộ 30 Test Cases Selenium kiểm thử tự động toàn diện Quiz. | Trung bình (Dùng để nghiệm thu cuối ở Cổng G7) |
| **L01-G8-01** | Nguyễn Tấn Phát | PO & BA | Bộ 20 kịch bản lâm sàng giả lập (Da dầu mụn, da khô lão hóa...) để nghiệm thu thuật toán. | Cao (Dùng để nghiệm thu kết quả phác đồ ở Cổng G8) |

---

## 3. Kế hoạch hành động khuyến nghị cho Bùi Hiếu Nhân

1. **Giai đoạn ngay bây giờ (Tuần 03 - 04):**
   - Hoàn tất soạn Prompt chuẩn **`PRM-L01-01`** (Task `L01-G4-01`).
   - Tự động thực thi mã nguồn cho:
     - Module mảng cấu hình câu hỏi & trọng số: `src/Config/QuizQuestions.php` (`L01-G5-03`).
     - Engine tính điểm và Safe Fallback: `src/Services/QuizEngineService.php` (`L01-G5-03`, `L01-G5-06`).
     - REST Controller & Endpoints: `src/Controllers/SkinQuizController.php` và `src/Endpoints/SkinQuizEndpoints.php` (`L01-G5-05`).
     - Bổ sung Unit Test Suite trong `tests/run_tests.php`.
2. **Giai đoạn chờ tích hợp (Tuần 07):**
   - Phối hợp với Scrum Master Lê Nguyễn Quốc An (`L01-G6-01`) để test tích hợp CORS và Schema Validation với Frontend của Huỳnh Ngọc Nhã Uyên.
3. **Giai đoạn tối ưu hóa SQL (Tuần 09):**
   - Khi Nguyễn Minh Trí hoàn tất HPOS (`L05-G5-03`) và stack Docker được nạp đủ 30 sản phẩm thực tế, tiến hành viết script thêm DB Index và đo benchmark theo đúng DoD của `L05-G5-04`.
