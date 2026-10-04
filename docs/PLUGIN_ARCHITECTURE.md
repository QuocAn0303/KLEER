# KLEER Plugin Architecture Specification

> **Dự án:** KLEER — Cửa hàng Skincare Unisex (WooCommerce / WordPress)  
> **Học phần:** EC312 — Thiết kế hệ thống Thương mại Điện tử  
> **Chủ sở hữu Task (Task Owner):** Bùi Hiếu Nhân  
> **Giai đoạn:** Tuần 1 — Nền tảng kiến trúc (Architecture Foundations)  
> **Phiên bản tài liệu:** 1.1.0  
> **Cập nhật gần nhất:** `L01-G6-01` — bổ sung tầng `Support` (CORS + JSON Schema validation)  
> **Yêu cầu môi trường:** PHP 8.2+ | WordPress 6.x+ | WooCommerce 8.x+

---

## Mục lục

1. [Mục đích kiến trúc (Purpose)](#1-mục-đích-kiến-trúc-purpose)
2. [Phạm vi kiến trúc (Scope)](#2-phạm-vi-kiến-trúc-scope)
3. [Tổng quan kiến trúc (Architecture Overview)](#3-tổng-quan-kiến-trúc-architecture-overview)
4. [Cấu trúc thư mục (Folder Structure)](#4-cấu-trúc-thư-mục-folder-structure)
5. [Ma trận trách nhiệm (Responsibility Matrix)](#5-ma-trận-trách-nhiệm-responsibility-matrix)
6. [Quy tắc phụ thuộc (Dependency Rules)](#6-quy-tắc-phụ-thuộc-dependency-rules)
7. [Ranh giới giữa Plugin và Theme (Plugin vs. Theme Boundary)](#7-ranh-giới-giữa-plugin-và-theme-plugin-vs-theme-boundary)
8. [Chu trình xử lý yêu cầu (Request Lifecycle)](#8-chu-trình-xử-lý-yêu-cầu-request-lifecycle)
9. [Quy ước REST API và Endpoints (REST API Conventions)](#9-quy-ước-rest-api-và-endpoints-rest-api-conventions)
10. [Quy ước đặt tên và Namespaces (Naming Conventions)](#10-quy-ước-đặt-tên-và-namespaces-naming-conventions)
11. [Danh mục Endpoints (Endpoint Catalog)](#11-danh-mục-endpoints-endpoint-catalog)
12. [Hướng dẫn mở rộng (Developer Extension Guide)](#12-hướng-dẫn-mở-rộng-developer-extension-guide)
13. [Ví dụ minh họa luồng hoàn chỉnh (End-to-End Architectural Example)](#13-ví-dụ-minh-họa-luồng-hoàn-chỉnh-end-to-end-architectural-example)
14. [Kiểm thử và Tiêu chuẩn chấp nhận (Testing & Acceptance)](#14-kiểm-thử-và-tiêu-chuẩn-chấp-nhận-testing--acceptance)
15. [Giải đáp 14 câu hỏi then chốt (Architectural Q&A)](#15-giải-đáp-14-câu-hỏi-then-chốt-architectural-qa)

---

## 1. Mục đích kiến trúc (Purpose)

Website thương mại điện tử mỹ phẩm Unisex **KLEER** đòi hỏi sự linh hoạt cao trong việc thay đổi nhận diện thương hiệu (Theme) mà không làm gián đoạn hoặc thất thoát logic nghiệp vụ bán hàng (E-commerce Business Logic).

Nếu xây dựng theo phong cách WordPress truyền thống (nhồi nhét toàn bộ hàm nghiệp vụ vào file `functions.php` của Theme), hệ thống sẽ gặp các rủi ro nghiêm trọng:
- **Tight Coupling:** Đổi Theme đồng nghĩa với việc mất toàn bộ tính năng nghiệp vụ, API và tích hợp thanh toán.
- **Khó kiểm thử tự động (Untestable):** Các hàm phân mảnh, truy cập biến toàn cục (`global $wpdb`, `global $post`) không thể viết Unit Test độc lập.
- **Vi phạm SRP (Single Responsibility Principle):** Một file gánh vác cả HTML template, database query, validation và API routing.

**Giải pháp:** Xây dựng **KLEER Custom Plugin (`kleer-plugin`)** theo mô hình **OOP Phân tầng (Layered Architecture)**, áp dụng các nguyên lý **SOLID**:
- Tách biệt rõ ràng 4 tầng: **Endpoints**, **Controllers**, **Services**, **Models**, cùng với **Contracts (Interfaces)** và **Repositories**.
- Đảm bảo Plugin là nguồn sự thật duy nhất (Single Source of Truth) cho toàn bộ Business Logic và REST API của KLEER.
- Đảm bảo Theme hoàn toàn độc lập, chỉ tập trung vào tầng hiển thị (Presentation Layer).

---

## 2. Phạm vi kiến trúc (Scope)

### Trong phạm vi (In-Scope):
- Thiết kế và chuẩn hóa cấu trúc thư mục OOP cho `kleer-plugin`.
- Tách bạch ranh giới trách nhiệm giữa **Endpoints**, **Controllers**, **Services**, **Models**, và **Contracts**.
- Cơ chế khởi động Plugin (`Plugin.php` và `kleer-plugin.php`).
- Phân định ranh giới tuyệt đối giữa Theme (`kleer-theme`) và Plugin (`kleer-plugin`).
- Chuẩn hóa quy ước REST API (Namespace `kleer/v1`, HTTP methods, permission callbacks, response shaping).
- Cung cấp Endpoint mẫu đã thực thi (`GET /kleer/v1/health`) và danh mục các Endpoint hoạch định (`Planned`).
- Quy trình chuẩn (Extension Guide) để các lập trình viên khác phát triển tính năng mới.
- Bộ kiểm thử kiến trúc (Architecture Verification Test Suite) và PHP 8.2 linting.

### Ngoài phạm vi (Out-of-Scope - Dành cho các sprint tiếp theo):
- Tích hợp cổng thanh toán thực tế (VNPay, MoMo, Stripe Sandbox/Live).
- Hệ thống xác thực người dùng hoàn chỉnh (JWT/OAuth2/Customer Authentication).
- Toàn bộ nghiệp vụ CRUD sản phẩm, giỏ hàng, đặt hàng (sẽ triển khai khi có tài liệu phân tích nghiệp vụ chính thức).
- Tối ưu hóa hiệu năng production (Redis caching nâng cao, CDN configuration).
- Thiết kế UI/UX và templates bên trong `kleer-theme`.

---

## 3. Tổng quan kiến trúc (Architecture Overview)

Kiến trúc tuân theo mô hình **Dòng phụ thuộc một chiều (Unidirectional Dependency Flow)** từ tầng ngoại vi (HTTP/WordPress) vào tầng lõi (Domain/Application):

```text
HTTP Request (Client / Frontend / Mobile App)
                      │
                      ▼
        ┌───────────────────────────┐
        │        Endpoints          │  <-- Định nghĩa Route & Permission Callback
        └─────────────┬─────────────┘
                      │ calls
                      ▼
        ┌───────────────────────────┐
        │       Controllers         │  <-- Request Adapter: Validate boundary & Shape Response
        └─────────────┬─────────────┘
                      │ delegates to
                      ▼
        ┌───────────────────────────┐
        │        Services           │  <-- Business Use Cases & Workflow Coordination
        └──────┬─────────────┬──────┘
               │             │
      uses     │             │ depends on
               ▼             ▼
        ┌──────────┐  ┌─────────────┐
        │  Models  │  │  Contracts  │  <-- Interfaces (Dependency Inversion Principle)
        └──────────┘  └──────┬──────┘
                             │
                             │ implemented by
                             ▼
                      ┌─────────────┐
                      │Repositories │  <-- Data Access Layer (Persistence)
                      └──────┬──────┘
                             │ queries
                             ▼
        ┌───────────────────────────────────────────┐
        │ WordPress / WooCommerce APIs / MariaDB DB │
        └───────────────────────────────────────────┘
```

### Các tầng kiến trúc cốt lõi:

1. **Endpoints (`Kleer\Endpoints`):** Lớp khai báo API surface với WordPress REST Server (`rest_api_init`). Chỉ đảm nhận cấu hình routing, HTTP method, và ủy quyền (permission callback).
2. **Controllers (`Kleer\Controllers`):** Lớp tiếp nhận HTTP Request (`WP_REST_Request`), trích xuất và validate tham số đầu vào, gọi Service phù hợp, và chuẩn hóa dữ liệu trả về (`WP_REST_Response` hoặc array).
3. **Services (`Kleer\Services`):** Lớp chứa quy tắc nghiệp vụ (Business Rules). Không phụ thuộc vào WordPress REST API hay HTTP context; có thể được tái sử dụng trong CLI, Cron job, hoặc Webhook.
4. **Models (`Kleer\Models`):** Lớp đối tượng miền (Domain Entities). Chứa trạng thái, thuộc tính của thực thể (ví dụ: `Product`), tuyệt đối không thực hiện I/O hay truy vấn database.
5. **Contracts (`Kleer\Contracts`):** Tập hợp các Interface định nghĩa hợp đồng giao tiếp (ví dụ: `ProductRepositoryInterface`), giúp Service không phụ thuộc trực tiếp vào cơ sở dữ liệu cụ thể.
6. **Repositories (`Kleer\Repositories` - Planned):** Tầng hiện thực hóa các Contract, trực tiếp truy vấn cơ sở dữ liệu, WooCommerce CPT hoặc API bên thứ ba.

---

## 4. Cấu trúc thư mục (Folder Structure)

Cấu trúc mã nguồn chuẩn của plugin tại `wp-content/plugins/kleer-plugin/`:

```text
wp-content/plugins/kleer-plugin/
├── kleer-plugin.php                         # Plugin Bootstrap & WordPress Entry Point
│
├── src/                                     # Toàn bộ mã nguồn hướng đối tượng (PSR-4)
│   ├── Plugin.php                           # Application Lifecycle & Hook Orchestrator
│   │
│   ├── Endpoints/                           # TẦNG 1: REST API Routing & Endpoints
│   │   ├── HealthEndpoints.php              # [Implemented] Đăng ký route GET /kleer/v1/health
│   │   └── ProductEndpoints.php             # [Planned] Đăng ký các routes sản phẩm
│   │
│   ├── Controllers/                         # TẦNG 2: HTTP Request Adapters
│   │   ├── HealthController.php             # [Implemented] Adapter xử lý payload health check
│   │   └── ProductController.php            # [Planned] Adapter xử lý request sản phẩm
│   │
│   ├── Services/                            # TẦNG 3: Business Logic & Use Cases
│   │   └── ProductService.php               # [Implemented] Logic lấy sản phẩm nổi bật
│   │
│   ├── Models/                              # TẦNG 4: Domain Data Entities
│   │   └── Product.php                      # [Implemented] Entity biểu diễn sản phẩm Skincare
│   │
│   ├── Contracts/                           # TẦNG 5: Abstraction Interfaces
│   │   └── ProductRepositoryInterface.php   # [Implemented] Contract truy xuất dữ liệu sản phẩm
│   │
│   └── Repositories/                        # TẦNG 6: Data Access Implementations (Persistence)
│       └── (Dành cho việc hiện thực hóa kết nối WooCommerce/DB ở các sprint sau)
│
│   └── Support/                             # TẦNG 7: Hạ tầng dùng chung (Cross-cutting)
│       ├── Cors/
│       │   ├── CorsPolicy.php               # [Implemented] Quyết định CORS thuần (whitelist, header)
│       │   └── CorsService.php              # [Implemented] Gép CORS vào REST API của WordPress
│       └── Validation/
│           ├── JsonSchema.php               # [Implemented] Bộ kiểm tra JSON Schema (không thu viện ngoài)
│           └── ValidationResult.php         # [Implemented] Kết quả kiểm tra + danh sách lỗi theo JSON Pointer
│
├── schemas/                                 # HỢP ĐỒNG JSON dùng chung Frontend & Backend (L01-G6-01)
│   ├── quiz-submission.schema.json          # Contract request cho POST /kleer/v1/skin-quiz
│   └── quiz-submission-response.schema.json # Contract response cho endpoint trên
│
└── tests/                                   # Kiểm thử kiến trúc độc lập
    └── run_tests.php                        # [Implemented] Architecture & Unit Verification Suite
```

---

## 5. Ma trận trách nhiệm (Responsibility Matrix)

Mỗi tầng trong hệ thống tuân thủ nghiêm ngặt nguyên lý **Đơn trách nhiệm (Single Responsibility Principle - SRP)**:

| Tầng (Layer) | Trách nhiệm bắt buộc (Must Do) | Hành vi bị nghiêm cấm (Must NOT Do) |
|---|---|---|
| **Endpoints** | - Đăng ký REST routes thông qua `register_rest_route()`<br>- Xác định HTTP methods (GET, POST, ...)<br>- Khai báo `permission_callback`<br>- Trỏ callback về Controller action tương ứng | - **CẤM** chứa logic nghiệp vụ (business logic)<br>- **CẤM** truy vấn database trực tiếp (`$wpdb`, `WP_Query`)<br>- **CẤM** sinh HTML hoặc phụ thuộc Theme |
| **Controllers** | - Tiếp nhận `WP_REST_Request`<br>- Validate và ép kiểu (sanitize/cast) tham số đầu vào ở ranh giới HTTP<br>- Gọi Service tương ứng để xử lý nghiệp vụ<br>- Chuẩn hóa output thành HTTP Response (status code, JSON headers) | - **CẤM** chứa business rules phức tạp<br>- **CẤM** truy vấn database trực tiếp<br>- **CẤM** phụ thuộc vào Theme hoặc nhúng file Theme<br>- **CẤM** gọi trực tiếp các hàm render giao diện |
| **Services** | - Điều phối và thực hiện các Use Case nghiệp vụ<br>- Kiểm tra ràng buộc kinh doanh (Business Rules validation)<br>- Tương tác với Repositories thông qua Contracts<br>- Tái sử dụng được từ mọi môi trường (REST, WP-CLI, Cron) | - **CẤM** đọc trực tiếp `$_GET`, `$_POST`, `WP_REST_Request`<br>- **CẤM** phụ thuộc vào Theme<br>- **CẤM** chứa mã nguồn xuất HTTP Response header/status<br>- **CẤM** đăng ký WordPress hooks/routes |
| **Models** | - Biểu diễn cấu trúc dữ liệu miền (Domain State/Attributes)<br>- Khai báo các thuộc tính kiểu dữ liệu mạnh (strong typing, PHP 8.2 readonly)<br>- Cung cấp hàm chuyển đổi định dạng cơ bản (`toArray()`) | - **CẤM** thực hiện I/O (Database, Network, File system)<br>- **CẤM** gọi HTTP APIs<br>- **CẤM** đăng ký routes hoặc phụ thuộc WordPress globals |
| **Contracts** | - Định nghĩa Interface trừu tượng cho tầng dữ liệu<br>- Cho phép thay thế (mocking) dữ liệu khi viết Unit Test<br>- Tạo ranh giới phân tách (Decoupling Boundary) | - **CẤM** chứa mã thực thi cụ thể (concrete implementation)<br>- **CẤM** gắn chặt với một cơ chế lưu trữ cố định |
| **Repositories** | - Truy xuất và lưu trữ dữ liệu từ WordPress, WooCommerce, Database, hoặc Remote APIs<br>- Hiện thực hóa các interface trong `Contracts` | - **CẤM** chứa quy trình nghiệp vụ của toàn bộ ứng dụng<br>- **CẤM** xử lý request HTTP hoặc sinh giao diện UI |
| **Support** | - Cung cấp hạ tầng dùng chung cho các tầng phía trên: CORS policy, kiểm tra JSON Schema<br>- Chứa **logic thuần** (không phụ thuộc WordPress) để kiểm thử được độc lập | - **CẤM** chứa business logic của domain<br>- **CẤM** truy vấn database<br>- **CẤM** đăng ký REST route cụ thể của domain |

---

## 6. Quy tắc phụ thuộc (Dependency Rules)

### 6.1. Hướng phụ thuộc hợp lệ (Allowed Dependencies)

- **`Endpoints`** chỉ được phép phụ thuộc vào **`Controllers`**.
- **`Controllers`** chỉ được phép phụ thuộc vào **`Services`** (và các DTO/Models nếu cần để format response).
- **`Services`** chỉ được phép phụ thuộc vào **`Models`** và **`Contracts` (Interfaces)**.
- **`Repositories`** hiện thực hóa (implements) **`Contracts`**, phụ thuộc vào WordPress Core/WooCommerce APIs/Database.
- **`Support`** được phép phụ thuộc vào **không có tầng nào** (hạ tầng dùng chung), nhưng chỉ phụ thuộc ngược lại từ `Endpoints` và `Controllers`.
- **`Plugin.php`** chịu trách nhiệm khởi tạo và điều phối các `Endpoints` và hạ tầng `Support`.
- **`kleer-plugin.php`** nạp mã nguồn theo đúng thứ tự phụ thuộc và hook vào `plugins_loaded`.

```text
[Endpoints] ──> [Controllers] ──> [Services] ──> [Contracts] <── [Repositories]
      │               │                │
      │               │                └──> [Models]
      └───────────────┴────────────────┘
                      │ đọc (CORS · JSON Schema)
                      ▼
                 [Support]
```

### 6.2. Các mối phụ thuộc bị NGHIÊM CẤM (Forbidden Anti-Patterns)

```text
❌ Plugin        ──> Theme internals (require, include, class call)
❌ Theme         ──> Plugin internal private/protected methods
❌ Model         ──> HTTP / Network / REST Request
❌ Model         ──> Database I/O / $wpdb
❌ Service       ──> HTTP Request / Response concerns
❌ Service       ──> Theme
❌ Endpoint      ──> Database query trực tiếp
❌ Controller    ──> Database query trực tiếp ($wpdb / WP_Query)
```

---

## 7. Ranh giới giữa Plugin và Theme (Plugin vs. Theme Boundary)

Hệ thống đặt ra **ranh giới độc lập tuyệt đối** giữa `wp-content/plugins/kleer-plugin/` và `wp-content/themes/kleer-theme/`:

### 7.1. Phân định trách nhiệm

| Thành phần | Khu vực thư mục | Nhiệm vụ duy nhất |
|---|---|---|
| **KLEER Plugin** | `wp-content/plugins/kleer-plugin/` | Nghiệp vụ (Business logic), REST API, Xử lý dữ liệu, Data Persistence Abstraction, Tích hợp bên thứ ba. |
| **KLEER Theme** | `wp-content/themes/kleer-theme/` | Trình bày (Presentation), HTML templates, CSS, JavaScript tương tác người dùng, Styling thương hiệu Skincare. |

### 7.2. Nguyên tắc độc lập (Zero-Coupling Rules)

1. **Thay thế Theme an toàn:** Khi đổi sang bất kỳ Theme nào khác (kể cả Default Themes như Twenty Twenty-Four), toàn bộ REST API (`/wp-json/kleer/v1/...`) và logic nghiệp vụ của `kleer-plugin` vẫn hoạt động bình thường 100% không suy giảm.
2. **Không Require/Include chéo:**
   - Trong `kleer-plugin`: Tuyệt đối không có lệnh `require` hay `include` nào trỏ tới thư mục `wp-content/themes`.
   - Không gọi trực tiếp class hay function nội bộ được định nghĩa trong Theme.
3. **Cơ chế giao tiếp hợp chuẩn (Communication Protocol):**
   - **Cách 1 (Khuyến nghị cho Frontend/SPA/Mobile):** Theme gọi Plugin thông qua **WordPress REST API** (`fetch('/wp-json/kleer/v1/...')`).
   - **Cách 2 (Cho Server-Side Rendering):** Theme và Plugin giao tiếp qua hệ thống **WordPress Action & Filter Hooks** chuẩn (`do_action()`, `apply_filters()`). Plugin cung cấp các filter công khai nếu Theme cần tùy biến hiển thị.

---

## 8. Chu trình xử lý yêu cầu (Request Lifecycle)

### 8.1. Luồng Health Check [Đã triển khai - Implemented]

```text
1. Client gửi request: GET /wp-json/kleer/v1/health
   │
2. WordPress Core tiếp nhận và match route qua 'rest_api_init'
   │
3. HealthEndpoints kiểm tra:
   ├── Method: GET
   └── Permission callback: '__return_true' (Public endpoint)
   │
4. HealthEndpoints kích hoạt action callback:
   └── HealthController::health()
   │
5. HealthController trả về payload mảng:
   └── ['status' => 'ok', 'service' => 'kleer-plugin']
   │
6. WordPress REST Server serialize thành JSON và trả về HTTP 200:
   └── Content-Type: application/json
```

### 8.2. Luồng truy vấn danh sách Sản phẩm [Hoạch định - Planned]

```text
1. Client gửi request: GET /wp-json/kleer/v1/products?limit=5
   │
2. ProductEndpoints bắt route '/kleer/v1/products':
   ├── Method: GET
   ├── Permission: Public hoặc Token Auth
   └── Ủy quyền cho: ProductController::getFeatured()
   │
3. ProductController:
   ├── Đọc và làm sạch query param: $limit = (int) $request->get_param('limit')
   └── Gọi tầng Service: $this->productService->featuredProducts($limit)
   │
4. ProductService:
   ├── Thực thi business rule: Giới hạn $limit trong khoảng [1, 20]
   └── Gọi Contract: $this->repository->findFeatured($clampedLimit)
   │
5. ProductRepository (triển khai ProductRepositoryInterface):
   ├── Truy vấn WooCommerce CPT / MariaDB DB
   └── Hydrate dữ liệu thô thành tập hợp các đối tượng Model: Product[]
   │
6. Dữ liệu trả ngược lên theo luồng:
   ProductRepository ──> ProductService ──> ProductController
   │
7. ProductController chuẩn hóa dữ liệu trả về và bọc trong WP_REST_Response:
   └── HTTP 200 OK kèm payload JSON
```

---

## 9. Quy ước REST API và Endpoints (REST API Conventions)

Toàn bộ API được phát triển trong `kleer-plugin` phải tuân thủ các chuẩn mực sau:

1. **Namespace:** Bắt buộc sử dụng prefix chuẩn:
   ```text
   kleer/v1
   ```
2. **Định dạng URI (Route style):** Chữ thường (lowercase), danh từ số nhiều hướng tài nguyên (RESTful resources):
   - Hợp lệ: `/kleer/v1/products`, `/kleer/v1/categories`, `/kleer/v1/skin-profiles`
   - Không hợp lệ: `/kleer/v1/getProducts`, `/kleer/v1/Product_List`
3. **HTTP Methods chuẩn:**
   - `GET`: Truy vấn tài nguyên (Idempotent, Safe).
   - `POST`: Tạo mới tài nguyên.
   - `PATCH` / `PUT`: Cập nhật tài nguyên.
   - `DELETE`: Xóa tài nguyên.
4. **Permission Callback bắt buộc:** Mọi route đăng ký qua `register_rest_route()` bắt buộc phải định nghĩa trường `'permission_callback'`. Tuyệt đối không để trống hoặc bỏ qua.
   - Public: `'__return_true'`
   - Private/Admin: Callback kiểm tra `current_user_can('manage_options')` hoặc token hợp lệ.
5. **Cấu trúc JSON phản hồi đồng nhất:**
   ```json
   {
     "status": "ok",
     "data": { ... }
   }
   ```
   Trường hợp lỗi:
   ```json
   {
     "code": "resource_not_found",
     "message": "Chi tiết lỗi mô tả bằng tiếng Việt/Anh rõ ràng.",
     "data": { "status": 404 }
   }
   ```

---

## 10. Quy ước đặt tên và Namespaces (Naming Conventions)

Mã nguồn áp dụng tiêu chuẩn chuẩn hóa **PSR-4** với Root Namespace: `Kleer\`.

### 10.1. Bảng quy ước Namespaces

| Tầng | Namespace | Vị trí thư mục | Hậu tố / Tiền tố | Ví dụ |
|---|---|---|---|---|
| **Root** | `Kleer` | `src/` | — | `Plugin.php` |
| **Endpoints** | `Kleer\Endpoints` | `src/Endpoints/` | `...Endpoints.php` | `HealthEndpoints.php`, `ProductEndpoints.php` |
| **Controllers** | `Kleer\Controllers` | `src/Controllers/` | `...Controller.php` | `HealthController.php`, `ProductController.php` |
| **Controllers (Concerns)** | `Kleer\Controllers\Concerns` | `src/Controllers/Concerns/` | Trait dùng chung | `ValidatesJsonPayload.php` |
| **Services** | `Kleer\Services` | `src/Services/` | `...Service.php` | `ProductService.php`, `OrderService.php` |
| **Models** | `Kleer\Models` | `src/Models/` | Danh từ số ít | `Product.php`, `Customer.php` |
| **Contracts** | `Kleer\Contracts` | `src/Contracts/` | `...Interface.php` | `ProductRepositoryInterface.php` |
| **Repositories** | `Kleer\Repositories` | `src/Repositories/` | `...Repository.php` | `WooCommerceProductRepository.php` |
| **Support (Cors)** | `Kleer\Support\Cors` | `src/Support/Cors/` | `...Policy.php` / `...Service.php` | `CorsPolicy.php`, `CorsService.php` |
| **Support (Validation)** | `Kleer\Support\Validation` | `src/Support/Validation/` | `...Schema.php` / `...Result.php` | `JsonSchema.php`, `ValidationResult.php` |

### 10.2. Quy chuẩn viết code (Coding Standards)
- Khai báo nghiêm ngặt kiểu dữ liệu: `declare(strict_types=1);` trên đầu mọi file PHP.
- Sử dụng thuộc tính `readonly` và Constructor Property Promotion của **PHP 8.2**.
- Các lớp không kế thừa được đánh dấu là `final class`.

---

## 11. Danh mục Endpoints (Endpoint Catalog)

Tình trạng thực tế của các endpoints trong hệ thống (phân định minh bạch giữa code đã có và thiết kế hoạch định):

| HTTP Method | Route | Endpoint Class | Controller Class | Service Class | Trạng thái (Status) | Ghi chú |
|---|---|---|---|---|---|---|
| **GET** | `/kleer/v1/health` | `HealthEndpoints` | `HealthController` | — | **Implemented** | Kiểm tra trạng thái hoạt động của Plugin |
| **GET** | `/kleer/v1/products` | `ProductEndpoints` | `ProductController` | `ProductService` | **Planned** | Lấy danh sách sản phẩm nổi bật/phân trang |
| **GET** | `/kleer/v1/products/{id}` | `ProductEndpoints` | `ProductController` | `ProductService` | **Planned** | Lấy chi tiết thông tin sản phẩm Skincare |
| **POST** | `/kleer/v1/cart/items` | `CartEndpoints` | `CartController` | `CartService` | **Future** | Thêm sản phẩm vào giỏ hàng WooCommerce |
| **POST** | `/kleer/v1/skin-quiz` | `SkinQuizEndpoints` | `SkinQuizController` | `SkinQuizService` | **Future** | Bài trắc nghiệm gợi ý sản phẩm phù hợp loại da. Contract: `schemas/quiz-submission.schema.json` |

> [!NOTE]
> > Chỉ có endpoint `/kleer/v1/health` là **Implemented** trong code hiện tại. Các endpoint khác ở trạng thái **Planned** hoặc **Future** sẽ được phát triển theo đúng tài liệu này.
>
> **Hạ tầng tích hợp đã có sẵn (`L01-G6-01`):** CORS áp dụng cho **toàn bộ** route `/kleer/v1/...` và bộ kiểm tra JSON Schema trong `Kleer\Support\Validation`. Endpoint mới **không cần** tự viết lại CORS hay tự định nghĩa lại payload — chỉ cần khai báo route và dùng trait `ValidatesJsonPayload`. Chi tiết: `docs/flows/L01-quiz-integration/flow-card.md`.

---

## 12. Hướng dẫn mở rộng (Developer Extension Guide)

Khi một Developer trong team cần bổ sung một API hoặc chức năng mới vào `kleer-plugin`, hãy thực hiện tuần tự theo quy trình 7 bước chuẩn:

### Bước 1: Khai báo Entity miền (Domain Model) nếu cần
Tạo file trong `src/Models/` để biểu diễn trạng thái thực thể (ví dụ: `Product.php`).
- Chỉ chứa thuộc tính và kiểu dữ liệu.
- Không chứa database query hay HTTP calls.

### Bước 2: Khai báo Contract nếu có tương tác lưu trữ dữ liệu
Tạo Interface trong `src/Contracts/` (ví dụ: `ProductRepositoryInterface.php`).
- Quy định các phương thức cần thiết (`findFeatured()`, `findById()`).

### Bước 3: Triển khai Business Logic tại tầng Service
Tạo hoặc mở rộng Service trong `src/Services/` (ví dụ: `ProductService.php`).
- Nhận Contract qua Dependency Injection tại `__construct()`.
- Thực hiện xác thực logic nghiệp vụ (ví dụ: giới hạn số lượng sản phẩm).

### Bước 4: Tạo Request Adapter tại tầng Controller
Tạo hoặc mở rộng Controller trong `src/Controllers/` (ví dụ: `ProductController.php`).
- Nhận request từ WordPress REST, validate boundary parameters.
- Gọi Service và định dạng mảng dữ liệu trả về.

### Bước 5: Đăng ký định tuyến tại tầng Endpoints
Tạo Endpoint class trong `src/Endpoints/` (ví dụ: `ProductEndpoints.php`).
- Hook vào `rest_api_init`.
- Gọi `register_rest_route('kleer/v1', ...)`, chỉ định phương thức HTTP, permission callback và trỏ callback về Controller.

### Bước 6: Khởi tạo trong Plugin Bootstrap
- Thêm khai báo `require_once` các file mới theo đúng thứ tự phụ thuộc vào `kleer-plugin.php`.
- Gọi phương thức `register()` của Endpoint mới trong `src/Plugin.php`.

### Bước 7: Viết kiểm thử và cập nhật Endpoint Catalog
- Thêm test case vào `tests/run_tests.php`.
- Cập nhật trạng thái endpoint từ `Planned` thành `Implemented` trong `docs/PLUGIN_ARCHITECTURE.md`.

---

## 13. Ví dụ minh họa luồng hoàn chỉnh (End-to-End Architectural Example)

Dưới đây là mã nguồn tối thiểu minh chứng sự phối hợp nhịp nhàng giữa các tầng từ HTTP Request đến Data Abstraction:

### 1. Contract Abstraction (`src/Contracts/ProductRepositoryInterface.php`)
```php
<?php
declare(strict_types=1);

namespace Kleer\Contracts;

interface ProductRepositoryInterface
{
    /** @return array<int, array{id: int, name: string}> */
    public function findFeatured(int $limit = 5): array;
}
```

### 2. Domain Model (`src/Models/Product.php`)
```php
<?php
declare(strict_types=1);

namespace Kleer\Models;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $price = 0,
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'price' => $this->price];
    }
}
```

### 3. Business Service (`src/Services/ProductService.php`)
```php
<?php
declare(strict_types=1);

namespace Kleer\Services;

use Kleer\Contracts\ProductRepositoryInterface;

final class ProductService
{
    public function __construct(private ProductRepositoryInterface $repository) {}

    /** @return array<int, array{id: int, name: string}> */
    public function featuredProducts(int $limit = 5): array
    {
        // Business Rule: Giới hạn tối thiểu 1, tối đa 20 sản phẩm
        return $this->repository->findFeatured(max(1, min($limit, 20)));
    }
}
```

### 4. Controller Request Adapter (`src/Controllers/HealthController.php`)
```php
<?php
declare(strict_types=1);

namespace Kleer\Controllers;

use Kleer\Endpoints\HealthEndpoints;

final class HealthController
{
    public function register(): void
    {
        (new HealthEndpoints($this))->register();
    }

    /** @return array{status: string, service: string} */
    public function health(): array
    {
        return ['status' => 'ok', 'service' => 'kleer-plugin'];
    }
}
```

### 5. Endpoint Route Registration (`src/Endpoints/HealthEndpoints.php`)
```php
<?php
declare(strict_types=1);

namespace Kleer\Endpoints;

use Kleer\Controllers\HealthController;

final class HealthEndpoints
{
    private HealthController $controller;

    public function __construct(?HealthController $controller = null)
    {
        $this->controller = $controller ?? new HealthController();
    }

    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('kleer/v1', '/health', [
                'methods' => 'GET',
                'callback' => [$this->controller, 'health'],
                'permission_callback' => '__return_true',
            ]);
        });
    }
}
```

---

## 14. Kiểm thử và Tiêu chuẩn chấp nhận (Testing & Acceptance)

Mọi thay đổi liên quan đến cấu trúc Plugin phải vượt qua quy trình kiểm thử trước khi commit hoặc tạo Pull Request.

### 14.1. Kiểm tra cú pháp PHP (PHP Linting)
Tất cả các tệp `.php` trong `wp-content/plugins/kleer-plugin` phải pass cú pháp PHP 8.2:

```bash
# Trên máy tính có cài PHP 8.2 (CLI hoặc XAMPP):
Get-ChildItem -Recurse "wp-content\plugins\kleer-plugin" -Filter "*.php" | ForEach-Object { php -l $_.FullName }

# Hoặc qua Docker container:
docker compose run --rm php sh -c "find wp-content/plugins/kleer-plugin -name '*.php' -print0 | xargs -0 -n1 php -l"
```

### 14.2. Chạy bộ kiểm thử kiến trúc (Architecture Verification Suite)
Chạy script kiểm thử kiến trúc độc lập (không cần khởi động web server hay MariaDB):

```bash
php wp-content/plugins/kleer-plugin/tests/run_tests.php
```

Bộ test tự động xác nhận 12 tiêu chí:
1. **Model Layer:** Khởi tạo `Product`, kiểm tra readonly properties và format `toArray()`.
2. **Controller Layer:** `HealthController::health()` trả về đúng payload `['status' => 'ok', 'service' => 'kleer-plugin']`.
3. **Service & Contract Layer:** `ProductService` tích hợp mock interface, thực thi logic clamp dữ liệu `[1, 20]`.
4. **Endpoints Layer:** `HealthEndpoints` đăng ký đúng hook `rest_api_init`, namespace `kleer/v1`, route `/health`, method `GET`.
5. **Bootstrap Layer:** `Plugin::register()` chạy hoàn chỉnh không phát sinh lỗi runtime.
6. **Theme Independence:** Quét toàn bộ mã nguồn `src/` và `kleer-plugin.php`, cam kết không có tham chiếu nào tới Theme.
7. **Security Case:** Mọi file trong `src/` đều có `defined('ABSPATH') || exit;`.
8. **CORS Policy:** Origin ngoài whitelist không nhận header CORS; `Vary: Origin` luôn có mặt; wildcard + credentials không bao giờ echo `*`.
9. **CORS Service:** Hook đúng `rest_pre_serve_request` / `rest_pre_dispatch` / `rest_allowed_cors_headers`; preflight trả HTTP 204 và lọc `Access-Control-Request-Headers`.
10. **JSON Schema Validation:** Đủ các trường bắt buộc, sai kiểu dữ liệu, `pattern`, `enum`, `oneOf`, giới hạn độ dài, `additionalProperties: false`; lỗi trả về kèm **JSON Pointer** chính xác.
11. **Controller Boundary:** Trait `ValidatesJsonPayload` trả `WP_Error` với code `kleer_invalid_payload` và HTTP 400.
12. **Fail-closed:** Schema chứa từ khoá chưa hỗ trợ hoặc kiểu dữ liệu lạ → ném exception thay vì âm thầm cho qua.

> **Kết quả hiện tại:** `130 tests, 0 failures`.

### 14.3. Tiêu chuẩn chấp nhận (Acceptance Criteria / Definition of Done)
- [x] Cú pháp toàn bộ file PHP đạt 100% không có lỗi (`php -l`).
- [x] Tách biệt hoàn toàn Endpoints, Controllers, Services, Models, Contracts, Support.
- [x] Plugin không chứa bất kỳ dòng mã nào phụ thuộc vào Theme (`kleer-theme`).
- [x] Endpoint `GET /wp-json/kleer/v1/health` giữ nguyên response chuẩn `{ "status": "ok", "service": "kleer-plugin" }`.
- [x] Bộ test kiến trúc chạy thành công với **130/130** test cases pass.
- [x] CORS dùng whitelist, có preflight, không dùng wildcard kèm credentials.
- [x] JSON Schema dùng chung cho Frontend & Backend nằm trong `schemas/`.
- [x] Tài liệu `docs/PLUGIN_ARCHITECTURE.md` được cập nhật toàn diện, giải đáp tất cả câu hỏi kiến trúc.

---

## 15. Giải đáp 14 câu hỏi then chốt (Architectural Q&A)

Dành cho bất kỳ lập trình viên nào mới tham gia dự án cần nắm bắt nhanh hệ thống:

1. **Custom Plugin của KLEER nằm ở đâu?**  
   Nằm tại thư mục `wp-content/plugins/kleer-plugin/`.
2. **Plugin khởi động như thế nào?**  
   File `kleer-plugin.php` nạp các tệp theo thứ tự phụ thuộc, sau đó hook vào `plugins_loaded` của WordPress để khởi tạo `(new Kleer\Plugin())->register()`.
3. **Controller làm gì?**  
   Đóng vai trò Request Adapter: nhận input từ `WP_REST_Request`, validate tham số ở ranh giới HTTP, gọi Service tương ứng và định dạng kết quả trả về (`WP_REST_Response`). Không chứa business logic và không query database.
4. **Model làm gì?**  
   Đại diện cho đối tượng dữ liệu miền (Domain Entity - ví dụ `Product.php`), lưu trữ trạng thái và thuộc tính thuần túy. Tuyệt đối không thực hiện I/O mạng hoặc database.
5. **Service làm gì?**  
   Chứa các Use Case và quy tắc nghiệp vụ (Business Rules). Điều phối tương tác giữa Models và Repositories/Contracts. Độc lập hoàn toàn với ngữ cảnh HTTP và Theme.
6. **Endpoint làm gì?**  
   Định nghĩa API surface: cấu hình routes với WordPress qua `register_rest_route()`, thiết lập HTTP method, permission callback, và ánh xạ tới action của Controller.
7. **Contract/Repository nằm ở đâu?**  
   Interfaces nằm tại `src/Contracts/` (ví dụ: `ProductRepositoryInterface.php`), còn các lớp hiện thực hóa truy vấn dữ liệu nằm tại `src/Repositories/`.
8. **Request đi qua những layer nào?**  
   Client ➔ Endpoints ➔ Controllers ➔ Services ➔ Models/Contracts ➔ Repositories ➔ Database/WooCommerce.
9. **Layer nào được phép phụ thuộc layer nào?**  
   Endpoints phụ thuộc Controllers; Controllers phụ thuộc Services; Services phụ thuộc Models và Contracts; Repositories implements Contracts. Không có phụ thuộc vòng hay phụ thuộc ngược.
10. **Theme và Plugin giao tiếp với nhau như thế nào?**  
    Chỉ thông qua 2 kênh chuẩn của WordPress: Gọi qua **REST API** (`/wp-json/kleer/v1/...`) hoặc qua **WordPress Action & Filter Hooks**. Tuyệt đối không `require`/`include` file của nhau.
11. **Plugin có phụ thuộc vào `kleer-theme` hay không?**  
    Hoàn toàn KHÔNG. Plugin hoạt động độc lập kể cả khi kích hoạt bất kỳ Theme nào khác.
12. **Khi thêm API mới thì developer phải tạo/sửa file nào?**  
    Tuân thủ quy trình 7 bước tại [Mục 12](#12-hướng-dẫn-mở-rộng-developer-extension-guide): Tạo Model (nếu có) ➔ Tạo Contract (nếu có) ➔ Viết Service ➔ Viết Controller ➔ Viết Endpoints ➔ Đăng ký trong `Plugin.php` & `kleer-plugin.php` ➔ Viết Test và cập nhật tài liệu.
13. **Endpoint nào đã implement và endpoint nào mới chỉ planned?**  
    Đã implement: `GET /kleer/v1/health`.  
    Hoạch định (Planned/Future): `GET /kleer/v1/products`, `GET /kleer/v1/products/{id}`, giỏ hàng và skin quiz (chi tiết tại [Mục 11](#11-danh-mục-endpoints-endpoint-catalog)).
14. **Làm sao chứng minh kiến trúc này hoạt động đúng?**  
    Chạy lệnh `php wp-content/plugins/kleer-plugin/tests/run_tests.php` để thực thi bộ kiểm thử kiến trúc tự động, và chạy `php -l` kiểm tra cú pháp toàn bộ tệp mã nguồn.
