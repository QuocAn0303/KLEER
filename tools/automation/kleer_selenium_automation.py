#!/usr/bin/env python3
import json
import os
import time
from datetime import datetime
from typing import Dict

from dotenv import load_dotenv
from selenium import webdriver
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service


def load_env() -> Dict[str, str]:
    # Load from .env in project root
    project_root = os.path.dirname(os.path.dirname(os.path.dirname(__file__)))
    env_path = os.path.join(project_root, '.env')
    load_dotenv(env_path)
    
    env = {}
    for k in ['KLEER_BASE_URL', 'WP_ADMIN_USER', 'WP_ADMIN_PASS', 'TEST_ORDER_ID', 'TEST_DELETE_PRODUCT_ID', 'TEST_STOCK_PRODUCT_ID']:
        env[k] = os.getenv(k, '')
    return env


def log(msg: str, log_file=None):
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    line = f"[{timestamp}] {msg}"
    print(line)
    if log_file:
        try:
            with open(log_file, 'a', encoding='utf-8') as f:
                f.write(line + '\n')
        except Exception as e:
            print(f"Failed to write log: {e}")


def main():
    env = load_env()
    base_url = env.get('KLEER_BASE_URL', 'http://localhost').rstrip('/')
    wp_admin_user = env.get('WP_ADMIN_USER', '')
    wp_admin_pass = env.get('WP_ADMIN_PASS', '')
    test_order_id = env.get('TEST_ORDER_ID', '')
    test_delete_product_id = env.get('TEST_DELETE_PRODUCT_ID', '')
    test_stock_product_id = env.get('TEST_STOCK_PRODUCT_ID', '')

    log_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'docs', 'flows', 'L10-automation')
    os.makedirs(log_dir, exist_ok=True)
    log_file = os.path.join(log_dir, 'selenium-execution.log')

    chrome_options = Options()
    chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--window-size=1920,1080')
    
    driver = None
    try:
        driver = webdriver.Chrome(options=chrome_options)
        wait = WebDriverWait(driver, 30)
        
        log("Starting Selenium automation", log_file)
        
        # Login to WP Admin
        login_url = f"{base_url}/wp-admin"
        log(f"Navigating to {login_url}", log_file)
        driver.get(login_url)
        
        # Wait for login form
        username_field = wait.until(EC.presence_of_element_located((By.ID, 'user_login')))
        password_field = driver.find_element(By.ID, 'user_pass')
        submit_btn = driver.find_element(By.ID, 'wp-submit')
        
        username_field.clear()
        username_field.send_keys(wp_admin_user)
        password_field.clear()
        password_field.send_keys(wp_admin_pass)
        submit_btn.click()
        
        log("Logged in successfully", log_file)
        
        # Task 1: Update order status to completed in HPOS
        if test_order_id:
            try:
                # Navigate to HPOS orders
                orders_url = f"{base_url}/wp-admin/admin.php?page=wc-orders"
                log(f"Navigating to HPOS orders: {orders_url}", log_file)
                driver.get(orders_url)
                time.sleep(2)
                
                # Try to find order by ID or navigate to edit page
                order_edit_url = f"{base_url}/wp-admin/admin.php?page=wc-orders&action=edit&id={test_order_id}"
                log(f"Navigating to order edit: {order_edit_url}", log_file)
                driver.get(order_edit_url)
                
                # Wait for order status dropdown
                status_select = wait.until(EC.presence_of_element_located((By.ID, 'order_status')))
                from selenium.webdriver.support.ui import Select
                select = Select(status_select)
                select.select_by_value('wc-completed')
                
                # Update order
                update_btn = wait.until(EC.element_to_be_clickable((By.NAME, 'save')))
                update_btn.click()
                
                log(f"Task 1: Order {test_order_id} status updated to completed", log_file)
                time.sleep(2)
            except Exception as e:
                log(f"Task 1 Exception: {e}", log_file)
        else:
            log("Task 1: TEST_ORDER_ID not provided", log_file)
        
        # Task 2: Delete product
        if test_delete_product_id:
            try:
                product_edit_url = f"{base_url}/wp-admin/post.php?post={test_delete_product_id}&action=edit"
                log(f"Navigating to product edit: {product_edit_url}", log_file)
                driver.get(product_edit_url)
                time.sleep(2)
                
                # Move to trash or delete permanently
                # Try to find move to trash button
                try:
                    trash_btn = wait.until(EC.element_to_be_clickable((By.ID, 'submitdiv')))
                    # Look for delete link
                    delete_link = driver.find_element(By.CSS_SELECTOR, '.submitdelete')
                    delete_link.click()
                    log(f"Task 2: Product {test_delete_product_id} moved to trash/deleted", log_file)
                except Exception as e:
                    log(f"Task 2 alternative delete: {e}", log_file)
                time.sleep(2)
            except Exception as e:
                log(f"Task 2 Exception: {e}", log_file)
        else:
            log("Task 2: TEST_DELETE_PRODUCT_ID not provided", log_file)
        
        # Task 3: Update stock quantity
        if test_stock_product_id:
            try:
                product_edit_url = f"{base_url}/wp-admin/post.php?post={test_stock_product_id}&action=edit"
                log(f"Navigating to product edit: {product_edit_url}", log_file)
                driver.get(product_edit_url)
                time.sleep(2)
                
                # Enable manage stock if not enabled
                try:
                    manage_stock = driver.find_element(By.ID, '_manage_stock')
                    if not manage_stock.is_selected():
                        manage_stock.click()
                    time.sleep(1)
                except Exception as e:
                    log(f"Manage stock checkbox: {e}", log_file)
                
                # Update stock quantity
                stock_qty = wait.until(EC.presence_of_element_located((By.ID, '_stock')))
                stock_qty.clear()
                stock_qty.send_keys('88')
                
                update_btn = wait.until(EC.element_to_be_clickable((By.ID, 'publish')))
                update_btn.click()
                
                log(f"Task 3: Product {test_stock_product_id} stock updated to 88", log_file)
                time.sleep(2)
            except Exception as e:
                log(f"Task 3 Exception: {e}", log_file)
        else:
            log("Task 3: TEST_STOCK_PRODUCT_ID not provided", log_file)
        
        log("Selenium automation completed", log_file)
        
    except Exception as e:
        log(f"Fatal error: {e}", log_file)
        raise
    finally:
        if driver:
            driver.quit()


if __name__ == '__main__':
    main()
