"""
Script cào và làm giàu dữ liệu Mô tả sản phẩm (Description) và Hoạt chất chủ đạo (Active Ingredients):
1. Bóc tách <div id="DescriptionInfo">:
   - Làm sạch HTML: Loại bỏ link nội bộ Hasaki và script/iframe.
   - Giữ nguyên cấu trúc h2, h3, p, ul, li chuẩn SEO cho cột Description.
   - Trích xuất đoạn giới thiệu súc tích vào Short description.
2. Bóc tách <div id="IngredientInfo">:
   - Lọc tối đa 5 hoạt chất chủ đạo nổi bật nhất (BHA, AHA, PHA, Retinol, Niacinamide, Vitamin C, Ceramide...).
   - Ghi vào cột Attribute 1 value(s) và Meta: _kleer_active_ingredients.
   - Cập nhật cờ Meta: _kleer_strong_active, Meta: _kleer_has_strong_actives, Meta: _kleer_gentle_fallback.
3. Áp dụng Cách 1 cho 120 sản phẩm Combo:
   - Để trống cột 'Taxonomy: routine_step' để Quiz tự động loại trừ khỏi kết quả gợi ý.
   - Giữ nguyên 3 sản phẩm Clinicos [Mua 1 Tặng 1] làm sản phẩm chống nắng đơn.
4. Cơ chế Cache bền vững (product_details_cache.json):
   - Lưu tiến độ liên tục sau mỗi 5 sản phẩm.
"""

import os
import sys
import json
import time
import re
import threading
import pandas as pd
from bs4 import BeautifulSoup
from concurrent.futures import ThreadPoolExecutor
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

# Cấu hình UTF-8 console Windows
sys.stdout.reconfigure(encoding='utf-8')

BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSV_WOO = os.path.join(BASE_DIR, "data", "woocommerce_500_skus.csv")
CACHE_FILE = os.path.join(BASE_DIR, "data", "product_details_cache.json")

cache_lock = threading.Lock()
global_cache = {}

# Danh mục định nghĩa hoạt chất phân theo độ ưu tiên
ACTIVE_DEFINITIONS = [
    # Nhóm 1: Hoạt chất mạnh (Strong Actives)
    ("BHA / Salicylic Acid", ["salicylic acid", "betaine salicylate", "capryloyl salicylic", "bha "], 1),
    ("AHA / Glycolic Acid", ["glycolic acid", "lactic acid", "mandelic acid", "aha ", "malic acid", "tartaric acid"], 1),
    ("PHA", ["gluconolactone", "lactobionic acid", "pha "], 1),
    ("Retinol / Retinoids", ["retinol", "retinal", "retinoid", "tretinoin", "bakuchiol", "adapalene"], 1),
    ("Vitamin C", ["ascorbic acid", "vitamin c", "ethyl ascorbic", "ascorbyl glucoside", "tetrahexyldecyl"], 1),
    
    # Nhóm 2: Hoạt chất trị liệu chuyên sâu & hàng rào bảo vệ (Targeted & Barrier)
    ("Niacinamide (B3)", ["niacinamide", "vitamin b3", "nicotinamide"], 2),
    ("Tranexamic Acid", ["tranexamic acid", "txa"], 2),
    ("Arbutin", ["alpha arbutin", "arbutine", "arbutin"], 2),
    ("Ceramide", ["ceramide", "ceramides", "phytosphingosine", "sphingolipids"], 2),
    ("Peptides", ["peptide", "peptides", "copper tripeptide", "palmitoyl"], 2),
    ("Zinc PCA / Kẽm", ["zinc pca", "zinc gluconate", "kẽm", "zinc oxide"], 2),
    
    # Nhóm 3: Chiết xuất thực vật / Kháng viêm / Thảo mộc tự nhiên
    ("Centella Asiatica (Rau má)", ["centella asiatica", "rau má", "madecassoside", "asiaticoside", "asiatic acid"], 3),
    ("Tea Tree (Tràm trà)", ["melaleuca alternifolia", "tea tree", "tràm trà"], 3),
    ("Winter Melon (Bí đao)", ["benincasa cerifera", "bí đao"], 3),
    ("Houttuynia Cordata (Diếp cá)", ["houttuynia cordata", "diếp cá", "heartleaf"], 3),
    ("Green Tea (Trà xanh)", ["camellia sinensis", "green tea", "trà xanh"], 3),
    ("Mugwort (Ngải cứu)", ["artemisia", "ngải cứu"], 3),
    ("Rice Bran (Cám gạo)", ["oryza sativa", "cám gạo"], 3),
    ("Coix Seed (Ý dĩ)", ["coix lacryma", "ý dĩ", "hatomugi"], 3),
    ("Snail Mucin (Ốc sên)", ["snail secretion", "snail mucin", "ốc sên"], 3),
    
    # Nhóm 4: Cấp ẩm & Phục hồi lành tính
    ("Hyaluronic Acid (HA)", ["hyaluronic acid", "sodium hyaluronate", "hydrolyzed hyaluronic"], 4),
    ("Panthenol (B5)", ["panthenol", "vitamin b5", "dexpanthenol"], 4),
    ("Allantoin", ["allantoin"], 4),
    ("Glycerin", ["glycerin", "glycerol"], 5),
]

