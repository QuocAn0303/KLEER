#!/usr/bin/env python3
import json
import os
import sys
import time
from datetime import datetime
from typing import Any, Dict, Optional

import requests
from requests.auth import HTTPBasicAuth


def load_env() -> Dict[str, str]:
    env = {}
    env_path = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), '.env')
    if os.path.exists(env_path):
        with open(env_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#'):
                    continue
                if '=' in line:
                    key, value = line.split('=', 1)
                    env[key.strip()] = value.strip()
    # Override with actual environment
    for k in ['KLEER_BASE_URL', 'WC_CONSUMER_KEY', 'WC_CONSUMER_SECRET', 'TEST_ORDER_ID', 'TEST_DELETE_PRODUCT_ID', 'TEST_STOCK_PRODUCT_ID']:
        if k in os.environ:
            env[k] = os.environ[k]
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


def make_request_with_retry(session: requests.Session, method: str, url: str, auth: HTTPBasicAuth, json_data: Optional[Dict[str, Any]] = None, retries: int = 3, timeout: int = 30):
    for attempt in range(1, retries + 1):
        try:
            if method.upper() == 'PUT':
                resp = session.put(url, auth=auth, json=json_data, timeout=timeout)
            elif method.upper() == 'DELETE':
                resp = session.delete(url, auth=auth, timeout=timeout)
            elif method.upper() == 'POST':
                resp = session.post(url, auth=auth, json=json_data, timeout=timeout)
            elif method.upper() == 'GET':
                resp = session.get(url, auth=auth, timeout=timeout)
            else:
                resp = session.request(method, url, auth=auth, json=json_data, timeout=timeout)
            return resp
        except requests.exceptions.RequestException as e:
            if attempt == retries:
                raise
            time.sleep(2 ** attempt)
    raise Exception("Max retries exceeded")


def main():
    env = load_env()
    base_url = env.get('KLEER_BASE_URL', 'http://localhost').rstrip('/')
    consumer_key = env.get('WC_CONSUMER_KEY', '')
    consumer_secret = env.get('WC_CONSUMER_SECRET', '')
    test_order_id = env.get('TEST_ORDER_ID', '')
    test_delete_product_id = env.get('TEST_DELETE_PRODUCT_ID', '')
    test_stock_product_id = env.get('TEST_STOCK_PRODUCT_ID', '')

    log_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), 'docs', 'flows', 'L10-automation')
    os.makedirs(log_dir, exist_ok=True)
    log_file = os.path.join(log_dir, 'rest-execution.log')

    auth = HTTPBasicAuth(consumer_key, consumer_secret)
    session = requests.Session()
    session.headers.update({
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    })

    results = []

    # Task 1: Update order status to completed
    if test_order_id:
        try:
            url = f"{base_url}/wp-json/wc/v3/orders/{test_order_id}"
            log(f"Task 1: Updating order {test_order_id} to completed", log_file)
            resp = make_request_with_retry(session, 'PUT', url, auth, json_data={'status': 'completed'})
            log(f"Task 1 HTTP Status: {resp.status_code}", log_file)
            try:
                data = resp.json()
                log(f"Task 1 Response: {json.dumps(data, ensure_ascii=False)}", log_file)
            except Exception as e:
                log(f"Task 1 Response parse error: {e}", log_file)
                data = resp.text
            results.append({'task': 'update_order_completed', 'order_id': test_order_id, 'status_code': resp.status_code, 'response': data})
            if resp.status_code == 401:
                log("Task 1: Authentication failed (401) - check WC_CONSUMER_KEY/SECRET", log_file)
            elif resp.status_code == 404:
                log("Task 1: Order not found (404)", log_file)
        except Exception as e:
            log(f"Task 1 Exception: {e}", log_file)
            results.append({'task': 'update_order_completed', 'order_id': test_order_id, 'error': str(e)})
    else:
        log("Task 1: TEST_ORDER_ID not provided", log_file)

    # Task 2: Delete product permanently
    if test_delete_product_id:
        try:
            url = f"{base_url}/wp-json/wc/v3/products/{test_delete_product_id}?force=true"
            log(f"Task 2: Deleting product {test_delete_product_id} permanently", log_file)
            resp = make_request_with_retry(session, 'DELETE', url, auth)
            log(f"Task 2 HTTP Status: {resp.status_code}", log_file)
            try:
                data = resp.json()
                log(f"Task 2 Response: {json.dumps(data, ensure_ascii=False)}", log_file)
            except Exception as e:
                log(f"Task 2 Response parse error: {e}", log_file)
                data = resp.text
            results.append({'task': 'delete_product', 'product_id': test_delete_product_id, 'status_code': resp.status_code, 'response': data})
            if resp.status_code == 401:
                log("Task 2: Authentication failed (401)", log_file)
            elif resp.status_code == 404:
                log("Task 2: Product not found (404)", log_file)
        except Exception as e:
            log(f"Task 2 Exception: {e}", log_file)
            results.append({'task': 'delete_product', 'product_id': test_delete_product_id, 'error': str(e)})
    else:
        log("Task 2: TEST_DELETE_PRODUCT_ID not provided", log_file)

    # Task 3: Update stock quantity
    if test_stock_product_id:
        try:
            url = f"{base_url}/wp-json/wc/v3/products/{test_stock_product_id}"
            log(f"Task 3: Updating stock quantity for product {test_stock_product_id} to 88", log_file)
            resp = make_request_with_retry(session, 'PUT', url, auth, json_data={'manage_stock': True, 'stock_quantity': 88})
            log(f"Task 3 HTTP Status: {resp.status_code}", log_file)
            try:
                data = resp.json()
                log(f"Task 3 Response: {json.dumps(data, ensure_ascii=False)}", log_file)
            except Exception as e:
                log(f"Task 3 Response parse error: {e}", log_file)
                data = resp.text
            results.append({'task': 'update_stock', 'product_id': test_stock_product_id, 'stock_quantity': 88, 'status_code': resp.status_code, 'response': data})
            if resp.status_code == 401:
                log("Task 3: Authentication failed (401)", log_file)
            elif resp.status_code == 404:
                log("Task 3: Product not found (404)", log_file)
        except Exception as e:
            log(f"Task 3 Exception: {e}", log_file)
            results.append({'task': 'update_stock', 'product_id': test_stock_product_id, 'error': str(e)})
    else:
        log("Task 3: TEST_STOCK_PRODUCT_ID not provided", log_file)

    result_file = os.path.join(log_dir, 'rest-execution-result.json')
    try:
        with open(result_file, 'w', encoding='utf-8') as f:
            json.dump(results, f, ensure_ascii=False, indent=2)
        log(f"Results saved to {result_file}", log_file)
    except Exception as e:
        log(f"Failed to save results: {e}", log_file)

    log("REST automation completed", log_file)


if __name__ == '__main__':
    main()
