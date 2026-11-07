\# PROMPT PRM-L03-01: 1-CLICK ROUTINE TO CART



\- \*\*Mã Prompt:\*\* `PRM-L03-01`

\- \*\*Mã task liên quan:\*\* `L03-G4-01`

\- \*\*Luồng:\*\* `L03` (Giỏ hàng \& Combo Routine)

\- \*\*Tác giả:\*\* Huỳnh Ngọc Nhã Uyên (Theme Dev 2 - Quiz Frontend, Cart/Checkout UX)

\- \*\*Người duyệt:\*\* Nguyễn Tấn Phát

\- \*\*Ngày lập:\*\* 25/10/2026

\- \*\*Dependency:\*\* `L03-G3-01` — DoR đạt 8/8, trạng thái \*\*SẴN SÀNG PROMPT\*\*

\- \*\*Đầu vào:\*\* Flow Card L03, Activity Diagram L03 và Biên bản phê duyệt G3 `L03-G3-01`



\---



\# 1. Bối cảnh \& Yêu cầu kỹ thuật



Dự án KLEER là website TMĐT mỹ phẩm Unisex trên WordPress/WooCommerce, chạy Docker với nginx, PHP 8.2-fpm, MariaDB 10.11 và Redis.



Theme: `wp-content/themes/kleer-theme/`



Plugin: `wp-content/plugins/kleer-plugin/`



Không sửa WordPress Core hoặc WooCommerce Core. Không sử dụng Elementor/WPBakery.



Chuẩn code:

\- PHP theo PSR-12.

\- JavaScript ES6+.

\- Tôn trọng kiến trúc hiện tại của `kleer-plugin`: Controllers, Services, Endpoints, Repositories và đăng ký module/hook thông qua `Kleer\\Plugin::register()`.



Theo Flow Card L03 và biên bản G3 đã phê duyệt, tính năng cần triển khai trong phạm vi prompt này là \*\*1-Click Routine to Cart\*\*.



Tại màn hình kết quả Quiz L01, khách hàng nhấn \*\*“Thêm trọn bộ Routine vào giỏ hàng”\*\*. Frontend gửi AJAX request chứa danh sách `product\_id` của 3–4 sản phẩm Routine. Backend thêm các sản phẩm hợp lệ vào WooCommerce Cart, cập nhật mini-cart fragments mà không reload trang và sau khi thành công chuyển khách hàng tới `/cart/`.



Phạm vi prompt này \*\*chỉ triển khai 1-Click Routine to Cart\*\*. Không triển khai Combo Routine Discount hoặc Ingredient Conflict Warning trong task này.



\# 2. Nhiệm vụ cụ thể



1\. \*\*Khảo sát và tích hợp kiến trúc hiện tại\*\*

&#x20;  - Đọc implementation hiện tại của `kleer-plugin` và phần Quiz Result trong `kleer-theme` trước khi sửa.

&#x20;  - Tái sử dụng kiến trúc hiện có, không tạo logic trùng lặp.

&#x20;  - Không sửa WordPress Core hoặc WooCommerce Core.

&#x20;  - Business logic không được đặt trực tiếp trong `Plugin.php`; `Plugin.php` chỉ làm nhiệm vụ đăng ký/orchestrate module.



2\. \*\*AJAX 1-Click Routine to Cart\*\*

&#x20;  - Xây dựng module AJAX cho phép thêm đồng thời 3–4 sản phẩm của Routine vào WooCommerce Cart trong một thao tác người dùng.

&#x20;  - Tích hợp với cơ chế WooCommerce AJAX add-to-cart đã được G3 chốt, bao gồm hook `woocommerce\_ajax\_add\_to\_cart`.

&#x20;  - Hỗ trợ khách đã đăng nhập và guest theo cơ chế session/cart chuẩn của WooCommerce.

&#x20;  - Backend nhận danh sách `product\_id` từ Routine do Quiz Result cung cấp.

&#x20;  - Không hard-code product ID.



3\. \*\*Validation\*\*

