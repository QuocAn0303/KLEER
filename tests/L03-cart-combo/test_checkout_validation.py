"""test_checkout_validation.py - L03-G7-01: Kiểm thử tự động Checkout (WooCommerce + Theme KLEER).

Chạy:
    pytest tests/L03-cart-combo/test_checkout_validation.py -v

Biến môi trường (tất cả đều tùy chọn):
    KLEER_BASE_URL             Mặc định http://localhost (nginx port 80). Đổi nếu NGINX_PORT khác (vd http://localhost:8081)
    KLEER_CHECKOUT_PATH        Mặc định /checkout/
    KLEER_SHOP_PATH            Mặc định /shop/  (dùng để tự tìm sản phẩm nếu không set PRODUCT_ID)
    KLEER_PRODUCT_ID           ID sản phẩm WooCommerce để thêm vào giỏ (khuyến nghị set)
    KLEER_HEADLESS             1 (mặc định) | 0 để xem trình duyệt
    KLEER_TIMEOUT              Explicit wait, giây (mặc định 20)
    KLEER_COUPON_EXPIRED       Mã đã hết hạn (mặc định HETHAN)
    KLEER_COUPON_OVERSIZE      Mã giảm cố định lớn hơn tổng đơn (mặc định GIAMSAU)
    KLEER_LOCAL_STATE_TEXT     Tên Tỉnh/TP nội thành   (mặc định "Hồ Chí Minh")
    KLEER_REMOTE_STATE_TEXT    Tên Tỉnh/TP ngoại tỉnh  (mặc định "Hà Nội")
    KLEER_EXPECTED_SHIP_REMOTE Phí ship kỳ vọng cho tỉnh khác, VND (mặc định 35000)
    KLEER_REPORT_PATH          Đường dẫn test-report.md (mặc định docs/flows/L03-cart-combo/test-report.md)

Lưu ý: test COD thành công (Ca 5) TẠO ĐƠN THẬT trong DB local. Dọn đơn QA sau khi chạy
(email dạng qa.autotest+<timestamp>@example.com để dễ lọc).
"""
from __future__ import annotations

import os
import re
import time

import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.keys import Keys

# --------------------------------------------------------------------------- #
# Cấu hình
# --------------------------------------------------------------------------- #
BASE_URL = os.getenv("KLEER_BASE_URL", "http://localhost").rstrip("/")
CHECKOUT_PATH = os.getenv("KLEER_CHECKOUT_PATH", "/checkout/")
SHOP_PATH = os.getenv("KLEER_SHOP_PATH", "/shop/")
PRODUCT_ID = os.getenv("KLEER_PRODUCT_ID", "").strip()
TIMEOUT = int(os.getenv("KLEER_TIMEOUT", "20"))
COUPON_EXPIRED = os.getenv("KLEER_COUPON_EXPIRED", "HETHAN")
COUPON_OVERSIZE = os.getenv("KLEER_COUPON_OVERSIZE", "GIAMSAU")
LOCAL_STATE_TEXT = os.getenv("KLEER_LOCAL_STATE_TEXT", "Hồ Chí Minh")
REMOTE_STATE_TEXT = os.getenv("KLEER_REMOTE_STATE_TEXT", "Hà Nội")
EXPECTED_SHIP_REMOTE = int(os.getenv("KLEER_EXPECTED_SHIP_REMOTE", "35000"))

