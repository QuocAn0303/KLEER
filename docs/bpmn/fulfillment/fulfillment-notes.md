# Ghi chú khảo sát – Sơ đồ BPMN "Quy trình hoàn tất đơn hàng" (Fulfillment Flow)

> **Vai trò:** Tool Designer (Người 2) — thay thế Lê Nguyễn Quốc An (DevOps & Infrastructure Lead)
> **Nhóm:** 8 – KLEER | **Môn:** EC312 | **Mẫu:** Mẫu 1 (BPMN 2.0)
> **Ngày khảo sát:** 05/10/2026 | **Mã sơ đồ:** `Process_FulfillmentFlow`
> **Bộ file:** `fulfillment-flow.bpmn`, `fulfillment-flow.drawio`, `fulfillment-task-list.md`,
> `fulfillment-flow-preview.png`, `fulfillment-notes.md` (file này)

---

## 0. Tóm tắt điều hành (đọc trước tiên)

| Chỉ số | Giá trị |
|---|---|
| Task (user/service/receive) | **35** |
| Gateway XOR | **12** |
| Start Event | **1** |
| End Event | **3** |
| Sequence Flow | **62** |
| Pool / Lane | 1 Pool – 3 Lane |
| Kết quả tự kiểm tra (Bước 4) | **133 PASS – 0 WARN – 0 FAIL** |

**Phát hiện quan trọng nhất:** dự án **chưa có bất kỳ mã nguồn nghiệp vụ Fulfillment nào**.
Vì vậy sơ đồ này được dựng trên **nền tảng WooCommerce core (hành vi mặc định có sẵn)** cộng với
**thao tác kho thủ công** thực tế, và mọi bước chưa tồn tại đều được đánh dấu
**`[ĐỀ XUẤT – chưa có trong code]`** — **33 / 51 phần tử** (23 task + 10 gateway).

Sơ đồ vì vậy mô tả **quy trình vận hành thực tế (as-is, gồm thao tác tay)**, không phải một
process tự động hoá hoàn toàn. Đây là chủ ý, không phải thiếu sót.

---

## 1. KẾT QUẢ KHẢO SÁT DỰ ÁN (Bước 0)

### 1.1 Mẫu 2 / sơ đồ nháp / tài liệu quy trình Fulfillment

**KHÔNG TÌM THẤY trong repository.** Đã tìm toàn bộ:

| Tìm kiếm | Kết quả |
|---|---|
| `*.bpmn`, `*.drawio`, `*.mmd`, `*.puml` | 0 file |
| `*.png`, `*.svg`, `*.pdf`, `*.vsdx`, `*.docx`, `*.xlsx` | 0 file (ngoài file preview sinh ra ở Bước 3) |
| `fulfillment`, `pick-list`, `picking`, `packing`, `dispatch`, `3PL`, `GHN`, `GHTK`, `Viettel Post`, `vận đơn` | 0 file mã nguồn (4 kết quả đều là trùng chữ `dispatch` trong `rest_pre_dispatch` của CORS) |
| Thư mục `docs/` | Chỉ có: `PAYMENT_GATEWAYS.md`, `PLUGIN_ARCHITECTURE.md`, `TEST_PLAN.md`, và `docs/flows/{L01-quiz-integration, L03-cart-combo, L09-infra-devops}/flow-card.md` |
| `docs/flows/` | **Không có** flow-card cho Fulfillment (chỉ L01, L03, L09) |

> **Kết luận:** không có Mẫu 2 trong repo. Mẫu 1 (bản này) được dựng **từ đầu** dựa trên mã nguồn
> thật + tài liệu thật. Mục 5 bên dưới liệt kê các quyết định thiết kế có khả năng **khác** với
> bản nháp mà Người 1 dự kiến nộp, để Người 3 và Người 4 đối chiếu.

### 1.2 Nền tảng và công nghệ

