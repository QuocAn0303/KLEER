# MA TRẬN KỊCH BẢN KIỂM THỬ (TEST CASES) - TASK L02-G7-01
- **Module:** Catalog Sản phẩm, Bộ lọc Taxonomy AJAX & Trang PDP (L02)
- **Tester:** Nguyễn Ngọc Thảo Uyên (QA & Automation)
- **Môi trường:** Docker Local (`http://localhost:8080/shop/`)

---

## 1. Danh sách Kịch bản Kiểm thử (Test Cases)

| Mã TC | Tên Kịch bản / Thao tác | Loại Test | Kết quả mong đợi (Expected Result) | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-L02-01** | Lọc sản phẩm đơn tiêu chí (`skin_type = da-dau`) | Pass Flow | Lưới sản phẩm render đúng danh sách da dầu via AJAX. Response time < 200ms. | Pass |
| **TC-L02-02** | Lọc đa tiêu chí (`skin_type = da-dau` + `skin_concern = mun`) | Pass Flow | Hiển thị sản phẩm thỏa mãn đồng thời 2 taxonomy. Không reload lại trang. | Pass |
| **TC-L02-03** | Lọc tổ hợp không có sản phẩm (Empty State) | Edge Case | Hiển thị thông báo "Không tìm thấy sản phẩm phù hợp", giao diện không vỡ/crash. | Pass |
| **TC-L02-04** | Đo hiệu năng thời gian phản hồi AJAX Filter | Performance | Thời gian lọc ngầm từ khi click đến khi render < 200ms (`/?wc-ajax=get_filtered_products`). | Pass |
| **TC-L02-05** | Rà soát lỗi JavaScript Console trình duyệt | Quality/NFR | Log trình duyệt ghi nhận `0 errors` (0 lỗi SEVERE / Warning nghiêm trọng). | Pass |
| **TC-L02-06** | Kiểm thử hiển thị trang PDP (`single-product.php`) | UI/UX | Render đủ Gallery slider ảnh, giá khuyến mãi, nhãn taxonomy badges và nút Thêm vào giỏ. | Pass |

---

## 2. Tiêu chí nghiệm thu DoD (Definition of Done)
- [x] 100% Test Cases đạt trạng thái **Pass**.
- [x] AJAX Filter response time < 200ms.
- [x] Không xuất hiện lỗi JavaScript trên Console Log.