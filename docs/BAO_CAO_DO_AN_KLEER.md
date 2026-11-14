# ĐỒ ÁN MÔN HỌC: THIẾT KẾ HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ (EC312)

---

## TRANG BÌA (COVER PAGE)

* **Trường:** Đại học Công nghệ Thông tin – ĐHQG TP.HCM (UIT)
* **Khoa:** Hệ thống Thông tin
* **Tên môn học:** Thiết kế Hệ thống Thương mại Điện tử (EC312)
* **Tên đề tài:** **Thiết kế Hệ thống Thương mại Điện tử Kinh doanh Sản phẩm Chăm sóc Da – KLEER**
* **Giảng viên hướng dẫn:** ThS. Trịnh Trọng Tín
* **Học kỳ / Năm học:** Học kỳ 1 – Năm học 2026–2027
* **Ngày nộp:** .../11/2026

---

## THÔNG TIN NHÓM & PHÂN CÔNG NHIỆM VỤ (TEAM INFORMATION)

| STT | Họ và Tên | MSSV | Vai trò dự án (Role) | Phần phụ trách trong Báo cáo & Hệ thống |
| :-: | :--- | :---: | :--- | :--- |
| **1** | **Nguyễn Tấn Phát** | *(Điền MSSV)* | Nhóm trưởng (PO & BA) | Phụ trách **Chương 1** (Giới thiệu), **Chương 2** (Phân tích yêu cầu), **Chương 5** (Demo & Báo cáo), **Chương 6** (Kết luận & Hướng phát triển), **Chương 7** & Tổng hợp Báo cáo. |
| **2** | **Lê Nguyễn Quốc An** | *(Điền MSSV)* | Scrum Master & DevOps | Phụ trách **Chương 4** (Thiết kế hệ thống: 4.1 Kiến trúc 3-Tier & Microkernel OOP, 4.2 Thiết kế CSDL 3NF & WooCommerce HPOS, 4.3 Thiết kế UI/UX tổng thể). |
| **3** | **Bùi Hiếu Nhân** | *(Điền MSSV)* | Plugin Dev 1 (Backend Quiz, REST) | Phụ trách **Chương 3 – Chức năng 1:** *Skin Quiz & Đề xuất Routine cá nhân hóa* (Mục 3.1 $\rightarrow$ 3.6). |
| **4** | **Nguyễn Ngọc Thảo Uyên** | *(Điền MSSV)* | QA, Automation & Instant Refund | Phụ trách **Chương 3 – Chức năng 6:** *Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước* (Mục 3.1 $\rightarrow$ 3.6). |
| **5** | **Lâm Trúc Uyên** | *(Điền MSSV)* | Theme Dev 1 (Catalog, UI) | Phụ trách **Chương 3 – Chức năng 2:** *Khám phá Danh mục (Catalog) & Bộ lọc sản phẩm đa tiêu chí* (Mục 3.1 $\rightarrow$ 3.6). |
| **6** | **Huỳnh Ngọc Nhã Uyên** | *(Điền MSSV)* | Theme Dev 2 (Cart/Quiz UX) | Phụ trách **Chương 3 – Chức năng 3:** *Giỏ hàng Routine Bundle (Giảm 15%) & Cảnh báo xung đột hoạt chất* (Mục 3.1 $\rightarrow$ 3.6). |
| **7** | **Trần Ngọc Minh Tuyền** | *(Điền MSSV)* | Integration Dev (Payment) | Phụ trách **Chương 3 – Chức năng 4:** *Checkout & Tích hợp Thanh toán trực tuyến (COD, VietQR, VNPay Sandbox)* (Mục 3.1 $\rightarrow$ 3.6). |
| **8** | **Nguyễn Minh Trí** | *(Điền MSSV)* | Plugin Dev 2 (HPOS, Mailer) | Phụ trách **Chương 3 – Chức năng 5:** *Quản lý vòng đời đơn hàng trên bảng phẳng HPOS & Hóa đơn Email QR* (Mục 3.1 $\rightarrow$ 3.6). |

> **Quy ước làm việc nhóm cho Chương 3 & Chương 4:**
> * Toàn bộ biểu đồ UML và ERD trong Chương 3 và Chương 4 **bắt buộc được vẽ bằng công cụ Draw.io** (`.drawio`), xuất ảnh độ phân giải cao (`.png`) và bàn giao kèm nội dung thuyết minh qua **Google Docs** cho Nhóm trưởng (Nguyễn Tấn Phát).
> * Trước khi vẽ biểu đồ và viết đặc tả, các thành viên **bắt buộc phải đọc kỹ nguồn tham chiếu thực tế (Reference)** đã được giảng viên phê duyệt cho từng chức năng để đảm bảo quy trình sát thực tế vận hành, không suy diễn cảm tính.

---

## 1. GIỚI THIỆU (INTRODUCTION)
*(Phụ trách: Nguyễn Tấn Phát)*

### 1.1. Lý do chọn đề tài & Bối cảnh thị trường
Thị trường thương mại điện tử (TMĐT) ngành hàng Chăm sóc da (Skincare) tại Việt Nam đang tăng trưởng mạnh mẽ với sự tham gia của nhóm khách hàng trẻ (đặc biệt là thế hệ Gen Z và nhân viên văn phòng bận rộn). Tuy nhiên, quá trình chuyển đổi từ người truy cập thành người mua hàng thực tế trên các website mỹ phẩm độc lập (Brand/Retail E-commerce) đang đối mặt với **4 rào cản lớn**:

1. **Rào cản nhận thức về thành phần hóa mỹ phẩm (Ingredient Literacy Barrier):** Các sản phẩm chăm sóc da hiện đại được định danh bằng danh pháp quốc tế INCI (*International Nomenclature of Cosmetic Ingredients*) với hàng loạt hoạt chất chuyên sâu như *AHA (Glycolic Acid, Lactic Acid)*, *BHA (Salicylic Acid)*, *Retinoids (Retinol, Adapalene, Tretinoin)*, *Niacinamide*, *Vitamin C (L-Ascorbic Acid)*. Người tiêu dùng phổ thông gặp nhiều khó khăn khi tự xác định hoạt chất nào phù hợp với loại da và tình trạng da hiện tại của mình.
2. **Trải nghiệm mua sắm dàn trải kiểu "Siêu thị danh mục" (Catalog-First Overload):** Hầu hết các sàn TMĐT tổng hợp và cửa hàng trực tuyến truyền thống chỉ cung cấp bộ lọc theo Thương hiệu, Danh mục (Sữa rửa mặt, Kem dưỡng...) hoặc Khoảng giá. Khi khách hàng có một vấn đề cụ thể (ví dụ: *"Da dầu, nhạy cảm, đang có mụn ẩn thì cần kết hợp những sản phẩm nào theo thứ tự nào?"*), hệ thống danh mục truyền thống không thể đưa ra câu trả lời trọn gói.
3. **Rủi ro kích ứng do xung đột hoạt chất khi tự phối sản phẩm:** Khi khách hàng tự mua lẻ nhiều sản phẩm từ các thương hiệu khác nhau trong cùng một giỏ hàng, họ rất dễ kết hợp nhầm các nhóm hoạt chất đối kháng hoặc có nguy cơ kích ứng cao khi dùng chung một buổi (điển hình là nhóm *Acid tẩy tế bào chết AHA/BHA* kết hợp trực tiếp với nhóm *Retinoids*), dẫn đến tổn thương hàng rào bảo vệ da.
4. **Rào cản niềm tin đối với nền tảng TMĐT mới (Trust & Branding Deficit):** Đối với một hệ thống website bán lẻ mới ra mắt, khách hàng thường có tâm lý dè dặt: sợ tư vấn thiếu căn cứ khoa học và lo ngại quy trình đổi trả/hoàn tiền phức tạp, giam tiền lâu nếu sản phẩm giao sai hoặc gây kích ứng.

Từ thực tiễn đó, nhóm quyết định thực hiện đề tài **“Thiết kế Hệ thống Thương mại Điện tử Kinh doanh Sản phẩm Chăm sóc Da – KLEER”**. Thay vì tiếp cận theo lối mòn bán lẻ từng sản phẩm rời rạc, KLEER được thiết kế theo mô hình **Bán hàng có định hướng (Guided Selling)** dựa trên dữ liệu thành phần khoa học: dẫn dắt khách hàng từ bước chẩn đoán tình trạng da qua **Skin Quiz**, tự động thiết lập **Phác đồ chăm sóc da cá nhân hóa (Personalized Routine)**, kiểm soát **an toàn xung đột hoạt chất trong giỏ hàng**, đến cam kết bảo vệ người mua bằng cơ chế **Hoàn tiền nhanh vào Số dư tài khoản (Instant Store Credit Refund)**.

### 1.2. Hệ thống Nguồn Tham chiếu Thực tế (Reference Framework)
Để đảm bảo tính chuẩn xác về mặt nghiệp vụ thương mại điện tử và cơ sở khoa học da liễu, mọi quy trình và chức năng của KLEER đều được thiết kế dựa trên **4 nguồn tham chiếu (References) thực tế** đã vận hành thành công trên thế giới và tại Việt Nam:

| STT | Nguồn tham chiếu (Reference) | Cấp độ tham chiếu | Phạm vi ứng dụng và tùy biến vào hệ thống KLEER |
| :-: | :--- | :--- | :--- |
| **1** | **Curology** (`curology.com`) | Hệ thống đã số hóa (Quốc tế) | Tham chiếu quy trình **Consultation Skin Quiz**: Cấu trúc bộ câu hỏi thu thập dữ liệu loại da, vấn đề da chủ đạo, tiền sử nhạy cảm và thói quen sinh hoạt để đề xuất phác đồ cá nhân hóa. Nhóm đã Việt hóa và tùy biến ma trận chấm điểm phù hợp với đặc điểm khí hậu nóng ẩm tại Việt Nam. |
| **2** | **INCIDecoder** (`incidecoder.com`) | Cơ sở dữ liệu khoa học thành phần | Tham chiếu chuẩn phân loại hoạt chất hóa mỹ phẩm (**INCI**), chức năng từng thành phần (*exfoliant, retinoid, humectant, soothing*) và quy tắc tương tác hoạt chất để xây dựng **Taxonomy sản phẩm** cùng **Cơ chế cảnh báo xung đột hoạt chất** trong giỏ hàng. |
| **3** | **Hasaki Beauty & Clinic** (`hasaki.vn`) | Hệ thống bán lẻ & Chính sách vận hành tại VN | Tham chiếu cấu trúc **Danh mục sản phẩm (Catalog)**, thông tin chi tiết sản phẩm (PDP), quy trình **Xử lý và hoàn tất đơn hàng (Order Fulfillment)** cũng như khung **Chính sách đổi trả mỹ phẩm** tại thị trường Việt Nam. |
| **4** | **Shopee Việt Nam** (`help.shopee.vn`) | Hệ thống TMĐT đã số hóa | Tham chiếu quy trình **Trả hàng & Hoàn tiền ngay (Instant Refund)** vào Số dư tài khoản/Voucher ngay khi khách hàng bàn giao kiện hàng trả và cung cấp mã vận đơn, giúp giải quyết bài toán niềm tin và giữ chân dòng tiền trong hệ sinh thái. |

### 1.3. Mục tiêu nghiên cứu
* **Mục tiêu tổng quát:** Phân tích, thiết kế và triển khai nguyên mẫu (prototype) hoàn chỉnh cho hệ thống TMĐT B2C chuyên biệt ngành hàng chăm sóc da **KLEER**, kết hợp tư vấn cá nhân hóa và kiểm soát an toàn hoạt chất.
* **Mục tiêu cụ thể:**
  1. Chuẩn hóa quy trình nghiệp vụ cốt lõi và mô hình hóa đầy đủ bằng bộ biểu đồ **UML 2.5** (*Activity Diagram, Use Case Diagram, Use Case Specification, Class Diagram, Sequence Diagram, State Machine Diagram*).
  2. Thiết kế kiến trúc hệ thống phân tầng (**3-Tier Architecture** kết hợp **Microkernel OOP Plugin**) trên nền tảng WordPress/WooCommerce, tách biệt hoàn toàn tầng giao diện (`kleer-theme`) và tầng xử lý nghiệp vụ (`kleer-plugin`, `kleer-payments`, `kleer-mailer`).
  3. Thiết kế cơ sở dữ liệu đạt chuẩn hóa **3NF** ở mức luận lý và ánh xạ vật lý sang kiến trúc bảng phẳng hiệu năng cao **WooCommerce HPOS (High-Performance Order Storage)** cùng hệ thống thuộc tính sản phẩm chuyên biệt (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`).
  4. Hiện thực hóa và kiểm thử bản Demo chạy thực tế trên môi trường **Docker Compose** với dữ liệu sản phẩm thật thu thập từ Hasaki.vn và chuẩn hóa theo INCIDecoder.

### 1.4. Phạm vi hệ thống (System Scope)
* **Trong phạm vi (In-Scope):**
  * **Chức năng 1 (L01):** Trắc nghiệm chẩn đoán da (Skin Quiz 6 câu hỏi) và thuật toán tính điểm trọng số đề xuất Routine 2–6 bước.
  * **Chức năng 2 (L02):** Duyệt danh mục sản phẩm (Catalog), xem chi tiết sản phẩm (PDP) kèm bảng thành phần INCI và lọc sản phẩm đa tiêu chí không tải lại trang (AJAX Filter).
  * **Chức năng 3 (L03):** Thêm nhanh trọn bộ Routine vào giỏ hàng (1-Click Routine to Cart), tự động chiết khấu **15% Combo Routine** (khi giỏ hàng có từ $\ge 3$ bước quy chuẩn) và cảnh báo xung đột hoạt chất (*AHA/BHA* vs *Retinoids*).
  * **Chức năng 4 (L04):** Đặt hàng (Checkout) và tích hợp 3 phương thức thanh toán: Tiền mặt khi nhận hàng (COD), Chuyển khoản mã QR động (VietQR Napas) và Cổng thanh toán trực tuyến VNPay Sandbox (chữ ký số HMAC-SHA512).
  * **Chức năng 5 (L05):** Quản lý vòng đời đơn hàng trên bảng phẳng HPOS (`wp_wc_orders`), tự động trừ/hoàn tồn kho theo trạng thái và gửi Email hóa đơn HTML đính kèm mã QR tra cứu đơn hàng.
  * **Chức năng 6 (L07):** Xử lý quy trình **Đổi trả 7 ngày & Hoàn tiền nhanh Nhánh 1 (Instant Store Credit Refund)** theo mô hình 3 bước tham chiếu từ Shopee và Hasaki.
* **Ngoài phạm vi (Out-of-Scope):**
  * Tích hợp trực tiếp API thời gian thực với các hãng vận chuyển thứ ba (GHN, GHTK) — hệ thống mô phỏng qua trạng thái đơn hàng và mã vận đơn.
  * Quy trình hoàn tiền Nhánh 2 (Standard Bank Refund – đối soát kế toán hoàn tiền mặt qua ngân hàng sau khi kiểm kho vật lý) — được định hướng tại Chương 6.

### 1.5. Cấu trúc báo cáo
Báo cáo được tổ chức thành 7 chương theo đúng khung tiêu chuẩn của môn học EC312:
* **Chương 1 – Giới thiệu:** Trình bày bối cảnh thị trường, hệ thống nguồn tham chiếu thực tế, mục tiêu và phạm vi đề tài.
* **Chương 2 – Phân tích yêu cầu:** Mô tả bài toán, phân tích Lean Canvas, ma trận SWOT, danh sách yêu cầu chức năng (FR) và phi chức năng (NFR).
* **Chương 3 – Mô hình hóa hệ thống:** Hệ thống hóa toàn bộ biểu đồ UML (*Activity, Use Case, Class, Sequence, State Machine*) cho 6 chức năng cốt lõi.
* **Chương 4 – Thiết kế hệ thống:** Thiết kế kiến trúc 3-Tier & OOP Plugin, thiết kế CSDL (ERD 3NF & WooCommerce HPOS) và thiết kế giao diện UI/UX.
* **Chương 5 – Demo & Báo cáo:** Trình bày dữ liệu thực nghiệm, kịch bản chạy Demo End-to-End và kết quả kiểm thử.
* **Chương 6 – Kết luận và hướng phát triển:** Tổng kết kết quả, đối chiếu so sánh KLEER với các nền tảng hiện có và đề xuất hướng mở rộng.
* **Chương 7 – Tài liệu tham khảo & Phụ lục:** Danh mục nguồn trích dẫn xác thực và tài liệu kỹ thuật bổ trợ.

---

## 2. PHÂN TÍCH YÊU CẦU (REQUIREMENT ANALYSIS)
*(Phụ trách: Nguyễn Tấn Phát)*

### 2.1. Mô tả bài toán & Phân tích mô hình kinh doanh

#### 2.1.1. Mô tả bài toán nghiệp vụ
Hệ thống **KLEER** vận hành theo mô hình **TMĐT B2C (Business-to-Consumer)** chuyên doanh sản phẩm chăm sóc da chính hãng. Khi khách hàng truy cập hệ thống, họ có thể lựa chọn hai hành trình mua sắm liên thông với nhau:
1. **Hành trình Tư vấn Cá nhân hóa (Guided Journey – Dành cho khách hàng mới hoặc chưa rõ chu trình dưỡng da):** Khách hàng thực hiện bài **Skin Quiz** gồm 6 câu hỏi khảo sát sinh lý da và thói quen chăm sóc (tham chiếu từ *Curology*). Hệ thống phân tích hồ sơ da (*Loại da, Vấn đề ưu tiên, Ngưỡng nhạy cảm*) và trả về một **Phác đồ Routine (từ 2 đến 6 bước)** gồm các sản phẩm tương thích, loại bỏ các hoạt chất mạnh nếu da nhạy cảm. Khách hàng có thể bấm **"Thêm trọn bộ vào giỏ hàng" (1-Click Add)** để nhận ngay ưu đãi **giảm giá 15% Combo Routine**.
2. **Hành trình Tự chọn (Self-Discovery Journey – Dành cho khách hàng đã có kiến thức skincare):** Khách hàng duyệt **Catalog**, sử dụng **Bộ lọc AJAX đa tiêu chí** (*Loại da, Vấn đề da, Bước chu trình, Hoạt chất chủ đạo, Thương hiệu, Khoảng giá*) để tìm sản phẩm mong muốn và thêm vào giỏ hàng. Tại giỏ hàng, cơ chế **Cảnh báo xung đột hoạt chất** (tham chiếu dữ liệu *INCIDecoder*) tự động quét toàn bộ sản phẩm; nếu phát hiện cặp chất kỵ nhau (*AHA/BHA* đi cùng *Retinoids*), hệ thống hiển thị cảnh báo da liễu để khách hàng điều chỉnh hoặc lưu ý dùng xen kẽ sáng/tối.

Sau khi xác nhận giỏ hàng, khách hàng tiến hành **Checkout** qua COD, VietQR hoặc VNPay Sandbox. Đơn hàng được lưu trữ vào các bảng chuyên biệt **WooCommerce HPOS**, tự động trừ tồn kho và gửi **Email hóa đơn HTML đính kèm hướng dẫn thứ tự dùng Routine & mã QR tra cứu đơn**. Trong vòng **7 ngày** kể từ khi đơn hàng hoàn tất (`Completed`), nếu sản phẩm gặp lỗi vận chuyển, giao sai hoặc gây kích ứng, khách hàng có thể gửi yêu cầu đổi trả trực tuyến. Ngay khi Admin duyệt yêu cầu và khách hàng cập nhật **Mã vận đơn gửi trả hàng**, hệ thống tự động kích hoạt **Instant Refund (Hoàn tiền nhanh)**: phát hành ngay mã **Store Credit** trị giá 100% đơn hàng vào tài khoản/email để khách hàng mua ngay sản phẩm thay thế mà không phải chờ kiện hàng hoàn về tới kho.

#### 2.1.2. Mô hình Kinh doanh Lean Canvas
| Khối (Block) | Nội dung chi tiết tại KLEER |
| :--- | :--- |
| **1. Vấn đề (Problems)** | • Khách hàng không biết đọc bảng thành phần INCI để chọn đúng sản phẩm cho loại da.<br>• Mua lẻ nhiều món dễ gặp xung đột hoạt chất gây kích ứng (AHA/BHA + Retinol).<br>• Thiếu niềm tin vào website mỹ phẩm mới; ngại thủ tục hoàn tiền lâu khi đổi trả.<br>• Hiện tượng *Showrooming*: Xem tư vấn một nơi nhưng sang sàn TMĐT mua lẻ từng món. |
| **2. Phân khúc khách hàng (Customer Segments)** | • **Gen Z (18–27 tuổi):** Quan tâm chăm sóc da khoa học (*Skintellectuals*), thích trải nghiệm số hóa nhanh gọn trên di động.<br>• **Nhân viên văn phòng bận rộn (24–35 tuổi):** Không có thời gian tự nghiên cứu từng chất, cần một chu trình (Routine) trọn gói an toàn và tiết kiệm thời gian. |
| **3. Giải pháp giá trị độc tôn (Unique Value Proposition)** | *"Chuyên gia thiết kế Routine chăm sóc da cá nhân hóa & an toàn hoạt chất chỉ trong 60 giây – Mua trọn bộ tiết kiệm 15%, hoàn tiền Store Credit tức thì."* |
| **4. Giải pháp (Solutions)** | • **Skin Quiz Engine (Ref: Curology):** Chẩn đoán loại da & đề xuất phác đồ tự động.<br>• **Ingredient Conflict Guard (Ref: INCIDecoder):** Cảnh báo hoạt chất đối kháng ngay trong giỏ hàng.<br>• **Dynamic Routine Bundling:** Giảm ngay 15% khi mua đủ từ $\ge 3$ bước Routine.<br>• **Instant Store Credit Refund (Ref: Shopee):** Cấp mã hoàn tiền 100% ngay khi nhập mã vận đơn trả hàng. |
| **5. Kênh tiếp cận (Channels)** | • Website TMĐT Responsive (Desktop & Mobile Web SPA Quiz).<br>• Hệ thống Email tự động (Transactional HTML Email kèm hướng dẫn Routine). |
| **6. Dòng doanh thu (Revenue Streams)** | • Doanh thu bán lẻ sản phẩm chăm sóc da chính hãng (B2C).<br>• Gia tăng giá trị đơn hàng trung bình (**AOV – Average Order Value**) nhờ bán theo gói Combo Routine 3–6 món thay vì bán lẻ 1 món. |
| **7. Cơ cấu chi phí (Cost Structure)** | • Chi phí hạ tầng máy chủ (Docker Compose: Nginx, PHP-FPM, MariaDB, Redis).<br>• Chi phí chiết khấu khuyến mãi Combo Routine (15%) & phí cổng thanh toán. |
| **8. Chỉ số đo lường chính (Key Metrics)** | • Tỷ lệ hoàn thành bài Skin Quiz (*Quiz Completion Rate*).<br>• Tỷ lệ chuyển đổi từ kết quả Quiz sang Giỏ hàng (*1-Click Routine Conversion Rate*).<br>• Giá trị đơn hàng trung bình (*AOV*) và Tỷ lệ mua lặp lại qua Store Credit (*Retention Rate*). |
| **9. Lợi thế cạnh tranh (Unfair Advantage)** | • Cơ sở dữ liệu sản phẩm được chuẩn hóa sâu theo 4 trục Taxonomy da liễu (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`) kết hợp thuật toán Safe Fallback không bao giờ trả về kết quả rỗng. |