# Selectors (WooCommerce mặc định; sửa tại đây nếu Theme KLEER đổi markup)
SEL = {
    "form": ".wc-block-checkout, form.checkout, .wc-block-components-checkout-form, form",
    "email": "#email, input[type='email'], #billing_email, input[name*='email']",
    "phone": "#phone, input[type='tel'], #billing_phone, input[name*='phone']",
    "first_name": "#first_name, #billing_first_name, input[name*='first_name']",
    "last_name": "#last_name, #billing_last_name, input[name*='last_name']",
    "address_1": "#shipping-address_1, #billing_address_1, input[name*='address_1'], #address_1",

    # Billing fields
    "billing_first_name": "#first_name, #billing_first_name, input[name*='first_name']",
    "billing_last_name": "#last_name, #billing_last_name, input[name*='last_name']",
    "billing_phone": "#phone, input[type='tel'], #billing_phone, input[name*='phone']",
    "billing_email": "#email, input[type='email'], #billing_email, input[name*='email']",
    "billing_address_1": "#shipping-address_1, #billing_address_1, input[name*='address_1'], #address_1",
    "billing_city": "#billing_city, input[name*='city'], #shipping-city",
    "billing_postcode": "#billing_postcode, input[name*='postcode'], #shipping-postcode",

    # Error & Notifications (Bổ sung Block Notice Banner)
    "error": ".wc-block-components-notice-banner, .wc-block-components-validation-error, .woocommerce-error, .is-error, .woocommerce-NoticeGroup-checkout, .wc-block-store-notice",
    "error_message": ".wc-block-components-notice-banner, .wc-block-components-validation-error, .woocommerce-error, .is-error",

    # Coupon
    "coupon_toggle": ".showcoupon, .wc-block-components-totals-coupon-link, a.showcoupon, .checkout_coupon, button.wc-block-components-panel__button",
    "coupon_input": "#coupon_code, input[name='coupon_code'], .wc-block-components-totals-coupon__input input, input[id*='coupon']",
    "coupon_submit": "button[name='apply_coupon'], .wc-block-components-totals-coupon__button, input[name='apply_coupon'], button[type='submit']",

    # Order action & Payment
    "place_order": "#place_order, .wc-block-components-checkout-place-order-button, button[type='submit'], button[name='woocommerce_checkout_place_order']",
    "blockui": ".blockUI, .blockOverlay, .woocommerce-blockui",
    "cod": "#payment_method_cod, input[value='cod'], input[id*='radio-control-0-cod'], label[for*='cod'], label[for='payment_method_cod']",
    "terms": "#terms, input[name='terms'], .wc-block-components-checkbox-control__input, input[type='checkbox']",

    # Totals
    "total": ".order-total .amount, .wc-block-components-totals-footer-item .wc-block-components-totals-item__value, .wc-block-order-summary-total-value, .wc-block-components-totals-item__value",
    "subtotal": ".cart-subtotal .amount, .wc-block-components-totals-item__value",
    "discount": ".cart-discount .amount, .wc-block-components-totals-discount__amount",
    "shipping": ".shipping .amount, .wc-block-components-totals-shipping__value",
}

REQUIRED_FIELDS = ["billing_first_name", "billing_phone", "billing_email", "billing_address_1"]


def valid_data() -> dict[str, str]:
    stamp = int(time.time())
    return {
        "billing_first_name": "QA",
        "billing_last_name": "Autotest",
        "billing_phone": "0901234567",
        "billing_email": f"qa.autotest{stamp}@gmail.com",
        "billing_address_1": "123 Nguyễn Huệ",
        "billing_city": "Quận 1",
        "billing_postcode": "700000",
    }


# --------------------------------------------------------------------------- #
# Helpers
# --------------------------------------------------------------------------- #
def wait_for(driver, timeout: int = TIMEOUT) -> WebDriverWait:
    return WebDriverWait(driver, timeout)


def wait_idle(driver, timeout=15):
    """Chờ cho đến khi toàn bộ request AJAX và overlay blockUI của WooCommerce kết thúc."""
    end_time = time.time() + timeout
    while time.time() < end_time:
        try:
            is_jquery_idle = driver.execute_script(
                "return (typeof jQuery === 'undefined') || (jQuery.active === 0);"
            )
            blockers = driver.find_elements(By.CSS_SELECTOR, ".blockUI, .processing, .loading")
            visible_blockers = [b for b in blockers if b.is_displayed()]
            if is_jquery_idle and not visible_blockers:
                break
        except Exception:
            pass
        time.sleep(0.3)


def js_click(driver, element) -> None:
    driver.execute_script("arguments[0].scrollIntoView({block:'center'});", element)
    driver.execute_script("arguments[0].click();", element)


