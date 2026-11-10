# L10-G5-02 - Postman Collection goi WooCommerce REST API v3 cho 3 nghiep vu tu dong (Rubric 5.6)

Nguoi lam: Tran Ngoc Minh Tuyen. Phe duyet: Le Nguyen Quoc An. Luong: L10 - Tu dong hoa.

## 1. San pham ban giao

| Muc | Duong dan |
|---|---|
| Postman Collection | `tools/automation/postman_collection_L10-G5-02.json` |
| Log chay thuc te (newman) | `docs/flows/L10-automation-api/L10-G5-02-newman-run.log` |
| Tai lieu nay | `docs/flows/L10-automation-api/L10-G5-02-postman-rest-api-3-nghiep-vu.md` |

3 nghiep vu theo yeu cau task:

1. Cap nhat don hang -> `completed` (`PUT /wc/v3/orders/{id}`)
2. Xoa san pham mau (`DELETE /wc/v3/products/{id}?force=true`)
3. Sua ton kho (`PUT /wc/v3/products/{id}` voi `manage_stock` + `stock_quantity`)

## 2. Cach chay

### 2.1 Chuan bi
- Base URL **phai** co `index.php`: `http://localhost/index.php`. Site dung permalink Plain nen `/wp-json/...` tra ve HTML trang chu (HTTP 200) chu khong phai JSON, va `/?rest_route=...` bi nginx tra `405` voi PUT/DELETE (xem muc 4).
- Xac thuc: **OAuth 1.0a, HMAC-SHA256**. WooCommerce chi cho Basic Auth khi site chay HTTPS; localhost la HTTP.
- Khoa Consumer Key/Secret lay tu `wp-config.php` (gitignored): `KLEER_WC_API_CK`, `KLEER_WC_API_CS`. Collection **khong** chua khoa; 2 bien `ck` / `cs` de trong.
- Plugin `wp-content/plugins/kleer-rest-api` (tu L10-G5-01) phai duoc kich hoat, neu khong moi request deu `401 woocommerce_rest_cannot_view`.

### 2.2 Tren Postman (GUI)
1. Import `tools/automation/postman_collection_L10-G5-02.json`.
2. Mo collection -> tab **Variables**, dien `ck` va `cs` (danh dau *secret*, khong commit). Bien `base` da san, `stock_qty` mac dinh `88`.
3. Nhan **Run collection** (Collection Runner) de chay tuan tu ca 11 request. Khong can nhap ID: folder `0. Setup` tu tao san pham/don hang mau qua API va luu ID vao bien collection.
4. Moi request deu co tab **Tests** voi assertion kiem tra ma HTTP va **du lieu da cap nhat trong body**, nen Runner se bao PASS/FAIL thay vi phai doc tay.

### 2.3 Chay khong can GUI (de CI hoac de kiem chung)
```bash
npx newman@6 run tools/automation/postman_collection_L10-G5-02.json \
  --env-var ck="$KLEER_WC_API_CK" --env-var cs="$KLEER_WC_API_CS" \
  --timeout-request 240000
```
Site local rat cham (trung binh ~27s/request) nen bat buoc tang `--timeout-request`; mac dinh 30s se timeout.

## 3. Cau truc collection (11 request, 40 assertion)

| Folder | Request | Method | Ket qua mong doi |
|---|---|---|---|
| 0. Setup | 0.1 Tao san pham se bi xoa | POST `/products` | 201 |
| 0. Setup | 0.2 Tao san pham de sua ton kho (ton kho 5) | POST `/products` | 201 |
| 0. Setup | 0.3 Tao don hang `processing` | POST `/orders` | 201 |
| **1** | 1.1 Cap nhat don -> completed | PUT `/orders/{id}` | 200, body `status=completed`, co `date_completed` |
| 1 | 1.2 Doc lai don tu server | GET `/orders/{id}` | 200, `status=completed` |
| **2** | 2.1 Xoa san pham mau | DELETE `/products/{id}&force=true` | 200, body tra ve dung san pham da xoa |
| 2 | 2.2 Doc lai san pham da xoa | GET `/products/{id}` | 404 `woocommerce_rest_product_invalid_id` (request kiem chung, khong tinh vao 3 nghiep vu) |
| **3** | 3.1 Sua ton kho = 88 | PUT `/products/{id}` | 200, body `stock_quantity=88`, `manage_stock=true`, `stock_status=instock` |
| 3 | 3.2 Doc lai san pham tu server | GET `/products/{id}` | 200, ton kho = 88 |
| 4. Don dep | 4.1 / 4.2 Xoa san pham + don hang test | DELETE | 200 |

