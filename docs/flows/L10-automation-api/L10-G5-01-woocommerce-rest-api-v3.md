# L10-G5-01 - Khoi tao WooCommerce REST API v3 (Rubric 5.6)

Nguoi lam: Tran Ngoc Minh Tuyen. Phe duyet: Le Nguyen Quoc An.

## 1. Ket qua

| Muc | Gia tri |
|---|---|
| WooCommerce | 9.3.3 |
| Base URL | `http://localhost/index.php?rest_route=/wc/v3/...` (site dung permalink Plain, **khong** co `/wp-json/`) |
| Khoa | 1 cap, quyen `read_write`, user `admin` (id 1), mo ta `KLEER L10-G5-01 REST API v3 (Rubric 5.6)` |
| Noi luu khoa | `wp-config.php` (gitignored): `KLEER_WC_API_CK`, `KLEER_WC_API_CS`. **Khong** nam trong repo/doc/chat. Bang `wp_woocommerce_api_keys` chi luu hash cua Consumer Key. |
| Xac thuc | OAuth 1.0a, HMAC-SHA256 (WooCommerce chi cho Basic Auth khi site la HTTPS; localhost la HTTP) |

## 2. Hai loi phat hien khi khoi tao (da sua)

| # | Van de | Nguyen nhan | Cach sua |
|---|---|---|---|
| 1 | Khoa dung nhung moi request deu `401 woocommerce_rest_cannot_view` | Permalink Plain: WooCommerce chi nhan dien REST khi URL chua `wp-json/wc/`, nen `?rest_route=/wc/v3/...` bi coi la khach | Plugin moi `wp-content/plugins/kleer-rest-api` dung filter chinh thuc `woocommerce_rest_is_request_to_rest_api` |
| 2 | `DELETE /?rest_route=...` tra `405 Not Allowed` cua nginx | nginx khong chuyen DELETE cho `location /` ve `index.php` | Goi qua `/index.php?rest_route=...` (DELETE/PUT/POST/GET deu OK). Trong Postman luon dung URL co `index.php` |

## 3. Cau hinh Postman

1. Tao Environment `KLEER-local`: `base = http://localhost/index.php`, `ck`, `cs` (lay tu `wp-config.php`, danh dau *secret*).
2. Request, vi du `GET {{base}}?rest_route=/wc/v3/products&per_page=2`.
3. Tab **Authorization** -> Type **OAuth 1.0**:
   - Add authorization data to: **Request Headers** (hoac Request URL/Body)
   - Signature Method: **HMAC-SHA256** (HMAC-SHA1 cung duoc)
   - Consumer Key: `{{ck}}`, Consumer Secret: `{{cs}}`
   - Access Token / Token Secret: **de trong**
   - Bo chon *Add empty parameters to signature* neu co; bat *Include body hash* = tat.
4. Send -> ky vong `200` va JSON danh sach san pham.
5. Tinh nang dung cho Rubric 5.6: `POST {{base}}?rest_route=/wc/v3/products` (Body raw JSON), `PUT .../products/<id>`, `DELETE .../products/<id>&force=true`.

Neu Postman tra `401 Invalid signature`: kiem tra URL co `index.php`, khong co dau `/` thua sau `index.php`, va `ck/cs` khong co khoang trang.

## 4. Bang chung da kiem (client tu ky OAuth1 HMAC-SHA256, Python, giong thuat toan Postman)

| Phep thu | Ket qua |
|---|---|
| Khong khoa `GET products` | 401 `woocommerce_rest_cannot_view` |
| Basic Auth qua HTTP / consumer_key trong query | 401 (dung thiet ke WooCommerce) |
| `GET /wc/v3/products` | 200 |
| `GET /wc/v3/system_status` | 200, version 9.3.3 |
| `POST /wc/v3/products/categories` (tao `ZZ-API-TEST`) | 201 (ghi) |
| `PUT` category vua tao | 200 |
| `DELETE ...?force=true` | 200, GET lai -> 404 (da don dep) |

**Chua kiem:** chua mo Postman that (agent khong dieu khien duoc ung dung Postman). Cac buoc o muc 3 la cau hinh chuan cua Postman; DoD "xac thuc hop le qua Postman" chi dat khi nguoi lam chay thuc te va chup anh `200`.

## 5. Viec con lai cho nguoi lam

- Mo `wp-config.php`, copy `KLEER_WC_API_CK/CS` vao Postman Environment (khong commit, khong gui chat).
- Nhom tu dong hoa (L10) dung cac hang so nay hoac bien moi truong; khong hard-code khoa trong script.
- Tren VPS (HTTPS) tao khoa rieng (WooCommerce -> Settings -> Advanced -> REST API -> Add key, quyen Read/Write); khong dung lai khoa local.