#### 2.1.3. Phân tích Ma trận SWOT & 4 Chiến lược Hành động
| Phân tích nội tại & môi trường | Điểm mạnh (Strengths – S) | Điểm yếu (Weaknesses – W) |
| :--- | :--- | :--- |
| **Yếu tố nội tại** | • **S1:** Có công cụ Skin Quiz tư vấn cá nhân hóa tạo niềm tin ban đầu (Ref: Curology).<br>• **S2:** Tính năng cảnh báo xung đột hoạt chất độc đáo dựa trên dữ liệu INCIDecoder.<br>• **S3:** Kiến trúc HPOS + Redis tối ưu tốc độ phản hồi ($< 200\text{ ms}$ cho lọc AJAX và $< 300\text{ ms}$ cho Quiz API).<br>• **S4:** Cơ chế chiết khấu 15% Combo Routine giúp tăng AOV và chống Showrooming. | • **W1:** Thương hiệu mới trên thị trường, độ nhận diện ban đầu chưa cao bằng các chuỗi lớn.<br>• **W2:** Quy mô danh mục khởi điểm ($\sim 500\text{ SKU}$ chọn lọc) nhỏ hơn các siêu thị mỹ phẩm vạn món.<br>• **W3:** Chưa có hệ thống cửa hàng vật lý (Offline Clinic/Store) để khách thử mẫu trực tiếp. |
| **Cơ hội (Opportunities – O)**<br>• **O1:** Xu hướng *Skintellectual* (chăm sóc da dựa trên khoa học thành phần) bùng nổ tại Việt Nam.<br>• **O2:** Thanh toán không tiền mặt (VietQR, Ví điện tử) phổ cập rộng rãi.<br>• **O3:** Người dùng ngày càng ưu tiên các nền tảng có chính sách hậu mãi minh bạch, nhanh chóng. | **Chiến lược S – O (Tận dụng điểm mạnh nắm bắt cơ hội):**<br>• Đẩy mạnh **Skin Quiz** và **Bảng phân tích thành phần INCI** làm phễu thu hút nhóm khách hàng trẻ yêu thích skincare khoa học (S1, S2 + O1).<br>• Kết hợp thanh toán nhanh **VietQR/VNPay** với **1-Click Add Routine** để rút ngắn tối đa phễu mua hàng trên di động (S3, S4 + O2). | **Chiến lược W – O (Khắc phục điểm yếu bằng cơ hội):**<br>• Xóa bỏ rào cản thương hiệu mới và thiếu cửa hàng vật lý bằng chính sách **Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit** ngay khi gửi trả hàng (W1, W3 + O3).<br>• Tập trung vào chất lượng tuyển chọn sản phẩm theo đúng các bước Routine thiết yếu thay vì chạy đua số lượng SKU dàn trải (W2 + O1). |
| **Thách thức (Threats – T)**<br>• **T1:** Cạnh tranh gay gắt về giá và phí vận chuyển từ các sàn TMĐT lớn (Shopee, Lazada, TikTok Shop) và chuỗi Hasaki.<br>• **T2:** Rủi ro khách hàng làm Quiz lấy phác đồ trên KLEER rồi sang sàn khác mua lẻ.<br>• **T3:** Rủi ro gian lận hoàn tiền trong thương mại điện tử. | **Chiến lược S – T (Dùng điểm mạnh vượt qua thách thức):**<br>• Chống *Showrooming* (T2) bằng **1-Click Routine Bundle giảm trực tiếp 15%** trên tổng giá trị các bước dưỡng — khiến việc mua trọn gói tại KLEER kinh tế và tiện lợi hơn mua lẻ nhiều shop trên sàn (S4 + T1, T2). | **Chiến lược W – T (Thu hẹp điểm yếu, phòng ngừa rủi ro):**<br>• Bảo vệ dòng tiền và chống gian lận hoàn tiền (T3) bằng cách áp dụng **Nhánh 1 – Hoàn tiền qua Store Credit** (tiền được giữ lại trong hệ sinh thái KLEER để mua đơn mới) kèm bước kiểm duyệt lý do đổi trả của Admin trước khi mở cổng nhập mã vận đơn (W1 + T3). |

---

### 2.2. Các yêu cầu chức năng (Functional Requirements)

Bảng dưới đây liệt kê 10 yêu cầu chức năng cốt lõi của hệ thống KLEER, gắn liền với chức năng phụ trách và nguồn tham chiếu thực tế:

| Mã YC | Tên yêu cầu chức năng | Mô tả chi tiết nghiệp vụ | Chức năng | Nguồn tham chiếu (REF) |
| :---: | :--- | :--- | :---: | :--- |
| **FR-01** | **Trắc nghiệm Chẩn đoán Da (Skin Quiz)** | Cung cấp giao diện SPA gồm 6 câu hỏi khảo sát đặc điểm tiết dầu, độ nhạy cảm, vấn đề da ưu tiên, độ dài chu trình mong muốn (2–6 bước) và thói quen chống nắng/trang điểm mà không cần tải lại trang. | Chức năng 1 (L01) | **Curology** (`curology.com`) |
| **FR-02** | **Đề xuất Phác đồ Cá nhân hóa (Routine Engine)** | Xử lý kết quả Quiz qua REST API (`POST /wp-json/kleer/v1/skin-quiz`), tính điểm trọng số xác định loại da, lọc bỏ hoạt chất mạnh nếu da nhạy cảm (`_kleer_strong_active = 0`), xếp hạng sản phẩm cho từng bước và kích hoạt cơ chế dự phòng **Safe Fallback** (`_kleer_gentle_fallback = 1`) khi hết hàng. | Chức năng 1 (L01) | **Curology** & **INCIDecoder** |
| **FR-03** | **Khám phá Danh mục & Xem Chi tiết Sản phẩm (Catalog & PDP)** | Hiển thị danh sách sản phẩm phân trang chuẩn UX, trang chi tiết sản phẩm thể hiện rõ nhãn Taxonomy (*Loại da, Vấn đề da, Bước Routine*), bảng thành phần đầy đủ (Full INCI) và hướng dẫn sử dụng. | Chức năng 2 (L02) | **Hasaki** (`hasaki.vn`) |
| **FR-04** | **Lọc Sản phẩm Đa tiêu chí Tức thì (AJAX Multi-Filter)** | Cho phép khách hàng lọc kết hợp theo *Loại da (`pa_skin_type`)*, *Vấn đề da (`pa_skin_concern`)*, *Bước chu trình (`pa_routine_step`)*, *Hoạt chất (`pa_active_ingredient`)*, *Thương hiệu* và *Khoảng giá* qua AJAX, đồng bộ URL bằng HTML5 History API. | Chức năng 2 (L02) | **Hasaki** & **INCIDecoder** |
| **FR-05** | **Thêm nhanh Trọn bộ Routine vào Giỏ (1-Click Routine to Cart)** | Cho phép thêm đồng loạt từ 2 đến 6 sản phẩm thuộc phác đồ Routine được đề xuất vào giỏ hàng WooCommerce chỉ bằng một thao tác bấm nút duy nhất qua AJAX, tự động cập nhật Mini-Cart. | Chức năng 3 (L03) | **Curology** |
| **FR-06** | **Chiết khấu Tự động Combo Routine 15% (Dynamic Bundle Discount)** | Tự động kiểm tra giỏ hàng mỗi khi có thay đổi; nếu giỏ hàng chứa các sản phẩm đại diện cho từ **$\ge 3$ bước Routine khác nhau** trở lên, hệ thống tự động trừ chiết khấu **15%** trên tổng giá trị các sản phẩm thuộc Routine; tự động gỡ chiết khấu nếu khách xóa sản phẩm xuống dưới 3 bước. | Chức năng 3 (L03) | Mô hình Guided Bundling |
| **FR-07** | **Cảnh báo Xung đột Hoạt chất (Ingredient Conflict Warning)** | Tự động quét thuộc tính `pa_active_ingredient` của toàn bộ sản phẩm trong giỏ hàng; hiển thị cảnh báo da liễu (Soft Advisory Banner) tại trang Giỏ hàng và Checkout khi phát hiện sự xuất hiện đồng thời của nhóm **Acid (AHA/BHA)** và nhóm **Retinoids (Retinol/Tretinoin/Adapalene)**. | Chức năng 3 (L03) | **INCIDecoder** (`incidecoder.com`) |
| **FR-08** | **Đặt hàng & Tích hợp Đa cổng Thanh toán (Checkout & Payment Gateways)** | Kiểm tra tính hợp lệ của thông tin giao nhận và xử lý thanh toán qua 3 kênh: **COD** (Thanh toán khi nhận hàng), **VietQR** (Sinh mã QR chuyển khoản Napas động nhúng số tiền & mã đơn) và **VNPay Sandbox** (Chuyển hướng cổng thanh toán & đối soát chữ ký số `HMAC-SHA512` qua Return URL / IPN). | Chức năng 4 (L04) | **Hasaki** & Chuẩn **VNPay / Napas VietQR** |
| **FR-09** | **Quản lý Vòng đời Đơn hàng HPOS & Hóa đơn Điện tử QR** | Lưu trữ đơn hàng trên 4 bảng phẳng chuyên biệt của **WooCommerce HPOS**, tự động trừ/hoàn tồn kho theo sự kiện chuyển trạng thái đơn và gửi Email hóa đơn HTML đính kèm mã QR tra cứu đơn hàng qua SMTP. | Chức năng 5 (L05) | **Hasaki** & **WooCommerce HPOS** |
| **FR-10** | **Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit (3-Step Instant Refund)** | Cho phép khách hàng gửi yêu cầu đổi trả đối với đơn hàng đã giao (`Completed` $\le 7$ ngày) $\rightarrow$ Admin duyệt yêu cầu hợp lệ (`Return Approved`) $\rightarrow$ Ngay khi khách hàng nhập **Mã vận đơn gửi trả hàng**, hệ thống tự động chuyển đơn sang `Refunded`, phát hành mã **Store Credit** (100% giá trị đơn, gắn email khách) và gửi Email thông báo tức thì. | Chức năng 6 (L07) | **Shopee** (`help.shopee.vn`) & **Hasaki** |

---

### 2.3. Các yêu cầu phi chức năng (Non-Functional Requirements)

| Mã YC | Nhóm tiêu chí | Mô tả chi tiết & Chỉ số đo lường (SLA / Metric) |
| :---: | :--- | :--- |
| **NFR-01** | **Hiệu năng (Performance)** | • Thời gian phản hồi của API chấm điểm Skin Quiz (`POST /wp-json/kleer/v1/skin-quiz`): **$< 300\text{ ms}$**.<br>• Thời gian phản hồi của Bộ lọc sản phẩm AJAX: **$< 200\text{ ms}$**.<br>• Kích hoạt **WooCommerce HPOS** và **Redis Object Cache** để giảm thiểu truy vấn SQL lặp lại. |
| **NFR-02** | **Bảo mật (Security)** | • Xác thực CSRF bằng **WordPress Nonce** (`wp_verify_nonce`) cho mọi thao tác AJAX thêm giỏ hàng và gửi form đổi trả.<br>• Xác thực toàn vẹn giao dịch thanh toán VNPay bằng chữ ký mật mã **`HMAC-SHA512`**.<br>• Ràng buộc mã **Store Credit** theo đúng địa chỉ email của chủ đơn hàng (`customer_email`) và giới hạn sử dụng đúng 1 lần (`usage_limit = 1`). |
| **NFR-03** | **Độ tin cậy & An toàn lỗi (Reliability)** | • Cơ chế **Safe Fallback** của Skin Quiz đảm bảo luôn trả về sản phẩm lành tính dự phòng (`_kleer_gentle_fallback = 1`) khi hết hàng, không bao giờ gây lỗi trang trắng (*White Screen of Death*).<br>• Kiểm tra đầu vào bằng **JSON Schema Validation** (`quiz-submission.schema.json`) trước khi xử lý nghiệp vụ. |
| **NFR-04** | **Khả năng bảo trì & Mở rộng (Maintainability)** | • Tuân thủ kiến trúc phân tầng **OOP theo chuẩn PSR-4 và PSR-12**.<br>• Tách biệt hoàn toàn giao diện (`kleer-theme`) và nghiệp vụ (`kleer-plugin`, `kleer-payments`, `kleer-mailer`); không can thiệp sửa mã nguồn lõi của WordPress hay WooCommerce Core. |
| **NFR-05** | **Khả năng tương thích & Giao diện (Usability / Responsive)** | • Giao diện thiết kế theo chuẩn **Mobile-First Responsive**, hiển thị chuẩn xác trên cả màn hình Desktop ($1440\text{px}$) và thiết bị di động ($375\text{px}$).<br>• Hình ảnh sản phẩm được chuẩn hóa tỷ lệ vuông $1:1$ định dạng `.webp` ($\le 120\text{ KB}$/ảnh). |
| **NFR-06** | **Tính đồng nhất môi trường (Portability)** | • Đóng gói toàn bộ môi trường chạy bằng **Docker Compose** (gồm 5 container: `nginx`, `php:8.2-fpm`, `mariadb:10.11`, `redis`, `phpmyadmin`), đảm bảo mọi thành viên khởi chạy hệ thống đồng nhất chỉ với một lệnh. |

---

## 3. MÔ HÌNH HÓA HỆ THỐNG (SYSTEM MODELING)

> **⚠️ QUY ĐỊNH BẮT BUỘC DÀNH CHO CÁC THÀNH VIÊN THỰC HIỆN CHƯƠNG 3:**
> 1. **Tham khảo Nguồn Reference trước khi thiết kế:** Trước khi đặt bút vẽ biểu đồ hoặc viết đặc tả, từng thành viên **bắt buộc phải đối chiếu nguồn tham chiếu thực tế (Reference)** đã được Giảng viên phê duyệt cho chức năng của mình (gồm **Curology**, **INCIDecoder**, **Hasaki.vn**, **Shopee.vn**). Tuyệt đối không tự suy diễn quy trình cảm tính hoặc dùng AI sinh quy trình rời rạc không có căn cứ thực tế.
> 2. **Công cụ thiết kế biểu đồ:** 100% các biểu đồ trong Chương 3 (*Activity Diagram, Use Case Diagram, Class Diagram, Sequence Diagram, State Machine Diagram*) **phải được thiết kế trên công cụ Draw.io (`.drawio`)**, sử dụng ký hiệu chuẩn **UML 2.5**, phông chữ rõ nét, xuất ra file ảnh **`.png` (tối thiểu 300 DPI)** và lưu kèm file gốc `.drawio` vào thư mục `docs/flows/` tương ứng.
> 3. **Hình thức bàn giao:** Mỗi thành viên trong 6 thành viên Chương 3 phụ trách trọn vẹn **1 chức năng cốt lõi** (từ mục **3.1 đến 3.6**) và **bàn giao qua Google Docs** cho Nhóm trưởng (**Nguyễn Tấn Phát**) để tổng hợp vào Báo cáo Word cuối kỳ.

### Bảng phân công 6 Chức năng Cốt lõi trong Chương 3

| Mã Chức năng | Tên Chức năng Nghiệp vụ | Thành viên phụ trách | Nguồn Reference bắt buộc tham khảo trước khi làm |
| :---: | :--- | :--- | :--- |
| **Chức năng 1** | **Skin Quiz & Đề xuất Routine cá nhân hóa** | **Bùi Hiếu Nhân** | **Curology** (`curology.com` – Luồng Consultation Quiz) & **INCIDecoder** (Quy tắc lọc hoạt chất mạnh cho da nhạy cảm). |
| **Chức năng 2** | **Khám phá Danh mục (Catalog) & Bộ lọc sản phẩm đa tiêu chí** | **Lâm Trúc Uyên** | **Hasaki Beauty & Clinic** (`hasaki.vn` – Bố cục Catalog/PDP) & **INCIDecoder** (Phân loại thuộc tính hoạt chất INCI). |
| **Chức năng 3** | **Giỏ hàng Routine Bundle (Giảm 15%) & Cảnh báo xung đột hoạt chất** | **Huỳnh Ngọc Nhã Uyên** | **INCIDecoder** (`incidecoder.com` – Tương tác cặp chất kỵ nhau AHA/BHA vs Retinoids) & Code thực tế `CartComboService.php`. |
| **Chức năng 4** | **Checkout & Tích hợp Thanh toán trực tuyến (COD, VietQR, VNPay Sandbox)** | **Trần Ngọc Minh Tuyền** | **Hasaki.vn** (Luồng Checkout TMĐT mỹ phẩm) & Tài liệu tích hợp chuẩn **VNPay Sandbox (`HMAC-SHA512`) / Napas VietQR**. |
| **Chức năng 5** | **Quản lý vòng đời đơn hàng trên bảng phẳng HPOS & Hóa đơn Email QR** | **Nguyễn Minh Trí** | **Hasaki.vn** (Quy trình xử lý đơn hàng Order Fulfillment) & Tài liệu **WooCommerce HPOS** (`developer.woocommerce.com/docs/hpos/`). |
| **Chức năng 6** | **Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước** | **Nguyễn Ngọc Thảo Uyên** | **Shopee Việt Nam** (`help.shopee.vn` – Quy trình Trả hàng & Hoàn tiền ngay 3 bước) & **Hasaki.vn** (`hotro.hasaki.vn` – Điều kiện đổi trả mỹ phẩm). |

---

### 3.1. Biểu đồ hoạt động (Activity Diagram)

> **Quy chuẩn vẽ trên Draw.io cho mục 3.1:**
> * Sử dụng biểu đồ **UML Activity Diagram có phân làn (Swimlanes)** để thể hiện rõ sự phối hợp giữa các tác nhân (*Khách hàng*, *Giao diện Web/Theme*, *Hệ thống Backend KLEER*, *Cổng thanh toán / Quản trị viên*).
> * Bắt buộc có đầy đủ: Điểm bắt đầu (*Initial Node*), các hành động (*Action Nodes*), nút rẽ nhánh điều kiện (*Decision/Merge Nodes*), thanh đồng bộ (*Fork/Join* nếu có xử lý song song như Trừ kho + Gửi Email), và Điểm kết thúc (*Activity Final Node*).

#### 3.1.1. Activity Diagram – Chức năng 1: Skin Quiz & Đề xuất Routine cá nhân hóa
* **Người thực hiện:** Bùi Hiếu Nhân
* **Nguồn Reference bắt buộc đối chiếu:** Quy trình trắc nghiệm chẩn đoán da của **Curology** (`curology.com`) và thuật toán chấm điểm v4 trong `QuizEngineService.php`.
* **Tên file Draw.io bàn giao:** `docs/flows/L01-quiz-routine/L01_Activity_Quiz_Routine.drawio` (và `.png`)
* **Các làn (Swimlanes) & Luồng xử lý cần thể hiện trên Draw.io:**
  1. **Làn Khách hàng (Customer):** Bấm "Làm Skin Quiz" $\rightarrow$ Trả lời lần lượt 6 câu hỏi (Q1–Q3: Độ dầu/khô & nhạy cảm; Q4: Vấn đề da chính; Q5: Số bước Routine mong muốn 2–6; Q6: Thói quen chống nắng/trang điểm) $\rightarrow$ Bấm "Xem kết quả Routine".
  2. **Làn Giao diện SPA (`kleer-theme`):** Kiểm tra từng câu trả lời trên Client $\rightarrow$ Gửi JSON payload tới REST API $\rightarrow$ Hiển thị hồ sơ da và danh sách sản phẩm theo thứ tự bước dưỡng.
  3. **Làn Hệ thống Backend (`QuizEngineService`):**
     * Rẽ nhánh `[Payload hợp lệ?]` $\rightarrow$ Nếu sai: Trả lỗi HTTP 400.
     * Nếu đúng: Tính vector điểm `skin_type`, kiểm tra ngưỡng nhạy cảm (`sensitive >= 4` $\rightarrow$ bật cờ `is_sensitive = true`).
     * Xác định các bước Routine cần giữ (nếu chọn 6C không dùng kem chống nắng và không thuộc nhóm `dark_spots`/`aging` thì loại bỏ `sunscreen` và `makeup_remover`).
     * Với từng bước Routine: Lọc cứng sản phẩm còn hàng, khớp `pa_skin_type`, và nếu `is_sensitive = true` thì loại bỏ sản phẩm có `_kleer_strong_active = 1` $\rightarrow$ Chấm điểm xếp hạng (`Product Score`) $\rightarrow$ Rẽ nhánh `[Tìm thấy SKU khớp?]` $\rightarrow$ Nếu không: Kích hoạt **Safe Fallback** lấy sản phẩm có `_kleer_gentle_fallback = 1`.