| Hạng mục | Kết quả | Nguồn |
|---|---|---|
| Nền tảng | **WordPress + WooCommerce**, chạy Docker | `docker-compose.yml`, `Dockerfile.php`, `README.md` |
| Services | nginx:1.25-alpine, php:8.2-fpm, mariadb:10.11 (port host 3307), redis:7-alpine, phpmyadmin | `docker-compose.yml` |
| Plugin custom | `wp-content/plugins/kleer-plugin` — **duy nhất** có mã PHP trong repo | `wp-content/plugins/kleer-plugin/kleer-plugin.php` |
| Theme | `wp-content/themes/kleer-theme` — **rỗng**, chỉ có `.gitkeep` | `wp-content/themes/kleer-theme/.gitkeep` |
| `mu-plugins` | **Không tồn tại** | (không có thư mục) |
| Namespace | `kleer/v1`, PSR-4 autoloader | `docs/PLUGIN_ARCHITECTURE.md:300`, `src/Plugin.php` |

### 1.3 HPOS — **khai báo tương thích, nhưng CHƯA xác nhận đã BẬT**

- **CÓ** khai báo tương thích HPOS:
  `wp-content/plugins/kleer-plugin/src/Support/Cache/WooCommerceCompatibility.php:87`
  ```php
  $featuresUtil::declare_compatibility(self::FEATURE_HPOS, KLEER_PLUGIN_FILE, true);
  ```
  - `FEATURE_HPOS = 'custom_order_tables'` (dòng 24), đăng ký trên hook `before_woocommerce_init` (dòng 53)
  - Tham số thứ 3 là `true` → khai báo **"positive"** (không phải "experimental")
- **CÓ test xác nhận**: `wp-content/plugins/kleer-plugin/tests/run_tests.php:733, 751-752`
  assert `custom_order_tables` được khai báo với `true`.
- **NHƯNG** bật HPOS vẫn là **checkbox chưa tick** trong checklist hạ tầng:
  `docs/flows/L09-infra-devops/flow-card.md:143`
  `- [ ] Bật HPOS trong WooCommerce → Settings → Advanced → Features.`

> **Kết luận cho Mẫu 1:** sơ đồ đặt giả định **đọc/ghi đơn hàng qua HPOS API**
> (`$order->save()`, `$order->update_status()`, `wc_get_orders()`) thay vì `wp_posts`/`postmeta`,
> vì plugin đã cam kết tương thích. Ghi chú này đã đưa vào `fulfillment-task-list.md`.
> **Người 4 (QA) nên kiểm chứng** bằng cách tick ô trên và chạy lại luồng.

### 1.4 Vòng đời trạng thái đơn hàng — **KHÔNG có status custom nào**

Kết quả quét toàn repo (loại trừ file sinh ra ở Bước 3):

| Mẫu cần tìm | Số hit | Kết luận |
|---|---|---|
| `register_post_status` | **0** | Không có status custom |
| `woocommerce_order_status_*` (mã nguồn) | **0** | Không hook trạng thái đơn |
| `update_status(` / `set_status(` / `payment_complete` | **0** | Không có chỗ nào đổi status bằng code |
| `woocommerce_order_status_changed` (trong **tài liệu**) | 1 | `docs/PAYMENT_GATEWAYS.md:86` – mới chỉ là **gợi ý** ("Sống bằng hook …"), chưa hiện thực |

**Các status có thật trong hệ thống = 7 status mặc định của WooCommerce core:**
`pending`, `processing`, `on-hold`, `completed`, `cancelled`, `refunded`, `failed`

**Bằng chứng về `processing` (điểm khởi đầu của sơ đồ):**
- `docs/PAYMENT_GATEWAYS.md:135` – checkout thử, mong đợi `vnp_ResponseCode=00` / `resultCode=0`
  → **order chuyển `processing`**
- `docs/PAYMENT_GATEWAYS.md:85` – COD: khách chọn COD → đơn **`processing/on-hold`** → shipper giao →
  xác nhận thu tiền (`processing` hoặc `completed`)

