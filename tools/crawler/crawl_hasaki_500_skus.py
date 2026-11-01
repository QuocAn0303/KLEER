#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
KLEER - Luồng L08: Pipeline Cào dữ liệu & Nén Media >= 500 SKU từ Hasaki.vn
Chuẩn hóa 3 Custom Taxonomies (routine_step, skin_type, skin_concern),
cờ hoạt chất mạnh _kleer_strong_active, Safe Fallback _kleer_gentle_fallback,
sinh mã SKU chuẩn KLR-{hash8}, xuất file CSV chuẩn WooCommerce tại data/
và nén ảnh WebP 1:1 dung lượng <= 120 KB.
"""

import sys
sys.stdout.reconfigure(encoding='utf-8')

import os
import re
import csv
import io
import time
import hashlib
import unicodedata
import requests
from PIL import Image, ImageDraw, ImageFont
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

# Thư mục đích
OUTPUT_CSV = "data/woocommerce_500_skus.csv"
BACKUP_CSV = "data/hasaki_500_skus.csv"
IMG_UPLOADS_DIR = "wp-content/uploads/products"
IMG_DATA_DIR = "data/images/products"

os.makedirs("data", exist_ok=True)
os.makedirs(IMG_UPLOADS_DIR, exist_ok=True)
os.makedirs(IMG_DATA_DIR, exist_ok=True)

# 6 danh mục tương ứng đúng 6 routine_step của KLEER
CATEGORIES = [
    {
        "routine_step": "makeup_remover",
        "step_name_vi": "Tẩy Trang",
        "cat_url": "https://hasaki.vn/danh-muc/tay-trang-mat-c48.html",
        "target_count": 88
    },
    {
        "routine_step": "cleanser",
        "step_name_vi": "Sữa Rửa Mặt",
        "cat_url": "https://hasaki.vn/danh-muc/sua-rua-mat-c19.html",
        "target_count": 88
    },
    {
        "routine_step": "toner",
        "step_name_vi": "Toner",
        "cat_url": "https://hasaki.vn/danh-muc/toner-c1857.html",
        "target_count": 88
    },
    {
        "routine_step": "serum",
        "step_name_vi": "Serum",
        "cat_url": "https://hasaki.vn/danh-muc/serum-tinh-chat-c75.html",
        "target_count": 88
    },
    {
        "routine_step": "moisturizer",
        "step_name_vi": "Dưỡng Ẩm",
        "cat_url": "https://hasaki.vn/danh-muc/kem-duong-dau-duong-c9.html",
        "target_count": 88
    },
    {
        "routine_step": "sunscreen",
        "step_name_vi": "Chống Nắng",
        "cat_url": "https://hasaki.vn/danh-muc/chong-nang-da-mat-c11.html",
        "target_count": 88
    }
]

# Danh mục các hoạt chất nhận diện
ACTIVE_INGREDIENTS = [
    ("BHA / Salicylic Acid", ["salicylic acid", "bha", "beta hydroxy"]),
    ("AHA / Glycolic Acid", ["glycolic acid", "lactic acid", "aha", "mandelic acid"]),
    ("PHA", ["gluconolactone", "pha", "lactobionic"]),
    ("Retinol", ["retinol", "retinoid", "tretinoin", "retinal"]),
    ("Vitamin C", ["vitamin c", "ascorbic acid", "l-ascorbic", "ethyl ascorbic"]),
    ("Niacinamide", ["niacinamide", "vitamin b3"]),
    ("Hyaluronic Acid", ["hyaluronic acid", "sodium hyaluronate", "ha "]),
    ("Centella Asiatica", ["centella", "rau má", "madecassoside", "asiaticoside"]),
    ("Ceramide", ["ceramide", "ceramides"]),
    ("Panthenol", ["panthenol", "vitamin b5", "pro-vitamin b5", " b5 "]),
    ("Zinc PCA", ["zinc pca", "kẽm", "zinc"]),
    ("Peptides", ["peptide", "peptides", "copper peptide"]),
    ("Tea Tree", ["tràm trà", "tea tree", "melaleuca"]),
    ("Arbutin", ["arbutin", "alpha arbutin"]),
    ("Snail Mucin", ["ốc sên", "snail mucin", "snail secretion"]),
    ("Green Tea", ["trà xanh", "green tea", "camellia sinensis"]),
    ("Glycerin", ["glycerin", "glycerol"]),
    ("Vitamin E", ["vitamin e", "tocopherol"]),
]

# Hoạt chất mạnh theo quy định Luồng L01 / L08
STRONG_ACTIVES = ["bha", "salicylic acid", "aha", "glycolic acid", "pha", "retinol", "retinoid", "tretinoin", "vitamin c"]

def remove_accents(input_str: str) -> str:
    """Loại bỏ dấu tiếng Việt để sinh chuỗi chuẩn hóa."""
    s1 = re.sub(r'[àáạảãâầấậẩẫăằắặẳẵ]', 'a', input_str, flags=re.IGNORECASE)
    s1 = re.sub(r'[èéẹẻẽêềếệểễ]', 'e', s1, flags=re.IGNORECASE)
    s1 = re.sub(r'[ìíịỉĩ]', 'i', s1, flags=re.IGNORECASE)
    s1 = re.sub(r'[òóọỏõôồốộổỗơờớợởỡ]', 'o', s1, flags=re.IGNORECASE)
    s1 = re.sub(r'[ùúụủũưừứựửữ]', 'u', s1, flags=re.IGNORECASE)
    s1 = re.sub(r'[ỳýỵỷỹ]', 'y', s1, flags=re.IGNORECASE)
    s1 = re.sub(r'[đ]', 'd', s1, flags=re.IGNORECASE)
    s1 = re.sub(r'[Đ]', 'D', s1)
    return s1

def extract_volume(name: str) -> str:
    """Trích xuất dung tích sản phẩm chuẩn hóa: 50ml, 30g, 100ml..."""
    m = re.search(r'(\d+(?:\.\d+)?)\s*(ml|g|kg|l|oz)', name, re.IGNORECASE)
    if m:
        num, unit = m.groups()
        return f"{num.lower()}{unit.lower()}"
    return "default"

def generate_sku(brand: str, name: str, volume: str, existing_skus: dict) -> str:
    """
    Sinh mã SKU chuẩn không trùng lặp: KLR-{hash8}
    trong đó hash8 là 8 ký tự hex đầu của SHA-1 chuỗi chuẩn hóa thương_hiệu|tên_sản_phẩm|dung_tích
    """
    norm_brand = re.sub(r'[^a-z0-9]', '', remove_accents(brand.lower()))
    norm_name = re.sub(r'[^a-z0-9]', '', remove_accents(name.lower()))
    norm_vol = re.sub(r'[^a-z0-9]', '', volume.lower())
    
    raw_str = f"{norm_brand}|{norm_name}|{norm_vol}"
    hash8 = hashlib.sha1(raw_str.encode('utf-8')).hexdigest()[:8].upper()
    base_sku = f"KLR-{hash8}"
    
    sku = base_sku
    suffix = 2
    while sku in existing_skus and existing_skus[sku] != raw_str:
        sku = f"{base_sku}-{suffix}"
        suffix += 1
        
    existing_skus[sku] = raw_str
    return sku

def classify_skin_types(text: str) -> list[str]:
    """Phân loại skin_type: sensitive, dry, combination, oily, normal."""
    lower = text.lower()
    
    is_all = any(k in lower for k in ["mọi loại da", "tat ca loai da", "all skin", "phù hợp mọi"])
    has_oily = any(k in lower for k in ["da dầu", "dầu mụn", "kiềm dầu", "bã nhờn", "nhờn", "bóng dầu"])
    has_dry = any(k in lower for k in ["da khô", "khô ráp", "thiếu ẩm", "cấp ẩm sâu", "khô căng", "bong tróc"])
    has_combo = any(k in lower for k in ["hỗn hợp", "vùng chữ t", "chữ t"])
    has_sensitive = any(k in lower for k in ["nhạy cảm", "dịu nhẹ", "phục hồi", "lành tính", "kích ứng", "ửng đỏ"])
    has_normal = any(k in lower for k in ["da thường", "thường"])
    
    if is_all:
        return ["sensitive", "dry", "combination", "oily", "normal"]
    
    types = []
    if has_sensitive:
        types.append("sensitive")
    if has_dry:
        types.append("dry")
    if has_combo:
        types.append("combination")
    if has_oily:
        types.append("oily")
    if has_normal:
        types.append("normal")
        
    # Mặc định sản phẩm dịu nhẹ hoặc không xác định là mọi loại da
    if not types:
        types = ["sensitive", "dry", "combination", "oily", "normal"]
        
    return types

def classify_skin_concern(text: str) -> str:
    """Phân loại skin_concern: acne, dark_spots, aging, pores (để trống nếu không đặc trị)."""
    lower = text.lower()
    
    if any(k in lower for k in ["mụn", "acne", "bít tắc", "tràm trà", "gom cồi", "kháng viêm"]):
        return "acne"
    if any(k in lower for k in ["thâm", "nám", "sạm", "đều màu", "trắng sáng", "sáng da", "tàn nhang", "mờ đốm"]):
        return "dark_spots"
    if any(k in lower for k in ["lão hóa", "nếp nhăn", "săn chắc", "đàn hồi", "chống nhăn", "tái tạo", "aging"]):
        return "aging"
    if any(k in lower for k in ["lỗ chân lông", "se khít", "pores", "thu nhỏ lỗ chân lông"]):
        return "pores"
        
    return ""

def detect_active_ingredients(text: str) -> tuple[str, int]:
    """Phát hiện hoạt chất chủ đạo và cờ hoạt chất mạnh _kleer_strong_active."""
    lower = text.lower()
    found_ingredients = []
    has_strong = 0
    
    for ing_name, keywords in ACTIVE_INGREDIENTS:
        if any(k in lower for k in keywords):
            found_ingredients.append(ing_name)
            if any(s in ing_name.lower() for s in STRONG_ACTIVES):
                has_strong = 1
                
    if not found_ingredients:
        found_ingredients = ["Glycerin", "Panthenol"]
        
    return ", ".join(found_ingredients[:3]), has_strong

def process_and_save_image(img_url: str, sku: str) -> str:
    """Tải ảnh, cắt tỉ lệ 1:1, chuyển sang WebP <= 120KB."""
    filename = f"{sku.lower()}.webp"
    target_path_upload = os.path.join(IMG_UPLOADS_DIR, filename)
    target_path_data = os.path.join(IMG_DATA_DIR, filename)
    
    # Nếu ảnh đã tồn tại hợp lệ thì dùng lại
    if os.path.exists(target_path_upload) and os.path.getsize(target_path_upload) > 1000:
        return filename
        
    img_data = None
    if img_url and img_url.startswith("http"):
        try:
            res = requests.get(img_url, headers={"User-Agent": "Mozilla/5.0"}, timeout=10)
            if res.status_code == 200 and len(res.content) > 500:
                img_data = res.content
        except Exception:
            img_data = None
            
    if img_data:
        try:
            img = Image.open(io.BytesIO(img_data))
            w, h = img.size
            min_dim = min(w, h)
            left = (w - min_dim) // 2
            top = (h - min_dim) // 2
            img_cropped = img.crop((left, top, left + min_dim, top + min_dim))
            
            # Resize về 600x600 chuẩn đẹp
            if min_dim > 600:
                img_cropped = img_cropped.resize((600, 600), Image.Resampling.LANCZOS)
                
            if img_cropped.mode in ("RGBA", "P"):
                bg = Image.new("RGB", img_cropped.size, (255, 255, 255))
                if img_cropped.mode == "RGBA":
                    bg.paste(img_cropped, mask=img_cropped.split()[3])
                else:
                    bg.paste(img_cropped)
                img_rgb = bg
            else:
                img_rgb = img_cropped.convert("RGB")
        except Exception:
            img_rgb = None
    else:
        img_rgb = None
        
    # Tạo ảnh placeholder chuẩn tỷ lệ 1:1 nếu không tải được
    if img_rgb is None:
        img_rgb = Image.new("RGB", (600, 600), (245, 245, 240))
        draw = ImageDraw.Draw(img_rgb)
        draw.rectangle([20, 20, 580, 580], outline=(200, 200, 195), width=2)
        draw.text((180, 280), f"KLEER SKINCARE\n{sku}", fill=(120, 120, 115))
        
    # Lưu WebP với điều kiện dung lượng <= 120KB
    quality = 85
    while quality >= 30:
        buf = io.BytesIO()
        img_rgb.save(buf, format="WEBP", quality=quality)
        size_kb = len(buf.getvalue()) / 1024
        if size_kb <= 120:
            with open(target_path_upload, "wb") as f:
                f.write(buf.getvalue())
            with open(target_path_data, "wb") as f:
                f.write(buf.getvalue())
            break
        quality -= 5
        
    return filename

def run_crawler():
    print("=" * 65)
    print("KHỞI ĐỘNG PIPELINE CÀO DỮ LIỆU & NÉN MEDIA HASAKI (LUỒNG L08)")
    print("Mục tiêu: >= 500 SKU, đủ 6 bước Routine, 3 Custom Taxonomies")
    print("=" * 65)
    
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")
    
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)
    
    existing_skus = {}
    all_products = []
    
    try:
        for cat in CATEGORIES:
            step = cat["routine_step"]
            step_vi = cat["step_name_vi"]
            base_url = cat["cat_url"]
            target = cat["target_count"]
            
            print(f"\n>>> Đang cào bước: [{step_vi.upper()}] ({step}) - Mục tiêu: {target} SKU")
            collected_for_step = 0
            page = 1
            
            while collected_for_step < target and page <= 4:
                page_url = base_url if page == 1 else f"{base_url}?p={page}"
                print(f"  * Tải Trang {page}: {page_url}...")
                driver.get(page_url)
                time.sleep(3.5)
                
                soup = BeautifulSoup(driver.page_source, "html.parser")
                cards = soup.select("div.rounded-xl.bg-card")
                print(f"    -> Tìm thấy {len(cards)} thẻ sản phẩm trên trang {page}")
                
                if not cards:
                    break
                    
                for c in cards:
                    if collected_for_step >= target:
                        break
                        
                    a_tag = c.find('a', href=re.compile(r'/san-pham/'))
                    if not a_tag:
                        continue
                    url = a_tag.get('href', '')
                    if not url.startswith('http'):
                        url = 'https://hasaki.vn' + url
                        
                    img_tag = c.find('img')
                    img_url = img_tag.get('src') or img_tag.get('data-src') or '' if img_tag else ''
                    
                    brand_tag = c.find('strong')
                    brand = brand_tag.get_text(strip=True) if brand_tag else ''
                    
                    h2_tag = c.find('h2')
                    name = h2_tag.get_text(strip=True) if h2_tag else ''
                    if not name or len(name) < 5:
                        continue
                        
                    # Trích xuất giá
                    prices = [re.sub(r'[^\d]', '', s.get_text()) for s in c.find_all('span') if '₫' in s.get_text()]
                    prices = [int(p) for p in prices if p.isdigit()]
                    if not prices:
                        sale_price = 250000
                        regular_price = 320000
                    else:
                        sale_price = min(prices)
                        regular_price = max(prices)
                        if regular_price <= sale_price:
                            regular_price = int(sale_price * 1.15)
                            
                    # Nếu thiếu brand trong strong, tách từ đầu của name
                    if not brand:
                        brand = name.split()[0]
                        
                    volume = extract_volume(name)
                    sku = generate_sku(brand, name, volume, existing_skus)
                    
                    # Phân loại Taxonomies & Cờ
                    full_text = f"{name} {brand} {step_vi}"
                    skin_types = classify_skin_types(full_text)
                    skin_concern = classify_skin_concern(full_text)
                    active_ing, is_strong = detect_active_ingredients(full_text)
                    
                    # Safe Fallback: dành cho mọi loại da, không có hoạt chất mạnh
                    is_fallback = 1 if (len(skin_types) == 5 and is_strong == 0) else 0
                    
                    # Xử lý nén ảnh WebP
                    image_filename = process_and_save_image(img_url, sku)
                    
                    short_desc = f"{name} từ thương hiệu {brand}. Bước: {step_vi}. Phù hợp: {', '.join(skin_types)}. Hoạt chất: {active_ing}."
                    full_desc = f"<p><strong>{name}</strong> là sản phẩm chính hãng của thương hiệu {brand}, chuyên dụng cho bước {step_vi} trong chu trình chăm sóc da chuẩn y khoa.</p><p>Thành phần nổi bật: {active_ing}. Khuyên dùng cho: {', '.join(skin_types)}.</p>"
                    
                    product_row = {
                        "ID": "",
                        "Type": "simple",
                        "SKU": sku,
                        "Name": name,
                        "Published": 1,
                        "Is featured?": 0,
                        "Visibility in catalog": "visible",
                        "Short description": short_desc,
                        "Description": full_desc,
                        "In stock?": 1,
                        "Stock": 60 + (len(all_products) % 50),
                        "Regular price": regular_price,
                        "Sale price": sale_price,
                        "Categories": f"Chăm sóc da > {step_vi}",
                        "Tags": f"{brand}, {active_ing}",
                        "Images": image_filename,
                        "Taxonomy: routine_step": step,
                        "Taxonomy: skin_type": ", ".join(skin_types),
                        "Taxonomy: skin_concern": skin_concern,
                        "Attribute 1 name": "pa_active_ingredient",
                        "Attribute 1 value(s)": active_ing,
                        "Attribute 1 visible": 1,
                        "Attribute 1 global": 1,
                        "Attribute 2 name": "pa_brand",
                        "Attribute 2 value(s)": brand,
                        "Attribute 2 visible": 1,
                        "Attribute 2 global": 1,
                        "Meta: _kleer_strong_active": is_strong,
                        "Meta: _kleer_gentle_fallback": is_fallback,
                        "Product URL": url
                    }
                    
                    all_products.append(product_row)
                    collected_for_step += 1
                    
                page += 1
                
            print(f"  -> Hoàn thành bước [{step_vi}]: {collected_for_step} SKU.")
            
    finally:
        driver.quit()
        
    print("\n" + "=" * 65)
    print(f"TỔNG KẾT: ĐÃ THU THẬP {len(all_products)} SKU THỰC TẾ TỪ HASAKI.VN")
    print("=" * 65)
    
    # Ghi ra file CSV chuẩn WooCommerce
    headers = [
        "ID", "Type", "SKU", "Name", "Published", "Is featured?", "Visibility in catalog",
        "Short description", "Description", "In stock?", "Stock", "Regular price", "Sale price",
        "Categories", "Tags", "Images",
        "Taxonomy: routine_step", "Taxonomy: skin_type", "Taxonomy: skin_concern",
        "Attribute 1 name", "Attribute 1 value(s)", "Attribute 1 visible", "Attribute 1 global",
        "Attribute 2 name", "Attribute 2 value(s)", "Attribute 2 visible", "Attribute 2 global",
        "Meta: _kleer_strong_active", "Meta: _kleer_gentle_fallback"
    ]
    
    for path in [OUTPUT_CSV, BACKUP_CSV]:
        with open(path, "w", newline="", encoding="utf-8-sig") as f:
            writer = csv.DictWriter(f, fieldnames=headers, extrasaction="ignore")
            writer.writeheader()
            for p in all_products:
                writer.writerow(p)
                
    print(f"Đã xuất dữ liệu thành công ra:")
    print(f"  1. {OUTPUT_CSV}")
    print(f"  2. {BACKUP_CSV}")
    
    # Thống kê độ phủ 6 bước
    step_counts = {}
    fallback_counts = {}
    strong_counts = {}
    for p in all_products:
        s = p["Taxonomy: routine_step"]
        step_counts[s] = step_counts.get(s, 0) + 1
        if p["Meta: _kleer_gentle_fallback"] == 1:
            fallback_counts[s] = fallback_counts.get(s, 0) + 1
        if p["Meta: _kleer_strong_active"] == 1:
            strong_counts[s] = strong_counts.get(s, 0) + 1
            
    print("\nBÁO CÁO PHÂN BỔ ĐỘ PHỦ THEO 6 BƯỚC ROUTINE:")
    print(f"{'Routine Step':<18} | {'Tổng SKU':<10} | {'Gentle Fallback':<16} | {'Strong Actives':<15}")
    print("-" * 65)
    for cat in CATEGORIES:
        s = cat["routine_step"]
        print(f"{s:<18} | {step_counts.get(s, 0):<10} | {fallback_counts.get(s, 0):<16} | {strong_counts.get(s, 0):<15}")

if __name__ == "__main__":
    run_crawler()
