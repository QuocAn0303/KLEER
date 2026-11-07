"""Measure complete HTTP requests. Never treat a fast error as a performance pass."""
import argparse
import json
import statistics
import time
import urllib.error
import urllib.request
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument('--base-url', default='http://localhost:8081')
parser.add_argument('--samples', type=int, default=20)
parser.add_argument('--output', type=Path)
args = parser.parse_args()
if args.samples < 2:
    parser.error('--samples must be at least 2')
payload = Path(__file__).with_name('valid-submission.json').read_bytes()
url = args.base_url.rstrip('/') + '/?rest_route=/kleer/v1/recommend'
results = []
for _ in range(args.samples):
    request = urllib.request.Request(url, data=payload, headers={'Content-Type': 'application/json'})
    start = time.perf_counter()
    try:
        response = urllib.request.urlopen(request, timeout=60)
    except urllib.error.HTTPError as error:
        response = error
    except urllib.error.URLError as error:
        results.append({'status': None, 'ms': round((time.perf_counter()-start)*1000, 2), 'error': str(error)})
        break
    with response:
        body = response.read()
        status = response.status
    try:
        decoded = json.loads(body)
        products = decoded.get('data', {}).get('recommended_products', [])
        valid = status == 200 and decoded.get('status') == 'ok' and len(products) == 4
    except (ValueError, AttributeError):
        valid = False
    results.append({'status': status, 'ms': round((time.perf_counter()-start)*1000, 2), 'complete_routine': valid})
times = [r['ms'] for r in results]
report = {
    'url': url, 'sample_count': len(results), 'first_request_ms': times[0],
    'median_ms': round(statistics.median(times), 2), 'max_ms': max(times),
    'all_successful_complete_routines': all(r.get('complete_routine', False) for r in results),
    'all_below_300ms': all(r.get('complete_routine', False) and r['ms'] < 300 for r in results),
    'samples': results,
}
rendered = json.dumps(report, ensure_ascii=False, indent=2)
if args.output:
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(rendered + '\n', encoding='utf-8')
print(rendered)
raise SystemExit(0 if report['all_below_300ms'] else 1)
