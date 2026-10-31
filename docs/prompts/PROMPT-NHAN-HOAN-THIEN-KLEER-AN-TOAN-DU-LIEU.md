# PROMPT THỰC THI — HOÀN THIỆN CÔNG VIỆC CỦA NHÂN TRONG KLEER

Bạn là coding agent làm việc cùng Bùi Hiếu Nhân, Plugin Dev 1 của dự án KLEER (EC312, WordPress/WooCommerce, PHP 8.2, Docker). Hãy thực hiện thay đổi mã nguồn và tài liệu có thể kiểm chứng, không chỉ đưa kế hoạch. Ưu tiên bảo toàn dữ liệu, hoàn thiện việc cũ và Tuần 04, rồi hoàn thiện phần backend/API liên quan đã được chỉ ra. Thời điểm lập prompt: 18/10/2026 (Tuần 04: 12–18/10; Tuần 05: 19–25/10).

## 0. Chế độ thực thi: BACKUP_FIRST rồi CODE_ONLY

PHẢI hoàn thành và kiểm tra bộ backup ở mục 0A TRƯỚC KHI sửa bất kỳ file gốc nào, tạo/chuyển nhánh, cập nhật Git, chạy test hoặc kích hoạt mã dự án. Trước đó chỉ được đọc, kiểm kê và ghi vào thư mục backup mới. Quyền bổ sung duy nhất với Docker/DB là nhận diện đúng tài nguyên và xuất backup theo mục 0A; không cấp quyền thay đổi dữ liệu hay vận hành lại stack.

Sau khi backup đạt, chỉ đọc/sửa source và tài liệu, lint, chạy test thuần đã kiểm tra dùng mock/fake. Ngoài ngoại lệ backup, không dùng Docker CLI, WP-CLI, không gọi website/API đang chạy, không kết nối database/Redis thật; không chạy setup.ps1, script deploy, reset-admin hoặc sync-to-docker. Không kích hoạt plugin/theme trên WordPress thật. Không tự tạo môi trường DB test trong lượt này.

Không tạo lại .env, wp-config.php hoặc wp-config-sample.php nếu chúng vắng mặt. Không sửa file cấu hình Docker, volume hay kết nối hiện có. Không chạy Git cleanup/reset, không tự pull; giữ checkout và thay đổi local. Không chạy lệnh dọn dẹp lấy từ README/HUONG_DAN_DOCKER/PERFORMANCE hoặc script khác. Việc cần Docker/DB chỉ viết code, dry-run/migration để review và hướng dẫn nghiệm thu sau, đánh dấu CHƯA CHẠY.

Trước khi chạy bất kỳ test/script nghiệp vụ nào, đọc nó và các hook bootstrap liên quan để xác nhận không kết nối dịch vụ thật, không xóa file hoặc sửa user. Công cụ xuất backup là ngoại lệ hẹp theo mục 0A, phải được kiểm tra chỉ đọc nguồn và chỉ ghi đích backup. Test không đáp ứng điều này thì dùng fake hoặc bỏ qua và ghi lý do. Không coi thiếu môi trường là lý do mở rộng quyền.

Mục 0 và 0A giới hạn tất cả các yêu cầu phía dưới. Không hỏi lại quyền tạo bản backup local đã được yêu cầu. Khi thiếu đầu vào cho backup đầy đủ, báo chính xác phần còn thiếu, tiếp tục kiểm kê chỉ đọc nếu hữu ích và chưa sửa source. Sau khi backup đạt, hoàn thiện phần code được phép; các kiểm thử môi trường thật vẫn là giai đoạn nghiệm thu sau.

## 0A. Backup toàn bộ trạng thái dự án trước chỉnh sửa — cổng bắt buộc

### Phạm vi và nơi lưu

- Trên máy hiện tại, nguồn file là TOÀN BỘ thư mục `C:\Users\Lenovo\Documents\STUDY\HK 1 26 - 27\EC312 - Thiết kế hệ thống TMĐT\Project`, bao gồm các prompt ở thư mục cha và checkout `project\KLEER`. Không chỉ sao chép các file sắp sửa.
- Tạo MỘT bộ backup theo thời gian thực hiện, ví dụ `<workspace>/.kleer-backups/KLEER-before-edit-<YYYYMMDD-HHmmss>-<id>/`, với `<workspace>` là thư mục `Project/project` đã xác minh trên máy này. Đích nằm ngoài repo KLEER. Nếu nguồn bao chứa đích, loại trừ TOÀN BỘ cây `.kleer-backups` để tránh tự sao chép đệ quy; ghi rõ đây là loại trừ bản backup, không phải loại dữ liệu dự án.
- Trên máy khác, resolve đúng nguồn/đích tương ứng trước; không đoán ổ đĩa hoặc chép cả ổ đĩa/home. Không ghi đè, xóa hay dọn bản backup cũ. Kiểm tra dung lượng trống và quyền ghi trước. Không đổi đường dẫn gốc để tạo backup.
- Bộ backup phải chứa file ẩn và file Git bỏ qua nếu có: `.git`, `.github`, `.agents`, `.codex` của dự án, `.env*`, `wp-config.php`, Compose override, uploads/media, plugin/theme bên thứ ba, dependencies, tài liệu, script, SQL/backup đã tồn tại và mọi thay đổi chưa commit. Không dùng git archive/clone/patch làm bản sao duy nhất vì chúng bỏ sót dữ liệu ngoài Git.
- Nếu `.git` là file trỏ tới worktree, nhận diện Git dir/common dir thật và ghi rõ phạm vi đã lưu; không giả rằng chép file trỏ đã sao lưu lịch sử Git. Không theo symlink/junction ra ngoài nguồn một cách mù quáng; ghi loại link, đích, khả năng phục hồi và các dữ liệu chưa được bao phủ.
- Không tạo lại file thiếu. File không có trước khi làm phải được ghi `ABSENT_AT_BASELINE`, không bịa nội dung rồi gọi đó là backup nguyên trạng.
- Đây là backup dữ liệu dự án local và dữ liệu persistent liên quan; không bao gồm toàn bộ Windows, RAM/container tạm, hay nội dung Google Drive. Ghi ranh giới này trong README của bộ backup. Giữ nguyên bản Google Drive, không sao chép/xuất thêm dữ liệu tài khoản ngoài phạm vi dự án.

### Thực hiện trước mọi thay đổi

