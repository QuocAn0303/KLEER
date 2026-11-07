"""Measure complete HTTP requests. Never treat a fast error as a performance pass."""
import argparse
import json
import statistics
import time
import sys
import urllib.error
import urllib.request
from pathlib import Path

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')


def routine_valid(decoded, version, allow_missing=False):
    if not isinstance(decoded, dict) or decoded.get('status') != 'ok':
        return False
    data = decoded.get('data', {})
    if not isinstance(data, dict):
        return False
    products = data.get('recommended_products', [])
    if not isinstance(products, list) or any(not isinstance(p, dict) for p in products):
        return False
    ids = [p.get('id') for p in products]
    if any(type(i) is not int or i <= 0 for i in ids) or len(set(ids)) != len(ids):
        return False
    if any(type(p.get('price')) not in (int, float) or p['price'] <= 0 for p in products):
        return False
    if version == 'v1':
        return len(products) == 4
    selected, missing = data.get('routine_steps_selected', []), data.get('missing_steps', [])
    steps = [p.get('routine_step') for p in products]
    if data.get('version') != 'v4-draft' or not isinstance(selected, list) or not 2 <= len(selected) <= 6:
        return False
    if any(not isinstance(s, str) for s in selected + steps) or not isinstance(missing, list) or any(not isinstance(s, str) for s in missing):
        return False
    if not isinstance(missing, list) or len(selected) != len(set(selected)) or len(steps) != len(set(steps)):
        return False
    if any(s not in selected for s in steps + missing) or set(steps) & set(missing):
        return False
    if set(steps + missing) != set(selected):
        return False
    return (allow_missing and len(products) >= 2 and bool(data.get('warnings'))) if missing else len(products) == len(selected)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--base-url', default='http://localhost:8081')
    parser.add_argument('--samples', type=int, default=20)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--version', choices=['v1', 'v4'], default='v1')
    parser.add_argument('--payload', type=Path)
    parser.add_argument('--allow-missing-steps', action='store_true', help='Only after PO accepts incomplete v4 routines.')
    args = parser.parse_args()
    if args.samples < 2:
        parser.error('--samples must be at least 2')
    payload_path = args.payload or Path(__file__).with_name('valid-submission-v4.json' if args.version == 'v4' else 'valid-submission.json')
    payload = payload_path.read_bytes()
    url = args.base_url.rstrip('/') + '/?rest_route=/kleer/v1/recommend&version=' + args.version
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
            valid = status == 200 and routine_valid(decoded, args.version, args.allow_missing_steps)
            complete = valid and not decoded.get('data', {}).get('missing_steps', [])
        except (ValueError, AttributeError, TypeError):
            valid = complete = False
        results.append({'status': status, 'ms': round((time.perf_counter()-start)*1000, 2),
                        'accepted_routine': valid, 'complete_routine': complete})
    times = [r['ms'] for r in results]
    report = {
        'checked_at_utc': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()),
        'url': url, 'version': args.version, 'payload_file': payload_path.name,
        'allow_missing_steps': args.allow_missing_steps,
        'sample_count': len(results), 'requested_samples': args.samples, 'first_request_ms': times[0],
        'warm_median_ms': round(statistics.median(times[1:]), 2) if len(times) > 1 else None,
        'cold_note': 'First request is cold only if the catalog cache was empty before this run.',
        'median_ms': round(statistics.median(times), 2), 'max_ms': max(times),
        'all_successful_accepted_routines': len(results) == args.samples and all(r.get('accepted_routine', False) for r in results),
        'all_successful_complete_routines': len(results) == args.samples and all(r.get('complete_routine', False) for r in results),
        'all_below_300ms': len(results) == args.samples and all(r.get('accepted_routine', False) and r['ms'] < 300 for r in results),
        'samples': results,
    }
    rendered = json.dumps(report, ensure_ascii=False, indent=2)
    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(rendered + '\n', encoding='utf-8')
    print(rendered)
    raise SystemExit(0 if report['all_below_300ms'] else 1)


if __name__ == "__main__":
    main()