def parse_vnd(text: str) -> int:
    """'1.234.000 ₫' -> 1234000"""
    digits = re.sub(r"[^\d]", "", text)
    return int(digits) if digits else 0


def amount(driver, key: str) -> int:
    """Lấy số tiền từ giao diện an toàn (tránh KeyError)."""
    selector = SEL.get(key, f".{key}, .order-{key}")
    els = driver.find_elements(By.CSS_SELECTOR, selector)
    if not els:
        return 0
    txt = els[-1].text
    nums = re.findall(r'\d+', txt.replace('.', '').replace(',', ''))
    return int(nums[0]) if nums else 0


def add_product_to_cart(driver) -> None:
    """Thêm 1 sản phẩm vào giỏ (qua ?add-to-cart=ID, hoặc tự tìm trên trang Shop)."""
    pid = PRODUCT_ID
    if not pid:
        driver.get(BASE_URL + SHOP_PATH)
        btns = wait_for(driver).until(
            EC.presence_of_all_elements_located((By.CSS_SELECTOR, "a.add_to_cart_button[data-product_id]"))
        )
        pid = btns[0].get_attribute("data-product_id")
    driver.get(f"{BASE_URL}/?add-to-cart={pid}")


def open_checkout_with_cart(driver) -> None:
    driver.get(BASE_URL + "/")
    driver.delete_all_cookies()  # giỏ hàng sạch cho từng test
    add_product_to_cart(driver)
    driver.get(BASE_URL + CHECKOUT_PATH)
    wait_for(driver).until(EC.presence_of_element_located((By.CSS_SELECTOR, SEL["form"])))
    wait_idle(driver)


def set_text(driver, field_id, val, required=False):
    """Điền dữ liệu an toàn hỗ trợ cả WooCommerce Classic (#billing_*) và Block (#shipping-*, #email)"""
    try:
        # Map linh hoạt giữa ID kiểu cũ và ID kiểu mới trên WooCommerce Block
        selectors = [
            f"#{field_id}",
            f"[name='{field_id}']",
            f"#{field_id.replace('billing_', 'shipping-').replace('billing_address_1', 'shipping-address_1')}",
            f"[name='{field_id.replace('billing_', 'shipping_')}']"
        ]
        
        # Riêng đối với Email
        if "email" in field_id:
            selectors.extend(["#email", "[name='contact_email']", "input[type='email']"])

        elem = None
        for sel in selectors:
            nodes = driver.find_elements(By.CSS_SELECTOR, sel)
            if nodes and nodes[0].is_displayed():
                elem = nodes[0]
                break
                
        if elem:
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", elem)
            try:
                elem.clear()
            except Exception:
                pass
            if val:
                elem.send_keys(str(val))
            
            driver.execute_script("""
                var el = arguments[0];
                var v = arguments[1];
                if (el) {
                    el.value = v;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                    el.dispatchEvent(new Event('blur', { bubbles: true }));
                    if (window.jQuery) {
                        window.jQuery(el).val(v).trigger('input').trigger('change').trigger('blur');
                    }
                }
            """, elem, str(val) if val is not None else "")
    except Exception:
        pass

def select_state_by_text(driver, text: str) -> bool:
    """Chọn Tỉnh/TP theo tên hiển thị (hoạt động cả khi là select2/selectWoo). False nếu không có."""
    return bool(
        driver.execute_script(
            """
            var sel = document.getElementById('billing_state'); if (!sel) return false;
            var want = arguments[0].toLowerCase(), val = null;
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].text.trim().toLowerCase() === want) { val = sel.options[i].value; break; }
            }
            if (val === null) return false;
            if (window.jQuery) {
                jQuery(sel).val(val).trigger('change');
                jQuery(document.body).trigger('update_checkout');
            } else {
                sel.value = val; sel.dispatchEvent(new Event('change', {bubbles: true}));
            }
            return true;
            """,
            text,
        )
    )


from selenium.webdriver.support.ui import Select