1. Ghi baseline vào thư mục backup: đường dẫn tuyệt đối, thời gian bắt đầu/kết thúc và múi giờ, Git HEAD/branch/status, staged/unstaged/untracked, danh sách file bị ignore. Không đưa secret từ `.env`/remote URL có token ra console hoặc báo cáo công khai. Bản copy `.git` và working tree là nguồn phục hồi chính; patch chỉ là bằng chứng bổ sung.
2. Sao chép file dưới dạng nhị phân, giữ tên/path, timestamp và metadata/permission cần thiết; nếu định dạng archive không giữ đủ metadata, ghi thông tin đó vào manifest. Không dùng thao tác move, mirror/purge, xóa nguồn hoặc chạy cleanup để tiết kiệm chỗ. Công cụ phải nhận file ẩn, đường dẫn dài và tên tiếng Việt. File không đọc được phải thành lỗi rõ ràng, không bỏ qua im lặng.
3. Lập manifest SHA-256, kích thước, đường dẫn tương đối và số lượng file/thư mục; đối chiếu nguồn với bản copy hoặc dữ liệu giải nén của archive. Phát hiện file bị thay đổi trong lúc backup và sao lại/kiểm lại phần đó; không tuyên bố snapshot nguyên tử của nhiều thành phần nếu không thực sự có cơ chế snapshot đồng bộ. Yêu cầu người dùng tránh chỉnh sửa/chạy ứng dụng ghi dữ liệu trong khoảng backup nếu có hoạt động đồng thời.
4. Kiểm tra cấu hình để nhận diện ĐÚNG Docker context, Compose project, container, database và mọi named volume/bind mount liên quan. Chỉ xuất trường metadata cần thiết; không in toàn bộ docker inspect/config chứa environment có mật khẩu. Không chỉ dựa vào tên `kleer-*` hoặc ví dụ `shopdongho` trong tài liệu.
5. DATABASE KHÔNG NẰM TRONG BẢN COPY SOURCE. Với MariaDB đang chạy, được dùng công cụ dump sẵn có để XUẤT ra bộ backup, gồm toàn bộ schema/data của KLEER và các bảng user/usermeta, options, sản phẩm, taxonomy, đơn HPOS/lịch sử đơn; kèm triggers/routines/events nếu tồn tại. Không dump lẫn DB của dự án khác. Giữ credentials trong tiến trình/container hiện có, không ghi mật khẩu vào command hiển thị hay log. Dùng chế độ snapshot phù hợp storage engine (ví dụ single transaction cho InnoDB), không coi nó bảo đảm nhất quán cho bảng nontransactional hay DDL đang chạy. Kiểm tra phiên bản/cờ công cụ thực tế trước.
6. Xuất SQL theo luồng bytes an toàn trên Windows, stderr tách khỏi SQL, kiểm tra exit code/size/hash, bảng dự kiến và footer/marker hoàn tất phù hợp công cụ. Nếu có nhiều bước pipeline, kiểm tra exit code từng bước. SQL trong dump có thể chứa câu lệnh phục hồi: chỉ lưu trữ, TUYỆT ĐỐI không thực thi/import dump vào database đang dùng.
7. Sao lưu dữ liệu persistent ngoài source: uploads/media và các volume plugin/theme hoặc bind mount liên quan đã xác minh. Với volume DB, dùng dump logic nhất quán hoặc công cụ backup engine phù hợp; không chép nóng `/var/lib/mysql` đang ghi rồi tuyên bố backup DB hợp lệ. Với volume persistent khác, dùng phương pháp snapshot/export phù hợp và ghi rõ tính nhất quán, dữ liệu cache/tạm và phần chưa bao phủ.
8. Docker chỉ được dùng cho thao tác đọc metadata/xuất backup, không pull/build/up/down/restart/stop/recreate/prune. Nếu phải dùng container helper để export file volume: chỉ image đã có, source volume phải tồn tại và được inspect trước, mount nguồn READ-ONLY, helper không có mạng và chỉ ghi vào đích backup mới; không chạy entrypoint ứng dụng, không cho phép mount gốc `/`/Docker socket, không tạo volume nguồn do gõ sai tên. Không dùng helper này cho test code hoặc thao tác SQL ghi.
9. Nếu Docker/DB tắt hoặc không truy cập được, KHÔNG tự khởi động/cài lại/reset để backup. Sao lưu file có thể đọc, ghi `PARTIAL — DATABASE/VOLUME NOT BACKED UP`, báo đúng điều kiện cần chủ máy xử lý, và CHƯA SỬA SOURCE. Chỉ bỏ yêu cầu bao phủ DB/volume khi chính người dùng xác nhận rõ phạm vi backup hẹp hơn; hết thời gian chờ không phải xác nhận.
10. Tạo `README-RESTORE.md` trong bộ backup, ghi inventory, mapping nguồn/đích, image/version liên quan, hash/kiểm tra đã làm, điểm chưa bao phủ, và cách phục hồi vào nơi tách biệt. Không tự restore để thử trên DB/source thật. Không ghi `RESTORE_TEST_PASSED` nếu chưa thực hiện; có thể ghi `RESTORE_TEST_NOT_RUN` và giới hạn kiểm tra integrity đã làm.

### Điều kiện được chuyển sang sửa code

- Đã có bản copy toàn bộ file thuộc phạm vi và kiểm tra manifest/hash thành công; không còn file bị bỏ sót không giải thích được hoặc source đang thay đổi chưa xử lý.
- Đã nhận diện đầy đủ persistence của KLEER, xuất DB nhất quán và các dữ liệu persistent liên quan, kiểm tra output thành công. Nếu chỉ xác minh được file thì trạng thái là PARTIAL, không phải FULL_BACKUP.
- Xuất một thông báo ngắn với đường dẫn bộ backup, các thành phần đã bao phủ, số file/size và mức kiểm chứng. Không đưa nội dung SQL, `.env` hoặc danh sách khách hàng ra chat.
- Chỉ khi các điều kiện trên đạt mới tạo/chuyển nhánh, sửa source hoặc chạy test. Việc sửa code diễn ra TRÊN DỰ ÁN GỐC sau khi backup đạt; giữ bộ backup bất biến.
- Không commit/push/upload backup hoặc manifest nhạy cảm. Không tự xóa backup sau khi code/test đạt, không dùng nó làm dữ liệu fixture. Mật khẩu/hash trong DB và file cấu hình chỉ được lưu nguyên trạng trong bản backup local có quyền truy cập hạn chế; quy tắc không lộ secret vẫn áp dụng.

Tài liệu kỹ thuật tham chiếu cho phương pháp backup, không phải lệnh tự thực thi:
- https://mariadb.com/docs/server/clients-and-utilities/backup-restore-and-import-clients/mariadb-dump
- https://docs.docker.com/engine/storage/volumes/

## 1. Phạm vi và quyền thực hiện

- Repo: https://github.com/QuocAn0303/KLEER ; nhánh nền develop.
- Thư mục hiện có trên máy này:
  C:\Users\Lenovo\Documents\STUDY\HK 1 26 - 27\EC312 - Thiết kế hệ thống TMĐT\Project\project\KLEER
- Xác minh đúng repo bằng git rev-parse và git remote trước khi thao tác; trên máy khác tìm checkout tương ứng.
- Đọc AGENTS.md áp dụng nếu có, git status, nhánh hiện tại, các file liên quan trước khi sửa.
- Giữ thay đổi sẵn có của người dùng. Làm trên nhánh feature riêng từ develop đã kiểm chứng, không checkout/reset làm mất nội dung đang sửa. Không tự push, merge hoặc sửa Google Sheets/Drive; bàn giao diff local và bảng cập nhật đề xuất.
- Được sửa kleer-plugin, kết nối Quiz tối thiểu trong kleer-theme, test, tài liệu và công cụ kiểm tra phục vụ phạm vi này. Giữ giao diện và phần việc của thành viên khác.
- Không sửa WordPress Core. Không tự xây lại Catalog, thanh toán, vận chuyển, crawler, HPOS hay giảm giá combo.
- Không nhắn tin thay người dùng, không ký thay Phát/An, không tự ghi đã phê duyệt hoặc Done khi thiếu bằng chứng.
- Không hỏi lại các lựa chọn triển khai thông thường. Nếu thiếu đầu vào nghiệp vụ/Drive, ghi rõ phần chờ quyết định và tiếp tục phần độc lập SAU KHI backup đạt. Thiếu Docker/DB khiến chưa sao lưu đủ persistence thì chỉ được tiếp tục đọc/kiểm kê, chưa sửa file gốc hoặc chạy test.

## 2. Ràng buộc bảo toàn database và tài khoản — bắt buộc

Đã từng xảy ra mất dữ liệu/tài khoản khi làm tác vụ bằng agent. Database MariaDB nằm trong Docker named volume; git pull không khôi phục dữ liệu đó. Trong tác vụ này:

