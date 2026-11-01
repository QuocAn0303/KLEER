"""
Script tái xử lý và cắt thông minh (Smart Crop) cho toàn bộ ảnh sản phẩm Hasaki 500 SKUs:
- Nhận diện ảnh banner ngang (tỉ lệ w/h > 1.3 như og-image-auto 1200x623 hoặc 1024x531):
  Cắt vuông phía BÊN TRÁI (0, 0, h, h) để giữ trọn vẹn chai/hộp sản phẩm, loại bỏ cột giá và quà tặng bị cắt ngang.
- Ảnh vuông hoặc gần vuông: Cắt giữa (center crop) chuẩn xác.
- Resize chất lượng cao Lanczos về max 600x600.
- Nén WebP chuẩn chất lượng cao <= 120 KB theo Luồng L08.
- Đồng bộ lưu vào 2 thư mục: data/images/products/ và wp-content/uploads/products/.
"""

import os
import io
import sys
import pandas as pd
import requests
from PIL import Image
from concurrent.futures import ThreadPoolExecutor, as_completed

# Hỗ trợ in tiếng Việt trên console Windows
sys.stdout.reconfigure(encoding='utf-8')

BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSV_PATH = os.path.join(BASE_DIR, "data", "woocommerce_500_skus.csv")
DATA_IMG_DIR = os.path.join(BASE_DIR, "data", "images", "products")
WP_IMG_DIR = os.path.join(BASE_DIR, "wp-content", "uploads", "products")

os.makedirs(DATA_IMG_DIR, exist_ok=True)
os.makedirs(WP_IMG_DIR, exist_ok=True)

session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
})

def process_product_image(row):
    sku = str(row['SKU']).strip()
    img_url = str(row['Image Source URL']).strip()
    filename = f"{sku.lower()}.webp"
    target_data = os.path.join(DATA_IMG_DIR, filename)
    target_wp = os.path.join(WP_IMG_DIR, filename)
    
    if not img_url or not img_url.startswith("http"):
        return sku, "SKIP_NO_URL", 0
        
    try:
        res = session.get(img_url, timeout=12)
        if res.status_code != 200 or len(res.content) < 500:
            return sku, f"FAIL_HTTP_{res.status_code}", 0
            
        img = Image.open(io.BytesIO(res.content))
        w, h = img.size
        aspect_ratio = w / h
        
        crop_type = "square"
        if aspect_ratio > 1.3:
            # Banner ngang rộng: Sản phẩm nằm gọn ở khối vuông bên trái (0, 0, h, h)
            img_cropped = img.crop((0, 0, h, h))
            crop_type = "left_banner"
        elif aspect_ratio < 0.77:
            # Banner dọc: Cắt giữa theo chiều cao
            top = (h - w) // 2
            img_cropped = img.crop((0, top, w, top + w))
            crop_type = "tall_center"
        else:
            # Ảnh vuông hoặc gần vuông: Cắt giữa cân đối
            min_dim = min(w, h)
            left = (w - min_dim) // 2
            top = (h - min_dim) // 2
            img_cropped = img.crop((left, top, left + min_dim, top + min_dim))
            crop_type = "center_crop"
            
        # Resize về kích thước chuẩn 600x600 nếu lớn hơn
        if max(img_cropped.size) > 600:
            img_cropped = img_cropped.resize((600, 600), Image.Resampling.LANCZOS)
            
        # Xử lý transparency (RGBA / P) chuyển về nền trắng
        if img_cropped.mode in ("RGBA", "P"):
            bg = Image.new("RGB", img_cropped.size, (255, 255, 255))
            if img_cropped.mode == "RGBA":
                bg.paste(img_cropped, mask=img_cropped.split()[3])
            else:
                bg.paste(img_cropped)
            img_rgb = bg
        else:
            img_rgb = img_cropped.convert("RGB")
            
        # Lưu file WebP tối ưu <= 120 KB
        quality = 85
        final_bytes = None
        while quality >= 30:
            buf = io.BytesIO()
            img_rgb.save(buf, format="WEBP", quality=quality)
            if len(buf.getvalue()) <= 120 * 1024:
                final_bytes = buf.getvalue()
                break
            quality -= 5
            
        if final_bytes is None:
            buf = io.BytesIO()
            img_rgb.save(buf, format="WEBP", quality=30)
            final_bytes = buf.getvalue()
            
        with open(target_data, "wb") as f:
            f.write(final_bytes)
        with open(target_wp, "wb") as f:
            f.write(final_bytes)
            
        return sku, crop_type, len(final_bytes)
    except Exception as e:
        return sku, f"ERR_{str(e)}", 0

def main():
    if not os.path.exists(CSV_PATH):
        print(f"Không tìm thấy file: {CSV_PATH}")
        return
        
    df = pd.read_csv(CSV_PATH)
    total = len(df)
    print(f"Bắt đầu cắt thông minh và tối ưu {total} ảnh từ {CSV_PATH}...")
    
    rows = [row for _, row in df.iterrows()]
    results = {}
    stats = {"left_banner": 0, "center_crop": 0, "square": 0, "tall_center": 0, "errors": 0}
    
    with ThreadPoolExecutor(max_workers=16) as executor:
        futures = {executor.submit(process_product_image, r): r['SKU'] for r in rows}
        done = 0
        for future in as_completed(futures):
            sku, status, size_bytes = future.result()
            done += 1
            if status in stats:
                stats[status] += 1
            else:
                stats["errors"] += 1
            results[sku] = (status, size_bytes)
            if done % 50 == 0 or done == total:
                print(f"Tiến độ: {done}/{total} SKU hoàn thành...")
                
    print("\n--- KẾT QUẢ XỬ LÝ ẢNH ---")
    print(f"- Banner ngang cắt cạnh trái (left crop): {stats.get('left_banner', 0)} ảnh")
    print(f"- Ảnh chuẩn cắt giữa (center crop): {stats.get('center_crop', 0)} ảnh")
    print(f"- Banner dọc cắt giữa (tall center): {stats.get('tall_center', 0)} ảnh")
    print(f"- Lỗi: {stats.get('errors', 0)} ảnh")
    
    # Kiểm tra kích thước file và dung lượng
    all_files = [os.path.join(DATA_IMG_DIR, f) for f in os.listdir(DATA_IMG_DIR) if f.endswith(".webp")]
    valid_count = sum(1 for f in all_files if 3500 <= os.path.getsize(f) <= 120 * 1024)
    print(f"\nTổng số ảnh WebP hợp lệ trong data/images/products: {valid_count}/{len(all_files)}")
    print(f"Dung lượng trung bình: {sum(os.path.getsize(f) for f in all_files) / (len(all_files) * 1024):.1f} KB")

if __name__ == "__main__":
    main()
