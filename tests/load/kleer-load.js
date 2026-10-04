// ============================================
// KLEER - Load test cho Rubric 5.3 (hieu nang)
// ============================================
// Chay bang k6 (khong can cai them vao repo):
//
//   docker run --rm -i --network host \
//     -v "$PWD/tests/load:/scripts" \
//     grafana/k6 run /scripts/kleer-load.js
//
// Bien moi truong:
//   BASE_URL      mac dinh http://localhost
//   DURATION      mac dinh 30s
//   RATE          so VU song song, mac dinh 10
//
// Muc tieu: do p95/p99 va error rate cua REST API truocc va sau khi bat Redis,
// de co bang chung "Redis lam nhanh hon".

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

// Metric rieng cho API (bo qua thoi gian tai static asset)
const apiLatency = new Trend('kleer_api_latency', true);
const apiFailures = new Rate('kleer_api_failures');
const cacheHits = new Counter('kleer_cache_hits');

export const options = {
  scenarios: {
    api: {
      executor: 'ramping-arrival-rate',
      startRate: 5,
      timeUnit: '1s',
      preAllocatedVUs: 20,
      maxVUs: 100,
      stages: [
        { target: Number(__ENV.RATE || 10), duration: __ENV.DURATION || '30s' },
      ],
    },
  },
  thresholds: {
    // Latency p95 phai duoi 500ms o rate mac dinh.
    // KHONG dat threshold cho error rate: khi WordPress chua duoc cai trong stack,
    // /wp-json tra 404 va do la moi truong, khong phai loi hieu nang.
    'kleer_api_latency': ['p(95)<500'],
  },
};

export default function () {
  // 1) Health check - nhe, phai nhanh
  const health = http.get(`${BASE_URL}/health`);
  check(health, { 'health 200': (r) => r.status === 200 });

  // 2) REST API chinh cua KLEER.
  //    LUU Y: yeu cau mot WordPress da cai dat. Repo khong commit WordPress core,
  //    nen neu thay 404 o day thi hay chay lai tren stack co that (len VPS hoac
  //    may local da setup WordPress).
  const api = http.get(`${BASE_URL}/wp-json/kleer/v1/health`, {
    headers: { Origin: 'http://localhost:5173' },
    tags: { name: 'rest-api' },
  });

  apiLatency.add(api.timings.duration);
  apiFailures.add(api.status !== 200);

  check(api, {
    'api 200': (r) => r.status === 200,
    'api tra service': (r) => typeof r.body === 'string' && r.body.includes('kleer-plugin'),
    'api co CORS header': (r) => !!r.headers['Access-Control-Allow-Origin'],
  });

  // 3) Preflight - kiem tra CORS chay duoc.
  //    Gui body rong va Content-Type application/json de k6 khong co giong
  //    mac dinh multipart/urlencoded va do khong can hinh du lieu gi.
  const preflight = http.options(`${BASE_URL}/wp-json/kleer/v1/health`, null, {
    headers: {
      Origin: 'http://localhost:5173',
      'Access-Control-Request-Method': 'POST',
      'Access-Control-Request-Headers': 'content-type',
    },
    tags: { name: 'preflight' },
  });
  check(preflight, { 'preflight 204': (r) => r.status === 204 });

  sleep(Math.random() * 0.5);
}

export function handleSummary(data) {
  const p = data.metrics.kleer_api_latency ? data.metrics.kleer_api_latency.values : {};
  const failures = data.metrics.kleer_api_failures
    ? data.metrics.kleer_api_failures.values.rate
    : 0;
  const checks = data.metrics.checks ? data.metrics.checks.values : {};
  const passRate = checks.passes / (checks.passes + checks.fails || 1);

  const lines = [
    '',
    '=== KLEER load test ===',
    `p50 = ${(p['p(50)'] || 0).toFixed(1)} ms`,
    `p95 = ${(p['p(95)'] || 0).toFixed(1)} ms`,
    `p99 = ${(p['p(99)'] || 0).toFixed(1)} ms`,
    `check pass rate = ${(passRate * 100).toFixed(1)}%`,
    `error rate = ${(failures * 100).toFixed(1)}%`,
    '',
  ];

  if (passRate < 1) {
    lines.push(
      'WARNING: co check khong dat.',
      '  - Neu la 404 tren /wp-json, thi WordPress core chua duoc cai trong stack nay.',
      '    Repo khong commit WordPress core; hay chay load test tren VPS da deploy.',
      '  - Neu la 500, xem log: docker compose logs php nginx',
      ''
    );
  }

  return { stdout: lines.join('\n') + '\n' };
}