1. Không chạy docker compose down -v, docker volume rm/prune, docker system prune --volumes; không xóa/di chuyển/recreate volume chứa dữ liệu. Không đổi COMPOSE_PROJECT_NAME, tên volume, mount, database name hoặc table prefix để lách lỗi. Không dùng git clean -fdx, git reset --hard hoặc xóa file ngoài phạm vi.
2. Không chạy DROP/TRUNCATE database/table, wp db reset, reinstall WordPress, core install, restore/import SQL vào database hiện có, hay seed ghi đè catalog.
3. Không tạo/xóa/reset tài khoản; không đổi mật khẩu, email, role hoặc quyền sở hữu dữ liệu của user hiện có. Không sửa wp-config.php, .env và các thông tin kết nối đang dùng.
4. KHÔNG tự chạy tools/reset-admin.ps1 hoặc tools/reset-admin.sh. Script hiện có không chỉ đổi mật khẩu: nó xóa MỌI user ngoài admin và reassign bài viết. Không dùng làm bước setup/test định kỳ, không sửa script này trong tác vụ.
5. Không chạy lệnh chứa user delete, xóa bảng users/usermeta, hoặc script cleanup người dùng. Không lấy user admin thật làm fixture.
6. Backup mục 0A phải diễn ra trước chỉnh sửa source, không đợi đến khi có thao tác ghi DB. Đích chuẩn là `<workspace>/.kleer-backups/KLEER-before-edit-<timestamp>-<id>/` ngoài repo; không dùng `tmp/` trong repo làm nơi lưu duy nhất. Backup không cấp quyền ghi DB; các bước kích hoạt/import/DDL/test thật vẫn chưa được phép trong CODE_ONLY.
7. Chỉ được sao chép nguyên trạng file cấu hình/dump nhạy cảm vào bộ backup local bảo vệ theo mục 0A. Không đưa dump, .env, mật khẩu, cookie hoặc dữ liệu khách hàng vào tài liệu/prompt/commit/log chia sẻ. Không mở/đăng dump lên công cụ bên ngoài.
8. Ưu tiên test thuần với fake repository. Test có ghi dữ liệu phải ở database/volume test tách biệt, tên và cổng riêng được kiểm chứng, không cùng container_name/mount với stack hiện có. Không nối test instance vào volume thật; không tự lấy dữ liệu cá nhân từ DB thật để tạo fixture.
9. DB thật chỉ được đọc/xuất cho backup mục 0A; sau backup vẫn không dùng nó để kiểm thử. Viết script apply/migration có dry-run và rollback để review; không áp DDL hoặc gán lại hàng loạt taxonomy trên DB đang dùng.
10. Nếu chưa xác minh được backup đầy đủ hoặc đang trỏ DB nào, báo điều kiện còn thiếu và chỉ tiếp tục kiểm kê chỉ đọc; chưa chỉnh sửa source hoặc chạy test. Không đánh đổi dữ liệu để có kết quả test xanh.

### Hướng dẫn khôi phục của nhóm trưởng nằm ngoài lượt CODE_ONLY

Người dùng từng được hướng dẫn cập nhật Git, khởi động stack và chuẩn hóa tài khoản local. Các bước này là bối cảnh, không phải nhiệm vụ của agent ở lượt này. Không đưa lại thành chuỗi lệnh setup tự động. Script reset-admin sẽ xóa mọi user ngoài admin; không sửa hoặc thực thi nó. Nếu thấy thiếu cấu hình hoặc chưa có WordPress, ghi nhận hiện trạng, không cài lại. Không dùng tài khoản local để cấu hình môi trường công khai.

## 3. Nguồn phải đọc và xử lý mâu thuẫn

Đọc nguồn gốc qua connector nếu có; các bảng phụ lục bên dưới là snapshot đã đọc ngày 18/10/2026, không phải phê duyệt mới.

- Kế hoạch, RACI, Tasks, DoR, Cần chốt, Prompt Log:
  https://docs.google.com/spreadsheets/d/18G57Nh4rFUFvIwn-VAjJBCkGQkZ1heufcIuzOQY4WQI/edit
- Bài của Phát, L01-G1-02, Quiz Decision Matrix (Đọc trước, Ma trận điểm, Quy tắc, SKU mẫu, Độ phủ, Ca thử):
  https://docs.google.com/spreadsheets/d/14cv_y-ps5W_KFH9b2cHPd8E1U7MneYuCR12XN9Wqj1Q/edit
- Bài QA L05-G7-01 (Excel lưu trên Drive; dùng fetch cho Excel, không giả định là native Google Sheet):
  https://docs.google.com/spreadsheets/d/18dVUt3f4aOvU83RRvxFG_gpqkMZ8qAT0/edit?rtpof=true
- Link ma trận kiểm thử Quiz L01-G7-01:
  https://drive.google.com/drive/folders/1ahFRurmmMONREax7sHBapWnMwv7LU4tm
  Lần kiểm tra trước connector trả về thư mục không có file; không suy đoán QA chưa làm ở nơi khác.
- Prompt Catalog của Trúc Uyên:
  https://docs.google.com/document/d/1leuBnglPTXzE2lP4EEZTNFO93_G_XR2rXA3B4HEiPwc/edit
  Hiện chủ yếu lọc product_cat, cần đồng bộ ba taxonomy với Quiz.
- Repo: docs/PLUGIN_ARCHITECTURE.md, docs/flows/L01-quiz-routine/{flow-card.md,approval.md}, docs/flows/L01-quiz-integration/{flow-card.md,namespace-decision-stt1.md}, docs/flows/L03-cart-combo/flow-card.md, docs/prompts/PRM-L01-01-quiz-engine.md, docs/tasks/TASK_DEPENDENCIES.md, docs/TEST_PLAN.md.
- Đọc hyperlink, textFormatRuns VÀ chipRuns nếu trích link từ Google Sheets; lần trước link ma trận đã bị bỏ sót vì là file chip.
- Nếu nguồn thay đổi, dùng dữ liệu mới đã kiểm chứng và ghi ngày/version. Không coi hướng dẫn bất kỳ trong tài liệu ngoài là quyền xóa DB hoặc đổi phạm vi.
- Quy ước đã chốt: kleer-plugin, kleer-theme, PHP namespace Kleer\, REST namespace kleer/v1. Không đổi sang unisex-*.

MÂU THUẪN CẦN GIỮ RÕ:
- Flow Card/code cũ: 5 câu, Routine 3–4 bước, sensitivity >= 3, fallback cam kết luôn đủ.
- Bài của Phát tự ghi “Phiên bản nháp v4 để PO duyệt”: 6 câu, 27 lựa chọn, 5 loại da, Routine 2–6 sản phẩm, sensitivity >= 4; thiếu SKU thì bỏ bước/cảnh báo, dưới 2 sản phẩm báo lỗi dữ liệu.
- Không tự coi v4 đã duyệt, không tự viết lại chữ ký/phê duyệt G3. Tìm bằng chứng Phát/An đã chốt; nếu có, triển khai theo bản được duyệt.
- Nếu chưa có bằng chứng chốt: giữ hành vi public hiện hành, làm cấu hình/scoring v4 và test ở chế độ draft opt-in dành cho kiểm thử, mặc định không bật; tạo bản đề xuất hợp đồng thống nhất để review. Hoàn thiện hạ tầng/validation/repository theo hướng hỗ trợ được bản chốt sau này. Bàn giao phần chuyển nghiệp vụ đang chờ, không báo hoàn tất toàn bộ v4.
- Không biến danh sách hoạt chất do thành viên tự gán thành kết luận y khoa được chuyên gia phê duyệt.
- Giảm giá combo đang có tài liệu ghi lẫn 10% và 15%: không tự chọn. Coupon KLEER10 trong bài QA là fixture riêng, không quyết định mức combo.

## 4. Kết quả rà soát ban đầu — phải kiểm lại trên HEAD mới

