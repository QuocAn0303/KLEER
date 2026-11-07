#!/usr/bin/env python3
"""
KLEER - Task L08-G7-01: Kiem thu tinh toan ven du lieu SKU (READ-ONLY, offline).

Doi chieu file CSV san pham voi thu muc anh cuc bo (images/products/):
  MISSING_SKU        : dong khong co ma SKU
  NULL_NAME          : ten san pham rong
  NULL_PRICE         : Regular price rong
  INVALID_CURRENCY   : gia chua chu / ky tu la / am / <= 0 (ap dung ca Sale price neu co)
  MISSING_IMAGE_FILE : cot Images rong, hoac file khong ton tai trong images/products/
  INVALID_IMAGE_FILE : file ton tai nhung 0 byte hoac khong phai .webp hop le
  DUPLICATE_SKU      : SKU trung (canh bao bo sung, tat bang --no-duplicate-check)

Script chi DOC. Khong sua CSV, khong xoa/doi ten anh, khong cham database.

Cai dat: pip install pandas
Chay:    python data_integrity_test.py
         python data_integrity_test.py --csv woocommerce_500_skus.csv --images-dir images/products
"""
from __future__ import annotations

import argparse
import re
import sys
import unicodedata
from pathlib import Path, PureWindowsPath

import pandas as pd

CSV_NAME = "woocommerce_500_skus.csv"
IMAGES_REL = Path("images") / "products"

ALIASES = {
    "sku": ["sku", "ma sku", "ma san pham", "ma sp"],
    "name": ["name", "ten san pham", "ten sp", "ten", "product name"],
    "price": ["regular price", "gia goc", "gia ban", "gia ban vnd", "price", "gia"],
    "sale": ["sale price", "gia khuyen mai"],
    "image": ["images", "image", "hinh anh", "url hinh anh", "anh san pham", "ten file anh"],
}
OPTIONAL = {"sale"}
PRICE_RE = re.compile(r"^\d+(\.\d+)?$")
REPORT_COLS = ["SKU", "Ten_San_Pham", "Ma_Loi", "Gia_Tri_Hien_Tai", "Chi_Tiet_Loi", "Dong_CSV"]


def norm(text: str) -> str:
    t = unicodedata.normalize("NFKD", str(text).replace("đ", "d").replace("Đ", "D"))
    t = "".join(c for c in t if not unicodedata.combining(c)).lower()
    return re.sub(r"[^a-z0-9]+", " ", t).strip()


def detect_columns(headers: list[str], overrides: dict[str, str | None]) -> dict[str, str]:
    nmap = {h: norm(h) for h in headers}
    found: dict[str, str] = {}
    for key, aliases in ALIASES.items():
        if overrides.get(key):
            if overrides[key] not in headers:
                sys.exit(f"[LOI] Khong co cot '{overrides[key]}'. Cot hien co: {headers}")
            found[key] = overrides[key]  # type: ignore[assignment]
            continue
        match = next((h for a in aliases for h, n in nmap.items() if n == a), None)
        if match is None:
            if key in OPTIONAL:
                continue
            sys.exit(f"[LOI] Khong tu khop duoc cot '{key}'. Dung --{key}-col. Cot hien co: {headers}")
        found[key] = match
    return found


def find_csv() -> Path:
    here = Path(__file__).resolve().parent
    for base in [Path.cwd(), here, *Path.cwd().parents[:3], *here.parents[:3]]:
        for cand in (base / CSV_NAME, *base.glob(f"*/{CSV_NAME}")):
            if cand.is_file():
                return cand
    sys.exit(f"[LOI] Khong tu tim thay '{CSV_NAME}'. Hay dung --csv <duong_dan>.")


def find_images_dir(csv_path: Path) -> Path:
    here = Path(__file__).resolve().parent
    for base in [csv_path.parent, Path.cwd(), here, *csv_path.parent.parents[:3]]:
        cand = base / IMAGES_REL
        if cand.is_dir():
            return cand
    sys.exit(f"[LOI] Khong tu tim thay thu muc '{IMAGES_REL}'. Hay dung --images-dir <duong_dan>.")


def check_price(raw: str, label: str = "Regular price") -> tuple[str, str] | None:
    v = (raw or "").strip()
    if v == "" or v.lower() in {"null", "none", "nan", "n/a"}:
        return "NULL_PRICE", f"{label} rong (NULL)."
    if not PRICE_RE.match(v):
        if re.search(r"[^\W\d_]", v):
            return "INVALID_CURRENCY", f"{label} chua ky tu chu: '{v}'."
        if v.startswith("-"):
            return "INVALID_CURRENCY", f"{label} am: '{v}'."
        return "INVALID_CURRENCY", f"{label} sai dinh dang tien te: '{v}'."
    if float(v) <= 0:
        return "INVALID_CURRENCY", f"{label} phai > 0, hien tai = {v}."
    return None


def split_images(raw: str) -> list[str]:
    # WooCommerce: nhieu anh cach nhau dau phay
    return [p.strip() for p in (raw or "").split(",") if p.strip()]


