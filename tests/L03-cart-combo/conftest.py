"""conftest.py - Fixture WebDriver + tự động xuất test-report.md (L03-G7-01).

Chỉ phụ thuộc `pytest` và `selenium`. Không đụng tới mã nguồn Theme/Plugin.
"""
from __future__ import annotations

import os
from datetime import datetime
from pathlib import Path

import pytest
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

REPO_ROOT = Path(__file__).resolve().parents[2]
REPORT_PATH = Path(
    os.getenv("KLEER_REPORT_PATH", REPO_ROOT / "docs" / "flows" / "L03-cart-combo" / "test-report.md")
)
SCREENSHOT_DIR = REPORT_PATH.parent / "screenshots"

# Danh sách kết quả từng test, được điền bởi hook bên dưới
RESULTS: list[dict] = []


def _env_flag(name: str, default: str) -> bool:
    return os.getenv(name, default).strip().lower() in {"1", "true", "yes", "on"}


@pytest.fixture()
def driver():
    """Khởi tạo Chrome. KLEER_HEADLESS=0 để xem trình duyệt chạy thật."""
    options = webdriver.ChromeOptions()
    if _env_flag("KLEER_HEADLESS", "1"):
        options.add_argument("--headless=new")
    options.add_argument("--window-size=1440,1400")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--lang=vi")
    options.add_experimental_option("prefs", {"intl.accept_languages": "vi,vi_VN"})

    drv = webdriver.Chrome(options=options)  # Selenium >= 4.6 tự tải chromedriver
    drv.implicitly_wait(0)  # Chỉ dùng Explicit Wait, tránh trộn hai cơ chế
    yield drv
    drv.quit()


@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item, call):
    outcome = yield
    rep = outcome.get_result()

    is_call_result = rep.when == "call"
    is_setup_problem = rep.when == "setup" and rep.outcome in ("failed", "skipped")
    if not (is_call_result or is_setup_problem):
        return

    status = rep.outcome.upper()  # PASSED / FAILED / SKIPPED
    if rep.when == "setup" and rep.outcome == "failed":
        status = "ERROR"

    note = ""
    if rep.failed:
        crash = getattr(rep.longrepr, "reprcrash", None)
        note = (crash.message if crash else str(rep.longrepr)).replace("\n", " ")[:300]
    elif rep.skipped and isinstance(rep.longrepr, tuple):
        note = str(rep.longrepr[2])[:300]

    shot = ""
    drv = item.funcargs.get("driver") if hasattr(item, "funcargs") else None
    if rep.failed and drv is not None:
        try:
            SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)
            safe = "".join(c if c.isalnum() or c in "-_" else "_" for c in item.name)
            path = SCREENSHOT_DIR / f"{safe}.png"
            drv.save_screenshot(str(path))
            shot = f"screenshots/{path.name}"
        except Exception:  # không để lỗi chụp ảnh làm hỏng báo cáo
            pass

    doc = (getattr(item.obj, "__doc__", "") or "").strip().splitlines()
    RESULTS.append(
        {
            "name": item.name,
            "desc": doc[0].strip() if doc else "",
            "status": status,
            "duration": rep.duration,
            "note": note,
            "shot": shot,
        }
    )


def pytest_sessionfinish(session, exitstatus):
    if not RESULTS:
        return
    total = len(RESULTS)
    count = lambda s: sum(1 for r in RESULTS if r["status"] == s)  # noqa: E731
    passed, failed, skipped, errors = count("PASSED"), count("FAILED"), count("SKIPPED"), count("ERROR")
    rate = f"{passed / total * 100:.1f}%"

    lines = [
        "# Báo cáo kiểm thử tự động - L03 Giỏ hàng & Thanh toán (Checkout)",
        "",
        f"- **Task:** L03-G7-01 | **Người thực hiện:** Nguyễn Ngọc Thảo Uyên (QA & Automation)",
        f"- **Thời điểm chạy:** {datetime.now():%d/%m/%Y %H:%M:%S}",
        f"- **Base URL:** `{os.getenv('KLEER_BASE_URL', 'http://localhost')}`",
        f"- **Script:** `tests/L03-cart-combo/test_checkout_validation.py`",
        "",
        "## Tổng hợp",
        "",
        "| Tổng | Pass | Fail | Error | Skip | Tỷ lệ Pass |",
        "|-----:|-----:|-----:|------:|-----:|-----------:|",
        f"| {total} | {passed} | {failed} | {errors} | {skipped} | {rate} |",
        "",
        "## Chi tiết từng test case",
        "",
        "| # | Test | Mô tả | Kết quả | Thời gian (s) | Ghi chú / Ảnh lỗi |",
        "|--:|------|-------|:-------:|--------------:|-------------------|",
    ]
    icon = {"PASSED": "✅ PASS", "FAILED": "❌ FAIL", "ERROR": "⚠️ ERROR", "SKIPPED": "⏭️ SKIP"}
    for i, r in enumerate(RESULTS, 1):
        note = r["note"].replace("|", "\\|")
        if r["shot"]:
            note += f" ([ảnh]({r['shot']}))"
        lines.append(
            f"| {i} | `{r['name']}` | {r['desc']} | {icon[r['status']]} | {r['duration']:.2f} | {note} |"
        )
    lines += [
        "",
        "> Lưu ý: test `Skip` nghĩa là thiếu điều kiện dữ liệu (coupon/zone ship chưa tạo...), "
        "không được tính là Pass. Xem lý do ở cột Ghi chú.",
        "",
    ]
    REPORT_PATH.parent.mkdir(parents=True, exist_ok=True)
    REPORT_PATH.write_text("\n".join(lines), encoding="utf-8")
    print(f"\n[KLEER] Đã ghi báo cáo: {REPORT_PATH}")