## 4. Bang chung chay thuc te

Chay bang newman (cung engine script/assertion voi Postman Runner) luc 2026-11-10, log day du o `L10-G5-02-newman-run.log`.

Ket qua: **11/11 request executed, 40/40 assertion PASS, 0 failed**, tong thoi gian 5m 3s.

| Request | HTTP | Du lieu tra ve (trich) |
|---|---|---|
| 0.1 POST tao san pham se bi xoa | **201** | `id=4378`, `name=ZZ-AUTO-DELETE ...` |
| 0.2 POST tao san pham ton kho | **201** | `id=4379`, `stock_quantity=5`, `manage_stock=true` |
| 0.3 POST tao don hang | **201** | `id=4380`, `status=processing` |
| 1.1 PUT don -> completed | **200** | `id=4380`, `status=completed`, `date_completed=2026-11-10T13:56:24` |
| 1.2 GET don | **200** | `status=completed` |
| 2.1 DELETE san pham | **200** | `id=4378` (san pham da xoa) |
| 2.2 GET san pham da xoa | 404 | `code=woocommerce_rest_product_invalid_id` |
| 3.1 PUT ton kho | **200** | `id=4379`, `stock_quantity=88`, `manage_stock=true`, `stock_status=instock` |
| 3.2 GET san pham | **200** | `stock_quantity=88` |
| 4.1 / 4.2 Don dep | **200** | da xoa san pham 4379 va don 4380 |

Kiem tra lai truc tiep trong DB sau khi chay (wp-cli): post `4378`, `4379`, `4380` deu `deleted` - du lieu test khong con ton trong he thong.

## 5. Loi da phat hien va cach xu ly

| # | Van de | Nguyen nhan | Cach sua |
|---|---|---|---|
| 1 | Collection cu `tools/automation/postman_collection_v3.json` dung `{{BASE_URL}}/wp-json/wc/v3/...` -> tra HTTP 200 nhung body la **HTML trang chu**, khong cap nhat gi | Permalink Plain: WordPress khong co route `/wp-json/`, nginx `try_files` tra ve trang chu voi ma 200 | Dung `{{base}}?rest_route=/wc/v3/...` voi `base = http://localhost/index.php` |
| 2 | Collection cu khong co test script, bien `WC_CONSUMER_KEY/SECRET` trong, khong co bang chung chay | - | Collection moi co 40 assertion kiem tra ca ma HTTP va truong du lieu da cap nhat; kem log chay |
| 3 | `PUT`/`DELETE` vao `http://localhost/?rest_route=...` tra `405 Not Allowed` | nginx chi chuyen cac method nay ve PHP khi URL tro truc tiep `index.php` | Luon dung `/index.php?rest_route=...` |
| 4 | Basic Auth (`-u ck:cs`) tra `401` | WooCommerce chi cho Basic Auth tren HTTPS | OAuth 1.0a HMAC-SHA256 |
| 5 | newman bao `request url is empty` voi moi request | newman 6 khong doc duoc `url` dang object chi co truong `raw` | Luu `url` dang **string** trong collection (Postman GUI van hien binh thuong) |

## 6. Gioi han / viec con lai

- Log bang chung duoc tao bang **newman** (CLI chinh thuc cua Postman, chay cung collection va cung test script). **Chua mo ung dung Postman GUI** - agent khong dieu khien duoc Postman desktop. Neu rubric yeu cau anh chup Postman Runner, nguoi lam import collection, dien `ck`/`cs` va chup lai man hinh Runner (ket qua se giong log nay).
- Chi kiem tren moi truong local HTTP. Tren VPS HTTPS: tao khoa REST rieng, co the dung Basic Auth, va neu permalink khong phai Plain thi doi `base` thanh `https://<domain>/wp-json/wc/v3`.
- Collection cu `postman_collection_v3.json` duoc giu nguyen de doi chieu; de nghi nhom dung ban L10-G5-02 nay lam ban chinh thuc.