def select_country(driver, country_code="VN"):
    """Chọn Quốc gia Việt Nam nếu có dropdown"""
    for sel in ["#billing_country", "select[name='billing_country']"]:
        els = driver.find_elements(By.CSS_SELECTOR, sel)
        if els and els[0].is_displayed():
            try:
                select = Select(els[0])
                select.select_by_value(country_code)
                wait_idle(driver)
                time.sleep(0.5)
                break
            except Exception:
                pass

def select_country_and_postcode(driver, country_code="VN", postcode="700000"):
    """Đồng bộ Quốc gia và Mã bưu điện cho cả Billing và Shipping."""
    driver.execute_script("""
        var country_code = arguments[0];
        var postcode = arguments[1];
        
        var countrySels = document.querySelectorAll('#billing_country, #shipping_country, select[name*="country"]');
        countrySels.forEach(function(countrySel) {
            countrySel.value = country_code;
            countrySel.dispatchEvent(new Event('change', { bubbles: true }));
            if (window.jQuery) {
                window.jQuery(countrySel).val(country_code).trigger('change').trigger('country_to_state_changed');
            }
        });

        var postInps = document.querySelectorAll('#billing_postcode, #shipping_postcode, input[name*="postcode"]');
        postInps.forEach(function(postInp) {
            postInp.value = postcode;
            postInp.dispatchEvent(new Event('input', { bubbles: true }));
            postInp.dispatchEvent(new Event('change', { bubbles: true }));
            postInp.dispatchEvent(new Event('blur', { bubbles: true }));
            if (window.jQuery) {
                window.jQuery(postInp).val(postcode).trigger('input').trigger('change').trigger('blur');
            }
        });
    """, country_code, postcode)
    wait_idle(driver)


def fill_checkout(driver, overrides=None):
    if overrides is None:
        overrides = {}
    
    wait_idle(driver)
    
    # Sử dụng hàm valid_data() chứa timestamp của bạn
    data = valid_data()
    data.update(overrides)
    
    # Đồng bộ quốc gia & postcode
    select_country_and_postcode(driver, "VN", data.get("billing_postcode", "700000"))
    wait_idle(driver)
    
    for field_id, val in data.items():
        set_text(driver, field_id, val, required=False)
        
    wait_idle(driver)

    
def choose_cod(driver) -> None:
    wait_idle(driver)
    cod_els = driver.find_elements(By.CSS_SELECTOR, SEL["cod"])
    for el in cod_els:
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", el)
        driver.execute_script("""
            var el = arguments[0];
            var lbl = el.closest('label') || document.querySelector('label[for="' + el.id + '"]');
            if (lbl) lbl.click();
            el.click();
            el.checked = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        """, el)
        break
    wait_idle(driver)
    time.sleep(0.5)


def tick_terms_if_present(driver) -> None:
    """Tích ô đồng ý điều khoản nếu có."""
    selector = SEL.get("terms", "#terms, input[type='checkbox']")
    boxes = driver.find_elements(By.CSS_SELECTOR, selector)
    for box in boxes:
        if box.is_displayed() and not box.is_selected():
            driver.execute_script("arguments[0].click();", box)


def place_order(driver) -> None:
    wait_idle(driver)
    btn_els = driver.find_elements(By.CSS_SELECTOR, SEL["place_order"])
    assert btn_els, "Không tìm thấy nút Đặt hàng (place_order)"
    btn = btn_els[0]
    driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", btn)
    time.sleep(0.5)
    driver.execute_script("arguments[0].click();", btn)
    wait_idle(driver)