* **[VỊ TRÍ CHÈN HÌNH 3.1.1 TRÊN GOOGLE DOCS]:** `![Hình 3.1.1 - Activity Diagram Chức năng Skin Quiz & Routine](docs/flows/L01-quiz-routine/L01_Activity_Quiz_Routine.png)`

#### 3.1.2. Activity Diagram – Chức năng 2: Khám phá Danh mục & Bộ lọc sản phẩm đa tiêu chí
* **Người thực hiện:** Lâm Trúc Uyên
* **Nguồn Reference bắt buộc đối chiếu:** Giao diện lọc danh mục sản phẩm và trang chi tiết sản phẩm của **Hasaki** (`hasaki.vn`) kết hợp phân nhóm hoạt chất của **INCIDecoder**.
* **Tên file Draw.io bàn giao:** `docs/flows/L02-catalog-product/L02_Activity_Catalog_Filter.drawio` (và `.png`)
* **Các làn (Swimlanes) & Luồng xử lý cần thể hiện trên Draw.io:**
  1. **Làn Khách hàng:** Truy cập trang Cửa hàng (Catalog) $\rightarrow$ Chọn tiêu chí lọc (*Loại da, Vấn đề da, Bước Routine, Hoạt chất chủ đạo, Khoảng giá*) $\rightarrow$ Chọn xem chi tiết 1 sản phẩm (PDP), đọc bảng thành phần INCI & hướng dẫn sử dụng.
  2. **Làn Giao diện Theme (`catalog-filter.js`):** Bắt sự kiện thay đổi bộ lọc $\rightarrow$ Cập nhật URL trình duyệt qua `history.pushState` $\rightarrow$ Gửi AJAX request (`wp_ajax_kleer_filter_products`) $\rightarrow$ Render lại lưới sản phẩm mà không tải lại trang.
  3. **Làn Hệ thống Backend (`ajax-catalog-filter.php`):** Xây dựng truy vấn `WP_Query` kết hợp `tax_query` (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`) và `meta_query` (giá bán, trạng thái tồn kho) $\rightarrow$ Trả về HTML fragment danh sách sản phẩm khớp điều kiện.
* **[VỊ TRÍ CHÈN HÌNH 3.1.2 TRÊN GOOGLE DOCS]:** `![Hình 3.1.2 - Activity Diagram Chức năng Catalog & Bộ lọc AJAX](docs/flows/L02-catalog-product/L02_Activity_Catalog_Filter.png)`

#### 3.1.3. Activity Diagram – Chức năng 3: Giỏ hàng Routine Bundle (Giảm 15%) & Cảnh báo xung đột hoạt chất
* **Người thực hiện:** Huỳnh Ngọc Nhã Uyên
* **Nguồn Reference bắt buộc đối chiếu:** Cơ sở dữ liệu tương tác hoạt chất của **INCIDecoder** (`incidecoder.com`) và logic thực tế trong `CartComboService.php`.
* **Tên file Draw.io bàn giao:** `docs/flows/L03-cart-combo/L03_Activity_Cart_Combo_Conflict.drawio` (và `.png`)
* **Các làn (Swimlanes) & Luồng xử lý cần thể hiện trên Draw.io:**
  1. **Làn Khách hàng:** Bấm "Thêm trọn bộ Routine vào giỏ" (1-Click Add từ trang kết quả Quiz) hoặc thêm/xóa từng sản phẩm lẻ $\rightarrow$ Xem trang Giỏ hàng $\rightarrow$ Đọc cảnh báo xung đột hoạt chất (nếu có) và kiểm tra mức giảm giá Combo $\rightarrow$ Bấm "Tiến hành thanh toán".
  2. **Làn Hệ thống Giỏ hàng (`CartComboService`):**
     * Xử lý thêm hàng loạt `product_ids` vào giỏ qua AJAX (kiểm tra CSRF Nonce, kiểm tra tồn kho từng món).
     * **Nhánh kiểm tra Chiết khấu Combo:** Đếm số bước `pa_routine_step` độc lập có mặt trong giỏ $\rightarrow$ Rẽ nhánh `[Số bước khác nhau >= 3?]` $\rightarrow$ Nếu Đúng: Tự động áp dụng khoản giảm giá **15% (`DISCOUNT_RATE = 0.15`)** trên tổng giá trị các sản phẩm thuộc Routine; Nếu Sai ($< 3$ bước): Tự động gỡ bỏ chiết khấu Combo.
     * **Nhánh kiểm tra Xung đột hoạt chất:** Quét thuộc tính `pa_active_ingredient` của mọi sản phẩm trong giỏ $\rightarrow$ Rẽ nhánh `[Có đồng thời nhóm Acid (AHA/BHA) VÀ nhóm Retinoids?]` $\rightarrow$ Nếu Đúng: Hiển thị thẻ cảnh báo da liễu màu cam (*Soft Warning Banner* khuyên dùng xen kẽ sáng/tối hoặc cách ngày, không khóa nút Checkout).
* **[VỊ TRÍ CHÈN HÌNH 3.1.3 TRÊN GOOGLE DOCS]:** `![Hình 3.1.3 - Activity Diagram Chức năng Giỏ hàng & Cảnh báo xung đột](docs/flows/L03-cart-combo/L03_Activity_Cart_Combo_Conflict.png)`

#### 3.1.4. Activity Diagram – Chức năng 4: Checkout & Thanh toán trực tuyến (COD, VietQR, VNPay Sandbox)
* **Người thực hiện:** Trần Ngọc Minh Tuyền
* **Nguồn Reference bắt buộc đối chiếu:** Quy trình thanh toán thương mại điện tử của **Hasaki.vn** và tài liệu kỹ thuật cổng **VNPay Sandbox / Napas VietQR**.
* **Tên file Draw.io bàn giao:** `docs/flows/L04-checkout-payment/L04_Activity_Checkout_Payment.drawio` (và `.png`)
* **Các làn (Swimlanes) & Luồng xử lý cần thể hiện trên Draw.io:**
  1. **Làn Khách hàng:** Nhập thông tin người nhận (Họ tên, SĐT, Email, Tỉnh/Thành, Địa chỉ) $\rightarrow$ Chọn 1 trong 3 phương thức thanh toán (*COD*, *Chuyển khoản VietQR*, hoặc *VNPay Sandbox*) $\rightarrow$ Thực hiện thanh toán.
  2. **Làn Hệ thống KLEER (`kleer-payments`):**
     * Kiểm tra tính hợp lệ của trường dữ liệu (Validation) $\rightarrow$ Khởi tạo đơn hàng ở trạng thái `Pending payment`.
     * Rẽ nhánh theo phương thức thanh toán:
       * **Nhánh COD:** Chuyển trạng thái đơn sang `Processing` $\rightarrow$ Chuyển tới trang Cảm ơn.
       * **Nhánh VietQR:** Chuyển trạng thái đơn sang `On-hold` $\rightarrow$ Sinh chuỗi payload QR chuẩn Napas EMVCo nhúng đúng số tiền và mã đơn `KLEER-{ORDER_ID}` $\rightarrow$ Hiển thị mã QR tại trang Cảm ơn.
       * **Nhánh VNPay Sandbox:** Tạo chuỗi tham số thanh toán, ký số bảo mật `HMAC-SHA512` bằng `HashSecret` $\rightarrow$ Chuyển hướng (Redirect) khách sang cổng VNPay $\rightarrow$ Nhận kết quả trả về qua Return URL / IPN Callback $\rightarrow$ Kiểm tra chữ ký số `vnp_SecureHash` $\rightarrow$ Nếu hợp lệ và `vnp_ResponseCode == '00'`: Cập nhật đơn hàng sang `Processing`; Nếu thất bại/hủy: Giữ `Pending` hoặc chuyển sang `Cancelled`.
  3. **Làn Cổng VNPay Sandbox / Ngân hàng:** Xác thực thẻ/tài khoản sandbox của khách hàng và gửi callback phản hồi về hệ thống KLEER.
* **[VỊ TRÍ CHÈN HÌNH 3.1.4 TRÊN GOOGLE DOCS]:** `![Hình 3.1.4 - Activity Diagram Chức năng Checkout & Thanh toán](docs/flows/L04-checkout-payment/L04_Activity_Checkout_Payment.png)`

#### 3.1.5. Activity Diagram – Chức năng 5: Quản lý vòng đời đơn hàng trên bảng phẳng HPOS & Hóa đơn Email QR
* **Người thực hiện:** Nguyễn Minh Trí
* **Nguồn Reference bắt buộc đối chiếu:** Quy trình xử lý đơn hàng (**Order Fulfillment**) của **Hasaki.vn** kết hợp kiến trúc lưu trữ **WooCommerce HPOS** (`developer.woocommerce.com/docs/hpos/`) và plugin `kleer-mailer`.
* **Tên file Draw.io bàn giao:** `docs/flows/L05-order-processing/L05_Activity_HPOS_Fulfillment_Mailer.drawio` (và `.png`)
* **Các làn (Swimlanes) & Luồng xử lý cần thể hiện trên Draw.io:**
  1. **Làn Khách hàng:** Hoàn tất đặt hàng (COD/VietQR/VNPay) $\rightarrow$ Nhận Email hóa đơn HTML đính kèm mã QR tra cứu $\rightarrow$ Quét mã QR theo dõi trạng thái xử lý đơn hàng và nhận kiện hàng.
  2. **Làn Hệ thống HPOS & Mailer (`HposService` & `kleer-mailer`):**
     * Ghi bản ghi đơn hàng trực tiếp vào 4 bảng phẳng chuyên biệt (`wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta`).
     * Khi đơn chuyển sang `Processing` (hoặc `On-hold` với VietQR): Sử dụng thanh đồng bộ (*Fork/Join*) thực hiện song song:
       * Tự động trừ số lượng tồn kho sản phẩm (`reduce_order_stock`).
       * Sinh ảnh mã QR PNG (`Kleer_Mailer\QR_Encoder`) nhúng inline CID và gửi **Email hóa đơn HTML** qua SMTP (`Kleer_Mailer\Order_Email`), đồng thời ghi cờ `_kleer_invoice_email_processing` chống gửi trùng.
     * Nếu đơn hàng bị hủy (`Cancelled`): Tự động hoàn trả lại số lượng tồn kho (`restore_order_stock`).
  3. **Làn Quản trị viên (Store Admin):** Kiểm tra đơn hàng trên giao diện quản trị HPOS $\rightarrow$ Đối soát thanh toán (với đơn `On-hold` VietQR) $\rightarrow$ Đóng gói kiện hàng, bàn giao đơn vị vận chuyển và cập nhật trạng thái đơn sang **`Completed` (Đã giao hàng thành công)**.
* **[VỊ TRÍ CHÈN HÌNH 3.1.5 TRÊN GOOGLE DOCS]:** `![Hình 3.1.5 - Activity Diagram Chức năng Xử lý đơn hàng HPOS & Hóa đơn Email QR](docs/flows/L05-order-processing/L05_Activity_HPOS_Fulfillment_Mailer.png)`

#### 3.1.6. Activity Diagram – Chức năng 6: Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước
* **Người thực hiện:** Nguyễn Ngọc Thảo Uyên
* **Nguồn Reference bắt buộc đối chiếu:** Quy trình **Trả hàng & Hoàn tiền ngay (Instant Refund 3 bước)** của **Shopee Việt Nam** (`help.shopee.vn`) kết hợp điều kiện đổi trả mỹ phẩm của **Hasaki.vn** (`hotro.hasaki.vn`).
* **Tên file Draw.io bàn giao:** `docs/flows/L05-order-processing/L07_Activity_Instant_Store_Credit_Refund.drawio` (và `.png`)
* **Các làn (Swimlanes) & Luồng xử lý cần thể hiện trên Draw.io:**
  1. **Làn Khách hàng:**
     * **Bước 1 (Gửi yêu cầu trả hàng):** Tại trang *Chi tiết đơn hàng* của đơn `Completed` (trong vòng $\le 7$ ngày), khách hàng chọn **"Yêu cầu Trả hàng/Hoàn tiền"**, chọn lý do đổi trả (*Lỗi vận chuyển / Giao sai sản phẩm / Kích ứng da*) và nhập mô tả chi tiết.
     * **Bước 3 (Nhập Mã vận đơn gửi trả):** Sau khi được Admin duyệt, khách hàng bàn giao kiện hàng cho đơn vị vận chuyển và nhập **Mã vận đơn gửi trả (`return_tracking_code`)** kèm tên đơn vị vận chuyển lên hệ thống $\rightarrow$ Nhận ngay mã **Store Credit 100%** trên màn hình và qua Email để mua đơn hàng mới.
  2. **Làn Quản trị viên (Store Admin):**
     * **Bước 2 (Xét duyệt yêu cầu):** Kiểm tra lý do và thông tin yêu cầu đổi trả trong trang quản trị đơn hàng HPOS $\rightarrow$ Rẽ nhánh `[Yêu cầu hợp lệ?]`:
       * Nếu **Từ chối**: Ghi chú lý do từ chối và chuyển trạng thái đơn hàng quay về `Completed`.
       * Nếu **Chấp thuận**: Chuyển trạng thái đơn hàng sang **`Return Approved` (`wc-return-approved` – Chờ gửi trả hàng)**, mở khóa biểu mẫu nhập Mã vận đơn cho khách.
  3. **Làn Hệ thống Hoàn tiền nhanh (`InstantRefundService` & `kleer-mailer`):**
     * Kiểm tra điều kiện thời hạn $\le 7$ ngày và lưu metadata yêu cầu (`_kleer_return_reason`, `_kleer_return_requested_at`), chuyển đơn sang **`Return Requested` (`wc-return-requested`)**.
     * Khi khách hàng gửi Mã vận đơn ở Bước 3: Kiểm tra định dạng mã hợp lệ ($\ge 6$ ký tự chữ/số) và **tự động kích hoạt Instant Refund ngay tức thì (không cần chờ hàng hoàn về tới kho)**:
       * Khởi tạo mã **Store Credit Coupon** (`KLR-CREDIT-{ORDER_ID}-{HASH}`) với mệnh giá bằng **100% tổng giá trị đơn hàng** (`discount_type = fixed_cart`), gắn cứng với email khách hàng (`customer_email`) và giới hạn sử dụng 1 lần (`usage_limit = 1`).
       * Chuyển trạng thái đơn hàng sang **`Refunded` (Đã hoàn tiền)**.
       * Kích hoạt hook gửi **Email thông báo Hoàn tiền nhanh Store Credit** ngay lập tức cho khách hàng.
* **[VỊ TRÍ CHÈN HÌNH 3.1.6 TRÊN GOOGLE DOCS]:** `![Hình 3.1.6 - Activity Diagram Chức năng Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit](docs/flows/L05-order-processing/L07_Activity_Instant_Store_Credit_Refund.png)`

---

### 3.2. Biểu đồ Use Case tổng thể (Overall Use Case Diagram)
* **Người thực hiện:** Phối hợp toàn nhóm Chương 3 (đầu mối tổng hợp file: `docs/flows/L00_Overall_UseCase_Diagram/L00_Overall_UseCase_Diagram.drawio`).
* **Các Tác nhân (Actors) bắt buộc thể hiện trên Draw.io:**
  1. **Khách hàng (Customer / Guest & Registered User):** Thực hiện làm bài Skin Quiz, duyệt & lọc sản phẩm, quản lý giỏ hàng Routine Bundle, đặt hàng & thanh toán, xem lịch sử đơn hàng và gửi yêu cầu đổi trả / nhập mã vận đơn nhận Store Credit.
  2. **Quản trị viên (Store Admin):** Quản lý sản phẩm & thuộc tính Taxonomy da liễu, xử lý đơn hàng HPOS, đối soát thanh toán, duyệt yêu cầu đổi trả (`Return Approved`) và kiểm nhận hàng hoàn kho.
  3. **Hệ thống Thanh toán VNPay / Ngân hàng (External Payment Gateway – Secondary Actor):** Xác thực giao dịch trực tuyến và gửi thông báo kết quả thanh toán (IPN/Return URL).
  4. **Hệ thống SMTP Mail Server (External Service – Secondary Actor):** Chuyển phát Email hóa đơn QR và Email cấp mã Store Credit.
* **Các quan hệ `<<include>>` và `<<extend>>` bắt buộc có trên biểu đồ:**
  * Use Case *"Làm bài Skin Quiz"* `<<include>>` *"Phân tích loại da & Đề xuất Routine"*.
  * Use Case *"Quản lý Giỏ hàng"* `<<extend>>` *"Tự động áp dụng giảm giá 15% Combo Routine"* (khi số bước $\ge 3$) và `<<extend>>` *"Cảnh báo xung đột hoạt chất"* (khi có AHA/BHA + Retinoids).
  * Use Case *"Đặt hàng (Checkout)"* `<<include>>` *"Xác thực thông tin giao hàng"* và kết nối khái quát hóa (*Generalization*) tới 3 phương thức: *Thanh toán COD*, *Thanh toán VietQR*, *Thanh toán VNPay Sandbox*.
  * Use Case *"Nhập mã vận đơn gửi trả hàng"* `<<include>>` *"Tự động phát hành Store Credit (Instant Refund)"*.
* **[VỊ TRÍ CHÈN HÌNH 3.2 TRÊN GOOGLE DOCS]:** `![Hình 3.2 - Biểu đồ Use Case tổng thể hệ thống KLEER](docs/flows/L00_Overall_UseCase_Diagram/L00_Overall_UseCase_Diagram.png)`

---

### 3.3. Đặc tả Use Case (Use Case Specifications)

> **Hướng dẫn cho các thành viên:** Dưới đây là khung đặc tả chuẩn đã được chuẩn hóa thông số kỹ thuật theo đúng codebase thực tế của KLEER. Các thành viên phụ trách từng chức năng kiểm tra, bổ sung chi tiết và đưa vào tài liệu Google Docs bàn giao.

#### 3.3.1. Đặc tả Use Case – Chức năng 1: Làm bài Skin Quiz & Nhận đề xuất Routine cá nhân hóa
*(Phụ trách: Bùi Hiếu Nhân – Tham chiếu: **Curology** & **INCIDecoder**)*

| Mục (Field) | Nội dung đặc tả chi tiết |
| :--- | :--- |
| **Use Case ID** | **UC-01** (Tương ứng FR-01, FR-02) |
| **Use Case Name** | Thực hiện trắc nghiệm chẩn đoán da (Skin Quiz) và nhận đề xuất Routine cá nhân hóa |
| **Actors** | Khách hàng (Customer) |
| **Reference** | Quy trình Consultation Quiz của **Curology** (`curology.com`) & Phân loại hoạt chất **INCIDecoder** (`incidecoder.com`). |
| **Pre-conditions** | • Hệ thống kho dữ liệu đã có các sản phẩm được gán đầy đủ 3 thuộc tính WooCommerce (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`) và cờ `_kleer_strong_active`, `_kleer_gentle_fallback`.<br>• Khách hàng truy cập vào trang `/skin-quiz/` trên trình duyệt. |
| **Post-conditions** | • Hệ thống xác định được hồ sơ da của khách hàng (*Loại da chính, Vấn đề da ưu tiên, Cờ nhạy cảm `is_sensitive`*).<br>• Hiển thị danh sách từ 2 đến 6 sản phẩm tương ứng với các bước Routine phù hợp kèm nút "Thêm trọn bộ vào giỏ hàng". |
| **Main Flow (Luồng sự kiện chính)** | 1. Khách hàng bấm nút "Bắt đầu làm Quiz" trên giao diện SPA.<br>2. Hệ thống hiển thị lần lượt 6 câu hỏi trắc nghiệm (Q1–Q3 khảo sát độ dầu/khô và mức độ kích ứng; Q4 chọn vấn đề da chủ đạo; Q5 chọn số bước chu trình mong muốn từ 2 đến 6; Q6 khảo sát thói quen dùng kem chống nắng/trang điểm).<br>3. Khách hàng chọn câu trả lời cho từng câu hỏi và bấm "Hoàn tất & Xem phác đồ".<br>4. Giao diện gửi HTTP request `POST /wp-json/kleer/v1/skin-quiz` kèm payload JSON câu trả lời.<br>5. `SkinQuizController` xác thực cấu trúc dữ liệu qua JSON Schema (`quiz-submission.schema.json`).<br>6. `QuizEngineService` tổng hợp điểm Q1–Q3 để xác định `skin_type` (quy tắc ưu tiên khi hòa điểm: `sensitive > dry > combination > oily > normal`) và bật cờ `is_sensitive = true` nếu tổng điểm nhạy cảm $\ge 4$.<br>7. Hệ thống lọc danh sách bước Routine theo số lượng $N$ khách chọn ở Q5 và quy tắc Q6.<br>8. Với từng bước, hệ thống lọc các sản phẩm còn hàng, khớp `pa_skin_type`, loại trừ sản phẩm có `_kleer_strong_active = 1` nếu `is_sensitive = true`, sau đó xếp hạng theo công thức: $\text{Score} = 2 \times (\text{Khớp } skin\_concern) + 1 \times (\text{Dành riêng cho } skin\_type)$.<br>9. Hệ thống trả về JSON kết quả; giao diện SPA hiển thị hồ sơ da và danh sách sản phẩm Routine đề xuất. |
| **Alternate Flow (Luồng thay thế)** | **AF-01 (Khách hàng không dùng kem chống nắng & không trang điểm – Đáp án 6C):**<br>• Tại bước 7, nếu khách chọn đáp án 6C và vấn đề da ở Q4 không phải là `dark_spots` (Thâm/nám) hay `aging` (Lão hóa), hệ thống tự động loại bỏ 2 bước `sunscreen` và `makeup_remover` khỏi chu trình để phù hợp thói quen thực tế.<br>**AF-02 (Kích hoạt cơ chế dự phòng Safe Fallback khi thiếu sản phẩm khớp chính xác):**<br>• Tại bước 8, nếu một bước Routine không tìm thấy sản phẩm khớp hoàn hảo hoặc sản phẩm đó vừa hết hàng, hệ thống tự động truy vấn sản phẩm lành tính dự phòng có cờ `_kleer_gentle_fallback = 1` của cùng bước đó để lấp vào chu trình và gắn nhãn `fallback_used = true`. |
| **Exception Flow (Luồng ngoại lệ)** | **EF-01 (Payload gửi lên sai định dạng hoặc thiếu câu trả lời bắt buộc):**<br>• Tại bước 5, nếu dữ liệu không vượt qua JSON Schema hoặc trùng lặp câu hỏi, API trả về mã lỗi `HTTP 400 Bad Request` kèm thông báo lỗi cụ thể.<br>**EF-02 (Toàn bộ kho hàng cạn kiệt không có cả sản phẩm Fallback):**<br>• Tại bước 8, hệ thống ném ngoại lệ `RoutineUnavailableException` và trả về `HTTP 503 Service Unavailable` kèm thông báo thân thiện, đảm bảo không gây lỗi sập trang. |

