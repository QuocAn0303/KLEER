# Danh sách phần tử – Sơ đồ BPMN Fulfillment Flow (KLEER)

> Nguồn sinh: `fulfillment-flow.bpmn` (BPMN 2.0) – **sinh tự động từ cùng một mô hình dữ liệu**
> nên đối chiếu 1-1 với `fulfillment-flow.bpmn` và `fulfillment-flow.drawio`.

- Pool: `Participant_FulfillmentFlow` – Fulfillment Flow – KLEER
- Process: `Process_FulfillmentFlow`

## 1. Lane (Pool / Lane)

| Lane ID | Tên lane | Số node |
|---|---|---|
| `Lane_F01` | Hệ thống xử lý (WooCommerce / Plugin / Integration) | 31 |
| `Lane_F02` | Nhân sự Kho | 16 |
| `Lane_F03` | Đối tác 3PL | 4 |

## 2. Start Event / End Event

| ID | Loại | Tên | Đi vào từ | Ghi chú / Nguồn trong code |
|---|---|---|---|---|
| `Start_F01` | startEvent | Đơn hàng chuyển sang trạng thái Processing | – | docs/PAYMENT_GATEWAYS.md:135 (IPN → order chuyển `processing`); COD: docs/PAYMENT_GATEWAYS.md:85 |
| `End_F01` | endEvent | Giao thành công – Completed | `Flow_F62` | WooCommerce core status `completed` |
| `End_F02` | endEvent | Đơn bị hủy – đã hoàn tồn và hoàn tiền | `Flow_F57` | WooCommerce core status `cancelled` |
| `End_F03` | endEvent | Giao thất bại – hoàn kho và hoàn tiền | `Flow_F60` | WooCommerce core status `refunded` |

## 3. Gateway (XOR)

| ID | Tên | Nhánh ra → điều kiện | Nhánh mặc định | Ghi chú / Nguồn trong code |
|---|---|---|---|---|
| `GW_F01` | Tồn kho hệ thống có đủ? | Tồn kho đủ → `Task_F04`; Không đủ tồn kho → `Task_F03` **(mặc định)** | `Task_F03` | WooCommerce core (product stock check) |
| `GW_F10` | Tạo vận đơn thành công? | Tạo vận đơn thành công → `Task_F20`; Lỗi / timeout API → `Task_F21` **(mặc định)** | `Task_F21` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F09` | Yêu cầu hủy / đổi địa chỉ sau bàn giao? | Không có yêu cầu hủy → `Task_F31`; Có yêu cầu hủy / đổi địa chỉ → `Task_F36` **(mặc định)** | `Task_F36` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F04` | Kiểm kê đủ hàng thực tế? | Kiểm kê đủ hàng thực tế → `Task_F04`; Vẫn thiếu hàng → `Task_F13` **(mặc định)** | `Task_F13` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F14` | Đơn đã thu tiền trước? | Đã thu tiền trước → `Task_F14`; COD – chưa thu tiền → `Task_F15` **(mặc định)** | `Task_F15` | PAYMENT_GATEWAYS.md:87 (COD không xác minh tiền trước) |
| `GW_F11` | Đã retry đủ số lần? | Chưa đạt giới hạn → `Task_F19`; Đã đạt giới hạn → `Task_F35` **(mặc định)** | `Task_F35` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F13` | Còn hàng đủ để giao lại? | Còn hàng – tạo lượt giao lại → `Task_F04`; Không đủ hàng → `Task_F38` **(mặc định)** | `Task_F38` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F02` | Số lượng thực lấy khớp đơn? | Số lượng khớp đơn → `Task_F26`; Lệch số lượng thực tế → `Task_F10` **(mặc định)** | `Task_F10` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F06` | Hàng nguyên vẹn sau đóng gói? | Hàng nguyên vẹn → `GW_F07`; Hỏng / hư hỏng → `Task_F10` **(mặc định)** | `Task_F10` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F07` | Khách có yêu cầu hủy đơn? | Khách không hủy → `Task_F19`; Khách yêu cầu hủy → `Task_F13` **(mặc định)** | `Task_F13` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F05` | Cách xử lý lệch tồn kho? | Còn hàng thay thế → `Task_F11`; Giao một phần → `Task_F12`; Chờ nhập hàng → `Task_F33`; Hủy đơn → `Task_F13` **(mặc định)** | `Task_F13` | [ĐỀ XUẤT – chưa có trong code] |
| `GW_F12` | Kết quả giao hàng? | Giao thành công → `Task_F17`; Giao thất bại → `Task_F32` **(mặc định)** | `Task_F32` | [ĐỀ XUẤT – chưa có trong code] |