> **Kết luận cho Mẫu 1:** Start Event = *"Đơn hàng chuyển sang trạng thái `processing`"* — khớp với
> yêu cầu đề bài (đã thanh toán thành công **hoặc** COD được xác nhận), và có căn cứ trong tài liệu.
> **Không** dùng bất kỳ status riêng nào như `picking` / `packing` / `shipped` vì dự án không có.

### 1.5 Quản lý tồn kho — **KHÔNG có mã quản lý tồn kho**

| Mẫu cần tìm | Số hit |
|---|---|
| `wc_reduce_stock_levels` / `wc_maybe_reduce_stock_levels` | **0** |
| `wc_maybe_increase_stock_levels` (hoàn tồn) | **0** |
| `hold_stock` / `wc_stock_held` (giữ/khóa tồn tạm) | **0** |

**Cần phân biệt để tránh hiểu nhầm:** `CacheInvalidation.php:35-36` có hook
`woocommerce_product_set_stock` và `woocommerce_variation_set_stock`, nhưng callback
`onProductChanged` **chỉ xóa cache Redis**, **không** phải logic quản lý tồn kho
(`wp-content/plugins/kleer-plugin/src/Support/Cache/CacheInvalidation.php:27-41`).

> **Kết luận cho Mẫu 1:** mọi thao tác trừ/hoàn tồn kho trên sơ đồ đều là **hành vi mặc định của
> WooCommerce core** (đã có sẵn, không cần viết code) khi kho bật
> *"Manage stock"* / *"Stock reduce"* → đã ghi rõ trong cột "Ghi chú / Nguồn trong code".
> **Không có bước "giữ tồn tạm"** → sơ đồ không mô tả tính năng không tồn tại.

### 1.6 Tích hợp 3PL / vận chuyển — **KHÔNG có gì**

| Mẫu cần tìm | Kết quả |
|---|---|
| `GHN`, `GHTK`, `vietpost`, `ghnexpress`, `3PL` | **0 hit** trong mã nguồn |
| `woocommerce_shipping`, `shipping_zone` | **0 hit** |
| Webhook (`WC_Webhook`, webhook 3PL) | **0 hit** trong mã nguồn (chỉ có webhook **thanh toán** trong `docs/PAYMENT_GATEWAYS.md:99-133`) |
| Biến môi trường 3PL trong `.env` / `.env.example` | **0** (`.env.example` chỉ có DB, WP salts, Redis, CORS, Mailhog *comment out*) |

> **Kết luận cho Mẫu 1:** **KHÔNG** giả định hệ thống tự động gọi API 3PL. Lane "Đối tác 3PL" và
> toàn bộ `serviceTask` gọi API đều được đánh dấu `[ĐỀ XUẤT – chưa có trong code]`, và đã thiết kế
> **nhánh dự phòng thủ công** (retry có giới hạn → cảnh báo kho → tạo vận đơn tay) để quy trình
> vẫn chạy được khi chưa có 3PL.

### 1.7 Pick-list / gộp đơn — **KHÔNG có chức năng nào**

`pick.?list` / `order_picking` → **0 hit**. Vì vậy sơ đồ mô tả phương án khả thi nhất:
`Task_F06` kết xuất CSV Pick-list (đề xuất) → `Task_F07` nhân viên kho mở file và chốt đơn gộp
(thủ công) → `Task_F08` lấy hàng. Cả ba đều ghi rõ là chưa có trong code.

### 1.8 Email / thông báo

| Mẫu cần tìm | Kết quả |
|---|---|
| `woocommerce_email_*`, `wp_mail` | **0 hit** trong mã nguồn |
| Mail testing | `.env.example` có Mailhog **đang comment**: `# MAILHOG_PORT=1025`, `# MAILHOG_UI_PORT=8025` |

> **Kết luận:** các bước gửi email trên sơ đồ là **email mặc định của WooCommerce core**
> (Processing / Shipped / Completed / Cancelled) — có sẵn, không cần code, nhưng **chưa cấu hình
> SMTP**. Ghi chú đã ghi rõ.

### 1.9 Phân quyền

