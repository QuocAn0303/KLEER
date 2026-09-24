#!/usr/bin/env python3
"""Extract product cards from a saved HTML page.

The crawler intentionally accepts local HTML fixtures so development and tests do
not depend on third-party sites or violate their robots/terms of service.
"""

from __future__ import annotations

import argparse
import json
from html.parser import HTMLParser
from pathlib import Path


class ProductParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.products: list[dict[str, str]] = []
        self._current: dict[str, str] | None = None
        self._field: str | None = None

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        attributes = dict(attrs)
        classes = set((attributes.get("class") or "").split())
        if tag == "article" and "product-card" in classes:
            self._current = {"name": "", "price": "", "url": attributes.get("data-url", "")}
        elif self._current is not None and tag in {"h2", "span"}:
            if "product-name" in classes:
                self._field = "name"
            elif "product-price" in classes:
                self._field = "price"

    def handle_data(self, data: str) -> None:
        if self._current is not None and self._field is not None:
            self._current[self._field] += " ".join(data.split())

    def handle_endtag(self, tag: str) -> None:
        if tag == "article" and self._current is not None:
            self.products.append(self._current)
            self._current = None
            self._field = None
        elif tag in {"h2", "span"}:
            self._field = None


def extract_products(source: str, limit: int = 5) -> list[dict[str, str]]:
    parser = ProductParser()
    parser.feed(source)
    return parser.products[:limit]


def main() -> None:
    argument_parser = argparse.ArgumentParser()
    argument_parser.add_argument("html_file", type=Path)
    argument_parser.add_argument("--limit", type=int, default=5)
    arguments = argument_parser.parse_args()
    products = extract_products(arguments.html_file.read_text(encoding="utf-8"), arguments.limit)
    print(json.dumps(products, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