## 4. Task (Activity)

| Task ID | Lane | Tên task | Loại | Nhánh vào | Nhánh ra (kèm điều kiện) | Ghi chú / Nguồn trong code |
|---|---|---|---|---|---|---|
| **— LANE: Hệ thống xử lý (WooCommerce / Plugin / Integration) —** | | | | | | |
| `Task_F01` | Hệ thống xử lý | Chốt trạng thái Processing | serviceTask (Hệ thống tự động) | `Flow_F01` | `Flow_F02` | WooCommerce core (`woocommerce_order_status_changed`) – CHƯA có hook trong kleer-plugin |
| `Task_F02` | Hệ thống xử lý | Kiểm tra tồn kho khả dụng | serviceTask (Hệ thống tự động) | `Flow_F02` | `Flow_F03` | WooCommerce core kiểm tra tồn kho; CacheInvalidation.php:35-36 chỉ bust cache, KHÔNG phải logic kho |
| `Task_F04` | Hệ thống xử lý | Ghi nhận đơn vào hàng đợi xuất kho | serviceTask (Hệ thống tự động) | `Flow_F04`, `Flow_F07`, `Flow_F24`, `Flow_F51` | `Flow_F09` | [ĐỀ XUẤT – chưa có trong code] hàng đợi xuất kho |
| `Task_F05` | Hệ thống xử lý | Trừ tồn kho kho lưu trữ | serviceTask (Hệ thống tự động) | `Flow_F09` | `Flow_F10` | WooCommerce core `wc_reduce_stock_levels` – CHƯA gọi trong dự án |
| `Task_F06` | Hệ thống xử lý | Kết xuất CSV Pick-list | serviceTask (Hệ thống tự động) | `Flow_F10` | `Flow_F11` | [ĐỀ XUẤT – chưa có trong code] export CSV Pick-list |
| `Task_F19` | Hệ thống xử lý | Gọi API 3PL tạo vận đơn | serviceTask (Hệ thống tự động) | `Flow_F28`, `Flow_F34` | `Flow_F30` | [ĐỀ XUẤT – chưa có trong code] KHÔNG có API 3PL nào trong dự án |
| `Task_F20` | Hệ thống xử lý | Ghi mã vận đơn vào đơn hàng | serviceTask (Hệ thống tự động) | `Flow_F31`, `Flow_F37` | `Flow_F38` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F22` | Hệ thống xử lý | Gửi email vận chuyển | serviceTask (Hệ thống tự động) | `Flow_F38` | `Flow_F39` | WooCommerce core email (Customer processing order / Shipped) – chưa cấu hình SMTP |
| `Task_F23` | Hệ thống xử lý | Nhận webhook trạng thái 3PL | receiveTask (Nhận tin từ 3PL) | `Flow_F41` | `Flow_F42` | [ĐỀ XUẤT – chưa có trong code] chưa có webhook 3PL |
| `Task_F03` | Hệ thống xử lý | Chuyển đơn sang On-hold | serviceTask (Hệ thống tự động) | `Flow_F05` | `Flow_F06` | WooCommerce core status `on-hold`; PAYMENT_GATEWAYS.md:85 |
| `Task_F17` | Hệ thống xử lý | Cập nhật trạng thái Completed | serviceTask (Hệ thống tự động) | `Flow_F47` | `Flow_F61` | WooCommerce core status `completed` |
| `Task_F18` | Hệ thống xử lý | Gửi email hoàn tất đơn | serviceTask (Hệ thống tự động) | `Flow_F61` | `Flow_F62` | WooCommerce core email Completed order |
| `Task_F13` | Hệ thống xử lý | Hủy đơn và hoàn tồn kho | serviceTask (Hệ thống tự động) | `Flow_F08`, `Flow_F21`, `Flow_F29` | `Flow_F53` | WooCommerce core `wc_maybe_increase_stock_levels` khi hủy đơn |
| `Task_F14` | Hệ thống xử lý | Hoàn tiền đơn bị hủy | serviceTask (Hệ thống tự động) | `Flow_F54` | `Flow_F56` | [ĐỀ XUẤT – chưa có trong code] Refund chưa nằm trong phạm vi plugin |
| `Task_F15` | Hệ thống xử lý | Gửi email thông báo hủy đơn | serviceTask (Hệ thống tự động) | `Flow_F55`, `Flow_F56` | `Flow_F57` | WooCommerce core email Cancelled order |
| `Task_F21` | Hệ thống xử lý | Retry tạo vận đơn | serviceTask (Hệ thống tự động) | `Flow_F32` | `Flow_F33` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F24` | Hệ thống xử lý | Nhập lại tồn kho hàng hoàn | serviceTask (Hệ thống tự động) | `Flow_F49` | `Flow_F50` | WooCommerce core tăng tồn kho khi đơn về kho |
| `Task_F38` | Hệ thống xử lý | Chuyển trạng thái Refunded | serviceTask (Hệ thống tự động) | `Flow_F52` | `Flow_F58` | WooCommerce core status `refunded` (mặc định có sẵn) |
| `Task_F41` | Hệ thống xử lý | Hoàn tiền đơn giao thất bại | serviceTask (Hệ thống tự động) | `Flow_F58` | `Flow_F59` | [ĐỀ XUẤT – chưa có trong code] chưa tích hợp hoàn tiền |
| `Task_F40` | Hệ thống xử lý | Gửi email thông báo hoàn tiền | serviceTask (Hệ thống tự động) | `Flow_F59` | `Flow_F60` | [ĐỀ XUẤT – chưa có trong code] (gợi ý Mailhog: .env.example) |
| **— LANE: Nhân sự Kho —** | | | | | | |
| `Task_F07` | Nhân sự Kho | Mở Pick-list và chốt đơn gộp | userTask (Nhân sự kho) | `Flow_F11` | `Flow_F12` | [ĐỀ XUẤT – chưa có trong code] gộp đơn thủ công |
| `Task_F08` | Nhân sự Kho | Lấy hàng theo Pick-list (Picking) | userTask (Nhân sự kho) | `Flow_F12` | `Flow_F13` | [ĐỀ XUẤT – chưa có trong code] thao tác kho thủ công |
| `Task_F09` | Nhân sự Kho | Đối chiếu số lượng thực lấy | userTask (Nhân sự kho) | `Flow_F13` | `Flow_F14` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F26` | Nhân sự Kho | Đóng gói và dán nhãn (Packing) | userTask (Nhân sự kho) | `Flow_F15`, `Flow_F22`, `Flow_F23` | `Flow_F25` | [ĐỀ XUẤT – chưa có trong code] đóng gói thủ công |
| `Task_F10` | Nhân sự Kho | Kiểm kê và ghi nhận lệch tồn kho | userTask (Nhân sự kho) | `Flow_F16`, `Flow_F27` | `Flow_F17` | [ĐỀ XUẤT – chưa có trong code] đối chiếu tồn kho vật lý |
| `Task_F11` | Nhân sự Kho | Đổi SKU thay thế trong kho | userTask (Nhân sự kho) | `Flow_F18` | `Flow_F22` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F12` | Nhân sự Kho | Tách lô giao một phần | userTask (Nhân sự kho) | `Flow_F19` | `Flow_F23` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F33` | Nhân sự Kho | Ghi nhận yêu cầu nhập bổ sung | userTask (Nhân sự kho) | `Flow_F20` | `Flow_F24` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F35` | Nhân sự Kho | Tiếp nhận cảnh báo và tạo vận đơn tay | userTask (Nhân sự kho) | `Flow_F35` | `Flow_F36` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F28` | Nhân sự Kho | Nhập mã vận đơn tay vào đơn hàng | userTask (Nhân sự kho) | `Flow_F36` | `Flow_F37` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F29` | Nhân sự Kho | Bàn gàng hàng cho 3PL | userTask (Nhân sự kho) | `Flow_F39` | `Flow_F40` | [ĐỀ XUẤT – chưa có trong code] |
| `Task_F36` | Nhân sự Kho | Chuyển yêu cầu hủy thành đơn hoàn hàng | userTask (Nhân sự kho) | `Flow_F44` | `Flow_F46` | [ĐỀ XUẤT – chưa có trong code] |
| **— LANE: Đối tác 3PL —** | | | | | | |
| `Task_F30` | Đối tác 3PL | Tiếp nhận và nhập kho vận đơn | receiveTask (Nhận tin từ 3PL) | `Flow_F40` | `Flow_F41` | [ĐỀ XUẤT – chưa có trong code] chưa tích hợp 3PL |
| `Task_F31` | Đối tác 3PL | Giao hàng và thu tiền COD | userTask (Nhân sự kho) | `Flow_F43` | `Flow_F45` | COD: PAYMENT_GATEWAYS.md:85 (shipper giao & xác nhận thu tiền) |
| `Task_F32` | Đối tác 3PL | Hoàn hàng về kho | userTask (Nhân sự kho) | `Flow_F46`, `Flow_F48` | `Flow_F49` | [ĐỀ XUẤT – chưa có trong code] |

