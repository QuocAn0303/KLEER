import unittest
from pathlib import Path

from product_crawler import extract_products


class ProductCrawlerTest(unittest.TestCase):
    def test_extracts_five_product_cards(self) -> None:
        fixture = Path(__file__).parent / "fixtures" / "products.html"
        products = extract_products(fixture.read_text(encoding="utf-8"))

        self.assertEqual(len(products), 5)
        self.assertEqual(products[0]["name"], "Gentle Daily Cleanser")
        self.assertEqual(products[-1]["url"], "/products/toner")


if __name__ == "__main__":
    unittest.main()