Tại develop f87184449ba2861fec24f61e72188689529a4d8d:
- Đã có kiến trúc OOP, plugin bootstrap, activation/deactivation, PSR-4 (PR #7), prompt PRM-L01-01, tài liệu G3, 5 câu PHP, QuizEngineService, SkinQuizController, SkinQuizEndpoints và test.
- Suite plugin từng đạt 187 tests, 0 failures bằng PHP 8.2.12 ở C:/xampp/php/php.exe. Đây là test có mock WordPress, không chứng minh theme/DB/HTTP thật.
- Với 20 sản phẩm hardcode, thử đủ 384 tổ hợp đáp án hợp lệ cũ đều đủ số bước. Không dùng kết quả này chứng minh ma trận v4.
- Chưa thấy code/script đăng ký ba taxonomy. Engine gọi getDefaultCatalog(), chưa lấy sản phẩm WooCommerce thật.
- Danh mục code gán một số toner (ID 9,10,14,15) thành moisturizer, khác SKU mẫu. Số thứ tự CSV không phải product ID WordPress.
- Schema chấp nhận question_id/answer không tồn tại; engine bỏ qua rồi trả combination_acne.
- Case da khô nhạy cảm: tài liệu cũ ghi dry_sensitive, code/test trả sensitive_hydration.
- Fallback với catalog rỗng trả 0, thiếu bước có thể trả 1; chốt cuối còn lấy sản phẩm bất kỳ trong bước.
- Frontend quiz.js dùng setTimeout mô phỏng 2.5s, kết quả cố định, chưa fetch API. Câu hỏi/option khác backend và có lựa chọn 2 bước.
- functions.php của kleer-theme có marker conflict dòng 2,60,73; php -l báo parse error dòng 2. Đây là lỗi chặn nạp theme nếu theme active.
- Bài QA gồm 25 ca: 8 account, 6 cart, 5 coupon, 6 COD; tất cả Not Run, chưa có kết quả thực tế/Bug ID. Không có test thuật toán Quiz.
- Prompt Log chỉ thấy dòng ví dụ; DoR L01 FALSE và Tasks Backlog dù repo có biên bản thông qua.
- Thiếu link bản vẽ gốc được duyệt; không tự dựng lại rồi ghi đó là bài của thành viên.
- Chưa có benchmark API/SQL thật, migration index riêng, OpenAPI hoặc Postman documentation hoàn chỉnh.
- Một số ghi chú QA cũ: php-fpm8.2 đã sửa thành php-fpm, theme không còn chỉ .gitkeep. Cần kiểm tra hiện trạng, không sao chép kết luận cũ.

## 5. Công việc thực hiện theo thứ tự

### A. Gỡ lỗi chặn và giữ nguyên phần việc của nhóm

- Sửa conflict functions.php bằng cách đọc cả hai phía/lịch sử, giữ theme setup, WooCommerce support, menu, CSS/JS chung và enqueue Quiz; không xóa một phía cho hết marker.
- Xử lý namespace PHP và callback sau khi ghép cho hợp lệ; tránh đăng ký/enqueue trùng.
- Lint tất cả PHP custom plugin và theme, quét marker; mở rộng kiểm tra hiện có để lỗi cú pháp theme không lọt qua nữa.
- Chỉ sửa config hạ tầng khi lỗi thực tế tái hiện và cần thiết cho chạy/test phạm vi này; không xóa volume, thay .env hay cài lại để chữa lỗi.

### B. Hoàn thiện việc cũ của Nhân

- TASK-03/Tuần 01: đồng bộ tài liệu kiến trúc với endpoint/layer thật.
- L01-G5-01/Tuần 02: giữ bootstrap đã có, bổ sung minh chứng activation/deactivation nếu có môi trường an toàn; không viết lại plugin.
- TASK-19 -> L08-G5-02/Tuần 03: hoàn thiện cơ chế taxonomy idempotent. Phân biệt global WooCommerce attributes pa_* với custom taxonomy thường; chọn một cách nhất quán với dữ liệu hiện có. Không tạo song song skin_type và pa_skin_type, không tạo attribute trùng.
- Tôn trọng registration order/lifecycle WooCommerce, show_in_rest và UI quản lý phù hợp. Plugin thiếu WooCommerce phải báo rõ, không fatal.
- Viết dry-run/schema-map; áp thực nghiệm chỉ trên test DB riêng. Không tự gán lại taxonomy toàn bộ sản phẩm DB thật.
- L01-G3-01/Tuần 03: ghi nhận repo có biên bản nhưng chứng cứ/link chưa khớp; tạo checklist thiếu gì và ai xác nhận, không sửa thành đã ký/8-8 nếu chưa đủ.

### C. L01-G4-01 và L01-G5-03/Tuần 04

- Đồng bộ prompt PRM-L01-01 với code và phiên bản nghiệp vụ đã xác minh; không sửa file prompt thực thi này để tự cấp thêm quyền.
- Dựng cấu hình câu hỏi, ánh xạ mã đáp án, điểm số, tie-break, cờ nhạy cảm và lựa chọn bước theo bản được duyệt; dùng snapshot v4 ở phụ lục cho draft.
- Ràng buộc đủ câu, duy nhất mỗi câu, đúng option, đúng cardinality/type; reject câu lạ/đáp án lạ/trùng/thiếu với HTTP 400 và lỗi machine-readable. Không âm thầm mặc định combination_acne cho payload sai.
- Tách validate cấu trúc JSON khỏi validate nghiệp vụ; không dùng keyword schema mà validator hiện tại không hỗ trợ.
- Chốt representation skin_profile, version và taxonomy slug qua một mapping rõ ràng; không sửa expected test chỉ để chạy xanh.
- Nếu chưa duyệt đổi nghiệp vụ, ghi rõ hành vi legacy và đề xuất thay đổi thay vì tự chọn dry_sensitive hay sensitive_hydration.

### D. Routine Query Engine, taxonomy và fallback (L01-G5-05/Tuần 06, L01-G5-06/Tuần 08)

- Dùng repository WooCommerce thật qua interface thích hợp cho Quiz; không phá interface findFeatured hiện có. Default runtime dùng sản phẩm thật; hardcode chỉ làm fixture test.
- Lấy ID WooCommerce thật, tên, giá, URL ảnh/sản phẩm, term và tình trạng mua được/còn hàng theo nhu cầu đã chốt. Không lấy STT CSV làm product ID.
- Truy vấn có giới hạn, tránh N+1; cache dữ liệu catalog/reference phù hợp, invalidation khi giá/tồn kho/terms thay đổi. Không cache nhầm session/nonce/consent giữa các khách.
- Chọn bước, lọc loại da, nhạy cảm, ưu tiên concern và tie-break đúng cấu hình. Không gán toner thành moisturizer, không bịa sản phẩm để đủ số bước.
- Nếu v4 được duyệt: ưu tiên sản phẩm theo concern x2 + match loại da riêng x1, tie-break giá thấp nhất; thêm ID làm tie-break ổn định khi bằng cả giá. Loại hoạt chất mạnh khi cờ nhạy cảm bật theo cờ dữ liệu đã được xác nhận.
- V4 có 6 bước, quy tắc Q6 và ngoại lệ; thứ tự chọn theo hạng khác thứ tự hiển thị chăm sóc da, đừng lấy vị trí mảng làm ý nghĩa hạng.
- Phân biệt không có exact match, hết hàng, thiếu hẳn bước và catalog rỗng. Không fallback tới sản phẩm không đạt điều kiện an toàn.
- V4 cho phép thiếu bước có thông báo và có giới hạn dữ liệu tối thiểu; legacy không thể hứa đủ sản phẩm khi dữ liệu không có. Đưa trạng thái thiếu dữ liệu có cấu trúc và error/status đã mô tả; không trả 200 “ok” với routine rỗng rồi coi đã xong.
- Không để thông tin exception/SQL/credentials lộ trong response public; log chẩn đoán có kiểm soát.

### E. REST và kết nối Frontend của Nhã Uyên

- Giữ POST /wp-json/kleer/v1/skin-quiz và alias /recommend đang có; version hóa hợp đồng khi cần, không âm thầm phá client.
- Giữ CORS whitelist/schema và xác thực cookie nonce theo chuẩn WordPress; khách vãng lai dùng Quiz không cần đăng nhập. Không yêu cầu tài khoản admin cho Quiz.
- Thay kết quả giả ở quiz.js bằng fetch API thật, gửi đúng question_id/answer/session/consent đã chốt, hiển thị dữ liệu response thật và loading/error/retry.
- Không hardcode policy_accepted=true. Chỉ gửi đồng ý khi người dùng chủ động chọn; marketing opt-in độc lập.
- Giữ giao diện hiện có, quay lại bước trước, chống submit trùng, xử lý localStorage cũ bằng version và invalidate/migrate phù hợp.
- Draft v4 không tự bật trên public UI khi chưa duyệt; có fixture/test/preview riêng có ghi rõ draft, không làm tăng gấp đôi luồng UI production.
- Nút thêm Routine bàn giao đúng IDs cho interface L03 nếu đã tồn tại; không giả thông báo thêm thành công. Nếu L03 chưa có handler, ghi phụ thuộc và không tự triển khai cả nghiệp vụ Cart/Combo.
- Ghi hợp đồng rõ cho Nhã Uyên/Trúc Uyên/Trí/QA dùng chung.

### F. Hiệu năng và tài liệu API (L05-G5-04/Tuần 09, TASK-75 -> L10-G8-01/Tuần 10)

- Đo baseline truy vấn và thời gian thực trước khi tối ưu; ưu tiên query/cache/term prefetch hợp lý.
- Inspect index đã có bằng read-only. Không thêm index trùng khóa sẵn của WordPress; nếu cần index mới, cung cấp migration idempotent, rollback và lý do từ query plan, chỉ kiểm thử trên DB test tách biệt.
- Cung cấp benchmark reproducible, ghi dataset/stack/cache cold-warm/p50/p95/số truy vấn/phạm vi đo. Mục tiêu sheet 15->4 truy vấn và <300ms phải có số đo thật; không dùng thời gian hàm PHP fixture thay HTTP+DB.
- Không đo/đánh dấu đạt khi server trả 404/500 hoặc chưa có sản phẩm thật.
- Viết OpenAPI hoặc Postman documentation có request/response/lỗi/consent/nonce/CORS/version, ví dụ khách vãng lai và hướng dẫn chạy. Phân biệt Quiz API với ba tác vụ WooCommerce REST của Tuyền.

### G. Hồ sơ bàn giao và đề xuất cập nhật sheet

Tạo một báo cáo trong docs/tasks cho Nhân gồm:
- task cũ/mới, tuần, thay đổi đã làm, file/commit/PR đã có, test thực chạy, kết quả, phần còn chờ.
- Bảng đề xuất cập nhật Tasks/DoR/Prompt Log; chưa ghi vào Google Sheets.
- Dòng Prompt Log thật PRM-L01-01: tác giả Nhân, agent/model/version dùng thực tế nếu biết, nguồn gốc và version nghiệp vụ, kết quả và sửa gì; không bịa model/approval/link PR.
- Đề xuất bổ sung các task bị rơi khỏi Tasks: taxonomy TASK-19/L08-G5-02, API docs TASK-75/L10-G8-01; chỉ rõ mapping cũ đang không nhất quán.
- Tài liệu G3: link bản vẽ và phê duyệt còn thiếu phải ghi “chờ”, không giả chứng cứ.
- Bài QA: 25 ca đã soạn chưa chạy; gợi ý phần test giao điểm Quiz->Cart dựa trên CART-09..14, CHK-20/23/25. Không nhận công soạn/chạy test thay QA.
- Danh sách dữ liệu cần Trí bổ sung: Tẩy trang và Dưỡng ẩm, các nhóm thiếu lựa chọn dự phòng; không tự crawl/import 500 SKU trong tác vụ này.
- Checklist nghiệm thu cho Phát/An/Nhã Uyên/QA; không nhắn tin thay người dùng.

## 6. Kiểm thử phải thực sự phân biệt được đúng/sai

- Chạy suite thuần sẵn có trước/sau nếu đã đọc và xác nhận dùng mock/fake, không kết nối dịch vụ thật hoặc cleanup dữ liệu; ghi số test thật, không xóa assertion để đạt.
- Lint PHP plugin + theme, quét marker, kiểm tra JS bằng công cụ sẵn có.
- Test hợp đồng legacy và draft/approved v4 riêng; 11 case phụ lục là expected nghiệp vụ để đối chiếu, không phải 11 API test đã chạy.
- Test ties, sensitivity 3/4, Q6 với/không ngoại lệ, lựa chọn 2..6, đủ/thiếu câu, câu/option lạ, duplicate, wrong type, payload/consent sai, catalog rỗng/thiếu bước/hết hàng, loại hoạt chất, không chọn trùng.
- Test repository dùng taxonomy đúng và ID đại diện rõ ràng qua fake; không gọi WooCommerce/DB thật. Chuẩn bị integration test nhưng không thực thi trong CODE_ONLY.
- Mô phỏng happy path và request sai ở boundary; HTTP thật, login smoke và benchmark DB đều ghi CHƯA CHẠY. Không dùng credential thật và không reset user.
- Không gọi test có tạo/xóa user/order/coupon trên DB đang dùng. Phần phụ thuộc môi trường đánh dấu chưa chạy, tuyệt đối không báo Pass.
- Sau mỗi nhóm thay đổi, xem diff và status; xác nhận không đổi .env/wp-config/reset-admin, không mất file/bài của nhóm. Nếu đã có kiểm tra read-only DB an toàn, đối chiếu baseline user/sản phẩm/đơn hàng, không in dữ liệu nhạy cảm.
- Chỉ dừng khi đã hoàn thành phần được phép và kiểm tra phù hợp; phần cần PO phê duyệt/DB test/Drive phải có đầu vào cần thiết và bước tiếp theo cụ thể, không “Done” giả.

## 7. Trả lời cuối và sản phẩm bàn giao

Trình bày ngắn:
1. Đã sửa gì cho từng mã task, link file local thật.
2. Tests đã chạy và kết quả; môi trường nào chưa kiểm được.
3. Đường dẫn bộ backup trước chỉnh sửa, thành phần được bao phủ/thiếu, kết quả manifest/hash và trạng thái restore test; tình trạng bảo toàn DB/tài khoản. Không nói “đã backup toàn bộ/đã login” nếu chưa làm hoặc chỉ có bản copy source.
4. Quyết định nghiệp vụ còn chờ ai, ảnh hưởng chính xác.
5. Lệnh vận hành tiếp theo chỉ khi đã kiểm tra an toàn. Không đề nghị xóa volume/cài lại/reset tài khoản.

Tuân thủ BACKUP_FIRST mục 0A rồi CODE_ONLY theo mục 0. Không cam kết đạt API <300ms hoặc giảm truy vấn nếu thiếu số đo. Không coi số lượng test xanh là nghiệm thu toàn bộ website. Trong báo cáo phải tách rõ backup đã kiểm tra, code đã hoàn thiện và nghiệm thu môi trường thật chưa chạy.

---

# PHỤ LỤC — SNAPSHOT BÀI NỘP CỦA PHÁT, CHƯA COI LÀ ĐÃ DUYỆT

## A. Toàn bộ ma trận 6 câu / 27 lựa chọn (bản nháp v4)

Các cột điểm: Nhạy cảm, Khô, Hỗn hợp, Dầu, Thường. Thứ tự này dùng xử lý hòa điểm. Mã 1A..6C là mã trong sheet; mapping sang API phải rõ và được version hóa.

| Câu | Nội dung | Mã | Lựa chọn | Nhạy cảm | Khô | Hỗn hợp | Dầu | Thường | Vấn đề chính | Số SP | Q6 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Q1 | Sau khi rửa mặt, khoảng 30 phút không bôi gì, da bạn cảm thấy thế nào? | 1A | Căng, khô, có thể bong tróc | 1 | 3 | 0 | 0 | 0 |
| Q1 |  | 1B | Thoải mái, không căng cũng không bóng | 0 | 0 | 0 | 0 | 3 |
| Q1 |  | 1C | Bóng dầu khắp mặt | 0 | 0 | 0 | 3 | 0 |
| Q1 |  | 1D | Bóng ở trán–mũi–cằm, hai má bình thường hoặc hơi khô | 0 | 0 | 3 | 1 | 0 |
| Q1 |  | 1E | Châm chích, đỏ hoặc ngứa | 3 | 1 | 0 | 0 | 0 |
| Q2 | Cuối ngày, vùng chữ T (trán, mũi, cằm) và hai má của bạn trông thế nào? | 2A | Cả mặt bóng dầu, lỗ chân lông to rõ | 0 | 0 | 0 | 3 | 0 |
| Q2 |  | 2B | Chữ T bóng, hai má bình thường hoặc khô | 0 | 0 | 3 | 1 | 0 |
| Q2 |  | 2C | Cả mặt mịn, không bóng, không khô | 0 | 0 | 0 | 0 | 3 |
| Q2 |  | 2D | Xỉn, khô ráp, có vảy nhỏ | 0 | 3 | 0 | 0 | 0 |
| Q2 |  | 2E | Hay ửng đỏ, nóng rát dù không làm gì | 3 | 0 | 0 | 0 | 0 |
| Q3 | Khi đổi sản phẩm, đổi thời tiết hoặc thử hoạt chất mới (BHA, vitamin C…), da bạn phản ứng thế nào? | 3A | Rất hay đỏ, ngứa, rát | 4 | 0 | 0 | 0 | 0 |
| Q3 |  | 3B | Thỉnh thoảng hơi đỏ hoặc châm chích nhẹ | 2 | 1 | 0 | 0 | 0 |
| Q3 |  | 3C | Hiếm khi có phản ứng | 0 | 0 | 0 | 0 | 1 |
| Q3 |  | 3D | Gần như không bao giờ có phản ứng | 0 | 0 | 0 | 0 | 2 |
| Q4 | Điều bạn muốn cải thiện nhất ở làn da hiện tại là gì? (chỉ chọn 1) | 4A | Mụn, mụn ẩn, mụn viêm | 0 | 0 | 0 | 0 | 0 | Mụn |
| Q4 |  | 4B | Thâm mụn, nám, da không đều màu | 0 | 0 | 0 | 0 | 0 | Thâm/nám |
| Q4 |  | 4C | Nếp nhăn, da kém đàn hồi | 0 | 0 | 0 | 0 | 0 | Lão hóa |
| Q4 |  | 4D | Lỗ chân lông to, da xỉn, sần sùi | 0 | 0 | 0 | 0 | 0 | Lỗ chân lông |
| Q4 |  | 4E | Không có vấn đề nổi bật, chỉ muốn duy trì da khỏe | 0 | 0 | 0 | 0 | 0 | Không |
| Q5 | Bạn muốn routine gồm bao nhiêu sản phẩm (mỗi sản phẩm một bước)? | 5A | Nhanh gọn (2 sản phẩm) | 0 | 0 | 0 | 0 | 0 |  | 2 |
| Q5 |  | 5B | Cơ bản (3 sản phẩm) | 0 | 0 | 0 | 0 | 0 |  | 3 |
| Q5 |  | 5C | Tiêu chuẩn (4 sản phẩm) | 0 | 0 | 0 | 0 | 0 |  | 4 |
| Q5 |  | 5D | Chi tiết (5 sản phẩm) | 0 | 0 | 0 | 0 | 0 |  | 5 |
| Q5 |  | 5E | Đầy đủ (6 sản phẩm) | 0 | 0 | 0 | 0 | 0 |  | 6 |
| Q6 | Hằng ngày bạn có trang điểm hoặc dùng kem chống nắng không? | 6A | Có, tôi trang điểm hằng ngày hoặc thường xuyên | 0 | 0 | 0 | 0 | 0 |  |  | Có |
| Q6 |  | 6B | Không trang điểm, nhưng có dùng kem chống nắng | 0 | 0 | 0 | 0 | 0 |  |  | Có |
| Q6 |  | 6C | Không trang điểm và không dùng kem chống nắng | 0 | 0 | 0 | 0 | 0 |  |  | Không |

## B. Quy tắc nguyên văn theo ô đã đọc (số đầu dòng là hàng nguồn)

1. Quy tắc tính điểm và chọn Routine
3. Tham số | Giá trị | Ý nghĩa
4. Ngưỡng bật cờ nhạy cảm (điểm Nhạy cảm ≥) | 4 | Đạt ngưỡng thì bật cờ an toàn, dù loại da chính là gì: loại sản phẩm có hoạt chất mạnh. Q3A một mình (4 điểm) đã đủ bật cờ.
5. Trọng số vấn đề chính (khớp skin_concern) | 2 | Điểm chọn sản phẩm = trọng số × [SP có thẻ vấn đề chính] + trọng số loại da × [SP dành riêng loại da, không phải 'Mọi loại da']. Hòa điểm thì chọn giá thấp hơn.
6. Trọng số khớp loại da riêng | 1 | Xem dòng trên.
7. Số sản phẩm tối thiểu | 2 | Routine luôn có ít nhất 2 sản phẩm, mỗi bước 1 sản phẩm.
8. Số sản phẩm tối đa | 6 | Khách chọn 2 đến 6 sản phẩm ở Q5. Số sản phẩm thực tế có thể ít hơn nếu bước không có SKU hoặc Chống nắng/Tẩy trang bị bỏ (Q6 = Không).
9. Hạng của bước được vấn đề chính đẩy lên | 2,5 | Số càng nhỏ càng được giữ trước. 2,5 nghĩa là sau Chống nắng (2) và Tẩy trang (2,1), trước mọi bước còn lại (nếu Chống nắng bị bỏ thì ngay sau Sữa rửa mặt). Bước nào được đẩy xem bảng bên dưới.
10. Hạng của Tẩy trang = hạng Chống nắng + | 0,1 | Tẩy trang luôn đi kèm Chống nắng, nên xếp ngay sau Chống nắng (2 + 0,1 = 2,1).
11. Hạng khi Chống nắng/Tẩy trang bị bỏ | 99 | Hạng từ 90 trở lên nghĩa là loại khỏi routine. Áp dụng khi Q6 = Không và vấn đề chính không nằm trong bảng 'giữ Chống nắng' bên dưới.
12. Chọn loại da chính | Điểm cao nhất | Hòa điểm → ưu tiên theo thứ tự: Nhạy cảm > Khô > Hỗn hợp > Dầu > Thường (ưu tiên an toàn cho da). Khách bắt buộc trả lời đủ 6 câu, không có nút bỏ qua.
14. Hạng nền của từng bước theo loại da (Tẩy trang tính riêng theo Chống nắng)
15. Loại da | Sữa rửa mặt | Toner | Serum | Dưỡng ẩm | Chống nắng
16. Nhạy cảm | 1 | 4 | 3 | 5 | 2
17. Khô | 1 | 4 | 3 | 5 | 2
18. Hỗn hợp | 1 | 4 | 3 | 5 | 2
19. Dầu | 1 | 4 | 3 | 5 | 2
20. Thường | 1 | 4 | 3 | 5 | 2
21. Cách chọn bước: mỗi bước có một 'hạng' (hạng nền, hoặc hạng đặc biệt nếu được vấn đề chính đẩy lên / là Tẩy trang). Routine lấy N bước có hạng nhỏ nhất, N do khách chọn ở Q5. Sữa rửa mặt (1) luôn được giữ. Chống nắng (2) và Tẩy trang (2,1) được giữ khi khách dùng chống nắng/trang điểm (Q6 = Có), hoặc khi vấn đề chính là Thâm/nám hoặc Lão hóa; sau đó là bước được vấn đề chính đẩy lên (2,5), Serum (3), Toner (4), và Dưỡng ẩm xếp cuối cùng (5). Bước không có sản phẩm phù hợp thì bỏ, không thay bằng bước khác.
23. Bước được vấn đề chính đẩy lên
24. Vấn đề chính | Bước được đẩy (cờ nhạy cảm TẮT) | Bước được đẩy (cờ nhạy cảm BẬT) | Giữ Chống nắng + Tẩy trang dù Q6 = Không?
25. Mụn | Toner | Serum | Không
26. Thâm/nám | Serum | Serum | Có
27. Lão hóa | Serum | Serum | Có
28. Lỗ chân lông | Toner | Toner | Không
29. Không | (không) | (không) | Không
30. Mụn: Toner (BHA/AHA) khi da bình thường, nhưng đa số toner trị mụn có hoạt chất mạnh nên khi cờ nhạy cảm bật thì đẩy Serum. Thâm/nám, Lão hóa: Serum. Lỗ chân lông: Toner. Cột cuối: Thâm/nám và Lão hóa vẫn giữ Chống nắng + Tẩy trang dù khách không dùng, vì chống nắng là nền của việc làm mờ thâm và ngừa lão hóa.
32. Hoạt chất mạnh (bị loại khi cờ nhạy cảm bật)
33. Hoạt chất | Ghi chú
34. BHA / Salicylic Acid | Tẩy tế bào chết hóa học
35. AHA | Tẩy tế bào chết hóa học
36. PHA | Dùng trong Toner Some By Mi (#15); Gluconolactone ở #7 là PHA nhẹ nên không bị loại
37. Vitamin C ≥ 5% | #19, #22, #23
39. Quy tắc dự phòng (fallback)
40. 1. Không có SP khớp vấn đề chính → chọn theo loại da (điểm 0 cho vấn đề), giá thấp nhất.
41. 2. Cờ nhạy cảm bật nhưng mọi SP khớp vấn đề đều có hoạt chất mạnh → bỏ qua vấn đề, chọn SP an toàn theo loại da (TC08).
42. 3. Bước không có SP nào → bỏ bước, hiện thông báo; routine ít hơn N sản phẩm là bình thường, nhưng ít hơn 2 thì báo lỗi dữ liệu cho admin.
43. 4. Chọn 4E (không có vấn đề nổi bật) → vấn đề chính = Không; không đẩy bước nào, chỉ chọn theo loại da.
44. 5. Khách không trả lời đủ 6 câu → không cho sang màn kết quả (validate ở frontend và ở REST).

## C. 30 SKU mẫu — STT là mã tham chiếu CSV, không phải ID WordPress

Các cột loại da/concern dùng 0/1. Giá hiển thị theo locale Việt Nam; khi chuyển dữ liệu phải parse rõ. Đây là nhãn do thành viên gán, nguồn tự ghi cần PO duyệt.

| STT | Tên SP | Danh mục gốc | Bước | Nhạy cảm | Khô | Hỗn hợp | Dầu | Thường | Mụn | Thâm/nám | Lão hóa | Lỗ chân lông | Hoạt chất mạnh | Giá VNĐ | Da gốc |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Sữa Rửa Mặt CeraVe Foaming Cleanser | Cleanser | Sữa rửa mặt | 0 | 0 | 0 | 1 | 0 | 1 | 0 | 0 | 0 | Không | 380.000 | Dầu, Mụn |
| 2 | Sữa Rửa Mặt CeraVe Hydrating Cleanser | Cleanser | Sữa rửa mặt | 1 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 380.000 | Khô, Nhạy cảm |
| 3 | Sữa Rửa Mặt Cetaphil Gentle Skin Cleanser | Cleanser | Sữa rửa mặt | 1 | 1 | 1 | 1 | 1 | 0 | 0 | 0 | 0 | Không | 330.000 | Mọi loại da |
| 4 | Gel Rửa Mặt La Roche-Posay Effaclar | Cleanser | Sữa rửa mặt | 0 | 0 | 0 | 1 | 0 | 1 | 0 | 0 | 0 | Không | 450.000 | Dầu, Mụn |
| 5 | Sữa Rửa Mặt Cosrx Low pH Good Morning | Cleanser | Sữa rửa mặt | 1 | 1 | 1 | 1 | 1 | 0 | 0 | 0 | 1 | Có | 250.000 | Mọi loại da |
| 6 | Sữa Rửa Mặt Simple Refreshing Facial Wash | Cleanser | Sữa rửa mặt | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 150.000 | Nhạy cảm |
| 7 | Gel Rửa Mặt SVR Sebiaclear Moussant | Cleanser | Sữa rửa mặt | 0 | 0 | 0 | 1 | 0 | 1 | 0 | 0 | 0 | Không | 400.000 | Dầu, Mụn |
| 8 | Sữa Rửa Mặt Innisfree Green Tea | Cleanser | Sữa rửa mặt | 0 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 220.000 | Hỗn hợp |
| 9 | Nước Hoa Hồng Klairs Supple Preparation | Toner | Toner | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 300.000 | Nhạy cảm |
| 10 | Toner Thayers Witch Hazel Aloe Vera | Toner | Toner | 1 | 1 | 1 | 1 | 1 | 0 | 0 | 0 | 1 | Không | 250.000 | Mọi loại da |
| 11 | Dung Dịch Tẩy Da Chết Paula's Choice 2% BHA | Toner | Toner | 0 | 0 | 0 | 1 | 0 | 1 | 0 | 0 | 1 | Có | 850.000 | Dầu, Mụn |
| 12 | Nước Cân Bằng COSRX AHA/BHA | Toner | Toner | 0 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 1 | Có | 320.000 | Hỗn hợp |
| 13 | Nước Hoa Hồng Mamonde Rose Water | Toner | Toner | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 280.000 | Khô |
| 14 | Toner Hada Labo Gokujyun Hydrating | Toner | Toner | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 200.000 | Khô |
| 15 | Toner Some By Mi AHA-BHA-PHA 30 Days | Toner | Toner | 0 | 0 | 0 | 1 | 0 | 1 | 0 | 0 | 0 | Có | 350.000 | Dầu, Mụn |
| 16 | Tinh Chất The Ordinary Niacinamide 10% | Serum | Serum | 0 | 0 | 0 | 1 | 0 | 1 | 1 | 0 | 0 | Không | 250.000 | Dầu, Mụn |
| 17 | Tinh Chất La Roche-Posay Hyalu B5 | Serum | Serum | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 850.000 | Nhạy cảm |
| 18 | Dưỡng Chất Vichy Mineral 89 | Serum | Serum | 1 | 1 | 1 | 1 | 1 | 0 | 0 | 0 | 0 | Không | 650.000 | Mọi loại da |
| 19 | Tinh Chất Klairs Freshly Juiced Vitamin Drop | Serum | Serum | 1 | 1 | 1 | 1 | 1 | 0 | 1 | 0 | 0 | Có | 350.000 | Mọi loại da |
| 20 | Tinh Chất L'Oreal Revitalift 1.5% HA | Serum | Serum | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 1 | 0 | Không | 450.000 | Khô, Lão hóa |
| 21 | Tinh Chất Skin1004 Madagascar Centella | Serum | Serum | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 400.000 | Nhạy cảm |
| 22 | Tinh Chất Kiehl's Clearly Corrective | Serum | Serum | 1 | 1 | 1 | 1 | 1 | 0 | 1 | 0 | 0 | Có | 1.500.000 | Mọi loại da |
| 23 | Tinh Chất balance Active Formula Vitamin C | Serum | Serum | 0 | 1 | 1 | 0 | 0 | 0 | 1 | 0 | 0 | Có | 180.000 | Khô, Hỗn hợp |
| 24 | Kem Chống Nắng La Roche-Posay Anthelios | Sunscreen | Chống nắng | 0 | 0 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | Không | 450.000 | Dầu, Mụn |
| 25 | Sữa Chống Nắng Anessa Perfect UV | Sunscreen | Chống nắng | 1 | 1 | 1 | 1 | 1 | 0 | 0 | 0 | 0 | Không | 550.000 | Mọi loại da |
| 26 | Tinh Chất Chống Nắng Skin Aqua Tone Up | Sunscreen | Chống nắng | 0 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 180.000 | Hỗn hợp |
| 27 | Kem Chống Nắng Cell Fusion C Laser | Sunscreen | Chống nắng | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 600.000 | Nhạy cảm |
| 28 | Gel Chống Nắng Eucerin Sun Dry Touch | Sunscreen | Chống nắng | 0 | 0 | 0 | 1 | 0 | 1 | 0 | 0 | 0 | Không | 500.000 | Dầu, Mụn |
| 29 | Kem Chống Nắng Vichy Capital Soleil | Sunscreen | Chống nắng | 0 | 0 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 480.000 | Hỗn hợp |
| 30 | Kem Chống Nắng Skin1004 Centella Air-Fit | Sunscreen | Chống nắng | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | Không | 350.000 | Nhạy cảm |

## D. 11 ca thử và kết quả kỳ vọng từ workbook

Dấu “+” trong danh sách bước biểu thị tập bước theo thứ tự trình bày của sheet, không thay cho thứ tự sử dụng đã được PO chốt. Workbook đang so công thức với kỳ vọng nhập tay: 11/11 ĐẠT, chưa kiểm chứng code/API.

| Mã | Mô tả | Đáp án Q1..Q6 | Da kỳ vọng | Cờ nhạy cảm | Vấn đề | Các bước kỳ vọng | Sản phẩm tham chiếu / thiếu dữ liệu |
| --- | --- | --- | --- | --- | --- | --- | --- |
| TC01 | Da dầu, muốn trị mụn, có dùng chống nắng, 4 sản phẩm (Mụn đẩy Toner lên) | 1C, 2A, 3D, 4A, 5C, 6B | Dầu | Không | Mụn | Tẩy trang + Sữa rửa mặt + Toner + Chống nắng | Tẩy trang: (thiếu SP)<br>Sữa rửa mặt: #1 Sữa Rửa Mặt CeraVe Foaming Cleanser (380.000đ)<br>Toner: #15 Toner Some By Mi AHA-BHA-PHA 30 Days (350.000đ)<br>Chống nắng: #28 Gel Chống Nắng Eucerin Sun Dry Touch (500.000đ) |
| TC02 | Ngoại lệ: da khô lo lão hóa, KHÔNG dùng chống nắng nhưng vẫn giữ Chống nắng + Tẩy trang vì vấn đề là Lão hóa | 1A, 2D, 3C, 4C, 5B, 6C | Khô | Không | Lão hóa | Tẩy trang + Sữa rửa mặt + Chống nắng | Tẩy trang: (thiếu SP)<br>Sữa rửa mặt: #2 Sữa Rửa Mặt CeraVe Hydrating Cleanser (380.000đ)<br>Chống nắng: #25 Sữa Chống Nắng Anessa Perfect UV (550.000đ) |
| TC03 | Da nhạy cảm, không vấn đề riêng, không dùng chống nắng, 3 sản phẩm → bỏ Chống nắng và Tẩy trang | 1E, 2E, 3A, 4E, 5B, 6C | Nhạy cảm | Có | Không | Sữa rửa mặt + Toner + Serum | Sữa rửa mặt: #6 Sữa Rửa Mặt Simple Refreshing Facial Wash (150.000đ)<br>Toner: #9 Nước Hoa Hồng Klairs Supple Preparation (300.000đ)<br>Serum: #21 Tinh Chất Skin1004 Madagascar Centella (400.000đ) |
| TC04 | Da hỗn hợp, thâm mụn, có trang điểm, 5 sản phẩm (Thâm/nám đẩy Serum lên) | 1D, 2B, 3C, 4B, 5D, 6A | Hỗn hợp | Không | Thâm/nám | Tẩy trang + Sữa rửa mặt + Toner + Serum + Chống nắng | Tẩy trang: (thiếu SP)<br>Sữa rửa mặt: #8 Sữa Rửa Mặt Innisfree Green Tea (220.000đ)<br>Toner: #12 Nước Cân Bằng COSRX AHA/BHA (320.000đ)<br>Serum: #23 Tinh Chất balance Active Formula Vitamin C (180.000đ)<br>Chống nắng: #26 Tinh Chất Chống Nắng Skin Aqua Tone Up (180.000đ) |
| TC05 | Da thường, duy trì, không dùng chống nắng, 4 sản phẩm (Dưỡng ẩm thiếu SP) | 1B, 2C, 3D, 4E, 5C, 6C | Thường | Không | Không | Sữa rửa mặt + Toner + Serum + Dưỡng ẩm | Sữa rửa mặt: #5 Sữa Rửa Mặt Cosrx Low pH Good Morning (250.000đ)<br>Toner: #10 Toner Thayers Witch Hazel Aloe Vera (250.000đ)<br>Serum: #19 Tinh Chất Klairs Freshly Juiced Vitamin Drop (350.000đ)<br>Dưỡng ẩm: (thiếu SP) |
| TC06 | Ngoại lệ: da dầu nhưng rất hay kích ứng, trị mụn (cờ nhạy cảm bật → loại hoạt chất mạnh, đẩy Serum thay Toner) | 1C, 2A, 3A, 4A, 5D, 6B | Dầu | Có | Mụn | Tẩy trang + Sữa rửa mặt + Toner + Serum + Chống nắng | Tẩy trang: (thiếu SP)<br>Sữa rửa mặt: #1 Sữa Rửa Mặt CeraVe Foaming Cleanser (380.000đ)<br>Toner: #10 Toner Thayers Witch Hazel Aloe Vera (250.000đ)<br>Serum: #16 Tinh Chất The Ordinary Niacinamide 10% (250.000đ)<br>Chống nắng: #28 Gel Chống Nắng Eucerin Sun Dry Touch (500.000đ) |
| TC07 | Ngoại lệ: hòa điểm Dầu = Khô → ưu tiên Khô; Lỗ chân lông đẩy Toner; không dùng chống nắng | 1C, 2D, 3C, 4D, 5B, 6C | Khô | Không | Lỗ chân lông | Sữa rửa mặt + Toner + Serum | Sữa rửa mặt: #5 Sữa Rửa Mặt Cosrx Low pH Good Morning (250.000đ)<br>Toner: #10 Toner Thayers Witch Hazel Aloe Vera (250.000đ)<br>Serum: #23 Tinh Chất balance Active Formula Vitamin C (180.000đ) |
| TC08 | Ngoại lệ: da nhạy cảm + thâm nám, 6 sản phẩm, không có serum thâm an toàn, thiếu kem dưỡng | 1E, 2E, 3B, 4B, 5E, 6C | Nhạy cảm | Có | Thâm/nám | Tẩy trang + Sữa rửa mặt + Toner + Serum + Dưỡng ẩm + Chống nắng | Tẩy trang: (thiếu SP)<br>Sữa rửa mặt: #6 Sữa Rửa Mặt Simple Refreshing Facial Wash (150.000đ)<br>Toner: #9 Nước Hoa Hồng Klairs Supple Preparation (300.000đ)<br>Serum: #21 Tinh Chất Skin1004 Madagascar Centella (400.000đ)<br>Dưỡng ẩm: (thiếu SP)<br>Chống nắng: #30 Kem Chống Nắng Skin1004 Centella Air-Fit (350.000đ) |
| TC09 | Da dầu, trị mụn, có trang điểm, 6 sản phẩm (đủ cả 6 bước) | 1C, 2A, 3D, 4A, 5E, 6A | Dầu | Không | Mụn | Tẩy trang + Sữa rửa mặt + Toner + Serum + Dưỡng ẩm + Chống nắng | Tẩy trang: (thiếu SP)<br>Sữa rửa mặt: #1 Sữa Rửa Mặt CeraVe Foaming Cleanser (380.000đ)<br>Toner: #15 Toner Some By Mi AHA-BHA-PHA 30 Days (350.000đ)<br>Serum: #16 Tinh Chất The Ordinary Niacinamide 10% (250.000đ)<br>Dưỡng ẩm: (thiếu SP)<br>Chống nắng: #28 Gel Chống Nắng Eucerin Sun Dry Touch (500.000đ) |
| TC10 | Ngoại lệ: không dùng chống nắng, trị mụn, chọn 6 sản phẩm nhưng chỉ có 4 (không có Chống nắng, Tẩy trang) | 1C, 2A, 3D, 4A, 5E, 6C | Dầu | Không | Mụn | Sữa rửa mặt + Toner + Serum + Dưỡng ẩm | Sữa rửa mặt: #1 Sữa Rửa Mặt CeraVe Foaming Cleanser (380.000đ)<br>Toner: #15 Toner Some By Mi AHA-BHA-PHA 30 Days (350.000đ)<br>Serum: #16 Tinh Chất The Ordinary Niacinamide 10% (250.000đ)<br>Dưỡng ẩm: (thiếu SP) |
| TC11 | Da thường, không dùng chống nắng, chọn 2 sản phẩm (mức tối thiểu) | 1B, 2C, 3D, 4E, 5A, 6C | Thường | Không | Không | Sữa rửa mặt + Serum | Sữa rửa mặt: #5 Sữa Rửa Mặt Cosrx Low pH Good Morning (250.000đ)<br>Serum: #19 Tinh Chất Klairs Freshly Juiced Vitamin Drop (350.000đ) |

Nguồn phụ lục: https://docs.google.com/spreadsheets/d/14cv_y-ps5W_KFH9b2cHPd8E1U7MneYuCR12XN9Wqj1Q/edit ; snapshot đọc 18/10/2026. Khi dùng nguồn mới, kiểm tra version/approval và ghi lại khác biệt, không tự coi snapshot là quyết định chính thức.
