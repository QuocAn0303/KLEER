"""Non-mutating v4 HTTP contract checks. No product/order/stock writes."""
import argparse
import copy
import json
import time
import sys
import urllib.error
import urllib.request
from pathlib import Path
from benchmark import routine_valid

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

parser = argparse.ArgumentParser()
parser.add_argument('--base-url', default='http://localhost:8081')
parser.add_argument('--output', type=Path)
args = parser.parse_args()
payload = json.loads(Path(__file__).with_name('valid-submission-v4.json').read_text(encoding='utf-8'))
cases = [('oily_acne', payload, 200, {}, 'v4')]
sensitive = copy.deepcopy(payload)
for answer, option in zip(sensitive['answers'], ['1E', '2E', '3A', '4B', '5E', '6C']):
    answer['answer'] = option
cases.append(('sensitive_dark_spots_six_steps', sensitive, 200, {}, 'v4'))
for name, change in [
    ('missing_question', lambda p: p['answers'].pop()),
    ('duplicate_question', lambda p: p['answers'].append(p['answers'][0])),
    ('invalid_option', lambda p: p['answers'][0].update(answer='invalid')),
    ('consent_false', lambda p: p['consent'].update(policy_accepted=False)),
]:
    body = copy.deepcopy(payload)
    change(body)
    cases.append((name, body, 400, {}, 'v4'))
cases.extend([('invalid_nonce', payload, 403, {'X-WP-Nonce': 'invalid'}, 'v4'),
              ('unapproved_origin', payload, 403, {'Origin': 'https://unapproved.example'}, 'v4'),
              ('unsupported_version', payload, 400, {}, 'unsupported')])
report = {'base_url': args.base_url, 'checked_at_utc': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()), 'cases': []}
for name, body, expected, extra_headers, version in cases:
    url = args.base_url.rstrip('/') + '/?rest_route=/kleer/v1/recommend&version=' + version
    request = urllib.request.Request(url, data=json.dumps(body).encode(), headers={'Content-Type': 'application/json', **extra_headers})
    start = time.perf_counter()
    try:
        response = urllib.request.urlopen(request, timeout=60)
    except urllib.error.HTTPError as error:
        response = error
    except urllib.error.URLError as error:
        report['cases'].append({'name': name, 'expected': expected, 'actual': None, 'passed': False, 'error': str(error.reason)})
        continue
    with response:
        status, raw = response.status, response.read()
    try:
        decoded = json.loads(raw)
    except ValueError:
        decoded = {'error': 'Response is not JSON'}
    passed = status == expected and (expected != 200 or routine_valid(decoded, 'v4'))
    report['cases'].append({'name': name, 'expected': expected, 'actual': status, 'passed': passed,
        'ms': round((time.perf_counter() - start) * 1000, 2), 'body': decoded})
report['passed'] = sum(c['passed'] for c in report['cases'])
report['total'] = len(report['cases'])
text = json.dumps(report, ensure_ascii=False, indent=2)
if args.output:
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(text + '\n', encoding='utf-8')
print(text)
raise SystemExit(0 if report['passed'] == report['total'] else 1)