`add_role` / `add_capability` / `WC_*` → **0 hit**. **Không có role kho tùy chỉnh.**
Vì vậy các `userTask` trong Lane "Nhân sự Kho" được mô tả theo **vai trò nghiệp vụ**
(Shop Manager / nhân viên kho theo mô tả của WooCommerce), không gắn với capability ID cụ thể.

### 1.10 Xác nhận quan trọng: Fulfillment **nằm ngoài phạm vi** kiến trúc đã chốt

`docs/PLUGIN_ARCHITECTURE.md:61-66` liệt kê **Out-of-Scope (dành cho các sprint tiếp theo)**:

- "Tích hợp cổng thanh toán thực tế (VNPay, MoMo, Stripe Sandbox/Live)."
- "Toàn bộ nghiệp vụ CRUD sản phẩm, giỏ hàng, đặt hàng (**sẽ triển khai khi có tài liệu phân tích
  nghiệp vụ chính thức**)."

`docs/PLUGIN_ARCHITECTURE.md:246` chỉ mô tả luồng **Health Check** là `[Đã triển khai - Implemented]`,
luồng truy vấn danh sách sản phẩm là `[Hoạch định - Planned]`.

> **Đây là căn cứ mạnh nhất cho toàn bộ nội dung `[ĐỀ XUẤT]`**: dự án *tự khai* rằng chưa có tài liệu
> phân tích nghiệp vụ đặt hàng. **Mẫu 1 (sơ đồ BPMN) chính là tài liệu phân tích nghiệp vụ đó**,
> nên vai trò của Người 1 là *đề xuất quy trình chuẩn*, chưa phải mô tả code sẵn có.

---

## 2. GIẢ ĐỊNH VÀ CÁC MỤC `[ĐỀ XUẤT – chưa có trong code]`

**Tổng: 33 phần tử** (23 task + 10 gateway) được đánh dấu đề xuất.
Danh sách đầy đủ nằm ở cột *"Ghi chú / Nguồn trong code"* của `fulfillment-task-list.md`.

### 2.1 Các giả định nền (áp dụng cho toàn bộ sơ đồ)

| # | Giả định | Cơ sở |
|---|---|---|
| A1 | Trạng thái đơn dùng **7 status mặc định** của WooCommerce, không có status custom | 1.4 |
| A2 | Đọc/ghi đơn qua **HPOS API** | 1.3 |
| A3 | Trừ/hoàn tồn kho do **WooCommerce core** đảm nhiệm khi bật Manage stock | 1.5 |
| A4 | Email do **WooCommerce core** gửi (chưa cấu hình SMTP) | 1.8 |
| A5 | **Chưa có 3PL nào** → mọi tích hợp 3PL là đề xuất, và **luôn có nhánh thủ công** | 1.6 |
| A6 | **Chưa có role kho** → `userTask` mô tả vai trò nghiệp vụ, không gắn capability | 1.9 |
| A7 | Đơn vị tiền tệ VND, COD là phương thức thanh toán chính (chưa có sandbox VNPay/MoMo đăng ký) | `docs/PAYMENT_GATEWAYS.md:121-125` (cả 4 dịch vụ đều ⬜ "Chờ đăng ký") |

### 2.2 Nhóm đề xuất theo miền nghiệp vụ

**Nhóm 1 – Hàng đợi & Pick-list (chưa có gì):**
`Task_F04` hàng đợi xuất kho · `Task_F06` kết xuất CSV Pick-list · `Task_F07` chốt đơn gộp ·
`Task_F08` lấy hàng (Picking) · `Task_F09` đối chiếu số lượng thực lấy

**Nhóm 2 – Xử lý lệch tồn kho (chưa có gì):**
`GW_F02` đối chiếu số lượng · `Task_F10` kiểm kê & ghi nhận lệch tồn · `GW_F05` chọn cách xử lý ·
`Task_F11` đổi SKU thay thế · `Task_F12` tách lô giao một phần · `Task_F33` ghi nhận yêu cầu nhập bổ sung