## 5. Sequence Flow (đường nối) – điều kiện và default

| Flow ID | Từ → Đến | Nhãn điều kiện | Mặc định |
|---|---|---|---|
| `Flow_F01` | `Start_F01` → `Task_F01` | – |  |
| `Flow_F02` | `Task_F01` → `Task_F02` | – |  |
| `Flow_F03` | `Task_F02` → `GW_F01` | – |  |
| `Flow_F04` | `GW_F01` → `Task_F04` | Tồn kho đủ |  |
| `Flow_F05` | `GW_F01` → `Task_F03` | Không đủ tồn kho | ✔ |
| `Flow_F06` | `Task_F03` → `GW_F04` | – |  |
| `Flow_F07` | `GW_F04` → `Task_F04` | Kiểm kê đủ hàng thực tế |  |
| `Flow_F08` | `GW_F04` → `Task_F13` | Vẫn thiếu hàng | ✔ |
| `Flow_F09` | `Task_F04` → `Task_F05` | – |  |
| `Flow_F10` | `Task_F05` → `Task_F06` | – |  |
| `Flow_F11` | `Task_F06` → `Task_F07` | – |  |
| `Flow_F12` | `Task_F07` → `Task_F08` | – |  |
| `Flow_F13` | `Task_F08` → `Task_F09` | – |  |
| `Flow_F14` | `Task_F09` → `GW_F02` | – |  |
| `Flow_F15` | `GW_F02` → `Task_F26` | Số lượng khớp đơn |  |
| `Flow_F16` | `GW_F02` → `Task_F10` | Lệch số lượng thực tế | ✔ |
| `Flow_F17` | `Task_F10` → `GW_F05` | – |  |
| `Flow_F18` | `GW_F05` → `Task_F11` | Còn hàng thay thế |  |
| `Flow_F19` | `GW_F05` → `Task_F12` | Giao một phần |  |
| `Flow_F20` | `GW_F05` → `Task_F33` | Chờ nhập hàng |  |
| `Flow_F21` | `GW_F05` → `Task_F13` | Hủy đơn | ✔ |
| `Flow_F22` | `Task_F11` → `Task_F26` | – |  |
| `Flow_F23` | `Task_F12` → `Task_F26` | – |  |
| `Flow_F24` | `Task_F33` → `Task_F04` | Nhập đủ – gộp lại hàng đợi |  |
| `Flow_F25` | `Task_F26` → `GW_F06` | – |  |
| `Flow_F26` | `GW_F06` → `GW_F07` | Hàng nguyên vẹn |  |
| `Flow_F27` | `GW_F06` → `Task_F10` | Hỏng / hư hỏng | ✔ |
| `Flow_F28` | `GW_F07` → `Task_F19` | Khách không hủy |  |
| `Flow_F29` | `GW_F07` → `Task_F13` | Khách yêu cầu hủy | ✔ |
| `Flow_F30` | `Task_F19` → `GW_F10` | – |  |
| `Flow_F31` | `GW_F10` → `Task_F20` | Tạo vận đơn thành công |  |
| `Flow_F32` | `GW_F10` → `Task_F21` | Lỗi / timeout API | ✔ |
| `Flow_F33` | `Task_F21` → `GW_F11` | – |  |
| `Flow_F34` | `GW_F11` → `Task_F19` | Chưa đạt giới hạn |  |
| `Flow_F35` | `GW_F11` → `Task_F35` | Đã đạt giới hạn | ✔ |
| `Flow_F36` | `Task_F35` → `Task_F28` | – |  |
| `Flow_F37` | `Task_F28` → `Task_F20` | Nhập mã vận đơn tay |  |
| `Flow_F38` | `Task_F20` → `Task_F22` | – |  |
| `Flow_F39` | `Task_F22` → `Task_F29` | – |  |
| `Flow_F40` | `Task_F29` → `Task_F30` | – |  |
| `Flow_F41` | `Task_F30` → `Task_F23` | – |  |
| `Flow_F42` | `Task_F23` → `GW_F09` | – |  |
| `Flow_F43` | `GW_F09` → `Task_F31` | Không có yêu cầu hủy |  |
| `Flow_F44` | `GW_F09` → `Task_F36` | Có yêu cầu hủy / đổi địa chỉ | ✔ |
| `Flow_F45` | `Task_F31` → `GW_F12` | – |  |
| `Flow_F46` | `Task_F36` → `Task_F32` | – |  |
| `Flow_F47` | `GW_F12` → `Task_F17` | Giao thành công |  |
| `Flow_F48` | `GW_F12` → `Task_F32` | Giao thất bại | ✔ |
| `Flow_F49` | `Task_F32` → `Task_F24` | – |  |
| `Flow_F50` | `Task_F24` → `GW_F13` | – |  |
| `Flow_F51` | `GW_F13` → `Task_F04` | Còn hàng – tạo lượt giao lại |  |
| `Flow_F52` | `GW_F13` → `Task_F38` | Không đủ hàng | ✔ |
| `Flow_F53` | `Task_F13` → `GW_F14` | – |  |
| `Flow_F54` | `GW_F14` → `Task_F14` | Đã thu tiền trước |  |
| `Flow_F55` | `GW_F14` → `Task_F15` | COD – chưa thu tiền | ✔ |
| `Flow_F56` | `Task_F14` → `Task_F15` | – |  |
| `Flow_F57` | `Task_F15` → `End_F02` | – |  |
| `Flow_F58` | `Task_F38` → `Task_F41` | – |  |
| `Flow_F59` | `Task_F41` → `Task_F40` | – |  |
| `Flow_F60` | `Task_F40` → `End_F03` | – |  |
| `Flow_F61` | `Task_F17` → `Task_F18` | – |  |
| `Flow_F62` | `Task_F18` → `End_F01` | – |  |

## 6. Tổng hợp

- Số **task** (user/service/send/receive): **35**
- Số **gateway XOR**: **12**
- Số **start event**: **1**
- Số **end event**: **3**
- Số **sequence flow**: **62**
- Số **lane**: **3** trong **5340×900** px