---

#### 3.3.2. Đặc tả Use Case – Chức năng 2: Khám phá Danh mục & Lọc sản phẩm đa tiêu chí AJAX
*(Phụ trách: Lâm Trúc Uyên – Tham chiếu: **Hasaki.vn** & **INCIDecoder**)*

| Mục (Field) | Nội dung đặc tả chi tiết |
| :--- | :--- |
| **Use Case ID** | **UC-02** (Tương ứng FR-03, FR-04) |
| **Use Case Name** | Duyệt danh mục sản phẩm (Catalog), lọc đa tiêu chí bằng AJAX và xem chi tiết sản phẩm (PDP) |
| **Actors** | Khách hàng (Customer) |
| **Reference** | Cấu trúc bộ lọc và trang sản phẩm của **Hasaki Beauty & Clinic** (`hasaki.vn`) & Danh pháp thành phần **INCIDecoder** (`incidecoder.com`). |
| **Pre-conditions** | • Kho dữ liệu sản phẩm đã được import vào hệ thống và gắn đầy đủ các term Taxonomy.<br>• Khách hàng đang ở trang Cửa hàng (`/shop/`). |
| **Post-conditions** | • Danh sách sản phẩm được cập nhật tức thì theo đúng tổ hợp bộ lọc của khách hàng.<br>• Đường dẫn URL trên trình duyệt được đồng bộ tham số lọc để có thể chia sẻ hoặc tải lại trang. |
| **Main Flow (Luồng sự kiện chính)** | 1. Khách hàng truy cập trang Danh mục sản phẩm (`/shop/`), hệ thống hiển thị lưới sản phẩm mặc định (12 sản phẩm/trang) cùng thanh công cụ lọc bên cạnh.<br>2. Khách hàng tích chọn một hoặc nhiều tiêu chí lọc: *Loại da (`pa_skin_type`)*, *Vấn đề da (`pa_skin_concern`)*, *Bước Routine (`pa_routine_step`)*, *Hoạt chất chủ đạo (`pa_active_ingredient`)*, hoặc kéo thanh trượt *Khoảng giá*.<br>3. Module JavaScript (`catalog-filter.js`) thu thập các tham số lọc, cập nhật URL trình duyệt thông qua `window.history.pushState` và gửi AJAX request tới action `wp_ajax_kleer_filter_products`.<br>4. Backend thực thi truy vấn `WP_Query` kết hợp `tax_query` (quan hệ `AND` giữa các nhóm thuộc tính) và `meta_query` (khoảng giá `_price`).<br>5. Hệ thống trả về danh sách sản phẩm thỏa mãn điều kiện và cập nhật lại vùng hiển thị sản phẩm trên giao diện trong thời gian $< 200\text{ ms}$.<br>6. Khách hàng bấm vào một sản phẩm để mở trang Chi tiết sản phẩm (PDP), xem nhãn phân loại da, bảng thành phần đầy đủ (Full INCI list) và hướng dẫn vị trí sử dụng trong chu trình dưỡng da. |
| **Alternate Flow (Luồng thay thế)** | **AF-01 (Khách hàng bấm nút "Xóa bộ lọc / Đặt lại"):**<br>• Hệ thống xóa toàn bộ tham số lọc trên URL và khôi phục lại danh sách sản phẩm mặc định ban đầu qua AJAX. |
| **Exception Flow (Luồng ngoại lệ)** | **EF-01 (Không có sản phẩm nào khớp với tổ hợp tiêu chí lọc quá hẹp):**<br>• Tại bước 5, hệ thống trả về thông báo *"Không tìm thấy sản phẩm phù hợp với tiêu chí đã chọn"* kèm nút gợi ý *"Xóa bớt bộ lọc"* hoặc *"Làm bài Skin Quiz để được gợi ý tự động"*. |

---

#### 3.3.3. Đặc tả Use Case – Chức năng 3: Giỏ hàng Routine Bundle (Giảm 15%) & Cảnh báo xung đột hoạt chất
*(Phụ trách: Huỳnh Ngọc Nhã Uyên – Tham chiếu: **INCIDecoder** & `CartComboService.php`)*

| Mục (Field) | Nội dung đặc tả chi tiết |
| :--- | :--- |
| **Use Case ID** | **UC-03** (Tương ứng FR-05, FR-06, FR-07) |
| **Use Case Name** | Quản lý giỏ hàng, tự động áp dụng chiết khấu 15% Combo Routine và cảnh báo xung đột hoạt chất |
| **Actors** | Khách hàng (Customer) |
| **Reference** | Quy tắc tương tác hoạt chất hóa mỹ phẩm trên **INCIDecoder** (`incidecoder.com`) & Thực thi tại `CartComboService.php`. |
| **Pre-conditions** | • Khách hàng thêm sản phẩm từ trang kết quả Skin Quiz (1-Click Add Routine) hoặc thêm từng sản phẩm lẻ từ trang Catalog/PDP. |
| **Post-conditions** | • Giỏ hàng cập nhật chính xác số lượng, tự động trừ 15% nếu thỏa điều kiện Combo Routine ($\ge 3$ bước), và hiển thị cảnh báo da liễu nếu phát hiện hoạt chất đối kháng. |
| **Main Flow (Luồng sự kiện chính)** | 1. Khách hàng bấm nút **"Thêm trọn bộ Routine vào giỏ"** tại màn hình kết quả Quiz (hoặc thêm các sản phẩm lẻ vào giỏ hàng).<br>2. Frontend gửi AJAX request (`kleer_add_routine_to_cart`) kèm mảng `product_ids` và mã bảo mật CSRF `nonce`.<br>3. `CartComboService` xác thực `nonce`, kiểm tra trạng thái tồn kho (`is_in_stock`) của từng sản phẩm và thêm toàn bộ vào `WC()->cart`.<br>4. Khi giỏ hàng tính toán tổng tiền (hook `woocommerce_cart_calculate_fees`), hệ thống duyệt qua các sản phẩm trong giỏ và đếm số lượng bước `pa_routine_step` khác nhau.<br>5. Nếu số bước Routine khác nhau **$\ge 3$ bước**, hệ thống tự động thêm khoản giảm giá **`Giảm 15% Combo Routine` (`DISCOUNT_RATE = 0.15`)** tính trên tổng giá trị các sản phẩm thuộc Routine.<br>6. Khi hiển thị trang Giỏ hàng hoặc Checkout (hook `woocommerce_before_cart` & `woocommerce_before_checkout_form`), phương thức `checkIngredientConflicts()` quét thuộc tính `pa_active_ingredient` của toàn bộ sản phẩm trong giỏ.<br>7. Khách hàng kiểm tra tổng tiền sau chiết khấu và bấm "Tiến hành thanh toán". |
| **Alternate Flow (Luồng thay thế)** | **AF-01 (Phát hiện xung đột hoạt chất giữa nhóm Acid và nhóm Retinoids):**<br>• Tại bước 6, nếu trong giỏ hàng đồng thời xuất hiện sản phẩm thuộc nhóm **Acid** (`aha`, `bha`, `glycolic_acid`, `lactic_acid`, `salicylic_acid`, `pha`...) VÀ sản phẩm thuộc nhóm **Retinoids** (`retinol`, `retinal`, `tretinoin`, `adapalene`...), hệ thống hiển thị thẻ cảnh báo màu cam (*Advisory Conflict Notice*): khuyên khách hàng sử dụng cách ngày hoặc chia buổi Sáng/Tối để tránh kích ứng hàng rào bảo vệ da, nhưng vẫn cho phép khách tiếp tục thanh toán.<br>**AF-02 (Khách xóa sản phẩm khiến số bước Routine còn dưới 3 bước):**<br>• Tại bước 4–5, nếu khách xóa bớt sản phẩm làm số bước `pa_routine_step` độc lập trong giỏ giảm xuống $< 3$, hệ thống tự động gỡ bỏ khoản giảm giá 15% và tính lại theo đơn giá gốc. |
| **Exception Flow (Luồng ngoại lệ)** | **EF-01 (Nonce không hợp lệ hoặc toàn bộ sản phẩm trong danh sách đều hết hàng):**<br>• Tại bước 3, hệ thống từ chối yêu cầu với mã lỗi HTTP 403/400 và hiển thị thông báo trên giao diện cho khách hàng. |

---

#### 3.3.4. Đặc tả Use Case – Chức năng 4: Đặt hàng (Checkout) & Thanh toán trực tuyến
*(Phụ trách: Trần Ngọc Minh Tuyền – Tham chiếu: **Hasaki.vn** & **VNPay / Napas VietQR**)*

| Mục (Field) | Nội dung đặc tả chi tiết |
| :--- | :--- |
| **Use Case ID** | **UC-04** (Tương ứng FR-08) |
| **Use Case Name** | Đặt hàng và thanh toán qua COD, Chuyển khoản VietQR hoặc Cổng VNPay Sandbox |
| **Actors** | Khách hàng (Customer), Cổng thanh toán VNPay Sandbox (External System) |
| **Reference** | Quy trình Checkout của **Hasaki** (`hasaki.vn`), Tài liệu kỹ thuật **VNPay Sandbox** (`HMAC-SHA512`) và chuẩn **VietQR Napas**. |
| **Pre-conditions** | • Giỏ hàng của khách hàng có ít nhất 1 sản phẩm hợp lệ và còn tồn kho. |
| **Post-conditions** | • Đơn hàng mới được khởi tạo trong cơ sở dữ liệu HPOS kèm trạng thái tương ứng với kết quả thanh toán. |
| **Main Flow (Luồng sự kiện chính)** | 1. Khách hàng truy cập trang Thanh toán (`/checkout/`), điền đầy đủ thông tin nhận hàng bắt buộc (*Họ tên, Số điện thoại, Email, Địa chỉ giao hàng*).<br>2. Hệ thống tính toán phí vận chuyển theo khu vực (Shipping Zone) và tổng giá trị thanh toán cuối cùng.<br>3. Khách hàng lựa chọn phương thức thanh toán **VNPay Sandbox** và bấm nút "Đặt hàng".<br>4. Hệ thống kiểm tra tính hợp lệ của dữ liệu nhập (Validation), tạo đơn hàng mới ở trạng thái `Pending payment`.<br>5. Class `WC_Gateway_Kleer_VNPay` (`kleer-payments`) tập hợp các tham số giao dịch (`vnp_TmnCode`, `vnp_Amount`, `vnp_TxnRef`, `vnp_ReturnUrl`...), sắp xếp theo thứ tự bảng chữ cái và tạo chữ ký bảo mật `vnp_SecureHash` bằng thuật toán **`HMAC-SHA512`**.<br>6. Hệ thống chuyển hướng trình duyệt của khách hàng sang cổng thanh toán VNPay Sandbox.<br>7. Khách hàng nhập thông tin thẻ ngân hàng thử nghiệm và xác nhận OTP trên cổng VNPay.<br>8. VNPay xử lý giao dịch và chuyển hướng khách hàng về `Return URL` của KLEER (đồng thời gọi `IPN Callback`).<br>9. Hệ thống KLEER tái tính toán chữ ký `HMAC-SHA512` để xác thực tính toàn vẹn dữ liệu; khi xác nhận `vnp_ResponseCode == '00'`, hệ thống cập nhật trạng thái đơn hàng sang **`Processing` (Đang xử lý)**, làm trống giỏ hàng và hiển thị trang Cảm ơn. |
| **Alternate Flow (Luồng thay thế)** | **AF-01 (Khách hàng chọn Thanh toán khi nhận hàng – COD):**<br>• Tại bước 3, nếu khách chọn COD, hệ thống tạo đơn hàng, chuyển thẳng trạng thái sang `Processing` và đưa khách tới trang Cảm ơn.<br>**AF-02 (Khách hàng chọn Chuyển khoản ngân hàng qua mã QR – VietQR):**<br>• Tại bước 3, nếu khách chọn VietQR, hệ thống tạo đơn hàng ở trạng thái `On-hold` (Tạm giữ chờ đối soát), sinh mã QR động chuẩn Napas nhúng sẵn chính xác số tiền và nội dung chuyển khoản `KLEER {ORDER_ID}` ngay trên trang Cảm ơn. |
| **Exception Flow (Luồng ngoại lệ)** | **EF-01 (Bỏ trống trường thông tin bắt buộc hoặc sai định dạng SĐT/Email):**<br>• Tại bước 4, hệ thống chặn tạo đơn và hiển thị thông báo lỗi đỏ ngay tại trường bị thiếu.<br>**EF-02 (Khách hàng hủy giao dịch trên cổng VNPay hoặc sai chữ ký bảo mật):**<br>• Tại bước 9, nếu `vnp_ResponseCode != '00'` hoặc chữ ký `vnp_SecureHash` không khớp, hệ thống ghi nhận giao dịch thất bại, giữ đơn ở trạng thái `Pending`/`Failed` và thông báo để khách hàng chọn thanh toán lại. |

---

#### 3.3.5. Đặc tả Use Case – Chức năng 5: Quản lý vòng đời đơn hàng HPOS & Hóa đơn Email QR
*(Phụ trách: Nguyễn Minh Trí – Tham chiếu: **Hasaki.vn** & **WooCommerce HPOS**)*

| Mục (Field) | Nội dung đặc tả chi tiết |
| :--- | :--- |
| **Use Case ID** | **UC-05** (Tương ứng FR-09) |
| **Use Case Name** | Quản lý vòng đời đơn hàng trên bảng phẳng HPOS, đồng bộ tồn kho và gửi hóa đơn Email QR |
| **Actors** | Quản trị viên (Store Admin), Khách hàng (Customer), Hệ thống SMTP Mail (`kleer-mailer`) |
| **Reference** | Quy trình xử lý đơn hàng của **Hasaki** (`hasaki.vn`) & Kiến trúc **WooCommerce HPOS** (`developer.woocommerce.com/docs/hpos/`). |
| **Pre-conditions** | • Hệ thống đã kích hoạt lưu trữ đơn hàng trên bảng chuyên biệt **WooCommerce HPOS** (`HposService.php`).<br>• Đơn hàng vừa được khởi tạo từ trang Checkout. |
| **Post-conditions** | • Dữ liệu đơn hàng được ghi đồng bộ vào 4 bảng phẳng HPOS (`wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta`).<br>• Tồn kho sản phẩm được trừ/hoàn tự động và khách hàng nhận được Email hóa đơn HTML đính kèm mã QR tra cứu. |
| **Main Flow (Luồng sự kiện chính)** | 1. Khi đơn hàng chuyển sang trạng thái **`Processing`** (sau khi đặt COD hoặc xác thực VNPay thành công), hệ thống lưu bản ghi trạng thái vào bảng `wp_wc_orders`.<br>2. Hook WooCommerce tự động kích hoạt `wc_reduce_stock_levels()` trừ số lượng tồn kho của từng SKU trong đơn hàng.<br>3. Đồng thời, class `Kleer_Mailer\Order_Email` bắt sự kiện `woocommerce_order_status_processing`, kiểm tra cờ `_kleer_invoice_email_processing` (chống gửi trùng), gọi `Kleer_Mailer\QR_Encoder` sinh ảnh mã QR PNG dẫn tới trang tra cứu đơn hàng.<br>4. Hệ thống gửi **Email hóa đơn HTML** nhúng ảnh QR inline (`cid:kleer-order-qr`) qua SMTP tới `billing_email` của khách hàng.<br>5. Quản trị viên kiểm tra đơn hàng trên trang quản trị HPOS, đóng gói kiện hàng, bàn giao đơn vị vận chuyển và cập nhật trạng thái đơn sang **`Completed` (Đã giao hàng)**. |
| **Alternate Flow (Luồng thay thế)** | **AF-01 (Đơn hàng chuyển khoản VietQR ở trạng thái `On-hold`):**<br>• Ngay khi đặt đơn VietQR, `Kleer_Mailer\Order_Email` gửi email xác nhận tạm giữ (`on-hold`); sau khi Admin đối soát nhận tiền và chuyển đơn sang `Processing`, hệ thống gửi email hóa đơn xác nhận đang xử lý.<br>**AF-02 (Khách hàng hoặc Admin hủy đơn trước khi giao – `Cancelled`):**<br>• Khi đơn đang ở trạng thái `Pending`, `On-hold` hoặc `Processing` và chuyển sang `Cancelled`, hệ thống tự động hoàn trả lại số lượng tồn kho (`restore_order_stock`). |
| **Exception Flow (Luồng ngoại lệ)** | **EF-01 (Địa chỉ email khách hàng không hợp lệ hoặc kết nối SMTP gián đoạn):**<br>• Tại bước 3–4, nếu `is_email($to)` trả về `false` hoặc `wp_mail()` thất bại, hệ thống không đánh dấu cờ đã gửi và tự động ghi chú lỗi (`add_order_note`) vào đơn hàng HPOS để Admin kiểm tra lại, không làm gián đoạn luồng tạo đơn. |

---

#### 3.3.6. Đặc tả Use Case – Chức năng 6: Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước
*(Phụ trách: Nguyễn Ngọc Thảo Uyên – Tham chiếu: **Shopee Instant Refund** & **Hasaki.vn**)*

| Mục (Field) | Nội dung đặc tả chi tiết |
| :--- | :--- |
| **Use Case ID** | **UC-06** (Tương ứng FR-10) |
| **Use Case Name** | Xử lý Đổi trả trong 7 ngày và Hoàn tiền nhanh qua Store Credit theo mô hình 3 bước chuẩn Shopee |
| **Actors** | Khách hàng (Customer), Quản trị viên (Store Admin), Hệ thống SMTP Mail (`kleer-mailer`) |
| **Reference** | Cơ chế **Trả hàng – Hoàn tiền ngay** của **Shopee Việt Nam** (`help.shopee.vn`) & Chính sách đổi trả mỹ phẩm của **Hasaki** (`hotro.hasaki.vn`). |
| **Pre-conditions** | • Đơn hàng đang ở trạng thái **`Completed` (Đã giao hàng)** trong thời hạn **$\le 7$ ngày** kể từ ngày hoàn tất.<br>• Khách hàng đã đăng nhập tài khoản chủ đơn hàng (hoặc truy cập qua liên kết xác thực `order_key` hợp lệ). |
| **Post-conditions** | • Đơn hàng chuyển sang trạng thái `Refunded` ngay khi khách hàng cung cấp mã vận đơn trả hàng hợp lệ.<br>• Một mã **Store Credit Coupon** (`KLR-CREDIT-{ORDER_ID}-{HASH}`) trị giá **100% đơn hàng** được phát hành, gắn cứng với email khách hàng và gửi qua Email tức thì. |
| **Main Flow (Luồng sự kiện chính)** | 1. **Bước 1 (Khách gửi yêu cầu trả hàng):** Tại trang *Chi tiết đơn hàng* của đơn `Completed` ($\le 7$ ngày), khách hàng bấm **"Yêu cầu Trả hàng/Hoàn tiền"**, chọn lý do đổi trả (*Sản phẩm lỗi vận chuyển / Giao sai sản phẩm / Kích ứng da*) và nhập mô tả chi tiết.<br>2. `InstantRefundService` xác thực CSRF Nonce, kiểm tra quyền sở hữu và thời hạn 7 ngày, lưu thông tin vào metadata HPOS (`_kleer_return_reason`, `_kleer_return_description`, `_kleer_return_requested_at`) và chuyển trạng thái đơn hàng sang **`Return Requested` (`wc-return-requested` – Yêu cầu trả hàng)**.<br>3. **Bước 2 (Admin duyệt yêu cầu trả hàng):** Quản trị viên kiểm tra lý do đổi trả trong trang quản trị đơn hàng; xác nhận hợp lệ và chuyển trạng thái đơn sang **`Return Approved` (`wc-return-approved` – Chờ gửi trả hàng)**.<br>4. Giao diện trang *Chi tiết đơn hàng* của khách hàng tự động mở khóa biểu mẫu **"Cập nhật Mã vận đơn gửi trả hàng"**.<br>5. **Bước 3 (Khách nhập Mã vận đơn $\rightarrow$ Kích hoạt Instant Refund tự động):** Khách hàng bàn giao kiện hàng cho đơn vị vận chuyển, chọn tên hãng vận chuyển và nhập **Mã vận đơn (`_kleer_return_tracking_code`)** lên web rồi bấm xác nhận.<br>6. Ngay khi xác thực mã vận đơn hợp lệ (không cần chờ kiện hàng hoàn về tới kho), `InstantRefundService` **tự động kích hoạt Instant Refund**:<br>   • Tự động tạo mới một mã **Store Credit Coupon** (`KLR-CREDIT-{ORDER_ID}-{HASH}`) trong WooCommerce với mệnh giá bằng **100% tổng tiền đơn hàng** (`discount_type = fixed_cart`), ràng buộc cứng với email của khách (`customer_email`) và giới hạn sử dụng 1 lần (`usage_limit = 1`).<br>   • Chuyển trạng thái đơn hàng sang **`Refunded` (Đã hoàn tiền)** và lưu mã Store Credit vào `_kleer_store_credit_coupon_code`.<br>   • Hiển thị ngay mã Store Credit trên trang *Chi tiết đơn hàng* của khách hàng, đồng thời kích hoạt `kleer-mailer` gửi **Email thông báo Hoàn tiền nhanh Store Credit** để khách hàng sử dụng mua ngay đơn hàng mới. |
| **Alternate Flow (Luồng thay thế)** | **AF-01 (Quản trị viên từ chối yêu cầu đổi trả do không đủ điều kiện):**<br>• Tại bước 3, nếu yêu cầu không hợp lệ (ví dụ lý do không thuộc danh mục hỗ trợ), Quản trị viên ghi chú lý do từ chối và chuyển trạng thái đơn hàng trở lại `Completed`. |
| **Exception Flow (Luồng ngoại lệ)** | **EF-01 (Đơn hàng đã quá thời hạn 7 ngày kể từ ngày hoàn tất):**<br>• Hệ thống ẩn biểu mẫu "Yêu cầu Trả hàng/Hoàn tiền" và từ chối mọi request gửi yêu cầu đổi trả của đơn hàng đó.<br>**EF-02 (Mã vận đơn trả hàng để trống hoặc sai định dạng):**<br>• Tại bước 5, nếu mã vận đơn rỗng hoặc ngắn hơn 6 ký tự chữ/số, hệ thống báo lỗi yêu cầu khách hàng nhập mã vận đơn hợp lệ trước khi kích hoạt hoàn tiền. |