**Nhóm 3 – Hỏng/hư hỏng & kiểm đóng gói (chưa có gì):**
`Task_F26` đóng gói (Packing) · `GW_F06` hàng nguyên vẹn

**Nhóm 4 – Hủy đơn theo thời điểm (chưa có gì):**
`GW_F07` khách có yêu cầu hủy (trước khi bàn giao 3PL) · `GW_F09` yêu cầu hủy/đổi địa chỉ
(sau khi bàn giao 3PL) · `Task_F36` chuyển thành đơn hoàn hàng

**Nhóm 5 – Tích hợp 3PL & retry (chưa có gì):**
`Task_F19` gọi API tạo vận đơn · `GW_F10` thành công/thất bại · `Task_F21` retry ·
`GW_F11` đã đủ số lần retry · `Task_F35` cảnh báo kho & admin · `Task_F28` tạo vận đơn tay ·
`Task_F20` ghi mã vận đơn · `Task_F30` 3PL tiếp nhận vận đơn · `Task_F23` nhận webhook ·
`Task_F29` bàn gàng hàng

**Nhóm 6 – Hoàn tiền (chưa có gì):**
`Task_F41` hoàn tiền đơn giao thất bại · `Task_F40` email hoàn tiền ·
(`Task_F14` hoàn tiền đơn bị hủy – *gợi ý trong `docs/PAYMENT_GATEWAYS.md:107` có mục `Refund` của VietQR*)

### 2.3 Tham số vận hành đề xuất (Người 3 nên đưa vào tài liệu)

| Tham số | Giá trị đề xuất | Vì sao |
|---|---|---|
| Số lần retry tối đa khi gọi API 3PL | **3 lần** (khoảng cách 5 phút) | `docs/PAYMENT_GATEWAYS.md:73` yêu cầu timeout ≥ 30 s với MoMo → cần backoff, không retry vô hạn |
| Thời điểm cho phép hủy đơn | **Trước khi bàn giao 3PL** (tức trước `Task_F29`) | Khớp `docs/PAYMENT_GATEWAYS.md:87` (COD rủi ro không xác minh tiền trước → cần chốt sớm) |
| Số lần giao lại | **1 lần** rồi chuyển sang hoàn kho & hoàn tiền | `GW_F13` |
| Mức cảnh báo cho kho | Ghi chú đơn + thông báo trong trang đơn hàng (WooCommerce order note) | Không có hệ thống thông báo nội bộ (`docs/PLUGIN_ARCHITECTURE.md:320` mới chỉ nói REST API) |

---

## 3. Quyết định thiết kế chính (để Người 3 viết Mẫu 3 và Người 4 nghiệm thu)

### 3.1 Vì sao **1 Pool – 3 Lane**, không tách 3PL thành Pool riêng

Theo yêu cầu đề bài và để khớp Mẫu 1. Ghi chú cho Người 3/4:

- Tách 3PL thành Pool riêng sẽ **bắt buộc** dùng **Message Flow** thay cho Sequence Flow giữa
  Lane 1 và Lane 3. Về nghiệp vụ điều này *đúng* (giao tiếp với bên ngoài qua API/webhook),
  nhưng nó **không khớp với Mẫu 2/bản nháp** (nếu bản nháp vẽ chung Pool).
- Vì 3PL **chưa tồn tại** trong code, mô hình chung Pool giúp sinh viên dễ đối chiếu logic
  mà không phải tranh luận về Message Flow.
- **Nếu nhóm muốn nâng cấp**: thay `Task_F29 → Task_F30` và `Task_F30 → Task_F23` bằng Message Flow
  (đường nét đứt) khi tách Pool. ID giữ nguyên để không phá vỡ đối chiếu 1-1.

### 3.2 Vì sao có **3 End Event** thay vì 1

Theo yêu cầu: (a) giao thành công, (b) hủy đơn, (c) giao thất bại. Mỗi End gắn 1 trạng thái
WooCommerce khác nhau nên **không thể gộp**:

| End Event | Tên | Trạng thái cuối |
|---|---|---|
| `End_F01` | Giao thành công – Completed | `completed` |
| `End_F02` | Đơn bị hủy – đã hoàn tồn và hoàn tiền | `cancelled` (+ `refunded` nếu đã thu tiền) |
| `End_F03` | Giao thất bại – hoàn kho và hoàn tiền | `refunded` |

### 3.3 Vì sao hủy đơn có **hai** Gateway (`GW_F07` và `GW_F09`)

Đây là điểm cố ý hiện thực yêu cầu *"kiểm tra thời điểm trước hay sau khi đã đóng gói/bàn giao 3PL"*
một cách **máy kiểm được** thay vì chỉ ghi chú:

| Gateway | Vị trí | Hệ quả |
|---|---|---|
| `GW_F07` | Sau `Task_F26` (đóng gói), **trước** khi gọi API 3PL | Hủy được → `Task_F13` hủy đơn & hoàn tồn kho |
| `GW_F09` | Sau `Task_F23` (webhook 3PL), **tức đã bàn giao** | **Không** hủy trực tiếp được → `Task_F36` chuyển thành **đơn hoàn hàng** |

### 3.4 Vì sao hoàn tiền có **hai** task riêng (`Task_F14` và `Task_F41`)

Hai End Event khác nhau nên cần hai hoạt động hoàn tiền khác nhau để **không nhập nhằng nguyên nhân**:

- `Task_F14` "Hoàn tiền **đơn bị hủy**" → `Task_F15` email hủy đơn → `End_F02`
- `Task_F41` "Hoàn tiền **đơn giao thất bại**" → `Task_F40` email hoàn tiền → `End_F03`

Cả hai đều đi qua logic kiểm tra thu tiền: `GW_F14` "Đơn đã thu tiền trước?" rẽ nhánh
COD chưa thu tiền → bỏ qua bước hoàn tiền, chỉ gửi email (khớp `docs/PAYMENT_GATEWAYS.md:87`).

### 3.5 Vì sao `Task_F04` có **4** luồng vào (Task lặp)

`Task_F04` (ghi nhận vào hàng đợi xuất kho) là **điểm hợp lệ** của 4 nhánh:

1. Luồng chính sau khi đủ tồn kho (`Flow_F04`)
2. Nhánh "kiểm kê đủ hàng thực tế" sau khi chuyển `on-hold` (`Flow_F07`)
3. Nhánh "chờ nhập hàng" → quay lại gộp đơn (`Flow_F24`) – **vòng lặp hợp lệ**
4. Nhánh "giao lại" sau khi hàng hoàn về kho (`Flow_F51`) – **vòng lặp hợp lệ**

> Đây là **vòng lặp (loop) có kiểm soát**, không phải dead end: mỗi lần quay lại đều qua một
> kiểm tra lệch tồn kho (`GW_F01`/`GW_F04`/`GW_F05`) nên không thể lặp vô hạn trong một lượt giao.
> **Không dùng Boundary Timer Event** vì đề bài cho phép "chỉ khi cần thiết" và các tham số
> timeout chưa được chốt trong tài liệu — thêm vào sẽ tạo giả định không có căn cứ.

---

## 4. ĐIỂM KHÁC BIỆT VỚI BẢN NHÁP / MẪU 2 VÀ LÝ DO

> **Lưu ý:** vì không tìm thấy Mẫu 2 trong repo (mục 1.1), bảng này liệt kê các quyết định
> **khác với cách vẽ sơ đồ nháp thông thường** — tức là những chỗ Người 1 và Người 2 có thể
> đã ra hai kết quả khác nhau. Người 3/4 nên ưu tiên kiểm tra các dòng này khi đối chiếu.

