# Báo cáo kiểm thử L02 – Catalog, Bộ lọc AJAX, PDP (L02-G7-01)

- Người thực hiện: Nguyễn Ngọc Thảo Uyên (QA & Automation)
- Thời điểm chạy: 08/11/2026 22:56
- Môi trường: http://localhost:8082/shop/ | Chrome 157.0.8103.38 | macOS-26.5.2-arm64-arm-64bit-Mach-O
- Script: `tests/L02-catalog-product/test_catalog_filters.py` (số liệu sinh tự động từ lần chạy này)

## Kết quả

| ID | Hạng mục | Kỳ vọng | Kết quả | Ghi chú |
|---|---|---|---|---|
| TC-L02-01 | Lọc đơn | Tick skin_type -> lưới chỉ gồm SP đúng loại da, không reload | **PASS** | Không đọc được tổng số từ giao diện -> chỉ kiểm tra có SP và đúng taxonomy trên mẫu |
| TC-L02-02 | Lọc đa tiêu chí | Tick skin_type + skin_concern -> lưới thỏa cả 2 tiêu chí | **PASS** |  |
| TC-L02-03 | Hiệu năng AJAX | Click -> lưới render xong < ngưỡng (mặc định 200ms) | **PASS** |  |
| TC-L02-04 | JS Console | Thao tác lọc liên tục -> 0 lỗi SEVERE | **PASS** |  |
| TC-L02-05 | PDP UI | Gallery trượt, giá (KM), badge taxonomy, nút Thêm vào giỏ | **BLOCKED** | Không có SP giảm giá trong 24 SP đầu -> chưa kiểm chứng 'giá khuyến mãi'; xem notes cho các mục còn lại. |
| TC-L02-06 | Bỏ lọc (bổ sung) | Untick hết -> số SP trở về như ban đầu | **PASS** |  |

Tổng: PASS 5 / FAIL 0 / BLOCKED 1 / chưa chạy 0

## Đo hiệu năng AJAX

Ngưỡng: < 200 ms; 10 mẫu (đã loại 1 lần warm-up). `click→render` = từ sự kiện click đến lần thay đổi DOM cuối của lưới; `AJAX` = thời gian request `admin-ajax.php` action `kleer_filter_products` đo trong trang.

| Chỉ số (ms) | Min | TB | P95 | Max |
|---|---|---|---|---|
| click→render | 150 | 150 | 150 | 150 |
| AJAX request | 100 | 100 | 100 | 100 |

## JS Console

Sau tải trang + 8 thao tác lọc: **0 lỗi SEVERE** (0 mục bị bỏ qua theo mẫu `favicon\.ico|/wp-content/uploads/|err_net|failed|404`).

- TC-L02-01: baseline=16, filtered=16, count_source=cards

- TC-L02-02: only_type=10, type_and_concern=5, count_source=cards

- TC-L02-06: baseline=16, after_reset=16

## Giả định cần xác nhận

- Slug/nhãn: `oily`/“oily”, `acne`/“acne” (lấy từ bảng wp_terms).
- Thẻ sản phẩm = con trực tiếp của `.products, #kleer-products-grid, .kleer-products-grid, ul.products`; selector PDP/badge: `.kleer-taxonomy-badges, .product-taxonomy-badges, .kleer-badge, .product_meta, .posted_in, .tagged_as, body`.
- Đúng/sai của lưới kiểm bằng taxonomy trên mẫu 5 SP, không phải toàn bộ.
- Script chỉ đọc dữ liệu; không thêm giỏ hàng, không ghi DB.