---

### 3.4. Biểu đồ lớp (Class Diagram)

> **Quy chuẩn vẽ trên Draw.io cho mục 3.4:**
> * Thể hiện rõ kiến trúc hướng đối tượng phân tầng (**Endpoints $\rightarrow$ Controllers $\rightarrow$ Services $\rightarrow$ Repositories $\rightarrow$ Models**) đang được triển khai thực tế trong `wp-content/plugins/kleer-plugin/`, `kleer-payments/` và `kleer-mailer/`.
> * Bắt buộc sử dụng đúng các mũi tên quan hệ UML: **Realization / Implementation** (nét đứt mũi tên tam giác rỗng từ Class tới Interface), **Dependency / Injection** (nét đứt mũi tên mở), **Association / Composition** (nét liền).

#### 3.4.1. Danh mục các Lớp (Classes & Interfaces) cho từng Chức năng cần vẽ trên Draw.io

1. **Chức năng 1 – Skin Quiz & Routine Engine (Bùi Hiếu Nhân):**
   * **File Draw.io:** `docs/flows/L01-quiz-routine/L01_Class_Diagram.drawio`
   * **Các lớp bắt buộc thể hiện:**
     * `Kleer\Endpoints\SkinQuizEndpoints` (`+registerRoutes(): void`)
     * `Kleer\Controllers\SkinQuizController` (`+evaluateQuiz(WP_REST_Request): WP_REST_Response`, sử dụng trait `ValidatesJsonPayload` và `JsonSchema`)
     * `Kleer\Services\QuizEngineService` (`+processSubmission(array, ?string): array`, `-processSubmissionV4(array): array`, `-scoreSkinProfileV4(array): array`, `-selectProductsForStepsV4(): array`)
     * `Kleer\Config\QuizQuestions` (`+getV4Questions(): array`, `+getStepHierarchy(): array`)
     * `Kleer\Contracts\ProductRepositoryInterface` & `Kleer\Contracts\QuizProductRepositoryInterface` *(Interfaces)*
     * `Kleer\Repositories\WooCommerceProductRepository` & `Kleer\Repositories\RoutineProductRepository` *(Implements Interfaces)*
     * `Kleer\Models\Product` & `Kleer\Exceptions\RoutineUnavailableException`
   * **[VỊ TRÍ CHÈN HÌNH 3.4.1 TRÊN GOOGLE DOCS]:** `![Hình 3.4.1 - Class Diagram Chức năng Skin Quiz & Routine](docs/flows/L01-quiz-routine/L01_Class_Diagram.png)`

2. **Chức năng 2 – Khám phá Danh mục & Bộ lọc đa tiêu chí (Lâm Trúc Uyên):**
   * **File Draw.io:** `docs/flows/L02-catalog-product/L02_Class_Diagram.drawio`
   * **Các lớp bắt buộc thể hiện:**
     * `Kleer\Taxonomy\TaxonomyRegistry` (`+register(): void`, `+registerProductAttributes(): void` cho `pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`)
     * `Kleer\Services\ProductService` (`+getFilteredProducts(array $filters): array`, `+getProductById(int $id): ?Product`)
     * `Kleer\Repositories\WooCommerceProductRepository` (`+findByTaxonomies(array $criteria): array`)
     * `Kleer\Support\Cache\KleerCache` & `Kleer\Support\Cache\CacheInvalidation`
   * **[VỊ TRÍ CHÈN HÌNH 3.4.2 TRÊN GOOGLE DOCS]:** `![Hình 3.4.2 - Class Diagram Chức năng Catalog & Taxonomy](docs/flows/L02-catalog-product/L02_Class_Diagram.png)`

3. **Chức năng 3 – Giỏ hàng Routine Bundle & Cảnh báo xung đột hoạt chất (Huỳnh Ngọc Nhã Uyên):**
   * **File Draw.io:** `docs/flows/L03-cart-combo/L03_Class_Diagram.drawio`
   * **Các lớp bắt buộc thể hiện:**
     * `Kleer\Services\CartComboService`:
       * Thuộc tính hằng: `DISCOUNT_RATE = 0.15`, `ROUTINE_STEPS`, `ACID_GROUP_SLUGS`, `RETINOID_GROUP_SLUGS`.
       * Phương thức: `+register(): void`, `+handleBulkAddRoutineAjax(): void`, `+applyRoutineBundleDiscount(WC_Cart $cart): void`, `+checkIngredientConflicts(): array`, `+renderConflictNotice(): void`, `-getRoutineStepsForProduct(int $productId): array`.
     * Quan hệ tương tác với lớp `WC_Cart` và `WC_Product` của WooCommerce.
   * **[VỊ TRÍ CHÈN HÌNH 3.4.3 TRÊN GOOGLE DOCS]:** `![Hình 3.4.3 - Class Diagram Chức năng Giỏ hàng & Cảnh báo xung đột](docs/flows/L03-cart-combo/L03_Class_Diagram.png)`

4. **Chức năng 4 – Checkout & Cổng thanh toán trực tuyến (Trần Ngọc Minh Tuyền):**
   * **File Draw.io:** `docs/flows/L04-checkout-payment/L04_Class_Diagram.drawio`
   * **Các lớp bắt buộc thể hiện (trong plugin `kleer-payments`):**
     * `Kleer_Payment_Gateways` (Đăng ký các cổng thanh toán vào WooCommerce)
     * `WC_Gateway_Kleer_VNPay` *(kế thừa `WC_Payment_Gateway`)*: `+init_form_fields()`, `+process_payment($order_id)`, `+handle_return()`, `+handle_ipn()`
     * `Kleer_VNPay_Signature`: `+build_hash_data(array $params): string`, `+generate_signature(array $params, string $secret): string`, `+verify_signature(array $params, string $secret): bool`
     * `Kleer_QR_Payload` & `Kleer_Thankyou_QR`: `+generate_vietqr_url(WC_Order $order): string`, `+render_qr_box(int $order_id): void`
   * **[VỊ TRÍ CHÈN HÌNH 3.4.4 TRÊN GOOGLE DOCS]:** `![Hình 3.4.4 - Class Diagram Chức năng Thanh toán](docs/flows/L04-checkout-payment/L04_Class_Diagram.png)`

5. **Chức năng 5 – Quản lý vòng đời đơn hàng HPOS & Hóa đơn Email QR (Nguyễn Minh Trí):**
   * **File Draw.io:** `docs/flows/L05-order-processing/L05_Class_Diagram.drawio`
   * **Các lớp bắt buộc thể hiện:**
     * `Kleer\Plugin\Services\HposService`: `+register(): void`, `+declareHposCompatibility(): void`, `+ensureHposConfiguration(): void`, `+isHposActive(): bool`
     * `Kleer_Mailer\Order_Email` (`kleer-mailer`): `+register(): void`, `+on_processing(int $order_id, ?WC_Order $order): void`, `+on_hold(int $order_id, ?WC_Order $order): void`, `+send(WC_Order $order, string $stage): bool`, `-build_data(WC_Order $order, string $stage): array`, `-render(array $data): string`
     * `Kleer_Mailer\QR_Encoder`: `+png(string $text, int $scale = 5, int $margin = 2): string`
     * `Kleer_Mailer\SMTP_Config`: `+register(): void`, `+configure_phpmailer(PHPMailer $phpmailer): void`
     * Quan hệ tương tác với `WC_Order` (lưu trữ trên 4 bảng phẳng HPOS).
   * **[VỊ TRÍ CHÈN HÌNH 3.4.5 TRÊN GOOGLE DOCS]:** `![Hình 3.4.5 - Class Diagram Chức năng Quản lý đơn hàng HPOS & Hóa đơn Email QR](docs/flows/L05-order-processing/L05_Class_Diagram.png)`

6. **Chức năng 6 – Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước (Nguyễn Ngọc Thảo Uyên):**
   * **File Draw.io:** `docs/flows/L05-order-processing/L07_Class_Diagram.drawio`
   * **Các lớp bắt buộc thể hiện:**
     * `Kleer\Services\InstantRefundService` *(Module xử lý Đổi trả 3 bước & phát hành Store Credit)*:
       * Thuộc tính hằng: `RETURN_WINDOW_DAYS = 7`, `STATUS_RETURN_REQUESTED = 'wc-return-requested'`, `STATUS_RETURN_APPROVED = 'wc-return-approved'`, `COUPON_PREFIX = 'KLR-CREDIT-'`.
       * Phương thức: `+register(): void`, `+registerCustomOrderStatuses(): void`, `+isEligibleForReturn(WC_Order $order): bool`, `+handleCustomerReturnRequest(int $orderId, string $reason, string $description): bool`, `+handleReturnTrackingSubmitted(int $orderId, string $trackingCode, string $carrier): string`, `+issueStoreCreditCoupon(WC_Order $order): string`, `+renderCustomerReturnPanel(WC_Order $order): void`.
     * `Kleer_Mailer\Order_Email`: `+send_store_credit_email(int $order_id, string $coupon_code): bool`
     * Quan hệ tương tác với `WC_Order` (đọc/ghi metadata HPOS) và `WC_Coupon` (khởi tạo mã Store Credit `fixed_cart` 100% giá trị đơn).
   * **[VỊ TRÍ CHÈN HÌNH 3.4.6 TRÊN GOOGLE DOCS]:** `![Hình 3.4.6 - Class Diagram Chức năng Đổi trả 7 ngày & Hoàn tiền Store Credit](docs/flows/L05-order-processing/L07_Class_Diagram.png)`

---

### 3.5. Biểu đồ trình tự (Sequence Diagram)

> **Quy chuẩn vẽ trên Draw.io cho mục 3.5:**
> * Mỗi biểu đồ trình tự cần có đầy đủ các đường đời (*Lifelines*), thanh kích hoạt (*Activation Bars*), thông điệp đồng bộ (*Synchronous Messages* – mũi tên đặc), thông điệp phản hồi (*Return Messages* – mũi tên nét đứt), và các khung tương tác (*Combined Fragments*: `alt/else` cho rẽ nhánh điều kiện, `loop` cho vòng lặp, `opt` cho tùy chọn).

#### 3.5.1. Sequence Diagram – Chức năng 1: Chấm điểm Skin Quiz & Truy vấn Routine (Bùi Hiếu Nhân)
* **File Draw.io bàn giao:** `docs/flows/L01-quiz-routine/L01_Sequence_Quiz_Routine.drawio` (và `.png`)
* **Các Lifelines theo thứ tự từ trái sang phải:** `Customer (Actor)` $\rightarrow$ `QuizSPA (kleer-theme)` $\rightarrow$ `SkinQuizEndpoints` $\rightarrow$ `SkinQuizController` $\rightarrow$ `QuizEngineService` $\rightarrow$ `WooCommerceProductRepository` $\rightarrow$ `MariaDB / KleerCache`.
* **Khung `alt` bắt buộc có:**
  * `[Validation Failed]`: Trả về `400 Bad Request`.
  * `[No Primary SKU Found for Step]`: Gọi `findGentleFallbackByStep($step)` để lấy sản phẩm dự phòng `_kleer_gentle_fallback = 1`.
* **[VỊ TRÍ CHÈN HÌNH 3.5.1 TRÊN GOOGLE DOCS]:** `![Hình 3.5.1 - Sequence Diagram Chức năng Skin Quiz & Routine](docs/flows/L01-quiz-routine/L01_Sequence_Quiz_Routine.png)`