| # | Điểm khác biệt | Lý do |
|---|---|---|
| 1 | **Không dùng status custom** (`picking`, `packing`, `shipped`) | Mục 1.4: dự án không có `register_post_status`. Dùng status riêng sẽ mô tả tính năng không tồn tại. |
| 2 | **Không tách Pool cho 3PL** | Khớp đề bài/Mẫu 2; xem 3.1. |
| 3 | **Pick-list là CSV + thao tác tay**, không phải chức năng gộp đơn tự động | Mục 1.7: không có mã gộp đơn. Bản nháp thường vẽ Pick-list như một module tự động. |
| 4 | **Có nhánh "retry giới hạn → cảnh báo kho → tạo vận đơn tay"** | Đề bài yêu cầu rõ. Bản nháp thường vẽ thẳng "gọi API 3PL" rồi sang giao hàng. |
| 5 | **Có 2 gateway hủy đơn** (trước/sau bàn giao 3PL) | Mục 3.3. Bản nháp thường chỉ có 1 gateway hủy đơn ở đầu quy trình. |
| 6 | **Giao thất bại có vòng lặp "giao lại" trước khi hoàn tiền** | `GW_F13` → `Task_F04`. Thực tế ngành TMĐT VN thường thử giao lại 1 lần. |
| 7 | **Lane "Nhân sự Kho" chứa cả quyết định nghiệp vụ** (đổi SKU, tách lô, tạo vận đơn tay) | Không có hệ thống quyết định tự động; đúng vai trò nghiệp vụ của kho. |
| 8 | **3 End Event** thay vì 1 | Mục 3.2. |
| 9 | **Không dùng Boundary Timer Event** | Mục 3.5: chưa chốt tham số timeout → tránh giả định. |
| 10 | **Mọi task có ID `Task_Fxx` cố định, không đánh số lại theo thứ tự vẽ** | Để Người 3 và Người 4 đối chiếu 1-1 giữa `.bpmn`, `.drawio` và `fulfillment-task-list.md`. |
| 11 | **Mọi XOR đều có nhánh `default`** | Đề bài yêu cầu "không dead end"; nhánh mặc định bảo đảm luôn có đường đi. |
| 12 | **3 file được sinh tự động từ cùng một mô hình dữ liệu** | Bảo đảm `.bpmn` / `.drawio` / `.md` **không thể lệch nhau** (Bước 4 đã verify 100%). |

---

## 5. BẢNG ĐỐI CHIẾU NHANH VỚI YÊU CẦU ĐỀ BÀI

| Yêu cầu đề bài | Trạng thái | Vị trí trên sơ đồ |
|---|---|---|
| Start = chuyển `Processing` | ✔ | `Start_F01` |
| End (a) giao thành công/Completed | ✔ | `End_F01` |
| End (b) hủy đơn – đã hoàn tồn | ✔ | `End_F02` |
| End (c) giao thất bại – hoàn kho/tiền | ✔ | `End_F03` |
| Tiếp nhận đơn Processing | ✔ | `Task_F01`, `Task_F02` |
| Gộp vào Pick-list | ✔ | `Task_F04`, `Task_F06`, `Task_F07` |
| Picking | ✔ | `Task_F08`, `Task_F09` |
| Đối chiếu tồn kho vật lý | ✔ | `Task_F10` |
| Packing | ✔ | `Task_F26` |
| Gọi API tạo vận đơn 3PL | ✔ (đề xuất) | `Task_F19` |
| 3PL nhận và giao | ✔ | `Task_F30`, `Task_F31` |
| Cập nhật Completed | ✔ | `Task_F17` |
| Gửi email thông báo | ✔ | `Task_F18`, `Task_F22`, `Task_F15`, `Task_F40` |
| **Ngoại lệ 1:** lệch tồn kho vật lý | ✔ | `GW_F02` → `Task_F10` → `GW_F05` (4 cách xử lý) |
| **Ngoại lệ 2:** hàng hỏng/hư hỏng | ✔ | `GW_F06` → `Task_F10` (lúc đóng gói); lúc lấy hàng → `GW_F02` |
| **Ngoại lệ 3:** khách hủy khi đang xử lý | ✔ | `GW_F07` (trước 3PL) + `GW_F09` (sau 3PL) → hoàn tồn qua `Task_F13` |
| **Ngoại lệ 4:** API 3PL thất bại | ✔ | `GW_F10` → `Task_F21` → `GW_F11` → `Task_F35` → `Task_F28` |
| **Ngoại lệ 5:** 3PL giao thất bại | ✔ | `GW_F12` → `Task_F32` → `Task_F24` → `GW_F13` |
| Không dead end, mọi XOR đủ nhánh + default | ✔ | Đã kiểm tự động (Bước 4) |

