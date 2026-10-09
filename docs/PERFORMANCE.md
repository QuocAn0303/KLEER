# Tối ưu tốc độ shop

## Vấn đề

Trước khi tối ưu, mỗi trang mất **~36 giây** để tải. Số đo cho thấy:

| Chỉ số | Giá trị |
|---|---|
| Bootstrap WordPress | 36.6s |
| Trong đó truy vấn DB | **0.05s** |
| `SHORTINIT` (chỉ lõi, không plugin/theme) | 0.61s |

99.9% thời gian nằm ở đọc file, không phải cơ sở dữ liệu.

## Nguyên nhân

Thư mục `html` được bind mount từ ổ đĩa Windows. Mỗi lần WordPress kiểm tra sự tồn tại của file trên bind mount đều chậm hơn rất nhiều so với filesystem nằm trong container:

| Thư mục | Số file | ms/file (bind mount) | ms/file (container) |
|---|---|---|---|
| `plugins/woocommerce` | 6.194 | **7,42** | ~0,001 |
| `themes/flatsome` | 1.888 | **6,71** | ~0,001 |
| `wp-includes` | 2.729 | 3,36 | ~0,001 |

WordPress phải kiểm tra hàng nghìn file mỗi request (nạp plugin, theme patterns, autoload). Mỗi lần kiểm tra mất vài mili giây → tích lại thành hàng chục giây.

## Cách sửa

Đưa các thư mục **không sửa** vào Docker named volume (đọc nhanh):

| Thư mục | Vị trí sau khi sửa |
|---|---|
| `wp-content/uploads` | named volume |
| `wp-content/plugins/woocommerce` | named volume |
| `wp-content/themes/flatsome` | named volume |

**Giữ nguyên trên bind mount:** toàn bộ mã nguồn còn lại, gồm `wp-content/plugins/kleer-payments` — nên bạn vẫn chỉ sửa plugin trên Windows bình thường.

Docker tự sắp xếp các mount theo độ sâu đường dẫn, nên mount thư mục con luôn được áp dụng đúng cho phần con đó.

## Kết quả

| Giai đoạn | Bootstrap | HTTP thực tế |
|---|---|---|
| Ban đầu | 36.6s | 28–38s |
| Chuyển uploads sang volume | 38.6s | — |
| Chuyển woocommerce + flatsome | **7.6s** | **5.0s** |

Nhanh hơn **4,8 lần**. Phần 7.6s còn lại đến từ `wp-includes` vẫn nằm trên bind mount — có thể đưa tiếp vào volume nếu muốn nhanh hơn nữa.

## Sửa code trong plugin

`kleer-payments` nằm trên bind mount nên sửa trực tiếp trên Windows là có hiệu lực. Nếu sửa file trong repo `D:\KLEER`, đồng bộ vào container:

```bash
node tools/sync-to-docker.js
```

Script sẽ xoá bản cũ trong container rồi chép toàn bộ thư mục mới qua. Sau khi chạy, **tải lại trang** (nhấn Ctrl+F5) để thấy thay đổi.

## Cảnh báo khi cập nhật WooCommerce hoặc Flatsome

Hai thư mục này giờ nằm trong volume. Khi cập nhật qua quản trị, thay đổi sẽ **không** ghi về ổ đĩa Windows.

Để đồng bộ ngược về host (ví dụ để sao lưu hoặc commit):

```bash
# Xuat woocommerce hien tai ra host
docker run --rm `
  -v shopdongho_shopdongho_woocommerce:/src:ro `
  -v "D:\uit\hk3 2026-2027\Thiết kế hệ thống TMDT\shopdongho\html\wp-content\plugins\woocommerce:/dst" `
  alpine sh -c "rm -rf /dst/* && cp -a /src/. /dst/"

# Xuat flatsome hien tai ra host
docker run --rm `
  -v shopdongho_shopdongho_flatsome:/src:ro `
  -v "D:\uit\hk3 2026-2027\Thiết kế hệ thống TMDT\shopdongho\html\wp-content\themes\flatsome:/dst" `
  alpine sh -c "rm -rf /dst/* && cp -a /src/. /dst/"
```

Media trong `uploads` cũng nằm trong volume theo cùng cơ chế.

## Sao lưu

Lần chuyển đổi đầu tiên đã tạo bản sao lưu media tại:

```
shopdongho/uploads-backup-<ngay-gio>/
```

Sau khi shop đã chạy ổn định vài ngày, có thể xoá thư mục này.

## Lệnh chẩn đoán

Nếu cần đo lại:

```bash
# Bootstrap + các truy vấn chậm nhất
docker exec shopdongho-wordpress-1 php /var/www/html/_prof.php full

# Chỉ lõi WordPress, bỏ qua plugin và theme
docker exec shopdongho-wordpress-1 php /var/www/html/_prof.php shortinit

# Tốc độ đọc file trên từng thư mục
docker exec shopdongho-wordpress-1 php /var/www/html/_bench.php

# Cấu hình PHP: OPcache, realpath cache, memory limit
docker exec shopdongho-wordpress-1 php /var/www/html/_prof.php ini
```

Ba file `_prof.php`, `_bench.php` là script chẩn đoán tạm thời, xoá khi không dùng nữa.

## Hướng tối ưu tiếp (chưa áp dụng)

Nếu 7.6s vẫn chậm, hai hướng còn lại:

1. **Đưa `wp-includes` vào volume** — phần lớn thời gian tòn lại. Nhưng khi đó mã nguồn lõi cũng không sửa được trên Windows (thường cũng không cần).
2. **Tăng `realpath_cache_size`** — hiện chỉ 4MB, nên cache không đủ chứa hết file. Tăng lên 64MB và tắt `validate_timestamps` sẽ giảm đáng kể số lần phải kiểm tra filesystem.