&#x20;  - Sanitize và validate toàn bộ dữ liệu nhận từ client.

&#x20;  - Kiểm tra danh sách `product\_id` tồn tại và đúng định dạng.

&#x20;  - Xác minh từng sản phẩm tồn tại và có thể mua trước khi thêm.

&#x20;  - Không tin tưởng trực tiếp dữ liệu gửi từ frontend.

&#x20;  - Không thao tác trực tiếp database để sửa WooCommerce Cart.



4\. \*\*Nonce / CSRF Protection\*\*

&#x20;  - Tạo WordPress nonce dành riêng cho thao tác thêm Routine vào cart.

&#x20;  - Frontend phải gửi nonce trong AJAX request.

&#x20;  - Backend phải verify nonce trước khi thay đổi cart.

&#x20;  - Nonce thiếu, hết hạn hoặc không hợp lệ phải trả lỗi và không thực hiện thao tác thêm sản phẩm.



5\. \*\*WooCommerce Cart \& Session\*\*

&#x20;  - Sử dụng API/cart session chuẩn của WooCommerce.

&#x20;  - Không tạo cơ chế cart/session riêng cạnh tranh với WooCommerce.

&#x20;  - Đảm bảo việc thêm nhiều sản phẩm trong cùng thao tác không gây xung đột WooCommerce session.

&#x20;  - Có cơ chế ngăn request lặp ngoài ý muốn do người dùng click nút nhiều lần trong khi request trước đang xử lý.



6\. \*\*Mini-cart Fragments\*\*

&#x20;  - Sau khi thêm sản phẩm thành công, tạo/trả về mini-cart fragments theo cơ chế chuẩn của WooCommerce.

&#x20;  - Frontend sử dụng fragments trả về để cập nhật mini-cart mà không reload toàn bộ trang.

&#x20;  - Không tự dựng dữ liệu mini-cart giả khi WooCommerce đã cung cấp fragments chuẩn.



7\. \*\*Frontend Integration\*\*

&#x20;  - Tích hợp với nút \*\*“Thêm trọn bộ Routine vào giỏ hàng”\*\* tại màn hình kết quả Quiz L01.

&#x20;  - Lấy `product\_id` từ dữ liệu Routine thực tế mà Quiz trả về.

&#x20;  - Gửi AJAX bằng JavaScript ES6+.

&#x20;  - Hiển thị trạng thái loading và disable nút trong lúc request đang xử lý.

&#x20;  - Khi thành công:

&#x20;    1. cập nhật mini-cart fragments;

&#x20;    2. xác nhận thao tác thành công;

&#x20;    3. redirect khách hàng đến `/cart/`.

&#x20;  - Không reload trang trước khi AJAX hoàn tất.

&#x20;  - Không hiển thị trạng thái thành công giả khi backend trả lỗi.



8\. \*\*Error Handling\*\*

&#x20;  - Xử lý tối thiểu:

&#x20;    - nonce/CSRF không hợp lệ;

&#x20;    - payload thiếu hoặc sai định dạng;

&#x20;    - danh sách sản phẩm rỗng;

&#x20;    - product ID không tồn tại;

&#x20;    - sản phẩm không purchasable;

&#x20;    - WooCommerce/cart session không khả dụng;

&#x20;    - lỗi trong quá trình thêm sản phẩm;

&#x20;    - AJAX/network error.

&#x20;  - Response lỗi phải có mã lỗi và message rõ ràng để frontend xử lý.

&#x20;  - Không expose stack trace, filesystem path, secret hoặc dữ liệu nhạy cảm cho client.



9\. \*\*Plugin Bootstrap\*\*

&#x20;  - Đăng ký module/hook mới thông qua kiến trúc hiện tại của `kleer-plugin`.

&#x20;  - Tích hợp registration vào `Kleer\\Plugin::register()`.

&#x20;  - Không đưa business logic trực tiếp vào lifecycle orchestrator.



10\. \*\*Tests\*\*

&#x20;  - Bổ sung test cho phần logic có thể kiểm thử độc lập.

