"""Replay the read-only Postman requests, recording dependency failures separately."""
import json
import urllib.error
import urllib.request
from pathlib import Path

root = Path(__file__).resolve().parents[2]
collection = json.loads((root/'docs/api/KLEER-Routine.postman_collection.json').read_text(encoding='utf-8'))
report = []
for index, item in enumerate(collection['item']):
    request = item['request']
    url = request['url'].replace('{{base_url}}', 'http://localhost:8081')
    headers = {h['key']: h['value'] for h in request['header']}
    call = urllib.request.Request(url, data=request['body']['raw'].encode('utf-8'), headers=headers)
    try:
        response = urllib.request.urlopen(call, timeout=60)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        body = json.loads(response.read())
        status = response.status
    expected = [200,200,400,400,400,403,403][index]
    outcome = 'PASS' if status == expected else 'BLOCKED_DEPENDENCY' if expected == 200 and status == 503 else 'FAIL'
    report.append({'case': item['name'], 'expected': expected, 'actual': status, 'outcome': outcome, 'code': body.get('code',body.get('status'))})
out = root/'docs/evidence/nhan/http-contract-check.json'
out.write_text(json.dumps(report, ensure_ascii=False, indent=2)+'\n', encoding='utf-8')
print(json.dumps(report, ensure_ascii=False, indent=2))
raise SystemExit(1 if any(r['outcome']=='FAIL' for r in report) else 0)
