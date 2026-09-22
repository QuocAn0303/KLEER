# KLEER - WordPress/WooCommerce Unisex Skincare Store

> **Đồ án môn EC312** | Hạ tầng nền tảng cho website TMĐT mỹ phẩm Unisex Skincare
>
> **Team:** 8 members | **Scrum Master & DevOps Lead:** You
>
> **GitHub:** [https://github.com/QuocAn0303/KLEER](https://github.com/QuocAn0303/KLEER)

## Mục lục

- [Yêu cầu cài đặt](#yêu-cầu-cài-đặt)
- [Bắt đầu nhanh (1-lệnh)](#bắt-đầu-nhanh-1-lệnh)
- [Cấu trúc dự án](#cấu-trúc-dự-án)
- [Dịch vụ (Services)](#dịch-vị-services)
- [Branch Protection](#branch-protection)
- [Tham gia đóng góp](#tham-gia-đóng-góp)

## Yêu cầu cài đặt

Trước khi bắt đầu, đảm bảo bạn đã cài đặt:

| Yêu cầu | Phiên bản tối thiểu | Tải về |
|---------|---------------------|--------|
| **Docker Desktop** | 24.0+ | [docker.com](https://www.docker.com/products/docker-desktop/) |
| **Docker Compose** | 2.0+ (đi kèm Docker Desktop) | - |
| **Git** | 2.30+ | [git-scm.com](https://git-scm.com/) |
| **Node.js** *(nếu theme có build)* | 18+ | [nodejs.org](https://nodejs.org/) |

> **Kiểm tra phiên bản:** Chạy `docker compose version` và `docker version` trong terminal.

## Bắt đầu nhanh (1-lệnh)

### Đối với Windows (Khuyến nghị)

1. **Clone repo:**
   ```powershell
   git clone https://github.com/QuocAn0303/KLEER.git
   cd KLEER
   ```

2. **Chạy setup script:**
   ```powershell
   powershell -ExecutionPolicy Bypass -File setup.ps1
   ```

3. **Mở trình duyệt và truy cập:**
   - WordPress: `http://localhost:80`
   - phpMyAdmin: `http://localhost:8080`

### Đối với mọi nền tảng (Manual)

```bash
git clone https://github.com/QuocAn0303/KLEER.git
cd KLEER
cp .env.example .env
# Sửa file .env với thông tin của bạn
docker compose up -d
```

## Cấu trúc dự án

```
KLEER/
├── .env.example              # Template môi trường (không chứa secret)
├── .gitignore                # File bỏ qua Git
├── docker-compose.yml        # Cấu hình 5 services
├── Dockerfile.php            # Build image PHP với các extension
├── nginx.conf                # Cấu hình Nginx reverse proxy
├── uploads.conf              # Cấu hình upload size cho Nginx
├── php.ini                   # PHP configuration
├── setup.ps1                 # Script thiết lập tự động (Windows)
├── README.md                 # Tài liệu dự án
├── HUONG_DAN_DOCKER.md       # Hướng dẫn Docker chi tiết
├── wp-config.php             # WordPress config (tạo từ setup)
├── wp-content/               # Themes, Plugins, Uploads
│   ├── uploads/              # Ảnh sản phẩm, media
│   ├── themes/               # Giao diện
│   │   └── kleer-theme/      # Theme của KLEER
│   └── plugins/              # Plugin
│       └── kleer-plugin/     # Plugin của KLEER
├── init-scripts/             # SQL init scripts cho MariaDB
├── .gitignore
```

## Dịch vụ (Services)

| Service | Image | Port (Host) | Port (Container) | Mô tả |
|---------|-------|-------------|------------------|-------|
| **nginx** | `nginx:1.25-alpine` | `${NGINX_PORT}` (mặc định: 80) | 80 | Reverse proxy, xử lý static files |
| **php** | `php:8.2-fpm` | - | 9000 | PHP-FPM với WordPress extensions |
| **mariadb** | `mariadb:10.11` | 3307 | 3306 | Database, persist qua Docker volume |
| **redis** | `redis:7-alpine` | - | 6379 | Object Cache cho WordPress |
| **phpmyadmin** | `phpmyadmin/phpmyadmin` | `${PHPMYADMIN_PORT}` (mặc định: 8080) | 80 | Giao diện quản lý Database |

> **Chỉ nginx và phpmyadmin được map port ra host.** Các service còn lại giao tiếp qua internal docker network `kleer-net`.

## Branch Protection

Để đảm bảo chất lượng code, thiết lập Branch Protection Rule trên GitHub:

### Cho branch `main`:

1. Vào GitHub → Repository `QuocAn0303/KLEER` → **Settings** → **Branches** → **Add rule**
2. Ở **Branch name pattern**, nhập `main`
3. Bật các tùy chọn:
   - ✅ **Require a pull request before merging**
   - ✅ **Require approvals** → **Required approving reviews**: `1`
   - ✅ **Require review from Code Owners** *(nếu có)*
   - ✅ **Require conversation resolution before merging**
   - ✅ **Do not allow bypassing the above settings**
   - ✅ **Require status checks to pass before merging**
4. Nhấn **Create**

### Cho branch `develop`:

1. Nhấn **Add rule** thêm
2. Ở **Branch name pattern**, nhập `develop`
3. Cấu hình tương tự branch `main`

### Tóm tắt:
- **Không ai được push trực tiếp** vào `main` hoặc `develop`
- **Phải tạo Pull Request** và có **ít nhất 1 reviewer** đồng ý trước khi merge
- Đảm bảo mọi thay đổi đều được review và kiểm tra

## Tham gia đóng góp

1. Tạo branch mới từ `develop`: `git checkout -b feature/ten-tinh-nang`
2. Commit và push: `git commit -m "feat: mô tả"` → `git push origin feature/ten-tinh-nang`
3. Tạo Pull Request vào `develop`
4. Chờ 1 reviewer approval trước khi merge

---

**Liên hệ:** Nếu gặp vấn đề, liên hệ Scrum Master/DevOps Lead hoặc tạo issue trên GitHub.
