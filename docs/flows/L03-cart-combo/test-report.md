# Báo cáo kiểm thử tự động - L03 Giỏ hàng & Thanh toán (Checkout)

- **Task:** L03-G7-01 | **Người thực hiện:** Nguyễn Ngọc Thảo Uyên (QA & Automation)
- **Thời điểm chạy:** 10/11/2026 14:48:00
- **Base URL:** `http://localhost:8082`
- **Script:** `tests/L03-cart-combo/test_checkout_validation.py`

## Tổng hợp

| Tổng | Pass | Fail | Error | Skip | Tỷ lệ Pass |
|-----:|-----:|-----:|------:|-----:|-----------:|
| 11 | 10 | 0 | 0 | 1 | 90.9% |

## Chi tiết từng test case

| # | Test | Mô tả | Kết quả | Thời gian (s) | Ghi chú / Ảnh lỗi |
|--:|------|-------|:-------:|--------------:|-------------------|
| 1 | `test_empty_required_fields` | Ca 1: Bỏ trống toàn bộ trường bắt buộc -> chặn tạo đơn, bôi đỏ, hiện .woocommerce-error | ✅ PASS | 2.23 |  |
| 2 | `test_missing_single_required_field[billing_first_name]` | Ca 1b: Chỉ bỏ trống 1 trường bắt buộc (Tên/SĐT/Email/Địa chỉ) -> bị chặn và bôi đỏ đúng trường | ✅ PASS | 3.50 |  |
| 3 | `test_missing_single_required_field[billing_phone]` | Ca 1b: Chỉ bỏ trống 1 trường bắt buộc (Tên/SĐT/Email/Địa chỉ) -> bị chặn và bôi đỏ đúng trường | ✅ PASS | 3.40 |  |
| 4 | `test_missing_single_required_field[billing_email]` | Ca 1b: Chỉ bỏ trống 1 trường bắt buộc (Tên/SĐT/Email/Địa chỉ) -> bị chặn và bôi đỏ đúng trường | ✅ PASS | 3.21 |  |
| 5 | `test_missing_single_required_field[billing_address_1]` | Ca 1b: Chỉ bỏ trống 1 trường bắt buộc (Tên/SĐT/Email/Địa chỉ) -> bị chặn và bôi đỏ đúng trường | ✅ PASS | 3.86 |  |
| 6 | `test_invalid_phone_format` | Ca 2: Nhập chữ 'abcde' vào #billing_phone -> từ chối thanh toán, bôi đỏ trường SĐT | ✅ PASS | 3.55 |  |
| 7 | `test_invalid_email_format` | Ca 2b: Nhập email sai định dạng 'abc@' -> từ chối thanh toán, bôi đỏ trường Email | ✅ PASS | 3.30 |  |
| 8 | `test_expired_coupon_rejected` | Ca 3a: Áp mã hết hạn 'hethan' -> báo lỗi đỏ/từ chối, tổng tiền không đổi | ✅ PASS | 5.72 |  |
| 9 | `test_oversize_coupon_never_negative` | Ca 3b: Mã giảm cố định lớn hơn tổng đơn -> tổng không bao giờ âm, giảm giá <= tạm tính | ✅ PASS | 2.06 |  |
| 10 | `test_shipping_fee_updates_for_remote_province` | Ca 4: Đổi địa chỉ sang tỉnh khác -> AJAX cập nhật, phí ship cộng đúng vào Total | ⏭️ SKIP | 2.37 | Skipped: Không có/không tìm thấy dropdown #billing_state (Theme có thể đã ẩn trường Tỉnh/TP) |
| 11 | `test_place_order_cod_success` | Ca 5: Điền form hợp lệ + chọn COD -> chuyển hướng tới trang Order Received (Thank You) | ✅ PASS | 36.93 |  |

> Lưu ý: test `Skip` nghĩa là thiếu điều kiện dữ liệu (coupon/zone ship chưa tạo...), không được tính là Pass. Xem lý do ở cột Ghi chú.