#### 3.5.2. Sequence Diagram – Chức năng 2: Lọc sản phẩm đa tiêu chí bằng AJAX (Lâm Trúc Uyên)
* **File Draw.io bàn giao:** `docs/flows/L02-catalog-product/L02_Sequence_Catalog_Filter.drawio` (và `.png`)
* **Các Lifelines theo thứ tự:** `Customer (Actor)` $\rightarrow$ `CatalogPageUI (catalog-filter.js)` $\rightarrow$ `WordPress AJAX Router (admin-ajax.php)` $\rightarrow$ `ProductFilterHandler` $\rightarrow$ `WP_Query (Tax & Meta Query)` $\rightarrow$ `MariaDB`.
* **Điểm nhấn kỹ thuật trên hình:** Thể hiện bước `history.pushState()` đồng bộ URL phía Client và bước xây dựng `tax_query` kết hợp 4 Taxonomy (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`).
* **[VỊ TRÍ CHÈN HÌNH 3.5.2 TRÊN GOOGLE DOCS]:** `![Hình 3.5.2 - Sequence Diagram Chức năng Lọc sản phẩm AJAX](docs/flows/L02-catalog-product/L02_Sequence_Catalog_Filter.png)`

#### 3.5.3. Sequence Diagram – Chức năng 3: 1-Click Add Routine, Tính giảm giá 15% & Quét xung đột hoạt chất (Huỳnh Ngọc Nhã Uyên)
* **File Draw.io bàn giao:** `docs/flows/L03-cart-combo/L03_Sequence_Cart_Combo_Conflict.drawio` (và `.png`)
* **Các Lifelines theo thứ tự:** `Customer (Actor)` $\rightarrow$ `RoutineCartJS` $\rightarrow$ `CartComboService` $\rightarrow$ `WC_Cart` $\rightarrow$ `Taxonomy Term Store`.
* **Khung tương tác bắt buộc có:**
  * Khung `loop [For each product_id in Routine]`: Kiểm tra `is_in_stock()` và gọi `WC()->cart->add_to_cart()`.
  * Khung `alt [Unique Routine Steps >= 3]`: Gọi `$cart->add_fee('Giảm 15% Combo Routine', -$discountAmount)`.
  * Khung `opt [Has Acid AND Has Retinoid]`: Hiển thị cảnh báo xung đột hoạt chất (`renderConflictNotice()`).
* **[VỊ TRÍ CHÈN HÌNH 3.5.3 TRÊN GOOGLE DOCS]:** `![Hình 3.5.3 - Sequence Diagram Chức năng Giỏ hàng & Cảnh báo xung đột](docs/flows/L03-cart-combo/L03_Sequence_Cart_Combo_Conflict.png)`

#### 3.5.4. Sequence Diagram – Chức năng 4: Thanh toán trực tuyến qua VNPay Sandbox & VietQR (Trần Ngọc Minh Tuyền)
* **File Draw.io bàn giao:** `docs/flows/L04-checkout-payment/L04_Checkout_VNPay_Sequence.drawio` (đã có sẵn file gốc trong repo, rà soát và xuất `.png`).
* **Các Lifelines theo thứ tự:** `Customer (Actor)` $\rightarrow$ `CheckoutUI` $\rightarrow$ `WC_Checkout` $\rightarrow$ `WC_Gateway_Kleer_VNPay` $\rightarrow$ `Kleer_VNPay_Signature` $\rightarrow$ `VNPay Sandbox Server` $\rightarrow$ `HPOS Database`.
* **Điểm nhấn kỹ thuật trên hình:** Thể hiện rõ bước ký số `HMAC-SHA512` khi tạo URL chuyển hướng và bước đối soát chữ ký số `verify_signature()` khi nhận Return URL / IPN Callback trước khi chuyển đơn sang `Processing`.
* **[VỊ TRÍ CHÈN HÌNH 3.5.4 TRÊN GOOGLE DOCS]:** `![Hình 3.5.4 - Sequence Diagram Chức năng Thanh toán VNPay & VietQR](docs/flows/L04-checkout-payment/L04_Checkout_VNPay_Sequence.png)`

#### 3.5.5. Sequence Diagram – Chức năng 5: Xử lý đơn hàng HPOS, Đồng bộ tồn kho & Gửi hóa đơn Email QR (Nguyễn Minh Trí)
* **File Draw.io bàn giao:** `docs/flows/L05-order-processing/L05_Sequence_HPOS_Invoice_Email.drawio` (và `.png`)
* **Các Lifelines theo thứ tự:** `StoreAdmin (Actor)` $\rightarrow$ `WC_Order (HPOS Custom Tables)` $\rightarrow$ `StockManager (WooCommerce)` $\rightarrow$ `Kleer_Mailer\Order_Email` $\rightarrow$ `Kleer_Mailer\QR_Encoder` $\rightarrow$ `SMTP Server` $\rightarrow$ `Customer (Actor)`.
* **Điểm nhấn kỹ thuật trên hình:**
  1. Khi đơn chuyển sang `Processing`: Ghi vào bảng phẳng `wp_wc_orders` & `wp_wc_order_operational_data` $\rightarrow$ Gọi `wc_reduce_stock_levels($order_id)` trừ tồn kho.
  2. Hook `woocommerce_order_status_processing` kích hoạt `Order_Email::on_processing()` $\rightarrow$ Kiểm tra meta `_kleer_invoice_email_processing` $\rightarrow$ Gọi `QR_Encoder::png($trackingUrl)` sinh mã QR nhúng inline (`cid:kleer-order-qr`) $\rightarrow$ Gửi HTML Email qua SMTP cho khách hàng.
  3. Khung `alt [Order Cancelled]`: Gọi `restore_order_stock()` hoàn lại tồn kho; `[Order Delivered]`: Admin chuyển trạng thái sang `Completed`.
* **[VỊ TRÍ CHÈN HÌNH 3.5.5 TRÊN GOOGLE DOCS]:** `![Hình 3.5.5 - Sequence Diagram Chức năng Xử lý đơn hàng HPOS & Hóa đơn Email QR](docs/flows/L05-order-processing/L05_Sequence_HPOS_Invoice_Email.png)`

#### 3.5.6. Sequence Diagram – Chức năng 6: Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước (Nguyễn Ngọc Thảo Uyên)
* **File Draw.io bàn giao:** `docs/flows/L05-order-processing/L07_Sequence_Instant_Store_Credit_Refund.drawio` (và `.png`)
* **Các Lifelines theo thứ tự:** `Customer (Actor)` $\rightarrow$ `OrderDetailUI` $\rightarrow$ `StoreAdmin (Actor)` $\rightarrow$ `InstantRefundService` $\rightarrow$ `WC_Order (HPOS wp_wc_orders)` $\rightarrow$ `WC_Coupon (Store Credit)` $\rightarrow$ `Kleer_Mailer\Order_Email (SMTP)`.
* **Thể hiện rõ 3 bước truyền thông điệp (Tham chiếu Shopee Instant Refund):**
  1. **Bước 1:** `Customer` $\rightarrow$ `OrderDetailUI` $\rightarrow$ `InstantRefundService`: `handleCustomerReturnRequest(orderId, reason, description)` $\rightarrow$ Kiểm tra `isEligibleForReturn(order)` ($\le 7$ ngày) $\rightarrow$ Lưu meta & cập nhật trạng thái đơn sang `wc-return-requested`.
  2. **Bước 2:** `StoreAdmin` $\rightarrow$ `WC_Order`: Kiểm duyệt yêu cầu hợp lệ $\rightarrow$ Cập nhật trạng thái đơn sang `wc-return-approved` (hoặc khung `else [Rejected]` trả về `completed`).
  3. **Bước 3:** `Customer` $\rightarrow$ `OrderDetailUI` $\rightarrow$ `InstantRefundService`: `handleReturnTrackingSubmitted(orderId, trackingCode, carrier)` $\rightarrow$ Kiểm tra định dạng mã vận đơn ($\ge 6$ ký tự) $\rightarrow$ Tự động gọi `issueStoreCreditCoupon(order)` sinh mã `KLR-CREDIT-{ORDER_ID}-{HASH}` (100% giá trị đơn, gắn email khách, dùng 1 lần) $\rightarrow$ Cập nhật đơn sang `refunded` $\rightarrow$ Phát hook `kleer_instant_refund_issued` để `Order_Email::send_store_credit_email()` gửi mã ngay cho khách hàng.
* **[VỊ TRÍ CHÈN HÌNH 3.5.6 TRÊN GOOGLE DOCS]:** `![Hình 3.5.6 - Sequence Diagram Chức năng Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit](docs/flows/L05-order-processing/L07_Sequence_Instant_Store_Credit_Refund.png)`

---

### 3.6. Biểu đồ trạng thái (State Machine Diagram)

> **Quy chuẩn vẽ trên Draw.io cho mục 3.6:**
> * Mỗi biểu đồ mô hình hóa vòng đời trạng thái của **1 đối tượng nghiệp vụ trọng tâm** thuộc chức năng đó.
> * Trên mỗi mũi tên chuyển trạng thái (*Transition*), ghi rõ nhãn theo cú pháp chuẩn UML: **`Event [Guard Condition] / Action`**.

#### 3.6.1. State Machine Diagram – Chức năng 1: Đối tượng Phiên Chẩn đoán & Đề xuất Routine (`QuizRoutineSession`)
* **Người thực hiện:** Bùi Hiếu Nhân
* **File Draw.io bàn giao:** `docs/flows/L01-quiz-routine/L01_State_Quiz_Session.drawio` (và `.png`)
* **Các trạng thái (States) & Chuyển tiếp (Transitions) cần vẽ:**
  * `[*] (Start)` $\rightarrow$ **`Initialized`** (Khởi tạo bài Quiz) $\rightarrow$ **`Answering (Q1..Q6)`** (Đang trả lời câu hỏi, lưu tạm trạng thái trên Client) $\rightarrow$ **`ValidatingPayload`** (Kiểm tra JSON Schema) $\rightarrow$ **`ScoringProfile`** (Tính điểm loại da & xét ngưỡng nhạy cảm `sensitive >= 4`).
  * Từ `ScoringProfile` $\rightarrow$ **`MatchingProducts`** (Truy vấn sản phẩm theo bước Routine):
    * Nếu đủ sản phẩm khớp hoàn hảo $\rightarrow$ **`RoutineCompleted`** (Hiển thị phác đồ chính thức).
    * Nếu thiếu SKU ở bước bất kỳ `[SKU Out of Stock / Missing]` $\rightarrow$ **`FallbackApplied`** (Bù sản phẩm lành tính `_kleer_gentle_fallback = 1`) $\rightarrow$ **`RoutineCompleted`**.
    * Nếu dữ liệu đầu vào lỗi hoặc kho trống hoàn toàn $\rightarrow$ **`Rejected / Failed`**.
* **[VỊ TRÍ CHÈN HÌNH 3.6.1 TRÊN GOOGLE DOCS]:** `![Hình 3.6.1 - State Machine Diagram Phiên Skin Quiz & Routine](docs/flows/L01-quiz-routine/L01_State_Quiz_Session.png)`

#### 3.6.2. State Machine Diagram – Chức năng 2: Đối tượng Bộ lọc Danh mục Sản phẩm (`CatalogFilterState`)
* **Người thực hiện:** Lâm Trúc Uyên
* **File Draw.io bàn giao:** `docs/flows/L02-catalog-product/L02_State_Catalog_Filter.drawio` (và `.png`)
* **Các trạng thái (States) & Chuyển tiếp (Transitions) cần vẽ:**
  * `[*]` $\rightarrow$ **`DefaultCatalog`** (Hiển thị toàn bộ sản phẩm phân trang mặc định, chưa áp bộ lọc).
  * Khi khách chọn tiêu chí (`selectFilterCriteria`) $\rightarrow$ **`FilteringAjax`** (Đang gửi truy vấn AJAX & đồng bộ `history.pushState`):
    * `[Matched Count > 0]` $\rightarrow$ **`FilteredActive`** (Hiển thị danh sách sản phẩm đã lọc + các thẻ Tag tiêu chí đang bật).
    * `[Matched Count == 0]` $\rightarrow$ **`EmptyResult`** (Hiển thị thông báo không tìm thấy sản phẩm & gợi ý xóa bộ lọc).
  * Từ `FilteredActive` hoặc `EmptyResult`, khi khách bấm `resetAllFilters` $\rightarrow$ Quay về **`DefaultCatalog`**.
* **[VỊ TRÍ CHÈN HÌNH 3.6.2 TRÊN GOOGLE DOCS]:** `![Hình 3.6.2 - State Machine Diagram Bộ lọc Catalog AJAX](docs/flows/L02-catalog-product/L02_State_Catalog_Filter.png)`

#### 3.6.3. State Machine Diagram – Chức năng 3: Đối tượng Phiên Giỏ hàng (`CartSession`)
* **Người thực hiện:** Huỳnh Ngọc Nhã Uyên
* **File Draw.io bàn giao:** `docs/flows/L03-cart-combo/L03_State_Cart_Session.drawio` (và `.png`)
* **Các trạng thái (States) & Chuyển tiếp (Transitions) cần vẽ:**
  * `[*]` $\rightarrow$ **`EmptyCart`** (Giỏ hàng trống).
  * Khi thêm sản phẩm (`addItem` / `bulkAddRoutine`):
    * Nếu tổng số bước Routine khác nhau $< 3$ $\rightarrow$ **`StandardCart`** (Tính nguyên giá).
    * Nếu tổng số bước Routine khác nhau $\ge 3$ $\rightarrow$ **`BundleDiscountApplied`** (Tự động kích hoạt giảm giá 15% Combo Routine).
  * Trạng thái song song / gắn cờ cảnh báo: Khi trong giỏ phát hiện đồng thời nhóm `Acid` và `Retinoids` $\rightarrow$ Kích hoạt trạng thái cảnh báo **`ConflictWarningActive`** (Hiển thị Banner cảnh báo da liễu); khi xóa 1 trong 2 chất kỵ nhau $\rightarrow$ Tắt cảnh báo (`ConflictCleared`).
  * Khi bấm đặt hàng thành công (`checkoutSuccess`) $\rightarrow$ **`ConvertedToOrder`** $\rightarrow$ `[*]`.
* **[VỊ TRÍ CHÈN HÌNH 3.6.3 TRÊN GOOGLE DOCS]:** `![Hình 3.6.3 - State Machine Diagram Phiên Giỏ hàng KLEER](docs/flows/L03-cart-combo/L03_State_Cart_Session.png)`

#### 3.6.4. State Machine Diagram – Chức năng 4: Đối tượng Giao dịch Thanh toán (`PaymentTransaction`)
* **Người thực hiện:** Trần Ngọc Minh Tuyền
* **File Draw.io bàn giao:** `docs/flows/L04-checkout-payment/L04_State_Payment_Transaction.drawio` (và `.png`)
* **Các trạng thái (States) & Chuyển tiếp (Transitions) cần vẽ:**
  * `[*]` $\rightarrow$ **`Initiated`** (Khởi tạo giao dịch khi khách bấm Đặt hàng).
  * Rẽ nhánh theo kênh thanh toán:
    * `[Method == COD]` $\rightarrow$ **`COD_Confirmed`** (Chờ thu tiền mặt khi giao hàng) $\rightarrow$ **`Paid`** (Khi giao thành công).
    * `[Method == VietQR]` $\rightarrow$ **`AwaitingBankTransfer`** (Hiển thị mã QR Napas động, chờ đối soát) $\rightarrow$ **`Paid`** (Khi xác nhận nhận tiền) hoặc **`Cancelled`** (Khi quá hạn).
    * `[Method == VNPay]` $\rightarrow$ **`RedirectedToVNPay`** (Đang ở cổng thanh toán VNPay Sandbox) $\rightarrow$ **`VerifyingSignature`** (Kiểm tra chữ ký `HMAC-SHA512` từ Return URL/IPN):
      * `[Signature Valid & ResponseCode == '00']` $\rightarrow$ **`Paid`**.
      * `[Signature Invalid OR ResponseCode != '00']` $\rightarrow$ **`PaymentFailed`**.
* **[VỊ TRÍ CHÈN HÌNH 3.6.4 TRÊN GOOGLE DOCS]:** `![Hình 3.6.4 - State Machine Diagram Giao dịch Thanh toán](docs/flows/L04-checkout-payment/L04_State_Payment_Transaction.png)`

#### 3.6.5. State Machine Diagram – Chức năng 5: Đối tượng Đơn hàng HPOS (`HposOrder`)
* **Người thực hiện:** Nguyễn Minh Trí
* **File Draw.io bàn giao:** `docs/flows/L05-order-processing/state.drawio` (đã có sẵn file gốc trong repo, rà soát và xuất `.png`).
* **Các trạng thái (States) & Chuyển tiếp (Transitions) cần vẽ:**
  1. **`Pending payment` (Chờ thanh toán):** Đơn hàng vừa khởi tạo, chờ kết quả cổng thanh toán.
  2. **`On-hold` (Tạm giữ):** Đơn chọn chuyển khoản VietQR chờ đối soát $\rightarrow$ *Entry Action:* Gửi Email xác nhận tạm giữ kèm mã QR.
  3. **`Processing` (Đang xử lý):** Đã xác nhận thanh toán VNPay, đối soát VietQR thành công hoặc đặt COD $\rightarrow$ *Entry Action:* Trừ tồn kho (`reduce_order_stock`) & Gửi Email hóa đơn HTML đính kèm mã QR.
  4. **`Completed` (Đã hoàn thành):** Đơn vị vận chuyển đã giao hàng thành công cho khách.
  5. **`Cancelled` / `Failed` (Đã hủy / Thất bại):** Khách hủy đơn hoặc thanh toán thất bại $\rightarrow$ *Entry Action:* Hoàn trả tồn kho (`restore_order_stock`).
* **[VỊ TRÍ CHÈN HÌNH 3.6.5 TRÊN GOOGLE DOCS]:** `![Hình 3.6.5 - State Machine Diagram Vòng đời Đơn hàng HPOS](docs/flows/L05-order-processing/state.png)`

#### 3.6.6. State Machine Diagram – Chức năng 6: Đối tượng Yêu cầu Đổi trả & Hoàn tiền Store Credit (`InstantRefundLifecycle`)
* **Người thực hiện:** Nguyễn Ngọc Thảo Uyên
* **File Draw.io bàn giao:** `docs/flows/L05-order-processing/L07_State_Instant_Refund.drawio` (và `.png`)
* **Các trạng thái (States) & Chuyển tiếp (Transitions) cần vẽ:**
  1. **`EligibleForReturn` (Đủ điều kiện đổi trả):** Đơn hàng ở trạng thái `Completed` và thời gian kể từ lúc hoàn tất $\le 7$ ngày (nếu $> 7$ ngày $\rightarrow$ chuyển sang **`ReturnWindowExpired`**, khóa quyền yêu cầu).
  2. **`ReturnRequested` (`wc-return-requested` – Bước 1 Shopee):** Khi khách hàng gửi lý do đổi trả hợp lệ $\rightarrow$ *Entry Action:* Lưu `_kleer_return_reason`, `_kleer_return_requested_at`.
  3. **`ReturnApproved` (`wc-return-approved` – Bước 2 Shopee):** Khi Admin phê duyệt yêu cầu $\rightarrow$ *Entry Action:* Mở khóa biểu mẫu nhập Mã vận đơn trả hàng trên trang Đơn hàng của khách. (Nếu Admin từ chối `[Admin Rejected]` $\rightarrow$ chuyển về **`ReturnRejected / Completed`**).
  4. **`InstantRefunded` (`wc-refunded` – Bước 3 Shopee Instant Refund):** Chuyển tự động ngay khi khách hàng nhập **Mã vận đơn hợp lệ ($\ge 6$ ký tự)** $\rightarrow$ *Entry Action:* Sinh mã `WC_Coupon` (`KLR-CREDIT-{ORDER_ID}-{HASH}`, mệnh giá 100% đơn, gắn `customer_email`, `usage_limit = 1`) & Gửi Email thông báo Store Credit.
  5. **`StoreCreditRedeemed` (Đã sử dụng mã hoàn tiền):** Khi khách hàng áp dụng mã `KLR-CREDIT-...` thành công vào đơn hàng mới (`usage_count == 1`) $\rightarrow$ `[*]`.
* **[VỊ TRÍ CHÈN HÌNH 3.6.6 TRÊN GOOGLE DOCS]:** `![Hình 3.6.6 - State Machine Diagram Đổi trả 7 ngày & Hoàn tiền Store Credit](docs/flows/L05-order-processing/L07_State_Instant_Refund.png)`

---

## 4. THIẾT KẾ HỆ THỐNG (SYSTEM DESIGN)
*(Phụ trách: Lê Nguyễn Quốc An – Thiết kế biểu đồ trên Draw.io và bàn giao qua Google Docs)*

> **⚠️ QUY ĐỊNH BÀN GIAO CHO CHƯƠNG 4 (LÊ NGUYỄN QUỐC AN):**
> * Toàn bộ sơ đồ Kiến trúc hệ thống (Mục 4.1) và sơ đồ Cơ sở dữ liệu ERD (Mục 4.2) **bắt buộc vẽ trên Draw.io (`.drawio`)**, xuất file ảnh `.png` sắc nét và bàn giao qua Google Docs cho Nhóm trưởng (Nguyễn Tấn Phát).
> * Dưới đây là toàn bộ nội dung thuyết minh kiến trúc và đặc tả các bảng CSDL chuẩn hóa 3NF + ánh xạ HPOS đã được thiết lập sẵn làm khung chuẩn.

---

### 4.1. Kiến trúc hệ thống (System Architecture)

Hệ thống thương mại điện tử **KLEER** được thiết kế bằng cách kết hợp hai mô hình kiến trúc hiện đại nhằm đảm bảo cả tính đóng gói triển khai lẫn khả năng mở rộng mã nguồn:
1. **Kiến trúc Phân tầng Vật lý 3 tầng (3-Tier Deployment Architecture)** trên nền tảng ảo hóa container **Docker Compose**.
2. **Kiến trúc Nhân thu nhỏ kết hợp Phân lớp Hướng đối tượng (Microkernel / Plugin-based + Layered OOP Architecture)** ở tầng ứng dụng.

#### 4.1.1. Kiến trúc Triển khai Vật lý 3 Tầng (3-Tier Docker Architecture)
* **Tên file Draw.io cần vẽ:** `docs/flows/L09-infra-devops/L09_System_Architecture_3Tier.drawio` (và `.png`)
* **Mô tả các thành phần trên sơ đồ:**
  * **Tầng 1 – Presentation & Web Proxy Tier (`nginx` container):** Đóng vai trò Reverse Proxy tiếp nhận toàn bộ kết nối HTTP/HTTPS từ trình duyệt khách hàng (Desktop/Mobile), phân phối trực tiếp các tài nguyên tĩnh (ảnh `.webp`, CSS, JS) và chuyển tiếp các yêu cầu động qua giao thức FastCGI tới tầng xử lý ứng dụng.
  * **Tầng 2 – Application & Business Logic Tier (`php:8.2-fpm` container):** Vận hành nhân nền tảng WordPress 6.x & WooCommerce 8.x cùng 4 module tách biệt hoàn toàn:
    * `kleer-theme`: Tầng giao diện người dùng (PHP Templates thuần, CSS BEM, Vanilla JS ES6 SPA cho Skin Quiz và AJAX Filter – tuyệt đối không dùng Page Builder nặng nề).
    * `kleer-plugin`: Tầng xử lý nghiệp vụ cốt lõi theo chuẩn PSR-4 (`Kleer\` namespace), cung cấp REST API `/wp-json/kleer/v1/skin-quiz`, thuật toán chấm điểm Routine v4, giỏ hàng Combo 15%, cảnh báo xung đột hoạt chất, HPOS và hoàn tiền Store Credit.
    * `kleer-payments`: Plugin độc lập tích hợp cổng thanh toán VNPay Sandbox (`HMAC-SHA512`) và trình tạo mã QR chuyển khoản động Napas VietQR.
    * `kleer-mailer`: Plugin độc lập xử lý gửi Email hóa đơn HTML đính kèm mã QR và Email cấp mã Store Credit qua giao thức SMTP.
  * **Tầng 3 – Data & Caching Tier (`mariadb:10.11` & `redis` containers):**
    * **MariaDB 10.11:** Lưu trữ cơ sở dữ liệu quan hệ với InnoDB Engine, vận hành kiến trúc bảng phẳng **WooCommerce HPOS** (`wp_wc_orders*`) cách ly dữ liệu đơn hàng khỏi bảng bài viết.
    * **Redis In-Memory Cache:** Đệm kết quả truy vấn sản phẩm và các thuộc tính Taxonomy (`KleerCache`), giảm tải truy vấn SQL trực tiếp xuống đĩa cứng.

* **[VỊ TRÍ CHÈN HÌNH 4.1.1 TRÊN GOOGLE DOCS]:** `![Hình 4.1.1 - Sơ đồ Kiến trúc Vật lý 3-Tier trên Docker Compose](docs/flows/L09-infra-devops/L09_System_Architecture_3Tier.png)`

#### 4.1.2. Kiến trúc Phân lớp Phần mềm trong `kleer-plugin` (Layered OOP Architecture)
* **Tên file Draw.io cần vẽ:** `docs/flows/L09-infra-devops/L09_Layered_Plugin_Architecture.drawio` (và `.png`)
* **Nguyên tắc dòng phụ thuộc một chiều (Unidirectional Dependency Flow):**
  $$\text{HTTP/AJAX Request} \rightarrow \mathbf{Endpoints} \rightarrow \mathbf{Controllers} \rightarrow \mathbf{Services} \rightarrow \mathbf{Contracts\ (Interfaces)} \leftarrow \mathbf{Repositories} \rightarrow \mathbf{Models / Database}$$

| Tầng kiến trúc (Layer) | Thư mục mã nguồn | Trách nhiệm cốt lõi (Responsibility) |
| :--- | :--- | :--- |
| **1. Presentation Layer** | `wp-content/themes/kleer-theme/` | Hiển thị giao diện HTML5/CSS BEM, xử lý tương tác SPA Skin Quiz (`quiz.js`), bộ lọc AJAX (`catalog-filter.js`) và giỏ hàng (`routine-cart.js`). |
| **2. Routing / Endpoint Layer** | `kleer-plugin/src/Endpoints/` | Đăng ký các route REST API (`register_rest_route` dưới namespace `kleer/v1`), kiểm tra quyền truy cập (`permission_callback`) và chính sách CORS. |
| **3. Controller Layer** | `kleer-plugin/src/Controllers/` | Tiếp nhận `WP_REST_Request`, kiểm tra tính hợp lệ của dữ liệu đầu vào bằng JSON Schema (`ValidatesJsonPayload`), gọi tầng Service và định dạng phản hồi `WP_REST_Response`. |
| **4. Service / Domain Layer** | `kleer-plugin/src/Services/` | Chứa toàn bộ quy tắc nghiệp vụ cốt lõi: `QuizEngineService` (chấm điểm da & chọn Routine), `CartComboService` (giảm giá 15% & quét xung đột hoạt chất), `HposService`, `InstantRefundService`. |
| **5. Repository / Data Access Layer** | `kleer-plugin/src/Repositories/` | Hiện thực hóa các `Contracts` (`ProductRepositoryInterface`), thực thi truy vấn dữ liệu sản phẩm, Taxonomy và đơn hàng HPOS kết hợp bộ nhớ đệm `KleerCache`. |

* **[VỊ TRÍ CHÈN HÌNH 4.1.2 TRÊN GOOGLE DOCS]:** `![Hình 4.1.2 - Sơ đồ Kiến trúc Phân lớp OOP của KLEER Plugin](docs/flows/L09-infra-devops/L09_Layered_Plugin_Architecture.png)`

---

### 4.2. Thiết kế cơ sở dữ liệu (Database Design)

Để vừa đảm bảo tính chặt chẽ của lý thuyết chuẩn hóa cơ sở dữ liệu quan hệ (**Normalization 1NF – 2NF – 3NF**), vừa phản ánh trung thực cơ chế lưu trữ hiệu năng cao **WooCommerce HPOS (High-Performance Order Storage)** đang chạy trong thực tế, thiết kế CSDL của KLEER được trình bày ở **2 tầng liên kết chặt chẽ**:
1. **Tầng 1 – Mô hình Dữ liệu Quan hệ Luận lý Chuẩn hóa 3NF (Logical 3NF ERD):** Biểu diễn 8 thực thể nghiệp vụ cốt lõi của bài toán KLEER với đầy đủ Khóa chính (`PK`), Khóa ngoại (`FK`) và ràng buộc toàn vẹn.
2. **Tầng 2 – Ánh xạ Vật lý sang Kiến trúc Bảng phẳng WooCommerce HPOS & Taxonomy Schema (Physical Schema Mapping):** Giải trình cách lưu trữ thực tế trong MariaDB giúp loại bỏ điểm nghẽn của bảng `wp_postmeta` truyền thống.

#### 4.2.1. Tầng 1: Mô hình ERD Luận lý Chuẩn hóa 3NF (Logical 3NF Schema)
* **Tên file Draw.io cần vẽ:** `docs/flows/L09-infra-devops/L09_Database_ERD_3NF_and_HPOS.drawio` (và `.png`)
* **Giải trình tính chuẩn hóa (Normalization):**
  * **Đạt chuẩn 1NF:** Mọi thuộc tính trong 8 bảng đều chứa giá trị nguyên tố (Atomic). Các thuộc tính đa trị của sản phẩm (như một sản phẩm phù hợp với nhiều *Loại da*, giải quyết nhiều *Vấn đề da*, hoặc chứa nhiều *Hoạt chất INCI*) được tách hoàn toàn ra bảng trung gian `Product_Taxonomies` thay vì lưu chuỗi ngăn cách bằng dấu phẩy.
  * **Đạt chuẩn 2NF:** Mọi thuộc tính không khóa trong các bảng có khóa chính ghép (`Product_Taxonomies`, `Order_Items`) đều phụ thuộc hàm đầy đủ vào toàn bộ khóa chính (ví dụ: `unit_price` và `quantity` trong `Order_Items` phụ thuộc vào cả `order_id` và `product_id`, còn `product_name` và `regular_price` gốc được tách về bảng `Products`).
  * **Đạt chuẩn 3NF:** Không tồn tại phụ thuộc bắc cầu (Transitive Dependency) giữa các thuộc tính không khóa. Thông tin chi tiết khách hàng tách ở bảng `Users`, thông tin danh mục thuộc tính da liễu tách ở `Taxonomy_Terms`, thông tin giao dịch thanh toán tách ở `Payment_Transactions`, và mã hoàn tiền nhanh tách ở bảng `Store_Credits`.

##### Đặc tả 8 Bảng Quan hệ Luận lý Cốt lõi của KLEER:

**1. Bảng `Users` (Người dùng / Khách hàng & Quản trị viên)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `user_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh duy nhất của người dùng |
| `email` | `VARCHAR(100)` | UNIQUE, NOT NULL | Địa chỉ email đăng nhập & nhận hóa đơn / Store Credit |
| `password_hash` | `VARCHAR(255)` | NOT NULL | Mật khẩu đã băm bảo mật |
| `full_name` | `VARCHAR(150)` | NOT NULL | Họ và tên người dùng |
| `phone` | `VARCHAR(20)` | NULL | Số điện thoại liên hệ giao hàng |
| `role` | `ENUM('customer','admin')` | NOT NULL | Vai trò phân quyền trên hệ thống |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm tạo tài khoản |

**2. Bảng `Products` (Sản phẩm Chăm sóc Da – $\ge 500\text{ SKU}$)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `product_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh sản phẩm |
| `sku` | `VARCHAR(50)` | UNIQUE, NOT NULL | Mã SKU chuẩn hóa (`KLR-{hash8}`) |
| `product_name` | `VARCHAR(255)` | NOT NULL | Tên thương mại của sản phẩm |
| `regular_price` | `DECIMAL(12,2)` | NOT NULL | Giá bán niêm yết (VNĐ) |
| `stock_quantity` | `INT` | NOT NULL, DEFAULT 0 | Số lượng tồn kho thực tế |
| `stock_status` | `ENUM('instock','outofstock')` | NOT NULL | Trạng thái còn hàng / hết hàng |
| `full_inci_list` | `TEXT` | NULL | Bảng thành phần danh pháp quốc tế INCI đầy đủ |
| `is_strong_active` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Cờ hoạt chất mạnh (`_kleer_strong_active = 1/0` phục vụ lọc da nhạy cảm) |
| `is_gentle_fallback` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Cờ sản phẩm lành tính dự phòng (`_kleer_gentle_fallback = 1/0`) |

**3. Bảng `Taxonomy_Terms` (Từ điển Thuộc tính Da liễu & Hoạt chất)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `term_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh giá trị thuộc tính |
| `taxonomy_group` | `VARCHAR(50)` | NOT NULL | Nhóm thuộc tính (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`, `pa_brand`) |
| `term_slug` | `VARCHAR(100)` | NOT NULL | Mã chuẩn hóa (VD: `oily`, `sensitive`, `acne`, `cleanser`, `serum`, `retinol`, `bha`...) |
| `term_name` | `VARCHAR(150)` | NOT NULL | Tên hiển thị tiếng Việt (VD: *Da dầu*, *Da nhạy cảm*, *Sữa rửa mặt*...) |

**4. Bảng `Product_Taxonomies` (Bảng liên kết Đa-Đa giữa Sản phẩm và Thuộc tính Da liễu)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `product_id` | `BIGINT UNSIGNED` | **PK, FK1** (`Products.product_id`) | Mã sản phẩm |
| `term_id` | `BIGINT UNSIGNED` | **PK, FK2** (`Taxonomy_Terms.term_id`) | Mã thuộc tính da liễu / bước routine / hoạt chất được gán |

**5. Bảng `Orders` (Đơn hàng – Lưu trữ trên cấu trúc phẳng HPOS)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `order_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã đơn hàng |
| `customer_id` | `BIGINT UNSIGNED` | **FK** (`Users.user_id`), NULL | Mã tài khoản khách hàng (NULL nếu mua dạng Guest kèm email) |
| `billing_email` | `VARCHAR(100)` | NOT NULL | Email người mua nhận hóa đơn QR & Store Credit |
| `shipping_address` | `VARCHAR(500)` | NOT NULL | Địa chỉ nhận hàng đầy đủ |
| `order_status` | `VARCHAR(40)` | NOT NULL | Trạng thái đơn (`pending`, `on-hold`, `processing`, `completed`, `cancelled`, `return-requested`, `return-approved`, `refunded`) |
| `subtotal_amount` | `DECIMAL(12,2)` | NOT NULL | Tổng tiền hàng trước giảm giá |
| `bundle_discount_amount`| `DECIMAL(12,2)` | NOT NULL, DEFAULT 0 | Số tiền giảm giá **15% Combo Routine** (khi đủ $\ge 3$ bước) |
| `shipping_fee` | `DECIMAL(12,2)` | NOT NULL, DEFAULT 0 | Phí vận chuyển theo khu vực |
| `total_amount` | `DECIMAL(12,2)` | NOT NULL | Tổng số tiền thanh toán cuối cùng |
| `return_reason` | `VARCHAR(255)` | NULL | Lý do khách yêu cầu đổi trả (nếu có) |
| `return_tracking_code` | `VARCHAR(100)` | NULL | Mã vận đơn khách gửi trả hàng để kích hoạt Instant Refund |
| `created_at` | `DATETIME` | NOT NULL | Thời điểm đặt hàng |

**6. Bảng `Order_Items` (Chi tiết từng dòng sản phẩm trong Đơn hàng)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `order_item_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã dòng chi tiết đơn hàng |
| `order_id` | `BIGINT UNSIGNED` | **FK1** (`Orders.order_id`) | Mã đơn hàng chứa sản phẩm |
| `product_id` | `BIGINT UNSIGNED` | **FK2** (`Products.product_id`) | Mã sản phẩm được mua |
| `quantity` | `INT UNSIGNED` | NOT NULL | Số lượng đặt mua |
| `unit_price` | `DECIMAL(12,2)` | NOT NULL | Đơn giá tại thời điểm đặt hàng |
| `line_total` | `DECIMAL(12,2)` | NOT NULL | Thành tiền của dòng sản phẩm |

**7. Bảng `Payment_Transactions` (Giao dịch Thanh toán COD / VietQR / VNPay)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `transaction_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã giao dịch nội bộ |
| `order_id` | `BIGINT UNSIGNED` | **FK** (`Orders.order_id`) | Mã đơn hàng liên kết |
| `payment_method` | `ENUM('cod','vietqr','vnpay')`| NOT NULL | Phương thức thanh toán khách chọn |
| `gateway_txn_ref` | `VARCHAR(100)` | NULL | Mã giao dịch tham chiếu trên cổng VNPay (`vnp_TransactionNo`) |
| `amount` | `DECIMAL(12,2)` | NOT NULL | Số tiền giao dịch |
| `signature_verified` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Cờ xác nhận chữ ký số `HMAC-SHA512` hợp lệ |
| `payment_status` | `ENUM('pending','paid','failed')` | NOT NULL | Trạng thái thanh toán |
| `paid_at` | `DATETIME` | NULL | Thời điểm xác nhận thanh toán thành công |

**8. Bảng `Store_Credits` (Mã Hoàn tiền nhanh Store Credit – Nhánh 1 Instant Refund)**
| Tên trường (Field) | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :---: | :--- |
| `credit_id` | `BIGINT UNSIGNED` | **PK**, Auto Increment | Mã định danh bản ghi Store Credit |
| `coupon_code` | `VARCHAR(64)` | UNIQUE, NOT NULL | Mã Store Credit phát hành (`KLR-CREDIT-{ORDER_ID}-{HASH}`) |
| `source_order_id` | `BIGINT UNSIGNED` | **FK** (`Orders.order_id`), UNIQUE | Đơn hàng gốc phát sinh hoàn tiền nhanh |
| `customer_email` | `VARCHAR(100)` | NOT NULL | Email khách hàng được phép sử dụng mã này |
| `credit_amount` | `DECIMAL(12,2)` | NOT NULL | Mệnh giá hoàn tiền (= 100% `total_amount` của đơn gốc) |
| `is_redeemed` | `TINYINT(1)` | NOT NULL, DEFAULT 0 | Trạng thái đã sử dụng (`0`: Chưa dùng, `1`: Đã dùng cho đơn mới) |
| `issued_at` | `DATETIME` | NOT NULL | Thời điểm phát hành ngay khi khách nhập mã vận đơn trả hàng |

---

#### 4.2.2. Tầng 2: Bảng Ánh xạ Vật lý sang WooCommerce HPOS & WordPress Schema
Trong môi trường thực thi MariaDB 10.11 của KLEER, các bảng luận lý 3NF ở trên được ánh xạ trực tiếp vào hệ thống bảng phẳng **WooCommerce HPOS** (`HposService.php`) và hệ thống bảng Taxonomy chuẩn như sau:

| Bảng Luận lý 3NF | Bảng Vật lý trong MariaDB (WooCommerce HPOS / WP) | Cơ chế lưu trữ & Lợi ích hiệu năng |
| :--- | :--- | :--- |
| `Users` | `wp_users` & `wp_usermeta` | Lưu thông tin định danh tài khoản, mật khẩu băm và phân quyền người dùng. |
| `Products` | `wp_posts` (`post_type='product'`) & `wp_wc_product_meta_lookup` | Sử dụng bảng phẳng `wp_wc_product_meta_lookup` để đánh chỉ mục (Index) trực tiếp cho `sku`, `min_price`, `max_price`, `stock_status`, `stock_quantity` giúp lọc nhanh mà không cần join nhiều lần vào `wp_postmeta`. |
| `Taxonomy_Terms` & `Product_Taxonomies` | `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships` | Hệ thống 3 bảng quan hệ chuẩn hóa của WordPress lưu toàn bộ 4 trục thuộc tính (`pa_skin_type`, `pa_skin_concern`, `pa_routine_step`, `pa_active_ingredient`), có đánh Index trên `(object_id, term_taxonomy_id)`. |
| `Orders` & `Payment_Transactions` | **`wp_wc_orders`**, **`wp_wc_order_addresses`**, **`wp_wc_order_operational_data`**, **`wp_wc_orders_meta`** | **Kiến trúc HPOS chuyên biệt:** Tách hoàn toàn đơn hàng ra khỏi `wp_posts`. Bảng `wp_wc_orders` lưu phẳng `id`, `status`, `total_amount`, `customer_id`, `billing_email`, `payment_method`, `transaction_id`; `wp_wc_order_addresses` lưu tách biệt địa chỉ `billing`/`shipping`; `wp_wc_orders_meta` lưu `_kleer_return_reason` và `_kleer_return_tracking_code`. |
| `Order_Items` | `wp_woocommerce_order_items` & `wp_wc_order_product_lookup` | Lưu chi tiết từng dòng sản phẩm và khoản phí giảm giá `Giảm 15% Combo Routine` (`order_item_type = 'fee'`). |
| `Store_Credits` | `wp_posts` (`post_type='shop_coupon'`) & `wp_postmeta` | Lưu mã Store Credit với `discount_type = 'fixed_cart'`, `coupon_amount`, `customer_email` và `usage_limit = 1`, tương thích 100% với bộ tính tiền tại trang Checkout của WooCommerce. |

* **[VỊ TRÍ CHÈN HÌNH 4.2 TRÊN GOOGLE DOCS]:** `![Hình 4.2 - Sơ đồ ERD Luận lý 3NF và Ánh xạ Bảng phẳng WooCommerce HPOS](docs/flows/L09-infra-devops/L09_Database_ERD_3NF_and_HPOS.png)`

---

### 4.3. Thiết kế giao diện, trải nghiệm người dùng (UI/UX Design)
*(Phụ trách tổng hợp: Lê Nguyễn Quốc An – Phối hợp thiết kế Figma cùng Lâm Trúc Uyên & Huỳnh Ngọc Nhã Uyên)*

#### 4.3.1. Hệ thống Quy chuẩn Thiết kế (Design System & Tokens)
* **Phong cách chủ đạo:** *Minimalist Clinical & Earthy Tone* — truyền tải thông điệp khoa học, minh bạch thành phần và thân thiện với mọi đối tượng người dùng.
* **Bảng màu (Color Palette):**
  * Màu nền chính (*Background Primary*): Trắng kem ấm (`#FAF8F5`) giảm mỏi mắt khi đọc bảng thành phần INCI dài.
  * Màu nhấn thương hiệu (*Brand Primary*): Xanh rêu đậm thảo mộc (`#2C4A3E`) tạo cảm giác an toàn, chuẩn da liễu.
  * Màu cảnh báo xung đột hoạt chất (*Warning Banner*): Vàng hổ phách (`#FFF3CD`, viền `#D97706`) thu hút sự chú ý nhưng không gây hoang mang như màu đỏ báo lỗi.
* **Typography & Bố cục:** Sử dụng font chữ không chân hiện đại (*Inter / Plus Jakarta Sans*), thiết kế **Mobile-First Responsive** trên 2 khung chuẩn: **Desktop ($1440\text{px}$)** và **Mobile ($375\text{px}$)**.

#### 4.3.2. Danh mục 6 Màn hình Giao diện Cốt lõi cần chèn vào Google Docs
1. **Màn hình 1 – Trang chủ (Home Page):** Hero Banner dẫn thẳng vào bài *Skin Quiz*, khu vực giới thiệu quy trình 3 bước chọn Routine và danh sách sản phẩm nổi bật theo từng bước dưỡng.
2. **Màn hình 2 – Giao diện SPA Skin Quiz & Kết quả Routine:** Thanh tiến trình động (Progress Bar 1/6 $\rightarrow$ 6/6), thẻ câu hỏi trực quan, và màn hình kết quả hiển thị hồ sơ da kèm nút **"Thêm trọn bộ vào giỏ hàng (Giảm 15%)"**.
3. **Màn hình 3 – Trang Danh mục (Catalog) & Bộ lọc AJAX:** Lưới sản phẩm tỷ lệ ảnh vuông $1:1$ kèm Sidebar bộ lọc đa tiêu chí (*Loại da, Vấn đề da, Bước Routine, Hoạt chất INCI, Giá*).
4. **Màn hình 4 – Trang Chi tiết Sản phẩm (PDP – Tham chiếu Hasaki & INCIDecoder):** Hiển thị rõ Badge phân loại da, vị trí bước trong Routine, và hộp thông tin **Bảng thành phần INCI chi tiết**.
5. **Màn hình 5 – Trang Giỏ hàng Thông minh (Cart Page):** Hiển thị dòng chiết khấu tự động **`Giảm 15% Combo Routine`** và **Thẻ cảnh báo xung đột hoạt chất (AHA/BHA + Retinol)**.
6. **Màn hình 6 – Trang Thanh toán (Checkout), Mã VietQR động & Giao diện Đổi trả Instant Refund:** Form đặt hàng tinh gọn, màn hình quét mã QR Napas động / cổng VNPay Sandbox, và khu vực **Yêu cầu trả hàng / Nhập mã vận đơn nhận ngay mã Store Credit**.

* **[VỊ TRÍ CHÈN CÁC HÌNH WIREFRAME/MOCKUP 4.3 TRÊN GOOGLE DOCS]:** *(Quốc An xuất các Frame từ Figma Design System của nhóm và chèn vào mục 4.3 này).*

---

## 5. DEMO & BÁO CÁO KẾT QUẢ THỰC NGHIỆM (DEMO & REPORTING)
*(Phụ trách: Nguyễn Tấn Phát)*

### 5.1. Chuẩn hóa Kho Dữ liệu Thực tế ($\ge 500\text{ SKU}$ từ Hasaki & INCIDecoder)
Để hệ thống vận hành giống một nền tảng thương mại điện tử thực thụ thay vì dùng dữ liệu giả lập ("Lorem ipsum"), nhóm đã xây dựng pipeline thu thập và chuẩn hóa dữ liệu sản phẩm thật:
1. **Nguồn dữ liệu gốc:** Thu thập thông tin sản phẩm chăm sóc da chính hãng từ **Hasaki.vn** (bao gồm Tên sản phẩm, Thương hiệu, Dung tích, Giá bán niêm yết, Hình ảnh và Bảng thành phần INCI).
2. **Định danh SKU tất định (Deterministic SKU):** Mỗi sản phẩm được sinh mã định danh duy nhất theo chuẩn `KLR-{hash8}` (8 ký tự đầu mã băm SHA-1 của chuỗi chuẩn hóa `thương_hiệu|tên_sản_phẩm|dung_tích`), đảm bảo không trùng lặp khi cập nhật dữ liệu.
3. **Chuẩn hóa 4 trục Taxonomy & Cờ an toàn da liễu (Tham chiếu INCIDecoder):**
   * `pa_skin_type` (*Loại da*): `sensitive`, `dry`, `combination`, `oily`, `normal`.
   * `pa_skin_concern` (*Vấn đề da*): `acne`, `dark_spots`, `aging`, `pores`.
   * `pa_routine_step` (*Bước chu trình*): Mỗi sản phẩm thuộc duy nhất 1 trong 6 bước: `makeup_remover` (Tẩy trang), `cleanser` (Sữa rửa mặt), `toner` (Nước cân bằng), `serum` (Tinh chất đặc trị), `moisturizer` (Kem dưỡng ẩm), `sunscreen` (Kem chống nắng).
   * `pa_active_ingredient` (*Hoạt chất chủ đạo*): Phân tách các nhóm hoạt chất (`aha`, `bha`, `pha`, `retinol`, `tretinoin`, `niacinamide`, `vitamin_c`, `ceramide`, `panthenol`...) phục vụ lọc sản phẩm và quét xung đột giỏ hàng.
   * Gán cờ `_kleer_strong_active = 1` cho các sản phẩm chứa hoạt chất tẩy/đặc trị mạnh cần loại trừ khi khách có làn da nhạy cảm (`is_sensitive = true`), và cờ `_kleer_gentle_fallback = 1` cho các sản phẩm lành tính dự phòng ở mỗi bước.

---

### 5.2. Kịch bản Demo End-to-End 6 Chức năng Cốt lõi
Hệ thống nguyên mẫu (Prototype) được đóng gói và khởi chạy trên môi trường **Docker Compose** tại địa chỉ local. Kịch bản trình diễn (Demo Walkthrough) đi xuyên suốt 6 chức năng:

#### Kịch bản 1: Tư vấn da qua Skin Quiz & Đề xuất Routine cá nhân hóa (Chức năng 1)
* **Thao tác:** Khách hàng truy cập trang **Skin Quiz**, trả lời 6 câu hỏi với hồ sơ giả định: *Da tiết nhiều dầu vùng chữ T nhưng dễ đỏ rát khi đổi thời tiết (Điểm nhạy cảm $\ge 4 \rightarrow$ `is_sensitive = true`), vấn đề ưu tiên là Mụn (`acne`), mong muốn chu trình 4 bước và có dùng kem chống nắng hàng ngày*.
* **Kết quả hệ thống:**
  * API `/wp-json/kleer/v1/skin-quiz` phản hồi trong thời gian $< 150\text{ ms}$.
  * Hệ thống nhận diện đúng hồ sơ **Da nhạy cảm – Mụn**, tự động loại bỏ các sản phẩm có `_kleer_strong_active = 1` (như BHA nồng độ cao hay Retinol mạnh) và trả về phác đồ 4 bước lành tính gồm: *Tẩy trang $\rightarrow$ Sữa rửa mặt dịu nhẹ $\rightarrow$ Tinh chất/Toner giảm mụn lành tính $\rightarrow$ Kem chống nắng*.
* **[HÌNH CHỤP MÀN HÌNH DEMO 5.2.1]:** *(Chèn ảnh màn hình câu hỏi Quiz và màn hình kết quả Routine v4 tại `docs/evidence/nhan/v4-2026-11-03/quiz-v4-result.jpg`)*.

#### Kịch bản 2: Duyệt Catalog, Lọc đa tiêu chí AJAX & Xem bảng thành phần INCI (Chức năng 2)
* **Thao tác:** Khách hàng chuyển sang trang **Cửa hàng (`/shop/`)**, tích chọn lọc kết hợp: *Loại da = Da dầu (`oily`)* + *Bước chu trình = Tinh chất (`serum`)* + *Hoạt chất = BHA / Retinol*.
* **Kết quả hệ thống:** Lưới sản phẩm cập nhật tức thì qua AJAX mà không tải lại trang, URL tự động đồng bộ tham số lọc. Khi bấm vào trang chi tiết sản phẩm, bảng thành phần INCI và nhãn phân loại da hiển thị rõ ràng.
* **[HÌNH CHỤP MÀN HÌNH DEMO 5.2.2]:** *(Chèn ảnh màn hình trang Catalog khi đang bật bộ lọc AJAX và trang Chi tiết sản phẩm PDP)*.

#### Kịch bản 3: 1-Click Add Routine, Tự động giảm 15% Combo & Cảnh báo xung đột hoạt chất (Chức năng 3)
* **Thao tác:**
  1. Từ kết quả Quiz, khách hàng bấm **"Thêm trọn bộ Routine vào giỏ hàng" (1-Click Add)**.
  2. Tiếp theo, khách hàng cố tình thêm lẻ 1 sản phẩm chứa **AHA/BHA** và 1 sản phẩm chứa **Retinol** vào chung giỏ hàng.
* **Kết quả hệ thống:**
  * Giỏ hàng nhận diện có đủ $\ge 3$ bước Routine khác nhau và **tự động trừ ngay khoản phí `Giảm 15% Combo Routine`** trên bảng tính tổng tiền.
  * Ngay phía trên bảng giỏ hàng và trang Checkout xuất hiện **Thẻ cảnh báo xung đột hoạt chất màu cam**: *"Lưu ý da liễu: Giỏ hàng của bạn đang chứa đồng thời nhóm Acid (AHA/BHA) và nhóm Retinoid (Retinol)..."*.
* **[HÌNH CHỤP MÀN HÌNH DEMO 5.2.3]:** *(Chèn ảnh màn hình Giỏ hàng hiển thị dòng Giảm 15% Combo Routine và Banner cảnh báo xung đột hoạt chất)*.

#### Kịch bản 4: Đặt hàng Checkout & Thanh toán qua VietQR / VNPay Sandbox (Chức năng 4)
* **Thao tác:** Khách hàng điền thông tin nhận hàng tại trang Checkout, thử nghiệm đặt hàng với phương thức **VietQR** (sinh mã QR động tại trang Cảm ơn) và phương thức **VNPay Sandbox** (thanh toán bằng thẻ NCB thử nghiệm `9704198526191432198`).
* **Kết quả hệ thống:**
  * Với VietQR: Trang Cảm ơn hiển thị mã QR chuẩn Napas đã nhúng sẵn đúng số tiền sau giảm giá và mã đơn hàng.
  * Với VNPay Sandbox: Sau khi xác thực OTP thành công, cổng VNPay redirect về KLEER; hệ thống xác thực chữ ký `HMAC-SHA512` hợp lệ và tự động chuyển trạng thái đơn hàng sang **`Processing` (Đang xử lý)**.
* **[HÌNH CHỤP MÀN HÌNH DEMO 5.2.4]:** *(Chèn ảnh màn hình cổng thanh toán VNPay Sandbox và trang Cảm ơn có mã VietQR động)*.

#### Kịch bản 5: Quản lý vòng đời đơn hàng HPOS, Đồng bộ tồn kho & Hóa đơn Email QR (Chức năng 5)
* **Thao tác:**
  1. Kiểm tra trong MariaDB (`phpMyAdmin`): Đơn hàng mới nằm hoàn toàn trong 4 bảng phẳng `wp_wc_orders`, `wp_wc_order_addresses`, `wp_wc_order_operational_data`, `wp_wc_orders_meta` (HPOS).
  2. Khi đơn chuyển sang `Processing`, tồn kho sản phẩm tự động giảm (`reduce_order_stock`) và hộp thư khách hàng nhận được **Email hóa đơn HTML đính kèm mã QR tra cứu đơn hàng**.
  3. Admin đóng gói, bàn giao vận chuyển và chuyển trạng thái đơn hàng sang **`Completed` (Đã giao hàng)**.
* **[HÌNH CHỤP MÀN HÌNH DEMO 5.2.5]:** *(Chèn ảnh bảng `wp_wc_orders` trên phpMyAdmin và ảnh Email hóa đơn HTML đính kèm mã QR)*.

#### Kịch bản 6: Đổi trả 7 ngày & Hoàn tiền nhanh Store Credit 3 bước (Chức năng 6)
* **Thao tác (Tham chiếu Shopee Instant Refund):**
  1. *Bước 1:* Khách hàng vào trang Xem đơn hàng `Completed` (trong hạn $\le 7$ ngày), bấm **"Yêu cầu Trả hàng/Hoàn tiền"**, chọn lý do *"Sản phẩm bị rò rỉ khi vận chuyển"* $\rightarrow$ Đơn chuyển sang `Return Requested`.
  2. *Bước 2:* Admin vào trang quản trị đơn hàng HPOS, duyệt yêu cầu hợp lệ $\rightarrow$ Đơn chuyển sang `Return Approved`, mở khóa biểu mẫu nhập mã vận đơn cho khách.
  3. *Bước 3:* Khách hàng nhập mã vận đơn gửi trả hàng (VD: `SPXVN20261111`) lên trang Đơn hàng $\rightarrow$ Hệ thống **tự động chuyển đơn sang `Refunded`**, phát hành ngay mã **Store Credit (`KLR-CREDIT-...`)** trị giá 100% đơn hàng hiển thị ngay trên màn hình và gửi qua Email. Khách hàng dùng mã Store Credit này áp dụng ngay cho đơn hàng mới thành công.
* **[HÌNH CHỤP MÀN HÌNH DEMO 5.2.6]:** *(Chèn ảnh giao diện nhập mã vận đơn trả hàng, thẻ mã Store Credit vừa được cấp tự động và Email thông báo Store Credit)*.

---

### 5.3. Tổng hợp Kết quả Kiểm thử Hệ thống (Testing Summary)
Nhóm đã thực hiện kiểm thử toàn diện trên các chức năng cốt lõi (chi tiết minh chứng lưu tại `docs/flows/*/test-report.md` và `docs/evidence/`):

| Chức năng kiểm thử | Số lượng kịch bản (Test Cases) | Kết quả chính đạt được | Trạng thái |
| :--- | :---: | :--- | :---: |
| **1. Skin Quiz API & Logic v4 (Chức năng 1)** | 24 Unit & Integration Tests | Kiểm tra toàn bộ ma trận quyết định Q1–Q6, quy tắc ưu tiên hòa điểm loại da, lọc hoạt chất mạnh khi `is_sensitive = true` và cơ chế `Safe Fallback`. Thời gian phản hồi trung bình đạt **$< 120\text{ ms}$**. | **100% PASS** |
| **2. Catalog & AJAX Multi-Filter (Chức năng 2)** | 12 Test Cases | Kiểm tra lọc đơn tiêu chí, đa tiêu chí kết hợp, lọc khoảng giá, đồng bộ URL History API và hiển thị bảng thành phần INCI trên PDP. | **100% PASS** |
| **3. Giỏ hàng Routine 15% & Conflict Guard (Chức năng 3)** | 15 Test Cases | Kiểm tra thêm 1-Click Routine, tự động áp dụng giảm giá 15% khi $\ge 3$ bước, tự động hủy giảm giá khi xóa xuống $< 3$ bước, và hiển thị đúng cảnh báo khi có cặp chất *AHA/BHA + Retinol*. | **100% PASS** |
| **4. Checkout Validation & Thanh toán (Chức năng 4)** | 14 Test Cases | Bắt lỗi 100% các trường hợp bỏ trống trường bắt buộc, sai định dạng email/SĐT, từ chối mã giảm giá hết hạn, tạo mã VietQR đúng số tiền và xác thực chữ ký `HMAC-SHA512` của VNPay Sandbox. | **100% PASS** |
| **5. Vòng đời đơn hàng HPOS & Hóa đơn Email QR (Chức năng 5)** | 8 Test Cases | Xác nhận 100% đơn hàng ghi vào bảng phẳng `wp_wc_orders`, trừ/hoàn kho chính xác theo trạng thái và gửi Email hóa đơn QR chống gửi trùng. | **100% PASS** |
| **6. Đổi trả 7 ngày & Hoàn tiền Store Credit (Chức năng 6)** | 7 Test Cases | Kiểm tra chặn đơn quá hạn 7 ngày (`EF-01`), chặn mã vận đơn sai định dạng (`EF-02`), phát hành mã Store Credit 100% giá trị đơn (idempotent) và gửi Email Store Credit tức thì. | **100% PASS** |

---

## 6. KẾT LUẬN VÀ HƯỚNG PHÁT TRIỂN (CONCLUSION & FUTURE WORK)
*(Phụ trách: Nguyễn Tấn Phát)*

### 6.1. Kết quả đạt được
Sau quá trình phân tích, thiết kế và hiện thực hóa, đồ án **Thiết kế Hệ thống Thương mại Điện tử Kinh doanh Sản phẩm Chăm sóc Da – KLEER** đã hoàn thành trọn vẹn các mục tiêu đề ra:
1. **Về mặt phương pháp luận & Mô hình hóa nghiệp vụ:** Xây dựng hệ thống dựa trên **4 nguồn tham chiếu thực tế xác thực** (**Curology**, **INCIDecoder**, **Hasaki**, **Shopee**), mô hình hóa đầy đủ và đồng bộ bằng bộ biểu đồ **UML 2.5** (*Activity, Use Case, Class, Sequence, State Machine*) cho 6 chức năng cốt lõi.
2. **Về mặt Kiến trúc & Cơ sở dữ liệu:** Thiết kế và triển khai thành công kiến trúc **3-Tier trên Docker Compose** kết hợp **Microkernel OOP Plugin** chuẩn PSR-4; chuẩn hóa cơ sở dữ liệu mức luận lý đạt chuẩn **3NF** và ánh xạ vật lý sang kiến trúc bảng phẳng **WooCommerce HPOS** giúp tối ưu tốc độ truy xuất đơn hàng.
3. **Về mặt Giải pháp giá trị sáng tạo cho ngành hàng Skincare:**
   * Giải quyết bài toán *"Khách hàng không biết chọn sản phẩm và thiếu niềm tin vào web mới"* bằng **Skin Quiz tư vấn Routine cá nhân hóa** kèm cơ chế **Safe Fallback**.
   * Giải quyết bài toán *"Nguy cơ kích ứng do phối sai mỹ phẩm"* bằng **Hệ thống cảnh báo xung đột hoạt chất tự động** ngay trong giỏ hàng.
   * Giải quyết bài toán *"Showrooming và rào cản hậu mãi"* bằng **Chiết khấu tự động 15% Combo Routine** và quy trình **Hoàn tiền nhanh Store Credit 3 bước** ngay khi khách hàng cung cấp mã vận đơn gửi trả hàng.

---

### 6.2. Bảng đối chiếu & So sánh KLEER với các mô hình TMĐT hiện có
Để làm rõ tính mới và sự phù hợp của hệ thống trong bối cảnh thị trường Việt Nam, bảng dưới đây đối chiếu trực tiếp **KLEER** với 3 mô hình tiêu biểu mà nhóm đã tham chiếu:

| Tiêu chí so sánh | Sàn TMĐT Tổng hợp (Shopee / Lazada) | Chuỗi Bán lẻ Mỹ phẩm (Hasaki.vn) | Nền tảng D2C Quốc tế (Curology.com) | Hệ thống TMĐT Chuyên biệt **KLEER** |
| :--- | :--- | :--- | :--- | :--- |
| **1. Định hướng mua sắm (Shopping Paradigm)** | *Catalog-First:* Khách hàng phải tự biết tên sản phẩm cần tìm giữa hàng triệu mặt hàng. | *Catalog & Brand-First:* Phân loại tốt theo danh mục và thương hiệu mỹ phẩm, nhưng khách vẫn phải tự ghép các bước dưỡng. | *Consultation-First (Đơn thương hiệu):* Làm Quiz để nhận 1 lọ kem pha chế theo đơn (Custom Formula) của riêng hãng Curology. | **Guided Multi-Brand Routine:** Làm Skin Quiz 60 giây để nhận trọn bộ Routine (2–6 bước) phối hợp từ nhiều sản phẩm chính hãng tại VN. |
| **2. Kiểm soát an toàn xung đột hoạt chất** | **Không có:** Khách mua AHA/BHA và Retinol cùng lúc không có bất kỳ cảnh báo da liễu nào. | **Không có trên giỏ hàng:** Chỉ có thông tin mô tả riêng lẻ ở từng trang sản phẩm. | Kiểm soát bởi bác sĩ/chuyên viên duyệt đơn thủ công cho sản phẩm nội bộ hãng. | **Tự động hóa trên Giỏ hàng:** Quét `pa_active_ingredient` (theo chuẩn INCIDecoder) và cảnh báo tức thì khi phát hiện cặp chất đối kháng (*AHA/BHA + Retinoids*). |
| **3. Cơ chế bán gói & Chống Showrooming** | Mua lẻ từng shop tốn nhiều lần phí vận chuyển; combo thường cố định sẵn theo từng shop. | Khuyến mãi theo từng sản phẩm hoặc quà tặng kèm theo hãng. | Mô hình đăng ký định kỳ (Subscription box) cố định hàng tháng. | **Dynamic Routine Bundling:** Tự động giảm **15%** khi giỏ hàng hội tụ đủ từ $\ge 3$ bước Routine bất kỳ kèm **1-Click Add to Cart**. |
| **4. Cơ chế Đổi trả & Hoàn tiền** | Rất mạnh với cơ chế **Hoàn tiền ngay** vào Ví ShopeePay/Số dư TK khi đơn vị vận chuyển lấy hàng trả. | Đổi trả trong 30 ngày với sản phẩm lỗi/nguyên tem, quy trình kiểm tra tại cửa hàng hoặc kho. | Chỉ áp dụng tại thị trường Mỹ với chính sách dùng thử 30 ngày. | **Học hỏi & Tinh gọn từ Shopee + Hasaki:** Đổi trả 7 ngày, tự động cấp ngay **Store Credit 100%** khi khách nhập mã vận đơn trả hàng để mua ngay đơn mới. |

---

### 6.3. Đề xuất hướng phát triển mở rộng (Future Work)
Trong giai đoạn tiếp theo, hệ thống KLEER có thể được mở rộng theo 4 hướng chiến lược:
1. **Triển khai Nhánh 2 – Hoàn tiền Tiêu chuẩn qua Ngân hàng / Cổng thanh toán (Standard Bank Refund):**
   * Bổ sung nhánh hoàn tiền mặt về tài khoản ngân hàng hoặc gọi trực tiếp API Refund của VNPay đối với khách hàng không có nhu cầu nhận Store Credit. Quy trình này sẽ đi qua bước xác nhận kiểm kho vật lý (`Warehouse QC Passed`) và phê duyệt giải ngân của bộ phận Kế toán như định hướng của Giảng viên.
2. **Tích hợp API Vận chuyển Thời gian thực (Real-time Shipping Webhook):**
   * Kết nối trực tiếp Webhook với các hãng vận chuyển nội địa (Giao Hàng Nhanh – GHN, Giao Hàng Tiết Kiệm – GHTK) để tự động xác thực trạng thái *"Shipper đã lấy hàng trả"* mà không cần khách hàng nhập mã vận đơn thủ công.
3. **Ứng dụng Thị giác Máy tính (AI Skin Analysis) hỗ trợ Skin Quiz:**
   * Kết hợp bài trắc nghiệm 6 câu hỏi hiện tại với mô hình nhận diện tình trạng mụn, lỗ chân lông và sắc tố da qua ảnh chụp camera điện thoại (tương tự bước tải ảnh của Curology) để nâng cao độ chính xác chẩn đoán.
4. **Cơ chế Nhắc lịch Tái đặt hàng Thông minh (Smart Replenishment Notification):**
   * Dựa trên dung tích sản phẩm (`30ml`, `50ml`, `150ml`) và tần suất sử dụng Sáng/Tối trong Routine, hệ thống tự động tính toán thời điểm sản phẩm sắp hết (sau 45–60 ngày) để gửi Email nhắc khách hàng đặt lại kèm ưu đãi thành viên.

---

## 7. TÀI LIỆU THAM KHẢO (REFERENCES)
*(Toàn bộ các nguồn tham khảo dưới đây là nguồn chính thống, xác thực và truy cập được thực tế)*

1. **Curology Inc.**, *Personalized Skincare Consultation & Skin Quiz System*, truy cập tại: https://curology.com/
2. **INCIDecoder**, *Decode Ingredient Lists – Cosmetic Ingredients Science Database*, truy cập tại: https://incidecoder.com/
3. **Hasaki Beauty & Clinic**, *Hệ thống Danh mục Sản phẩm & Chính sách Đổi trả – Hoàn tiền tại Hasaki.vn*, truy cập tại: https://hasaki.vn/ và https://hotro.hasaki.vn/doi-tra-hoan-tien.html
4. **Shopee Việt Nam**, *Trung tâm Trợ giúp Shopee – Quy trình Trả hàng & Hoàn tiền / Hoàn tiền ngay*, truy cập tại: https://help.shopee.vn/
5. **WooCommerce Developer Documentation**, *High-Performance Order Storage (HPOS) Architecture & Custom Order Tables*, truy cập tại: https://developer.woocommerce.com/docs/hpos/ và https://woocommerce.com/document/high-performance-order-storage/
6. **VNPay Developer Portal**, *Tài liệu Kỹ thuật Tích hợp Cổng Thanh toán VNPay (Website Payment Gateway – HMAC-SHA512)*, truy cập tại: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
7. **VietQR (NAPAS)**, *Tiêu chuẩn Kỹ thuật Mã QR Thanh toán Chuyển khoản Ngân hàng VietQR*, truy cập tại: https://vietqr.net/
8. **Kenneth C. Laudon, Carol Guercio Traver**, *E-Commerce: Business. Technology. Society.*, Pearson Education.
9. **Object Management Group (OMG)**, *OMG Unified Modeling Language (OMG UML) Specification, Version 2.5.1*, truy cập tại: https://www.omg.org/spec/UML/

---

## PHỤ LỤC (APPENDIX)

### Phụ lục A: Danh mục REST API & AJAX Endpoints của Hệ thống KLEER

| Phương thức & Endpoint / Action | Module xử lý | Mô tả chức năng |
| :--- | :--- | :--- |
| `GET /wp-json/kleer/v1/health` | `Kleer\Controllers\HealthController` | Kiểm tra trạng thái hoạt động của KLEER Plugin, kết nối CSDL và trạng thái HPOS/Cache. |
| `POST /wp-json/kleer/v1/skin-quiz` | `Kleer\Controllers\SkinQuizController` | Nhận payload câu trả lời Quiz (`v1` / `v4`), chấm điểm loại da và trả về danh sách sản phẩm Routine đề xuất. |
| `POST /wp-admin/admin-ajax.php?action=kleer_filter_products` | `kleer-theme/inc/ajax-catalog-filter.php` | Lọc sản phẩm đa tiêu chí theo 4 trục Taxonomy và khoảng giá không tải lại trang. |
| `POST /wp-admin/admin-ajax.php?action=kleer_add_routine_to_cart` | `Kleer\Services\CartComboService` | Thêm đồng loạt danh sách sản phẩm Routine vào giỏ hàng (1-Click Add) kèm kiểm tra CSRF Nonce. |
| `GET/POST /?wc-api=wc_gateway_kleer_vnpay` | `WC_Gateway_Kleer_VNPay` | Tiếp nhận Return URL và IPN Callback từ cổng thanh toán VNPay Sandbox, đối soát chữ ký số `HMAC-SHA512`. |

### Phụ lục B: Danh sách Kiểm tra Bàn giao File Draw.io của Các Thành viên (Submission Checklist)

| Thành viên phụ trách | Chức năng / Chương | Danh sách sơ đồ Draw.io (`.drawio` + `.png`) cần bàn giao qua Google Docs | Trạng thái |
| :--- | :--- | :--- | :---: |
| **Bùi Hiếu Nhân** | Chương 3 – Chức năng 1 (Skin Quiz & Routine) | • `L01_Activity_Quiz_Routine.drawio`<br>• `L01_Class_Diagram.drawio`<br>• `L01_Sequence_Quiz_Routine.drawio`<br>• `L01_State_Quiz_Session.drawio` | ⏳ Chờ bàn giao |
| **Lâm Trúc Uyên** | Chương 3 – Chức năng 2 (Catalog & AJAX Filter) | • `L02_Activity_Catalog_Filter.drawio`<br>• `L02_Class_Diagram.drawio`<br>• `L02_Sequence_Catalog_Filter.drawio`<br>• `L02_State_Catalog_Filter.drawio` | ⏳ Chờ bàn giao |
| **Huỳnh Ngọc Nhã Uyên** | Chương 3 – Chức năng 3 (Giỏ hàng 15% & Xung đột) | • `L03_Activity_Cart_Combo_Conflict.drawio`<br>• `L03_Class_Diagram.drawio`<br>• `L03_Sequence_Cart_Combo_Conflict.drawio`<br>• `L03_State_Cart_Session.drawio` | ⏳ Chờ bàn giao |
| **Trần Ngọc Minh Tuyền** | Chương 3 – Chức năng 4 (Checkout & Thanh toán) | • `L04_Activity_Checkout_Payment.drawio`<br>• `L04_Class_Diagram.drawio`<br>• `L04_Checkout_VNPay_Sequence.drawio` *(đã có sẵn file gốc)*<br>• `L04_State_Payment_Transaction.drawio` | ⏳ Chờ bàn giao |
| **Nguyễn Minh Trí** | Chương 3 – Chức năng 5 (Vòng đời đơn HPOS & Hóa đơn QR) | • `L05_Activity_HPOS_Fulfillment_Mailer.drawio`<br>• `L05_Class_Diagram.drawio`<br>• `L05_Sequence_HPOS_Invoice_Email.drawio`<br>• `state.drawio` *(đã có sẵn file gốc, rà soát cập nhật)* | ⏳ Chờ bàn giao |
| **Nguyễn Ngọc Thảo Uyên** | Chương 3 – Chức năng 6 (Đổi trả 7 ngày & Instant Refund) | • `L07_Activity_Instant_Store_Credit_Refund.drawio`<br>• `L07_Class_Diagram.drawio`<br>• `L07_Sequence_Instant_Store_Credit_Refund.drawio`<br>• `L07_State_Instant_Refund.drawio` | ⏳ Chờ bàn giao |
| **Toàn nhóm Chương 3** | Mục 3.2 – Use Case Tổng thể | • `L00_Overall_UseCase_Diagram.drawio` *(đã có sẵn file gốc, rà soát cập nhật)* | ⏳ Chờ bàn giao |
| **Lê Nguyễn Quốc An** | Chương 4 – Thiết kế Hệ thống & CSDL | • `L09_System_Architecture_3Tier.drawio`<br>• `L09_Layered_Plugin_Architecture.drawio`<br>• `L09_Database_ERD_3NF_and_HPOS.drawio`<br>• Xuất ảnh các màn hình UI/UX từ Figma | ⏳ Chờ bàn giao |
