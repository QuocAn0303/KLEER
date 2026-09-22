# HƯỚNG DẪN DOCKER - KLEER Project

> Tài liệu này giúp mọi thành viên (kể cả không rành Docker) tự chạy được dự án.

---

## 1. Yêu cầu cài đặt trước

### 1.1 Docker Desktop

Tải và cài đặt từ: [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/)

**Kiểm tra cài đặt thành công:**
```powershell
docker --version
docker compose version
```

**Đảm bảo Docker Desktop đang chạy:**
- Windows: Kiểm tra icon Docker ở hệ thống tray (góc phải thanh taskbar). Icon phải màu xanh.

### 1.2 Git

Tải từ: [https://git-scm.com/download/win](https://git-scm.com/download/win)

```powershell
git --version
```

### 1.3 Phiên bản tối thiểu

| Thành phần | Tối thiểu |
|------------|-----------|
| Docker Engine | 24.0 |
| Docker Compose | 2.0 (v2) |
| RAM | 4GB trở lên |
| Disk | 5GB trống |

---

## 2. Các bước từ clone đến khi thấy trang WordPress chạy

### Bước 1: Clone repository

```powershell
git clone https://github.com/QuocAn0303/kleer.git
cd kleer
```

### Bước 2: Chạy script thiết lập tự động

```powershell
powershell -ExecutionPolicy Bypass -File setup.ps1
```

Script sẽ tự động:
- ✅ Copy `.env.example` → `.env`
- ✅ Tạo các thư mục cần thiết (`uploads`, `themes`, `plugins`, `init-scripts`)
- ✅ Tải WordPress nếu chưa có
- ✅ Khởi chạy tất cả 5 container bằng `docker compose up -d`
- ✅ Đợi MariaDB sẵn sàng
- ✅ In ra URL truy cập

### Bước 3: Sửa file `.env` (nếu cần)

Mở file `.env` bằng VS Code hoặc Notepad:

```powershell
code .env
```

Các thông tin cần chỉnh sửa:
- `NGINX_PORT` - Port truy cập web (mặc định 80, có thể đổi thành 8080 nếu bị chiếm)
- `PHPMYADMIN_PORT` - Port phpMyAdmin (mặc định 8080, đổi nếu bị chiếm)
- `MYSQL_ROOT_PASSWORD` - Mật khẩu root DB
- `MYSQL_USER` / `MYSQL_PASSWORD` - Tài khoản database

### Bước 4: Mở trình duyệt

```
WordPress:  http://localhost:80
phpMyAdmin: http://localhost:8080
```

### Bước 5: Hoàn tất cài đặt WordPress

1. Truy cập `http://localhost:80`
2. Chọn ngôn ngữ → **Continue**
3. Điền thông tin:
   - **Site Title:** KLEER Store
   - **Username:** admin
   - **Password:** *(tự đặt)*
   - **Your Email:** *(email của bạn)*
4. Nhấn **Install WordPress**
5. Đăng nhập bằng tài khoản vừa tạo

---

## 3. Xử lý lỗi thường gặp

### 3.1 Cổng bị chiếm (Port Conflict)

**Triệu chứng:** `docker compose up -d` báo lỗi `port already in use` hoặc truy cập không được.

**Giải pháp:** Đổi port trong file `.env`:

```bash
# Nếu port 80 bị chiếm:
NGINX_PORT=8081

# Nếu port 8080 bị chiếm:
PHPMYADMIN_PORT=8081
```

Sau đó:
```powershell
docker compose down
docker compose up -d
```

**Kiểm tra port đang dùng:**
```powershell
netstat -ano | findstr :80
```

### 3.2 Quyền thư mục (Permission denied)

**Triệu chứng:** WordPress không ghi được file, hoặc lỗi `Unable to locate WordPress package`.

**Giải pháp:** Đảm bảo thư mục `wp-content/uploads` tồn tại và có quyền ghi:
```powershell
mkdir wp-content\uploads -Force
chmod -R 777 wp-content
```

### 3.3 MariaDB chưa sẵn sàng khi PHP khởi động

**Triệu chứng:** WordPress báo lỗi kết nối database `Error establishing a database connection`.

**Giải pháp:** Đợi MariaDB khởi động xong trước:
```powershell
# Xem trạng thái container:
docker compose ps

# Đợi cho đến khi mariadb hiển thị "healthy":
docker inspect --format='{{.State.Health.Status}}' kleer-mariadb
```

Hoặc restart lại toàn bộ:
```powershell
docker compose down
docker compose up -d
```

### 3.4 Container bị lỗi khi chạy

**Triệu chứng:** Container liên tục restart hoặc hiển thị trạng thái `Restarting`.

**Giải pháp:** Xem log để biết lỗi:
```powershell
docker compose logs php
docker compose logs mariadb
docker compose logs nginx
```

### 3.5 Không thể truy cập phpMyAdmin

**Triệu chứng:** Error 502 Bad Gateway hoặc Connection refused.

**Giải pháp:**
- Kiểm tra MariaDB đang chạy: `docker compose ps`
- Đảm bảo `MYSQL_ROOT_PASSWORD` trong `.env` đúng với giá trị trong MariaDB
- Restart phpmyadmin: `docker compose restart phpmyadmin`

### 3.6 Ảnh upload thất bại

**Triệu chứng:** Upload ảnh sản phẩm bị lỗi hoặc timeout.

**Giải pháp:**
- Kiểm tra `client_max_body_size` trong `nginx.conf` và `uploads.conf`
- Đảm bảo `php.ini` có `upload_max_filesize = 100M`

---

## 4. Lệnh thường dùng

### Cơ bản

```powershell
# Khởi động tất cả dịch vụ (nhất là sau khi clone)
docker compose up -d

# Tắt tất cả dịch vụ
docker compose down

# Tắt và xóa luôn cả data volume (⚠️ XÓA HẾT DỮ LIỆU DB)
docker compose down -v
```

### Xem log

```powershell
# Xem log tất cả container
docker compose logs -f

# Xem log của 1 service cụ thể
docker compose logs -f php
docker compose logs -f mariadb
docker compose logs -f nginx
```

### Restart 1 service

```powershell
# Restart PHP
docker compose restart php

# Restart Nginx (sau khi sửa nginx.conf)
docker compose restart nginx

# Restart MariaDB (cẩn thận - có thể mất kết nối tạm thời)
docker compose restart mariadb
```

### Vào shell container

```powershell
# Vào container PHP
docker compose exec php sh

# Vào container MariaDB
docker compose exec mariadb sh

# Vào container Nginx
docker compose exec nginx sh
```

### Quản lý container

```powershell
# Xem trạng thái tất cả service
docker compose ps

# Xem tất cả image đang dùng
docker images

# Xóa image không dùng đến
docker image prune

# Xem dung lượng Docker đang dùng
docker system df

# Rebuild PHP image (sau khi sửa Dockerfile.php)
docker compose build --no-cache php
docker compose up -d php
```

### WordPress CLI (nếu cài wp-cli)

```powershell
# Vào container PHP, dùng wp cli
docker compose exec php wp plugin list
docker compose exec php wp theme list
docker compose exec php wp user list
```

---

## 5. Cấu hình bổ sung (Tuần sau)

### 5.1 Cài WordPress CLI

```powershell
docker compose exec php curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
docker compose exec php chmod +x wp-cli.phar
docker compose exec php mv wp-cli.phar /usr/local/bin/wp
```

### 5.2 Import database từ file `.sql`

```powershell
# Copy file .sql vào init-scripts
cp backup.sql init-scripts/

# Restart MariaDB để import tự động
docker compose restart mariadb
```

### 5.3 Theme development

```powershell
# Theme ở thư mục: wp-content/themes/kleer-theme/
# Sau khi sửa file theme, Nginx sẽ tự động reload (nhưng PHP-FPM cache có thể cần restart)
docker compose restart php
```

---

## 6. Mẹo & Best Practices

1. **Luôn sửa `.env.example`** thay vì `.env` khi đổi cấu hình chung cho team
2. **Không commit `.env`** vào Git - chỉ commit `.env.example`
3. **Sau khi sửa cấu hình**, luôn `docker compose down` rồi `docker compose up -d` để áp dụng
4. **Sửa `nginx.conf`** → `docker compose restart nginx` (không cần restart toàn bộ)
5. **Luôn kiểm tra `docker compose ps`** trước khi bắt đầu làm việc để đảm bảo tất cả service đang chạy
6. **Backup database** thường xuyên: `docker compose exec mariadb mysqldump -u root -p kleer_db > backup_$(date +%Y%m%d).sql`

---

## 7. Liên hệ hỗ trợ

Nếu gặp lỗi không giải quyết được:
- Tạo **Issue** trên GitHub: `https://github.com/QuocAn0303/kleer/issues`
- Liên hệ **Scrum Master / DevOps Lead**
- Tham gia channel **#devops** trên Slack/Discord của team