def check_image(name: str, images_dir: Path, listing: dict[str, str]) -> tuple[str, str] | None:
    base = PureWindowsPath(name).name  # chap nhan "images/products/x.webp" hoac "x.webp"
    path = images_dir / base
    if not path.is_file():
        alt = listing.get(base.lower())
        if alt:
            return "MISSING_IMAGE_FILE", f"Khong thay '{base}' nhung co '{alt}' (sai chu hoa/thuong - se loi tren Linux/Docker)."
        return "MISSING_IMAGE_FILE", f"Khong tim thay file '{base}' trong {images_dir}."
    try:
        size = path.stat().st_size
        with path.open("rb") as f:
            head = f.read(12)
    except OSError as exc:
        return "INVALID_IMAGE_FILE", f"Khong doc duoc '{base}': {exc.__class__.__name__}."
    if size == 0:
        return "INVALID_IMAGE_FILE", f"File '{base}' co dung luong 0 byte."
    if path.suffix.lower() == ".webp" and not (head[:4] == b"RIFF" and head[8:12] == b"WEBP"):
        return "INVALID_IMAGE_FILE", f"File '{base}' khong phai dinh dang WebP hop le (header sai)."
    return None


def main() -> int:
    ap = argparse.ArgumentParser(description="KLEER - kiem thu toan ven du lieu SKU (read-only)")
    ap.add_argument("--csv", type=Path, help=f"Mac dinh: tu tim '{CSV_NAME}'")
    ap.add_argument("--images-dir", type=Path, help=f"Mac dinh: tu tim '{IMAGES_REL}'")
    ap.add_argument("-o", "--output", type=Path, default=Path("error_report.csv"))
    for k in ("sku", "name", "price", "sale", "image"):
        ap.add_argument(f"--{k}-col")
    ap.add_argument("--no-duplicate-check", action="store_true")
    a = ap.parse_args()

    csv_path = a.csv or find_csv()
    images_dir = a.images_dir or find_images_dir(csv_path)
    if not csv_path.is_file():
        sys.exit(f"[LOI] Khong thay file CSV: {csv_path}")
    if not images_dir.is_dir():
        sys.exit(f"[LOI] Khong thay thu muc anh: {images_dir}")
    if a.output.resolve() == csv_path.resolve():
        sys.exit("[LOI] File bao cao khong duoc trung file CSV goc.")

    df = pd.read_csv(csv_path, dtype=str, keep_default_na=False, encoding="utf-8-sig")
    df.columns = [c.strip() for c in df.columns]
    cols = detect_columns(list(df.columns), {k: getattr(a, f"{k}_col") for k in ALIASES})
    listing = {p.name.lower(): p.name for p in images_dir.iterdir() if p.is_file()}
    print(f"[INFO] CSV : {csv_path} ({len(df)} dong)")
    print(f"[INFO] Anh : {images_dir} ({len(listing)} file)")
    print("[INFO] Cot : " + " | ".join(f"{k}='{v}'" for k, v in cols.items()))

    errors: list[dict] = []
    bad: set[int] = set()
    sku_counts = df[cols["sku"]].str.strip().value_counts()
    image_cache: dict[str, tuple[str, str] | None] = {}

    def add(i: int, sku: str, name: str, code: str, value: str, detail: str) -> None:
        errors.append({"SKU": sku, "Ten_San_Pham": name, "Ma_Loi": code,
                       "Gia_Tri_Hien_Tai": value, "Chi_Tiet_Loi": detail, "Dong_CSV": i + 2})
        bad.add(i)

    for i, row in df.iterrows():
        sku = row[cols["sku"]].strip()
        name = row[cols["name"]].strip()
        price = row[cols["price"]]

        if sku == "":
            add(i, "", name, "MISSING_SKU", "", "Dong san pham khong co ma SKU.")
        elif not a.no_duplicate_check and sku_counts.get(sku, 0) > 1:
            add(i, sku, name, "DUPLICATE_SKU", sku, f"SKU xuat hien {sku_counts[sku]} lan trong file.")
        if name == "":
            add(i, sku, "", "NULL_NAME", "", "Ten san pham rong (NULL).")

        err = check_price(price)
        if err:
            add(i, sku, name, err[0], price, err[1])
        if "sale" in cols and row[cols["sale"]].strip() != "":
            err = check_price(row[cols["sale"]], "Sale price")
            if err:
                add(i, sku, name, err[0], row[cols["sale"]], err[1])

        imgs = split_images(row[cols["image"]])
        if not imgs:
            add(i, sku, name, "MISSING_IMAGE_FILE", "", "Cot anh rong, SKU khong co file anh.")
        for img in imgs:
            if img not in image_cache:
                image_cache[img] = check_image(img, images_dir, listing)
            if image_cache[img]:
                code, detail = image_cache[img]  # type: ignore[misc]
                add(i, sku, name, code, img, detail)

    report = pd.DataFrame(errors, columns=REPORT_COLS)
    if not report.empty:
        report = report.sort_values(["Dong_CSV", "Ma_Loi"]).reset_index(drop=True)
    report.to_csv(a.output, index=False, encoding="utf-8-sig")

    total, fail = len(df), len(bad)
    print("\n" + "=" * 52)
    print(f"{'Tong so SKU':<22}|{'So SKU Pass':^15}|{'So SKU Fail':^13}")
    print(f"{total:<22}|{total - fail:^15}|{fail:^13}")
    print("=" * 52)
    if not report.empty:
        for code, n in report["Ma_Loi"].value_counts().items():
            print(f"  {code:<20}: {n}")
    print(f"Bao cao: {a.output.resolve()} ({len(report)} dong loi)")
    return 1 if fail else 0


if __name__ == "__main__":
    sys.exit(main())