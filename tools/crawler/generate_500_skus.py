import os
import csv
import random

# Danh mục mở rộng dựa trên file 30 sản phẩm thực tế
BRANDS = [
    ("CeraVe", ["Ceramide", "Hyaluronic Acid", "Niacinamide"]),
    ("La Roche-Posay", ["Zinc PCA", "Nước khoáng LRP", "Salicylic Acid"]),
    ("Paula's Choice", ["BHA 2%", "Niacinamide 20%", "AHA 8%"]),
    ("Cocoon", ["Bí đao", "Tràm trà", "Cà phê Đắk Lắk", "Hoa hồng Cao Bằng"]),
    ("The Ordinary", ["Niacinamide 10%", "Zinc 1%", "Hyaluronic Acid 2% + B5", "Alpha Arbutin 2%"]),
    ("Klairs", ["Centella Asiatica", "Vitamin C", "Sodium Hyaluronate"]),
    ("Cosrx", ["BHA", "Chiết xuất ốc sên (Snail Mucin)", "Propolis"]),
    ("Cetaphil", ["Glycerin", "Panthenol", "Niacinamide"]),
    ("Innisfree", ["Trà xanh Jeju", "Tro núi lửa", "Bija"]),
    ("Vichy", ["Nước khoáng núi lửa", "Hyaluronic Acid", "Volcanic Minerals"])
]

CATEGORIES = {
    "Cleanser": ("Sữa Rửa Mặt", (180000, 450000), "Làm sạch sâu bụi bẩn, bã nhờn mà không gây khô căng da"),
    "Toner": ("Nước Hoa Hồng", (220000, 550000), "Cân bằng độ pH, làm dịu da và cấp ẩm tức thì"),
    "Serum": ("Tinh Chất", (350000, 950000), "Phục hồi chuyên sâu, dưỡng sáng và cải thiện cấu trúc da"),
    "Sunscreen": ("Kem Chống Nắng", (290000, 680000), "Bảo vệ da phổ rộng SPF50+ PA++++, kiềm dầu kháng nước"),
    "Moisturizer": ("Kem Dưỡng Ẩm", (280000, 720000), "Khóa ẩm 24h, củng cố hàng rào lipid bảo vệ da"),
    "Treatment": ("Chấm Mụn & Tẩy Tế Bào Chết", (240000, 850000), "Giảm mụn sưng viêm, gom cồi mụn và thông thoáng lỗ chân lông")
}

SKIN_TYPES = ["Dầu, Mụn", "Khô, Nhạy cảm", "Hỗn hợp", "Nhạy cảm", "Mọi loại da", "Lão hóa"]

OUTPUT_CSV = "data/woocommerce_500_skus.csv"
os.makedirs("data", exist_ok=True)

# Headers chuẩn 100% theo WooCommerce Product Import Schema
HEADERS = [
    "ID", "Type", "SKU", "Name", "Published", "Is featured?", "Visibility in catalog",
    "Short description", "Description", "In stock?", "Stock", "Regular price",
    "Categories", "Tags", "Images",
    "Attribute 1 name", "Attribute 1 value(s)", "Attribute 1 visible", "Attribute 1 global",
    "Attribute 2 name", "Attribute 2 value(s)", "Attribute 2 visible", "Attribute 2 global"
]

rows = []
for i in range(1, 521):
    sku = f"KLR-{i:04d}"
    brand, brand_ingredients = random.choice(BRANDS)
    cat_key, (cat_prefix, price_range, desc_base) = random.choice(list(CATEGORIES.items()))
    skin_type = random.choice(SKIN_TYPES)
    ingredient = random.choice(brand_ingredients)
    
    name = f"{cat_prefix} {brand} {ingredient}"
    price = random.randrange(price_range[0], price_range[1], 10000)
    stock = random.randint(20, 150)
    image_file = f"img_{cat_key.lower()}_{i:03d}.webp"
    
    short_desc = f"{desc_base}. Phù hợp cho da: {skin_type}."
    full_desc = f"<p>{name} là giải pháp chăm sóc da chuyên sâu từ {brand}. Chứa thành phần {ingredient} giúp nuôi dưỡng làn da khỏe mạnh.</p>"
    
    rows.append([
        "", "simple", sku, name, 1, 0, "visible",
        short_desc, full_desc, 1, stock, price,
        f"Chăm sóc da > {cat_key}", f"{brand}, {ingredient}", image_file,
        "Loại da", skin_type, 1, 1,
        "Thương hiệu", brand, 1, 1
    ])

with open(OUTPUT_CSV, mode="w", newline="", encoding="utf-8-sig") as f:
    writer = csv.writer(f)
    writer.writerow(HEADERS)
    writer.writerows(rows)

print(f"Đã xuất thành công {len(rows)} SKU chuẩn WooCommerce ra {OUTPUT_CSV}!")