&#x20;  - Kiểm tra tối thiểu:

&#x20;    - thêm thành công nhiều sản phẩm;

&#x20;    - nonce hợp lệ;

&#x20;    - nonce thiếu/không hợp lệ;

&#x20;    - payload/product ID không hợp lệ;

&#x20;    - sản phẩm không tồn tại hoặc không purchasable;

&#x20;    - mini-cart fragments có trong response thành công;

&#x20;    - lỗi không trả trạng thái thành công giả.

&#x20;  - Ưu tiên mock/fake cho WordPress/WooCommerce dependencies; không sửa dữ liệu WooCommerce thật chỉ để chạy test.



\# 3. Tiêu chí chấp nhận (Given-When-Then)



\- \*\*Happy Path:\*\* Given khách hàng đang ở màn hình kết quả Quiz và Routine có 3–4 `product\_id` hợp lệ, When khách nhấn \*\*“Thêm trọn bộ Routine vào giỏ hàng”\*\*, Then frontend gửi AJAX request và toàn bộ sản phẩm hợp lệ được thêm vào WooCommerce Cart mà không reload trang trong quá trình xử lý.



\- \*\*WooCommerce AJAX Case:\*\* Given module 1-Click Routine to Cart được đăng ký, When yêu cầu thêm Routine được xử lý, Then implementation sử dụng/tích hợp cơ chế `woocommerce\_ajax\_add\_to\_cart` đã được G3 chốt và không sửa WooCommerce Core.



\- \*\*CSRF Case:\*\* Given AJAX request thiếu hoặc có nonce không hợp lệ, When backend nhận request, Then request bị từ chối và WooCommerce Cart không bị thay đổi.



\- \*\*Mini-cart Case:\*\* Given Routine được thêm thành công, When backend trả response, Then response chứa mini-cart fragments của WooCommerce và frontend có thể cập nhật mini-cart mà không reload toàn bộ trang.



\- \*\*Redirect Case:\*\* Given frontend đã nhận response thành công và xử lý fragments, When quy trình hoàn tất, Then khách hàng được chuyển tới `/cart/`.



\- \*\*Validation Case:\*\* Given request chứa product ID không hợp lệ, không tồn tại hoặc sản phẩm không thể mua, When backend validate request, Then hệ thống trả lỗi có cấu trúc và không báo thành công giả.



\- \*\*Guest Case:\*\* Given người dùng chưa đăng nhập, When họ thực hiện 1-Click Routine to Cart, Then WooCommerce guest cart/session vẫn được sử dụng đúng và Routine có thể được thêm mà không yêu cầu đăng nhập chỉ vì thao tác cart.



\- \*\*Duplicate Request Case:\*\* Given AJAX request đang được xử lý, When người dùng tiếp tục nhấn nút thêm Routine, Then frontend ngăn thao tác gửi request lặp ngoài ý muốn.



\- \*\*Network Error Case:\*\* Given kết nối bị gián đoạn trong lúc gửi AJAX request, When frontend không nhận được response hợp lệ, Then hiển thị thông báo lỗi thân thiện, không báo thêm giỏ thành công và cho phép người dùng thử lại.



\# 4. Ràng buộc phạm vi



\- Chỉ triển khai \*\*1-Click Routine to Cart\*\* trong prompt này.

\- Không triển khai Dynamic Routine Bundle Discount.

\- Không triển khai Ingredient Conflict Warning.

\- Không sửa WordPress Core hoặc WooCommerce Core.

\- Không hard-code product ID của Routine.

\- Không tự tạo cart/session thay thế WooCommerce.

\- Không thay đổi nghiệp vụ Quiz ngoài phần tích hợp cần thiết để lấy danh sách sản phẩm Routine.

\- Nếu phát hiện mâu thuẫn giữa code hiện tại với Flow Card, Activity Diagram hoặc biên bản G3, không tự thay đổi nghiệp vụ; báo rõ mâu thuẫn trước khi triển khai phần bị ảnh hưởng.