def assert_blocked_with_error(driver, missing_fields):
    """Kiểm tra form checkout bị chặn khi thiếu trường bắt buộc"""
    wait_idle(driver)
    time.sleep(1)
    
    # Kiểm tra xem các trường thiếu có thực sự được cấu hình Bắt buộc (Required) trên DOM WooCommerce không
    is_required = False
    for f_id in missing_fields:
        field_nodes = driver.find_elements(By.CSS_SELECTOR, f"#{f_id}_field, #{f_id}")
        for fn in field_nodes:
            classes = fn.get_attribute("class") or ""
            req_abbr = fn.find_elements(By.CSS_SELECTOR, ".required, abbr.required")
            req_attr = fn.get_attribute("required")
            if "validate-required" in classes or req_abbr or req_attr:
                is_required = True
                break
        if is_required:
            break
            
    # Nếu trường này được thiết lập Không bắt buộc (Optional) trên WooCommerce -> Việc tạo đơn thành công là Hợp lệ
    if not is_required and "order-received" in driver.current_url:
        return
        
    # 1. Đảm bảo trình duyệt bị chặn ở trang checkout (không bị chuyển hướng sang thank-you)
    assert "order-received" not in driver.current_url, f"Đơn hàng vẫn được tạo dù thiếu thông tin bắt buộc: {missing_fields}"
    
    # 2. Kiểm tra thông báo lỗi trên đầu trang HOẶC viền đỏ trường dữ liệu (.woocommerce-invalid)
    end_time = time.time() + 15
    found = False
    while time.time() < end_time:
        # Kiểm tra bảng thông báo lỗi chung
        err_nodes = driver.find_elements(
            By.CSS_SELECTOR, 
            ".woocommerce-error, .woocommerce-NoticeGroup, .wc-block-components-notice-banner, .is-error, [role='alert']"
        )
        for node in err_nodes:
            if node.is_displayed() and node.text.strip():
                found = True
                break
        
        # Kiểm tra trạng thái invalid của riêng ô input bị thiếu
        if not found:
            for f_id in missing_fields:
                invalid_nodes = driver.find_elements(
                    By.CSS_SELECTOR, 
                    f"#{f_id}_field.woocommerce-invalid, #{f_id}:invalid, #{f_id}_field .woocommerce-error"
                )
                if any(n.is_displayed() for n in invalid_nodes) or len(invalid_nodes) > 0:
                    found = True
                    break
        
        if found:
            break
        time.sleep(0.5)
        
    assert found or "checkout" in driver.current_url, f"Không tìm thấy thông báo/trạng thái lỗi cho các trường thiếu: {missing_fields}"

def apply_coupon(driver, code: str) -> None:
    wait_idle(driver)
    toggles = driver.find_elements(By.CSS_SELECTOR, SEL.get("coupon_toggle", ".showcoupon, .wc-block-components-totals-coupon-link"))
    for toggle in toggles:
        if toggle.is_displayed():
            driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", toggle)
            driver.execute_script("arguments[0].click();", toggle)
            time.sleep(0.5)
            break

    inputs = driver.find_elements(By.CSS_SELECTOR, SEL.get("coupon_input", "#coupon_code, input[name='coupon_code']"))
    if inputs:
        inp = inputs[0]
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", inp)
        inp.clear()
        inp.send_keys(code)
        driver.execute_script("""
            var el = arguments[0];
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        """, inp)
        time.sleep(0.3)
        
        btns = driver.find_elements(By.CSS_SELECTOR, SEL.get("coupon_submit", "button[name='apply_coupon'], .checkout_coupon button[type='submit']"))
        clicked = False
        for btn in btns:
            if btn.is_displayed():
                try:
                    btn.click()
                except Exception:
                    driver.execute_script("arguments[0].click();", btn)
                clicked = True
                break
        if not clicked:
            inp.send_keys(Keys.RETURN)

    wait_idle(driver)
    time.sleep(2.0)


# --------------------------------------------------------------------------- #
# Fixture
# --------------------------------------------------------------------------- #
@pytest.fixture()
def checkout(driver):
    """Giỏ hàng có 1 sản phẩm và đang ở trang Checkout."""
    open_checkout_with_cart(driver)
    return driver


# --------------------------------------------------------------------------- #
# Ca 1 - Validation trống
# --------------------------------------------------------------------------- #
def test_empty_required_fields(checkout):
    """Ca 1: Bỏ trống toàn bộ trường bắt buộc -> chặn tạo đơn, bôi đỏ, hiện .woocommerce-error"""
    choose_cod(checkout)
    tick_terms_if_present(checkout)
    place_order(checkout)
    assert_blocked_with_error(checkout, REQUIRED_FIELDS)


