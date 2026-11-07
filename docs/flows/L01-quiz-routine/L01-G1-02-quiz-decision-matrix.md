# TÀI LIỆU ĐẶC TẢ MA TRẬN QUYẾT ĐỊNH QUIZ (QUIZ DECISION MATRIX - v4)

- **Mã Task:** `L01-G1-02`
- **Tên luồng:** Luồng L01 — Quiz chẩn đoán da & Gợi ý Routine cá nhân hóa
- **Chủ task:** Nguyễn Tấn Phát (PO & BA)
- **Người duyệt:** Lê Nguyễn Quốc An (Scrum Master / DevOps)
- **Phiên bản:** v4 (Dự thảo chính thức trình PO/SM phê duyệt Gate 1 & Gate 3)
- **Ngày lập:** 28/10/2026 (Cập nhật đồng bộ: 01/11/2026)
- **Tham chiếu hệ thống:** [Đặc tả Hệ thống TMĐT KLEER](file:///C:/UIT_ThietKeHeThongTMDT/KLEER/docs/flows/L01-quiz-routine/flow-card.md) · [Approval Gate 3](file:///C:/UIT_ThietKeHeThongTMDT/KLEER/docs/flows/L01-quiz-routine/approval.md) · [`QuizEngineService.php`](file:///C:/UIT_ThietKeHeThongTMDT/KLEER/wp-content/plugins/kleer-plugin/src/Services/QuizEngineService.php)

---

## 1. HƯỚNG DẪN SỬ DỤNG & CẤU TRÚC TÀI LIỆU

1. **Ma trận điểm (Quiz Scoring Matrix):** 6 câu hỏi, 27 lựa chọn.
   - **Q1–Q3:** Cộng điểm tích lũy xác định loại da chính (`skin_type`) và độ nhạy cảm (`is_sensitive`).
   - **Q4:** Xác định vấn đề da chủ đạo (`skin_concern`).
   - **Q5:** Lựa chọn quy mô Routine mong muốn ($N$ từ $2$ đến $6$ sản phẩm).
   - **Q6:** Thói quen trang điểm & chống nắng hằng ngày (quyết định giữ hay bỏ bước Chống nắng và Tẩy trang).
2. **Quy tắc tính toán (Algorithm & Business Rules):** Tham số trọng số, hạng nền 6 bước theo loại da, quy tắc đẩy bước theo vấn đề da, lọc cờ nhạy cảm loại trừ hoạt chất mạnh, và cơ chế fallback khi thiếu SKU.
3. **SKU mẫu & Kiểm tra độ phủ (Catalog Coverage):** 30 SKU thực tế từ `Task08_30_Skincare.csv` đã chuẩn hóa 3 Custom Taxonomies, kèm ma trận 4 bảng kiểm tra độ phủ chéo.
4. **Bộ Test Cases kiểm chứng (Verification Suite):** 11 ca thử nghiệm (6 ca bình thường, 5 ca ngoại lệ) đối soát 100% giữa công thức ma trận tự động và kết quả kỳ vọng nhập tay.

---

## 2. QUY TRÌNH CHỌN BƯỚC ROUTINE (ROUTINE STEP HIERARCHY)

* **6 bước trong chu trình:** Tẩy trang (`makeup_remover`), Sữa rửa mặt (`cleanser`), Toner (`toner`), Serum (`serum`), Dưỡng ẩm (`moisturizer`), Chống nắng (`sunscreen`).
* **Cơ chế xếp hạng (Rank):** Mỗi bước có một chỉ số hạng. Routine sẽ lấy $N$ bước có hạng nhỏ nhất ($N$ do khách chọn tại Q5, từ $2$ đến $6$).
* **Thứ tự giữ bước mặc định:**
  $$\text{Sữa rửa mặt (1)} \rightarrow \text{Chống nắng (2)} \rightarrow \text{Tẩy trang (2.1)} \rightarrow \text{Bước vấn đề đẩy (2.5)} \rightarrow \text{Serum (3)} \rightarrow \text{Toner (4)} \rightarrow \text{Dưỡng ẩm (5)}$$
* **Quy tắc Q6 (Chống nắng / Tẩy trang):**
  - Khách chọn **6C** (*"Không trang điểm và không dùng chống nắng"*): Hệ thống loại bỏ cả bước `sunscreen` và `makeup_remover` (gán hạng $99$).
  - **Ngoại lệ bắt buộc:** Nếu vấn đề chính tại Q4 là **Thâm/nám (`dark_spots`)** hoặc **Lão hóa (`aging`)**, hệ thống vẫn giữ nguyên Chống nắng và Tẩy trang, vì chống tia UV là điều kiện tiên quyết bắt buộc của phác đồ điều trị thâm và ngăn ngừa lão hóa.
  - Khách chọn 6C không rơi vào ngoại lệ sẽ có Routine tối đa 4 bước (`cleanser`, `toner`, `serum`, `moisturizer`), dù tại Q5 chọn 5 hay 6 sản phẩm.
* **Quy tắc đẩy bước theo vấn đề chính (Hạng 2.5):**
  - **Mụn (`acne`):** Đẩy `toner` lên hạng $2.5$ (BHA/AHA). *Ngoại lệ:* Nếu cờ nhạy cảm bật (`is_sensitive = true`), đẩy `serum` lên hạng $2.5$ (do toner trị mụn thường chứa acid nồng độ cao dễ gây bỏng rát).
  - **Lỗ chân lông (`pores`):** Đẩy `toner` lên hạng $2.5$.
  - **Thâm/nám (`dark_spots`):** Đẩy `serum` lên hạng $2.5$.
  - **Lão hóa (`aging`):** Đẩy `serum` lên hạng $2.5$.
  - **Không (`none`):** Giữ nguyên hạng nền, không đẩy bước nào.

---

## 3. BẢNG MA TRẬN TRỌNG SỐ QUIZ (6 CÂU HỎI / 27 LỰA CHỌN)

| Câu | Nội dung câu hỏi | Mã | Nội dung lựa chọn | Nhạy cảm | Khô | Hỗn hợp | Dầu | Thường | Vấn đề chính (`skin_concern`) | Số sản phẩm ($N$) | Chống nắng / Makeup? |
|:---:|:---|:---:|:---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Q1** | Sau khi rửa mặt, khoảng 30 phút không bôi gì, da bạn cảm thấy thế nào? | 1A | Căng, khô, có thể bong tróc | 1 | 3 | 0 | 0 | 0 | — | — | — |
| | | 1B | Thoải mái, không căng cũng không bóng | 0 | 0 | 0 | 0 | 3 | — | — | — |
| | | 1C | Bóng dầu khắp mặt | 0 | 0 | 0 | 3 | 0 | — | — | — |
| | | 1D | Bóng ở trán–mũi–cằm, má bình thường/hơi khô | 0 | 0 | 3 | 1 | 0 | — | — | — |
| | | 1E | Châm chích, đỏ hoặc ngứa | 3 | 1 | 0 | 0 | 0 | — | — | — |
| **Q2** | Cuối ngày, vùng chữ T và hai má trông thế nào? | 2A | Cả mặt bóng dầu, lỗ chân lông to rõ | 0 | 0 | 0 | 3 | 0 | — | — | — |
| | | 2B | Chữ T bóng, má bình thường hoặc khô | 0 | 0 | 3 | 1 | 0 | — | — | — |
| | | 2C | Cả mặt mịn, không bóng, không khô | 0 | 0 | 0 | 0 | 3 | — | — | — |
| | | 2D | Xỉn, khô ráp, có vảy nhỏ | 0 | 3 | 0 | 0 | 0 | — | — | — |
| | | 2E | Hay ửng đỏ, nóng rát dù không làm gì | 3 | 0 | 0 | 0 | 0 | — | — | — |
| **Q3** | Khi đổi sản phẩm, đổi thời tiết hoặc thử hoạt chất mới, da bạn phản ứng ra sao? | 3A | Rất hay đỏ, ngứa, rát | 4 | 0 | 0 | 0 | 0 | — | — | — |
| | | 3B | Thỉnh thoảng hơi đỏ hoặc châm chích nhẹ | 2 | 1 | 0 | 0 | 0 | — | — | — |
| | | 3C | Hiếm khi có phản ứng | 0 | 0 | 0 | 0 | 1 | — | — | — |
| | | 3D | Gần như không bao giờ có phản ứng | 0 | 0 | 0 | 0 | 2 | — | — | — |
| **Q4** | Điều bạn muốn cải thiện nhất ở làn da hiện tại là gì? (chọn 1) | 4A | Mụn, mụn ẩn, mụn viêm | 0 | 0 | 0 | 0 | 0 | `acne` | — | — |
| | | 4B | Thâm mụn, nám, da không đều màu | 0 | 0 | 0 | 0 | 0 | `dark_spots` | — | — |
| | | 4C | Nếp nhăn, da kém đàn hồi | 0 | 0 | 0 | 0 | 0 | `aging` | — | — |
| | | 4D | Lỗ chân lông to, da xỉn, sần sùi | 0 | 0 | 0 | 0 | 0 | `pores` | — | — |
| | | 4E | Không có vấn đề nổi bật, duy trì da khỏe | 0 | 0 | 0 | 0 | 0 | `none` | — | — |
| **Q5** | Bạn muốn routine gồm bao nhiêu sản phẩm (mỗi bước 1 sản phẩm)? | 5A | Nhanh gọn (2 sản phẩm) | 0 | 0 | 0 | 0 | 0 | — | 2 | — |
| | | 5B | Cơ bản (3 sản phẩm) | 0 | 0 | 0 | 0 | 0 | — | 3 | — |
| | | 5C | Tiêu chuẩn (4 sản phẩm) | 0 | 0 | 0 | 0 | 0 | — | 4 | — |
| | | 5D | Chi tiết (5 sản phẩm) | 0 | 0 | 0 | 0 | 0 | — | 5 | — |
| | | 5E | Đầy đủ (6 sản phẩm) | 0 | 0 | 0 | 0 | 0 | — | 6 | — |
| **Q6** | Hằng ngày bạn có trang điểm hoặc dùng kem chống nắng không? | 6A | Có, trang điểm hằng ngày / thường xuyên | 0 | 0 | 0 | 0 | 0 | — | — | Có |
| | | 6B | Không trang điểm, nhưng có dùng kem chống nắng | 0 | 0 | 0 | 0 | 0 | — | — | Có |
| | | 6C | Không trang điểm và không dùng chống nắng | 0 | 0 | 0 | 0 | 0 | — | — | Không |

### Kiểm tra tính toàn vẹn của Ma trận:
* **Điểm tối đa đạt được (Q1–Q3):** Nhạy cảm: 10 điểm | Khô: 7 điểm | Hỗn hợp: 6 điểm | Dầu: 6 điểm | Thường: 8 điểm.
* **Mỗi loại da đều có khả năng thắng:** ĐẠT (đều $> 0$).
* **Thứ tự ưu tiên khi hòa điểm:** `sensitive` > `dry` > `combination` > `oily` > `normal` (ưu tiên an toàn cao nhất cho da).
* **Độ phủ lựa chọn:** Đầy đủ 4 vấn đề da + lựa chọn duy trì (`none`), 5 mức routine (2–6 bước), 2 trạng thái bảo vệ (Có/Không).

---

## 4. QUY TẮC TÍNH ĐIỂM, XẾP HẠNG & DỰ PHÒNG (ALGORITHM RULES)

### 4.1. Tham số hệ thống
| Tham số | Giá trị | Ý nghĩa nghiệp vụ |
|---|:---:|---|
| **Ngưỡng bật cờ nhạy cảm** | $\ge 4$ điểm | Đạt ngưỡng thì bật cờ `is_sensitive = true` bất kể loại da chính là gì. Riêng đáp án **3A** đã có 4 điểm, lập tức kích hoạt cờ. |
| **Trọng số khớp vấn đề chính** | $\times 2$ | Cộng 2 điểm khi SKU có chứa thẻ `skin_concern` của khách (nếu chọn `none` thì cộng 0). |
| **Trọng số khớp loại da riêng** | $\times 1$ | Cộng 1 điểm nếu SKU dành riêng cho loại da của khách (không gắn thẻ "Mọi loại da" `all_skin_types`). |
| **Hạng bước được đẩy** | $2.5$ | Bước được ưu tiên đẩy lên ngay sau Tẩy trang (2.1) và trước Serum (3), Toner (4), Dưỡng ẩm (5). |
| **Hạng Tẩy trang** | $2.1$ | Luôn đi liền sau Chống nắng ($2 + 0.1 = 2.1$). |
| **Hạng khi bước bị bỏ** | $99.0$ | Các bước có hạng $\ge 90$ sẽ bị loại khỏi danh sách cân nhắc. |

### 4.2. Công thức tính điểm SKU (Giai đoạn 2 - Ranking Formula)
$$\text{Product Score} = 2 \times (\text{Khớp } skin\_concern) + 1 \times (\text{Dành riêng cho } skin\_type)$$
* **Quy tắc hòa điểm:**
  1. Ưu tiên sản phẩm có giá bán thấp hơn (`_price` nhỏ hơn).
  2. Nếu tiếp tục bằng giá, ưu tiên sản phẩm có mã `product_id` nhỏ hơn để kết quả đề xuất luôn ổn định (deterministic).

### 4.3. Danh sách hoạt chất mạnh (Strong Actives - Bị loại khi `is_sensitive = true`)
1. **BHA / Salicylic Acid** (Tẩy tế bào chết hóa học)
2. **AHA** (Glycolic Acid, Lactic Acid nồng độ cao)
3. **PHA** (Some By Mi toner #15; Gluconolactone ở #7 là PHA nồng độ thấp nên chấp nhận an toàn)
4. **Vitamin C $\ge 5\%$** (Tinh chất Klairs Freshly Juiced #19, Kiehl's #22, Balance #23)

### 4.4. Quy tắc Fallback & Xử lý biên
1. **Không có SKU khớp vấn đề chính:** Chọn sản phẩm an toàn theo loại da (điểm vấn đề = 0), chọn giá thấp nhất.
2. **Cờ nhạy cảm bật nhưng mọi SKU đặc trị đều chứa hoạt chất mạnh:** Bỏ qua vấn đề đặc trị, chọn sản phẩm an toàn, lành tính theo loại da (Ví dụ: TC08 chọn Serum rau má Centella #21 thay vì serum chứa Vitamin C).
3. **Kho thiếu bước:** Bỏ bước thiếu và trả về mảng `warnings`. Nếu routine khả dụng dưới 2 sản phẩm, hệ thống ghi log cảnh báo dữ liệu nghiêm trọng cho quản trị viên.
4. **Chọn 4E (Duy trì da khỏe):** Vấn đề = `none`, không đẩy bước nào lên hạng 2.5, chỉ xếp hạng theo loại da.
5. **Validation:** Bắt buộc trả lời đủ 6 câu; thiếu hoặc thừa câu đều bị chặn với HTTP 400.

---

## 5. PHÂN TÍCH ĐỘ PHỦ DỮ LIỆU TỪ 30 SKU MẪU (DATA GAP ANALYSIS)

Dựa trên bảng đếm 30 SKU thực tế trích xuất từ `Task08_30_Skincare.csv`:

```
BẢNG 1: Loại da × Bước (Tất cả 30 SKU)
┌─────────────┬───────────┬─────────────┬───────┬───────┬──────────┬────────────┐
│ Loại da     │ Tẩy trang │ Sữa rửa mặt │ Toner │ Serum │ Dưỡng ẩm │ Chống nắng │
├─────────────┼───────────┼─────────────┼───────┼───────┼──────────┼────────────┤
│ Nhạy cảm    │     0     │      4      │   2   │   5   │    0     │     3      │
│ Khô         │     0     │      3      │   3   │   5   │    0     │     1      │
│ Hỗn hợp     │     0     │      3      │   2   │   4   │    0     │     3      │
│ Dầu         │     0     │      5      │   3   │   4   │    0     │     3      │
│ Thường      │     0     │      2      │   1   │   3   │    0     │     1      │
└─────────────┴───────────┴─────────────┴───────┴───────┴──────────┴────────────┘

BẢNG 2: Loại da × Bước (Chỉ SKU an toàn - Sau khi lọc cờ nhạy cảm)
┌─────────────┬───────────┬─────────────┬───────┬───────┬──────────┬────────────┐
│ Loại da     │ Tẩy trang │ Sữa rửa mặt │ Toner │ Serum │ Dưỡng ẩm │ Chống nắng │
├─────────────┼───────────┼─────────────┼───────┼───────┼──────────┼────────────┤
│ Nhạy cảm    │     0     │      3      │   2   │   3   │    0     │     3      │
│ Khô         │     0     │      2      │   3   │   2   │    0     │     1      │
│ Hỗn hợp     │     0     │      2      │   1   │   1   │    0     │     3      │
│ Dầu         │     0     │      4      │   1   │   2   │    0     │     3      │
│ Thường      │     0     │      1      │   1   │   1   │    0     │     1      │
└─────────────┴───────────┴─────────────┴───────┴───────┴──────────┴────────────┘

BẢNG 3: Vấn đề da × Bước (Tất cả 30 SKU)
┌───────────────┬───────────┬─────────────┬───────┬───────┬──────────┬────────────┐
│ Vấn đề        │ Tẩy trang │ Sữa rửa mặt │ Toner │ Serum │ Dưỡng ẩm │ Chống nắng │
├───────────────┼───────────┼─────────────┼───────┼───────┼──────────┼────────────┤
│ Mụn           │     0     │      3      │   2   │   1   │    0     │     1      │
│ Thâm/nám      │     0     │      0      │   0   │   4   │    0     │     0      │
│ Lão hóa       │     0     │      0      │   0   │   1   │    0     │     0      │
│ Lỗ chân lông  │     0     │      1      │   3   │   0   │    0     │     0      │
└───────────────┴───────────┴─────────────┴───────┴───────┴──────────┴────────────┘
```

### Các phát hiện và rủi ro dữ liệu nghiêm trọng:
1. **Thiếu hoàn toàn 2 bước:** Cả kho 30 SKU **không có bất kỳ sản phẩm nào cho Tẩy trang và Dưỡng ẩm**. Bất kỳ Routine nào chọn bước này sẽ bị thiếu sản phẩm cho tới khi pipeline crawler L08 cào đủ $\ge 500\text{ SKU}$.
2. **Da Thường không có sản phẩm chuyên biệt:** 100% SKU cho da Thường là sản phẩm "Mọi loại da". Chống nắng cho da Thường (#25) và da Khô (#25) chỉ có đúng 1 lựa chọn duy nhất.
3. **Nghẽn hoạt chất điều trị Thâm/nám cho da Nhạy cảm:** Tất cả serum trị thâm cho mọi loại da (#19, #22, #23) đều chứa Vitamin C $\ge 5\%$, còn serum Niacinamide (#16) chỉ gắn cho da Dầu. Da nhạy cảm bị thâm nám sẽ phải fallback sang serum rau má Centella #21 (Xem TC08).
4. **Vấn đề Lão hóa quá mỏng:** Chỉ có duy nhất 1 serum (#20 - L'Oreal Revitalift HA dành cho da Khô).

---

## 6. MA TRẬN 11 TEST CASES KIỂM CHỨNG (TC01 – TC11)

| Mã | Mô tả ca kiểm thử | Q1..Q6 | Kết quả tính toán hệ thống | Kỳ vọng nhập tay (Expected) | Kết quả | Sản phẩm kỳ vọng được chọn |
|:---:|:---|:---:|:---|:---|:---:|:---|
| **TC01** | Da dầu mụn, có dùng chống nắng, chọn 4 sản phẩm (Mụn đẩy Toner) | 1C, 2A, 3D, 4A, 5C, 6B | Dầu · Không NC · Mụn · 4 SP · Có CN<br>$\rightarrow$ Tẩy trang + SRM + Toner + Chống nắng | Trùng khớp 100% | **ĐẠT** | SRM: #1 CeraVe Foaming (380k)<br>Toner: #15 Some By Mi (350k)<br>CN: #28 Eucerin Dry Touch (500k)<br>*(Tẩy trang thiếu SKU)* |
| **TC02** | *Ngoại lệ:* Da khô lão hóa, KHÔNG dùng CN nhưng vẫn giữ CN+Tẩy trang do ngoại lệ Lão hóa | 1A, 2D, 3C, 4C, 5B, 6C | Khô · Không NC · Lão hóa · 3 SP · Không CN<br>$\rightarrow$ Tẩy trang + SRM + Chống nắng | Trùng khớp 100% | **ĐẠT** | SRM: #2 CeraVe Hydrating (380k)<br>CN: #25 Anessa Perfect UV (550k)<br>*(Tẩy trang thiếu SKU)* |
| **TC03** | Da nhạy cảm, không vấn đề, không dùng CN, 3 sản phẩm $\rightarrow$ Bỏ CN & Tẩy trang | 1E, 2E, 3A, 4E, 5B, 6C | Nhạy cảm (10đ) · Có NC · Không · 3 SP · Không CN<br>$\rightarrow$ Sữa rửa mặt + Toner + Serum | Trùng khớp 100% | **ĐẠT** | SRM: #6 Simple Refreshing (150k)<br>Toner: #9 Klairs Supple (300k)<br>Serum: #21 Skin1004 Centella (400k) |
| **TC04** | Da hỗn hợp thâm mụn, có makeup, 5 sản phẩm (Thâm đẩy Serum) | 1D, 2B, 3C, 4B, 5D, 6A | Hỗn hợp · Không NC · Thâm/nám · 5 SP · Có CN<br>$\rightarrow$ Tẩy trang + SRM + Toner + Serum + Chống nắng | Trùng khớp 100% | **ĐẠT** | SRM: #8 Innisfree Green Tea (220k)<br>Toner: #12 Cosrx AHA/BHA (320k)<br>Serum: #23 Balance Vit C (180k)<br>CN: #26 Skin Aqua Tone Up (180k) |
| **TC05** | Da thường, duy trì da khỏe, không CN, 4 sản phẩm | 1B, 2C, 3D, 4E, 5C, 6C | Thường · Không NC · Không · 4 SP · Không CN<br>$\rightarrow$ SRM + Toner + Serum + Dưỡng ẩm | Trùng khớp 100% | **ĐẠT** | SRM: #5 Cosrx Low pH (250k)<br>Toner: #10 Thayers Witch Hazel (250k)<br>Serum: #19 Klairs Vit Drop (350k)<br>*(Dưỡng ẩm thiếu SKU)* |
| **TC06** | *Ngoại lệ:* Da dầu nhưng hay kích ứng (cờ NC bật $\rightarrow$ loại hoạt chất mạnh, đẩy Serum thay vì Toner) | 1C, 2A, 3A, 4A, 5D, 6B | Dầu · Có NC · Mụn · 5 SP · Có CN<br>$\rightarrow$ Tẩy trang + SRM + Toner + Serum + Chống nắng | Trùng khớp 100% | **ĐẠT** | SRM: #1 CeraVe Foaming (380k)<br>Toner: #10 Thayers (250k - lành tính)<br>Serum: #16 TO Niacinamide (250k)<br>CN: #28 Eucerin Dry Touch (500k) |
| **TC07** | *Ngoại lệ:* Hòa điểm Dầu = Khô (3-3) $\rightarrow$ Ưu tiên Khô; Lỗ chân lông đẩy Toner; Không CN | 1C, 2D, 3C, 4D, 5B, 6C | Khô · Không NC · Lỗ chân lông · 3 SP · Không CN<br>$\rightarrow$ Sữa rửa mặt + Toner + Serum | Trùng khớp 100% | **ĐẠT** | SRM: #5 Cosrx Low pH (250k)<br>Toner: #10 Thayers (250k)<br>Serum: #23 Balance Vit C (180k) |
| **TC08** | *Ngoại lệ:* Da nhạy cảm thâm nám, 6 SP, không có serum thâm an toàn $\rightarrow$ Fallback serum dịu nhẹ | 1E, 2E, 3B, 4B, 5E, 6C | Nhạy cảm · Có NC · Thâm/nám · 6 SP · Không CN (nhưng giữ CN)<br>$\rightarrow$ Đủ 6 bước lý thuyết | Trùng khớp 100% | **ĐẠT** | SRM: #6 Simple (150k)<br>Toner: #9 Klairs (300k)<br>Serum: #21 Skin1004 (400k - an toàn)<br>CN: #30 Skin1004 Air-Fit (350k) |
| **TC09** | Da dầu mụn, trang điểm, 6 sản phẩm (đủ 6 bước) | 1C, 2A, 3D, 4A, 5E, 6A | Dầu · Không NC · Mụn · 6 SP · Có CN<br>$\rightarrow$ Đủ 6 bước lý thuyết | Trùng khớp 100% | **ĐẠT** | SRM: #1 CeraVe Foaming (380k)<br>Toner: #15 Some By Mi (350k)<br>Serum: #16 TO Niacinamide (250k)<br>CN: #28 Eucerin (500k) |
| **TC10** | *Ngoại lệ:* Da dầu mụn, không CN, chọn 6 SP $\rightarrow$ Thực tế chỉ có 4 SP khả dụng | 1C, 2A, 3D, 4A, 5E, 6C | Dầu · Không NC · Mụn · 6 SP (thực tế 4) · Không CN<br>$\rightarrow$ SRM + Toner + Serum + Dưỡng ẩm | Trùng khớp 100% | **ĐẠT** | SRM: #1 CeraVe Foaming (380k)<br>Toner: #15 Some By Mi (350k)<br>Serum: #16 TO Niacinamide (250k)<br>*(Dưỡng ẩm thiếu SKU)* |
| **TC11** | Da thường, không CN, chọn 2 sản phẩm (mức tối thiểu) | 1B, 2C, 3D, 4E, 5A, 6C | Thường · Không NC · Không · 2 SP · Không CN<br>$\rightarrow$ Sữa rửa mặt + Serum | Trùng khớp 100% | **ĐẠT** | SRM: #5 Cosrx Low pH (250k)<br>Serum: #19 Klairs Vit Drop (350k) |

---

## 7. CÁC QUYẾT ĐỊNH & GIẢ ĐỊNH CẦN PO NGUYỄN TẤN PHÁT XÁC NHẬN CHÍNH THỨC

1. **Cập nhật Definition of Done (DoD):**
   - DoD cũ trong sheet Tasks ghi nhận *"5 câu hỏi, 4 loại da, 4 bước"*.
   - Khuyến nghị PO cập nhật chính thức thành: **"6 câu hỏi, 5 loại da (thêm da Thường), 4 vấn đề, 6 bước Routine"** để đồng bộ với bản v4 và toàn bộ hệ thống API.
2. **Q6 & Cơ chế Chống nắng:**
   - Xác nhận câu hỏi Q6 và quy tắc loại trừ `sunscreen` + `makeup_remover` đối với khách 6C, ngoại trừ nhóm Thâm/nám và Lão hóa.
3. **Cờ nhạy cảm & Hoạt chất mạnh:**
   - Xác nhận ngưỡng $\ge 4$ điểm nhạy cảm tự động loại trừ 4 nhóm hoạt chất mạnh (BHA, AHA, PHA, Vitamin C $\ge 5\%$) ngay cả khi khách thuộc loại da Dầu hoặc Hỗn hợp.
4. **Hạng ưu tiên và trọng số chọn SKU:**
   - Xác nhận trọng số ưu tiên: $2 \times (\text{vấn đề chính}) + 1 \times (\text{loại da chuyên biệt})$.
   - Xác nhận việc nâng bước đặc trị lên hạng $2.5$.
5. **Điều chỉnh Kế hoạch & Sheet Tasks:**
   - Task `L01-G1-02` hiện xếp ở **Tuần 05** trong khi các task phụ thuộc gồm `L01-G3-01` (Tuần 3), `L01-G4-01` & `L01-G5-03` (Tuần 4) cần ma trận này làm đầu vào. Khuyến nghị PO và SM dời lịch `L01-G1-02` lên **Tuần 03**.
   - Cập nhật công thức VLOOKUP tra cứu ngày trong sheet `Tasks` từ vùng `A3:D12` thành `A2:D11` để tránh bỏ sót Tuần 01.