CLINICOS_KEEP_SKUS = {"KLR-FCA59FF9", "KLR-47FBB67C", "KLR-142153FC"}

def load_cache():
    if os.path.exists(CACHE_FILE):
        try:
            with open(CACHE_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            return {}
    return {}

def save_cache_incremental(new_data: dict):
    with cache_lock:
        global_cache.update(new_data)
        try:
            with open(CACHE_FILE, "w", encoding="utf-8") as f:
                json.dump(global_cache, f, ensure_ascii=False, indent=2)
        except Exception as e:
            print(f"Lỗi lưu cache: {e}", flush=True)

def create_driver():
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--blink-settings=imagesEnabled=false")
    options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")
    driver = webdriver.Chrome(options=options)
    driver.set_page_load_timeout(15)
    return driver

def clean_description_html(raw_html: str, product_name: str, brand: str) -> str:
    """Làm sạch HTML của Hasaki để thành mô tả WooCommerce chuẩn SEO."""
    if not raw_html:
        return f"<p><strong>{product_name}</strong> là sản phẩm chính hãng của thương hiệu {brand}, chuyên dụng cho chu trình chăm sóc da chuẩn y khoa.</p>"
        
    soup = BeautifulSoup(raw_html, "html.parser")
    
    # Xóa script, style, iframe, form, button
    for tag in soup(["script", "style", "iframe", "form", "button", "input", "svg"]):
        tag.decompose()
        
    # Thay thế thẻ a thành text thuần (loại bỏ link sang Hasaki)
    for a in soup.find_all("a"):
        a.replace_with(a.get_text())
        
    # Xóa các thuộc tính không cần thiết (id, class, onclick...) để HTML gọn gàng
    allowed_attrs = ["src", "alt", "title"]
    for tag in soup.find_all(True):
        attrs = dict(tag.attrs)
        for attr in attrs:
            if attr not in allowed_attrs:
                del tag[attr]
                
    content = soup.get_text(separator="\n", strip=True)
    if len(content) < 50:
        return f"<p><strong>{product_name}</strong> là sản phẩm chính hãng của thương hiệu {brand}, chuyên dụng cho chu trình chăm sóc da chuẩn y khoa.</p>"
        
    clean_html = "".join(str(c) for c in soup.children).strip()
    return clean_html

def extract_top5_actives(ingredient_text: str, product_name: str, brand: str) -> tuple[str, int, int]:
    """Bóc tách tối đa 5 hoạt chất nổi bật nhất theo thứ tự ưu tiên y khoa."""
    combined_text = f"{product_name} {brand} {ingredient_text}".lower()
    
    found_actives = []
    has_strong = 0
    
    for name, keywords, priority in sorted(ACTIVE_DEFINITIONS, key=lambda x: x[2]):
        if any(k in combined_text for k in keywords):
            if name not in found_actives:
                found_actives.append(name)
                if priority == 1:
                    has_strong = 1
                if len(found_actives) >= 5:
                    break
                    
    if not found_actives:
        found_actives = ["Panthenol (B5)", "Glycerin"]
        
    top5_str = ", ".join(found_actives[:5])
    is_fallback = 1 if (has_strong == 0 and any(f in top5_str for f in ["Panthenol", "Centella", "Allantoin", "Ceramide", "Hyaluronic"])) else 0
    
    return top5_str, has_strong, is_fallback

def scrape_worker(items_subset, worker_id, total_items_count):
    driver = None
    batch = {}
    
    try:
        driver = create_driver()
        for idx, (url, name, brand, sku) in enumerate(items_subset):
            try:
                driver.get(url)
                time.sleep(1.5)
                
                soup = BeautifulSoup(driver.page_source, "html.parser")
                
                # 1. Mô tả
                desc_div = soup.find(id="DescriptionInfo")
                raw_desc_html = str(desc_div) if desc_div else ""
                clean_desc = clean_description_html(raw_desc_html, name, brand)
                
                # 2. Thành phần
                ing_div = soup.find(id="IngredientInfo")
                ing_text = ing_div.get_text(separator=" ", strip=True) if ing_div else ""
                
                # 3. Tối đa 5 hoạt chất
                top5_actives, has_strong, is_fallback = extract_top5_actives(ing_text, name, brand)
                
                # 4. Mô tả ngắn
                intro_text = ""
                if desc_div:
                    p_tag = desc_div.find("p")
                    if p_tag:
                        intro_text = p_tag.get_text(strip=True)[:200]
                if not intro_text:
                    intro_text = f"{name} chính hãng từ thương hiệu {brand}."
                short_desc = f"{intro_text} Hoạt chất chủ đạo: {top5_actives}."
                
                item_data = {
                    "sku": sku,
                    "description": clean_desc,
                    "short_description": short_desc,
                    "active_ingredients": top5_actives,
                    "strong_active": has_strong,
                    "gentle_fallback": is_fallback
                }
                batch[url] = item_data
                
                if len(batch) >= 5 or (idx + 1) == len(items_subset):
                    save_cache_incremental(batch)
                    batch = {}
                    print(f"[Worker {worker_id}] Tiến độ: {idx + 1}/{len(items_subset)} sản phẩm. Đã lưu cache (Tổng cache: {len(global_cache)}/{total_items_count})...", flush=True)
            except Exception as e:
                print(f"[Worker {worker_id}] URL timeout/lỗi {url[:60]}: {e}", flush=True)
                item_data = {
                    "sku": sku,
                    "description": f"<p><strong>{name}</strong> là sản phẩm chính hãng của {brand}.</p>",
                    "short_description": f"{name} từ thương hiệu {brand}. Hoạt chất: Panthenol (B5), Glycerin.",
                    "active_ingredients": "Panthenol (B5), Glycerin",
                    "strong_active": 0,
                    "gentle_fallback": 1
                }
                batch[url] = item_data
                save_cache_incremental(batch)
                batch = {}
    finally:
        if batch:
            save_cache_incremental(batch)
        if driver:
            try:
                driver.quit()
            except Exception:
                pass

def main():
    global global_cache
    if not os.path.exists(CSV_WOO):
        print(f"Không tìm thấy file: {CSV_WOO}", flush=True)
        return
        
    df = pd.read_csv(CSV_WOO)
    total_rows = len(df)
    print(f"=== BẮT ĐẦU LÀM GIÀU MÔ TẢ & HOẠT CHẤT CHO {total_rows} SẢN PHẨM ===", flush=True)
    
    global_cache = load_cache()
    print(f"Đã tải {len(global_cache)} sản phẩm từ cache hiện có.", flush=True)
    
    to_crawl = []
    for _, row in df.iterrows():
        url = str(row["Source URL"]).strip()
        sku = str(row["SKU"]).strip()
        name = str(row["Name"]).strip()
        brand = str(row["Attribute 2 value(s)"] if pd.notna(row.get("Attribute 2 value(s)")) else "").strip()
        if not brand:
            brand = name.split()[0]
            
        if url and url.startswith("http"):
            if url not in global_cache:
                to_crawl.append((url, name, brand, sku))
                
    print(f"Số lượng sản phẩm cần cào từ Hasaki: {len(to_crawl)} / {total_rows}", flush=True)
    
    if to_crawl:
        num_workers = 5
        chunk_size = (len(to_crawl) + num_workers - 1) // num_workers
        chunks = [to_crawl[i:i + chunk_size] for i in range(0, len(to_crawl), chunk_size)]
        
        print(f"Khởi chạy {len(chunks)} Chrome Headless workers song song...", flush=True)
        with ThreadPoolExecutor(max_workers=len(chunks)) as executor:
            futures = [executor.submit(scrape_worker, chunk, i + 1, total_rows) for i, chunk in enumerate(chunks)]
            for f in futures:
                f.result()
                
        print(f"Hoàn thành cào! Tổng số sản phẩm trong cache: {len(global_cache)}", flush=True)
        
    # Áp dụng dữ liệu vào DataFrame
    print("\nĐang cập nhật các cột vào DataFrame...", flush=True)
    combo_count = 0
    single_count = 0
    strong_count = 0
    
    for idx, row in df.iterrows():
        sku = str(row["SKU"]).strip()
        name = str(row["Name"]).strip()
        url = str(row["Source URL"]).strip()
        
        # 1. Xử lý Combo (Cách 1)
        is_combo = ("combo" in name.lower()) and (sku not in CLINICOS_KEEP_SKUS)
        if is_combo:
            df.at[idx, "Taxonomy: routine_step"] = ""
            combo_count += 1
        else:
            single_count += 1
            
        # 2. Cập nhật Mô tả và Hoạt chất từ Cache
        cached_item = global_cache.get(url)
        if cached_item:
            df.at[idx, "Description"] = cached_item["description"]
            df.at[idx, "Short description"] = cached_item["short_description"]
            
            actives = cached_item["active_ingredients"]
            has_strong = cached_item["strong_active"]
            gentle_fallback = cached_item["gentle_fallback"]
            
            df.at[idx, "Attribute 1 name"] = "pa_active_ingredient"
            df.at[idx, "Attribute 1 value(s)"] = actives
            df.at[idx, "Attribute 1 visible"] = 1
            df.at[idx, "Attribute 1 global"] = 1
            
            df.at[idx, "Meta: _kleer_active_ingredients"] = actives
            df.at[idx, "Meta: _kleer_strong_active"] = has_strong
            df.at[idx, "Meta: _kleer_has_strong_actives"] = has_strong
            df.at[idx, "Meta: _kleer_gentle_fallback"] = gentle_fallback
            
            if has_strong == 1:
                strong_count += 1
                
    # Lưu ra file CSV chính
    df.to_csv(CSV_WOO, index=False, encoding="utf-8")
    
    print("\n=== KẾT QUẢ CẬP NHẬT HOÀN TẤT ===", flush=True)
    print(f"- Tổng số SKU trong catalog: {len(df)}", flush=True)
    print(f"- Sản phẩm đơn lẻ (có routine_step cho Quiz): {single_count} SKU", flush=True)
    print(f"- Sản phẩm Combo (để trống routine_step, không vào Quiz): {combo_count} SKU", flush=True)
    print(f"- Số sản phẩm chứa hoạt chất mạnh (_kleer_strong_active = 1): {strong_count} SKU", flush=True)
    print(f"- File CSV đã được cập nhật tại: {CSV_WOO}", flush=True)

if __name__ == "__main__":
    main()
