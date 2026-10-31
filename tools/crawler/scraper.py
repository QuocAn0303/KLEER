import os
import time
import random
import requests
from bs4 import BeautifulSoup
import pandas as pd

# Thiết lập thư mục lưu ảnh
IMAGE_DIR = "images/products"
os.makedirs(IMAGE_DIR, exist_ok=True)

# Danh sách User-Agents xoay vòng chống chặn bot
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0"
]

def get_headers():
    return {
        "User-Agent": random.choice(USER_AGENTS),
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
    }

def download_image(img_url, file_name):
    try:
        res = requests.get(img_url, headers=get_headers(), timeout=10)
        if res.status_code == 200:
            file_path = os.path.join(IMAGE_DIR, file_name)
            with open(file_path, "wb") as f:
                f.write(res.content)
            return file_name
    except Exception as e:
        print(f"Lỗi tải ảnh {img_url}: {e}")
    return "placeholder.jpg"

def crawl_hasaki_skincare():
    products = []
    print("Bắt đầu cào dữ liệu...")
    # Demo cào mẫu và xử lý chống chặn IP
    # Áp dụng cơ chế delay ngẫu nhiên:
    time.sleep(random.uniform(1.2, 2.5))
    return products

if __name__ == "__main__":
    crawl_hasaki_skincare()