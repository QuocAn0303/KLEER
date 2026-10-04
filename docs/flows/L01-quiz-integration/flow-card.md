# FLOW CARD: L01 - G6 - GHÉP NỐI FRONTEND QUIZ & BACKEND QUIZ API

- **Mã task:** `L01-G6-01`
- **Luồng:** L01 (Quiz & Gợi ý sản phẩm)
- **Cổng:** G6 (Integration / Hợp nhất)
- **Vai trò:** Scrum Master & DevOps — Lê Nguyễn Quốc An
- **Người duyệt:** Nguyễn Tấn Phát
- **Tuần:** Tuần 07
- **Trạng thái:** Đã triển khai trên nhánh `develop`
- **Thư mục tài liệu:** `docs/flows/L01-quiz-integration/`

---

## 1. Mục tiêu

Xây **hạ tầng tích hợp dùng chung** giữa Frontend Quiz và Backend Quiz API, gồm hai phần:

1. **CORS** — mọi route REST của KLEER đều trả CORS header theo whitelist, có xử lý preflight `OPTIONS`.
2. **Schema validation** — mọi payload JSON Quiz được kiểm tra tính toàn vẹn theo một JSON Schema duy nhất, dùng chung cho cả Frontend lẫn Backend.

**Kết quả mong đợi:** Frontend và Backend cùng khai triển `L01` mà không phát sinh xung đột, vì cùng tuân theo **một nguồn chân lý duy nhất** (các file trong `schemas/`).

---

## 2. Nguyên tắc thiết kế (điều phối, không phải code nghiệp vụ)

| Nguyên tắc | Cách thực hiện |
|---|---|
| Một nguồn chân lý | Schema nằm ở `wp-content/plugins/kleer-plugin/schemas/*.schema.json`, Frontend và Backend **cùng đọc file này**, không mô tả lại bằng lời. |
| Fail-closed | Gặp từ khoá schema chưa hỗ trợ (`$ref`, `allOf`, ...) hoặc sai kiểu dữ liệu trong schema → ném `RuntimeException`, **không** bỏ qua để payload sai lọt qua. |
| Không reflect origin tuỳ ý | Chỉ echo origin nằm trong whitelist. Origin lạ → **không** gửi bất kỳ header CORS nào. |
| Không dùng `*` khi có credentials | Nếu bật `credentials`, luôn echo origin cụ thể. `Access-Control-Allow-Origin: *` là sai theo spec khi request có cookie/Authorization. |
| Chuẩn hoá lỗi thống nhất | Mọi lỗi payload trả `kleer_invalid_payload` + HTTP 400 + danh sách lỗi có **JSON Pointer** để Frontend báo đúng chỗ. |

---

## 3. CORS

### 3.1. Cấu hình

Khai báo trong `.env` (xem `.env.example`):

```dotenv
KLEER_CORS_ALLOWED_ORIGINS=http://localhost:8080,http://localhost:5173
KLEER_CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
KLEER_CORS_ALLOWED_HEADERS=Authorization,Content-Type,X-Requested-With,X-WP-Nonce,X-KLEER-Session
KLEER_CORS_ALLOW_CREDENTIALS=false
KLEER_CORS_MAX_AGE=86400
```

> **Bỏ trống `KLEER_CORS_ALLOWED_ORIGINS` = chặn toàn bộ cross-origin request.** Đây là mặc định an toàn cho production.

### 3.2. Hành vi

- Áp dụng cho **toàn bộ** route `/wp-json/kleer/v1/...`, không cần khai báo lại ở từng endpoint.
- Preflight `OPTIONS` trả **HTTP 204** kèm header CORS, **không** chạy qua `permission_callback` (nên tránh bị chặn 401 khi chưa đăng nhập).
- `Access-Control-Request-Headers` do client gửi lên được **lọc lại** theo whitelist trước khi trả về.
- Luôn gửi `Vary: Origin` để proxy/CDN không phục vụ nhầm header CORS cho người khác.

### 3.3. Bảng kiểm thử tay (bắt buộc chạy trước khi bàn giao)

Lệnh chạy trong container PHP:

```bash
curl -i -X OPTIONS "http://localhost/wp-json/kleer/v1/health" \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: content-type, x-wp-nonce"

curl -i "http://localhost/wp-json/kleer/v1/health" \
  -H "Origin: http://localhost:5173"

curl -i "http://localhost/wp-json/kleer/v1/health" \
  -H "Origin: https://evil.example.com"   # phải KHÔNG có Access-Control-Allow-Origin
```

| Tình huống | Kết quả mong đợi |
|---|---|
| Origin trong whitelist | Có `Access-Control-Allow-Origin` + `Vary: Origin` |
| Origin ngoài whitelist | Không có header CORS nào |
| Preflight `OPTIONS` | HTTP 204 + `Access-Control-Max-Age` |
| Frontend dùng cookie/nonce | Bật `KLEER_CORS_ALLOW_CREDENTIALS=true` và gửi header `X-WP-Nonce` |

---

## 4. Schema validation

### 4.1. Contract

| Schema | Dùng cho |
|---|---|
| `schemas/quiz-submission.schema.json` | Kiểm tra **request** gửi lên `POST /wp-json/kleer/v1/skin-quiz` |
| `schemas/quiz-submission-response.schema.json` | Kiểm tra **response** Backend trả về |

### 4.2. Cách Frontend dùng chung schema

Frontend **không** cần chạy lại validator PHP — chỉ cần lấy file schema làm chuẩn để sinh type/model:

```bash
# Xuất schema ra để sinh TypeScript
npx json-schema-to-typescript \
  wp-content/plugins/kleer-plugin/schemas/quiz-submission.schema.json \
  -o src/types/quiz-submission.d.ts
```

### 4.3. Cách Backend dùng

```php
use Kleer\Controllers\Concerns\ValidatesJsonPayload;

final class SkinQuizController
{
    use ValidatesJsonPayload;

    public function submit(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $params = $this->validateJsonPayload($request, 'quiz-submission.schema.json');

        if (is_wp_error($params)) {
            return $params;   // Tự động trả HTTP 400 + danh sách lỗi có JSON Pointer
        }

        // $params đã hợp lệ theo schema -> gọi Service
    }
}
```

### 4.4. Định dạng lỗi trả về

```json
{
  "code": "kleer_invalid_payload",
  "message": "Dữ liệu gửi lên không hợp lệ theo schema của API.",
  "data": {
    "status": 400,
    "errors": [
      {
        "pointer": "/answers/0/question_id",
        "keyword": "pattern",
        "message": "Chuỗi không khớp định dạng yêu cầu: ^[a-z0-9_]{1,64}$."
      }
    ]
  }
}
```

Frontend dùng `pointer` để highlight đúng ô input bị lỗi.

---

## 5. Kiểm thử tự động

```bash
docker compose run --rm php php wp-content/plugins/kleer-plugin/tests/run_tests.php
```

Bộ test hiện có **130 test / 0 failure**, trong đó phần CORS + schema validation chiếm 60 test
(mục 8, 9, 10, 11, 12).

---

## 6. Việc còn lại cho Luong L01

- [ ] Backend L01 viết `SkinQuizEndpoints` + `SkinQuizController` + `SkinQuizService` theo mẫu mục 4.3.
- [ ] Frontend L01 đọc schema để sinh type và dùng `data.errors[].pointer` để báo lỗi.
- [ ] Chạy mục 3.3 trên môi trường thật, chụp lại kết quả làm bằng chứng bàn giao.