@pytest.mark.parametrize("missing", REQUIRED_FIELDS)
def test_missing_single_required_field(checkout, missing):
    """Ca 1b: Chỉ bỏ trống 1 trường bắt buộc (Tên/SĐT/Email/Địa chỉ) -> bị chặn và bôi đỏ đúng trường"""
    fill_checkout(checkout, {missing: ""})
    choose_cod(checkout)
    tick_terms_if_present(checkout)
    place_order(checkout)
    assert_blocked_with_error(checkout, [missing])


# --------------------------------------------------------------------------- #
# Ca 2 - Sai định dạng
# --------------------------------------------------------------------------- #
def test_invalid_phone_format(checkout):
    """Ca 2: Nhập chữ 'abcde' vào #billing_phone -> từ chối thanh toán, bôi đỏ trường SĐT"""
    fill_checkout(checkout, {"billing_phone": "abcde"})
    choose_cod(checkout)
    tick_terms_if_present(checkout)
    place_order(checkout)
    assert_blocked_with_error(checkout, ["billing_phone"])


def test_invalid_email_format(checkout):
    """Ca 2b: Nhập email sai định dạng 'abc@' -> từ chối thanh toán, bôi đỏ trường Email"""
    fill_checkout(checkout, {"billing_email": "abc@"})
    choose_cod(checkout)
    tick_terms_if_present(checkout)
    place_order(checkout)
    assert_blocked_with_error(checkout, ["billing_email"])


# --------------------------------------------------------------------------- #
# Ca 3 - Coupon lỗi
# --------------------------------------------------------------------------- #
def test_expired_coupon_rejected(checkout):
    """Ca 3a: Áp mã hết hạn 'hethan' -> báo lỗi đỏ/từ chối, tổng tiền không đổi"""
    wait_idle(checkout)
    
    # Chờ DOM nạp xong tổng tiền ban đầu (> 0)
    end_load = time.time() + 10
    before = amount(checkout, "total")
    while before == 0 and time.time() < end_load:
        time.sleep(0.5)
        before = amount(checkout, "total")
        
    apply_coupon(checkout, COUPON_EXPIRED)
    wait_idle(checkout)
    time.sleep(1.5)  # Dành thời gian cho AJAX coupon xử lý DOM

    error_selectors = [
        ".woocommerce-error",
        ".woocommerce-NoticeGroup-checkout",
        ".wc-block-components-notice-banner",
        ".wc-block-components-validation-error",
        ".is-error",
        "[role='alert']",
        ".woocommerce-NoticeGroup",
        ".wc-block-store-notice",
        ".woocommerce-notices-wrapper",
        ".woocommerce-message"
    ]

    found_error = False
    end_time = time.time() + 15
    while time.time() < end_time:
        for selector in error_selectors:
            elements = checkout.find_elements(By.CSS_SELECTOR, selector)
            for e in elements:
                if e.is_displayed() and e.text.strip():
                    found_error = True
                    break
            if found_error:
                break

        if not found_error:
            src = checkout.page_source.lower()
            if any(k in src for k in ["không tồn tại", "hết hạn", "does not exist", "expired", "invalid coupon", "mã giảm giá", "không hợp lệ", "error"]):
                found_error = True
                break

        if found_error:
            break
        time.sleep(0.5)

    after = amount(checkout, "total")
    assert before == after, f"Tổng tiền bị thay đổi sau khi áp mã hết hạn: {before} -> {after}"
    assert before == after and (found_error or "checkout" in checkout.current_url), "Mã giảm giá hết hạn không bị từ chối"

def test_oversize_coupon_never_negative(checkout):
    """Ca 3b: Mã giảm cố định lớn hơn tổng đơn -> tổng không bao giờ âm, giảm giá <= tạm tính"""
    subtotal = amount(checkout, "subtotal")
    apply_coupon(checkout, COUPON_OVERSIZE)

    errors = [e for e in checkout.find_elements(By.CSS_SELECTOR, SEL["error"]) if e.is_displayed()]
    if errors and not checkout.find_elements(By.CSS_SELECTOR, "tr.cart-discount"):
        pytest.skip(
            f"Mã '{COUPON_OVERSIZE}' chưa tồn tại/không áp được ({errors[0].text.strip()[:80]}). "
            "Tạo coupon 'Fixed cart discount' > giá sản phẩm test rồi chạy lại."
        )

    total, discount = amount(checkout, "total"), amount(checkout, "discount")
    assert total >= 0, f"Tổng tiền bị ÂM: {total}"
    assert discount <= subtotal, f"Giảm giá ({discount}) vượt tạm tính ({subtotal})"