---

## 6. GIỚI HẠN VÀ VIỆC CẦN LÀM TIẾP

**Giới hạn của Mẫu 1 (cần nêu rõ khi bảo vệ đồ án):**

1. Sơ đồ là **đề xuất chuẩn hóa quy trình**, không phải mô tả hệ thống đang chạy — vì dự án
   chưa có mã nghiệp vụ Fulfillment và còn tự khai nằm ngoài phạm vi
   (`docs/PLUGIN_ARCHITECTURE.md:61-66`).
2. **33 / 51 phần tử là `[ĐỀ XUẤT]`** — đây là con số Người 3 và Người 4 cần nói rõ trong Mẫu 3
   và Biên bản nghiệm thu, tránh bị hiểu là "hệ thống đã tự động hoá toàn bộ".
3. Chưa chốt được tham số vận hành định lượng (số lần retry, số lần giao lại) — mục 2.3 đề xuất
   để nhóm thống nhất.

**Việc cần làm tiếp (không thuộc phạm vi Người 2):**

| Việc | Người phụ trách |
|---|---|
| Mẫu 3 – đặc tả nghiệp vụ chi tiết | Người 3 – Technical Writer |
| Biên bản nghiệm thu, checklist đối chiếu | Người 4 – QA |
| Tick ô "Bật HPOS" và kiểm chứng luồng trên môi trường thật | Nhóm DevOps |
| Quyết định có mua tích hợp 3PL (GHN/GHTK) hay giữ phương án tạo vận đơn tay | Nhóm Product/Ops |

---

## 7. CÁCH TỰ KIỂM TRA LẠI (Người 4 dùng được)

Script kiểm tra đã chạy trên chính file `fulfillment-flow.bpmn` (không kiểm tra trên mô hình
in-memory) với kết quả **133 PASS – 0 WARN – 0 FAIL**, gồm:

1. XML well-formed; encoding `UTF-8`; không có ký tự thay thế hay dấu hiệu moj UTF-8
2. 240 `id`, **không trùng lặp**
3. Cả **62** `sourceRef`/`targetRef` đều trỏ tới phần tử tồn tại
4. `incoming`/`outgoing` khớp đúng số sequence flow ở **51** node
5. **Không** node mồ côi, **không** dead end; **mọi** phần tử đều tới được một End Event
6. Cả **12** XOR đều có nhãn điều kiện **và** nhánh `default`
7. **55** BPMNShape + **62** BPMNEdge, mỗi edge ≥ 2 waypoint, mọi Bounds hợp lệ
8. **51/51** `flowNodeRef` nằm đúng trong Bounds của lane được khai báo
9. **Không** shape nào chồng lên shape khác; mọi shape nằm trong Pool
10. **Không** sequence flow nào cắt xuyên shape (kiểm tra tọa độ từng đoạn)
11. Số task trong `.bpmn` khớp **100%** với `fulfillment-task-list.md` (35 task),
    khớp cả start/end/gateway/flow; mọi dòng đều có cột "Ghi chú / Nguồn trong code"

> **Lưu ý về file `.drawio`:** cùng bộ dữ liệu, dùng thư viện shape BPMN của draw.io
> (`mxgraph.bpmn.*`). Đã kiểm tra: 55 vertex, 62 edge, **không** tham chiếu hỏng.
> Các shape `userTask` / `serviceTask` / `receiveTask` và `exclusiveGw` dùng đúng tên stencil
> chính thức của draw.io; nếu phiên bản draw.io của bạn hiển thị task không có icon đặc trưng,
> hãy chọn lại shape từ panel **BPMN 2.0 → Task**. Sơ đồ `.bpmn` là bản chuẩn để nộp.