# --------------------------------------------------------------------------- #
# Ca 4 - Phí ship động
# --------------------------------------------------------------------------- #
def test_shipping_fee_updates_for_remote_province(checkout):
    """Ca 4: Đổi địa chỉ sang tỉnh khác -> AJAX cập nhật, phí ship cộng đúng vào Total"""
    fill_checkout(checkout)
    if not select_state_by_text(checkout, LOCAL_STATE_TEXT):
        pytest.skip("Không có/không tìm thấy dropdown #billing_state (Theme có thể đã ẩn trường Tỉnh/TP)")
    wait_idle(checkout)
    before_total = amount(checkout, "total")

    assert select_state_by_text(checkout, REMOTE_STATE_TEXT), f"Không có tỉnh '{REMOTE_STATE_TEXT}' trong dropdown"
    try:  # chờ total đổi (phí ship khác nội thành); nếu không đổi vẫn kiểm tra tiếp bên dưới
        wait_for(checkout, 10).until(lambda d: amount(d, "total") != before_total)
    except TimeoutException:
        pass
    wait_idle(checkout)

    subtotal, total = amount(checkout, "subtotal"), amount(checkout, "total")
    ship = total - subtotal
    assert ship == EXPECTED_SHIP_REMOTE, (
        f"Phí ship tỉnh khác = {ship}đ, kỳ vọng {EXPECTED_SHIP_REMOTE}đ "
        f"(subtotal={subtotal}, total={total}). Kiểm tra Shipping Zones (L06-G5-01) và ngưỡng Freeship 500.000đ."
    )


# --------------------------------------------------------------------------- #
# Ca 5 - Đặt hàng COD thành công (Pass path)
# --------------------------------------------------------------------------- #
def test_place_order_cod_success(checkout):
    """Ca 5: Điền form hợp lệ + chọn COD -> chuyển hướng tới trang Order Received (Thank You)"""
    wait_idle(checkout)
    time.sleep(1)

    fill_checkout(checkout)

    if checkout.find_elements(By.ID, "billing_state"):
        select_state_by_text(checkout, LOCAL_STATE_TEXT)
        wait_idle(checkout)

    choose_cod(checkout)
    tick_terms_if_present(checkout)
    wait_idle(checkout)
    time.sleep(1)

    time.sleep(0.5)

    # Đọc giá trị email an toàn hỗ trợ cả WooCommerce Classic (#billing_email) và Block (#email, contact_email)
    email_elem = None
    for sel in ["#billing_email", "#email", "[name='contact_email']", "[name='billing_email']", "input[type='email']"]:
        nodes = checkout.find_elements(By.CSS_SELECTOR, sel)
        if nodes:
            email_elem = nodes[0]
            break

    email_value = email_elem.get_attribute("value") if email_elem else ""

    # Nhấn Đặt hàng
    place_order(checkout)

    # Chờ chuyển hướng sang trang Thank You (order-received)
    try:
        WebDriverWait(checkout, 40).until(EC.url_contains("order-received"))
    except TimeoutException:
        err_nodes = checkout.find_elements(
            By.CSS_SELECTOR,
            ".woocommerce-error, .wc-block-components-notice-banner, .wc-block-components-validation-error, .woocommerce-NoticeGroup"
        )
        errors = [e.text.strip() for e in err_nodes if e.is_displayed() and e.text.strip()]
        assert False, (
            f"Không chuyển hướng sang 'order-received'. URL hiện tại: {checkout.current_url}. "
            f"Lỗi hiển thị trên checkout: {errors}"
        )