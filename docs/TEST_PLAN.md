# KLEER Test Plan

## Scope

This first test plan covers Docker configuration, WordPress bootstrap, plugin loading, REST health behavior, and crawler parsing. Payment sandbox behavior, UX research, and production performance are out of scope until their requirements are approved.

## Strategy

- Static checks: PowerShell parse, PHP lint, YAML/Compose render, conflict-marker scan.
- Unit checks: crawler fixture extraction and plugin service tests when the WordPress/PHP test harness is available.
- Smoke checks: build images, start services, check container health, request `/health` and `/wp-json/kleer/v1/health`.
- Regression checks: run the same commands in CI for every pull request to `main` and `develop`.

## Automated suites

| Suite | Command | Expected |
| --- | --- | --- |
| Plugin lint | `docker compose run --rm php sh -c "find wp-content/plugins/kleer-plugin -name '*.php' -print0 \| xargs -0 -n1 php -l"` | No syntax errors |
| Plugin architecture + integration | `docker compose run --rm php php wp-content/plugins/kleer-plugin/tests/run_tests.php` | `167 tests, 0 failures` |
| Runtime config gate | `docker compose run --rm php php wp-content/plugins/kleer-plugin/tests/verify_php_ini.php` | `php.ini applied and required extensions present` |
| Redis object cache | `docker exec kleer-php php /var/www/html/wp-content/plugins/kleer-plugin/tests/verify_cache.php` | `Redis cache smoke test PASSED!` (needs the `redis` container running) |
| Nginx config | `docker compose run --rm nginx nginx -t` | `test is successful` |
| Crawler fixture | `python -m pytest tools/crawler` | Five products extracted |
| Load test | `docker run --rm -i --network kleer_kleer-net -v "$PWD/tests/load:/scripts" -e BASE_URL=http://kleer-nginx grafana/k6 run /scripts/kleer-load.js` | p95 under 500 ms (needs WordPress installed) |

The plugin suite covers the architecture layers plus the `L01-G6-01` integration layer:
CORS whitelist decisions, preflight handling, JSON Schema validation, and the Controller
payload boundary. It needs no database and no web server, so it is safe to run in CI.

### Why there is a separate `verify_php_ini.php` gate

PHP silently ignores a malformed `.ini` file. A file that exists but is never applied looks
identical to a correct one from the outside, so a broken config can ship unnoticed. This gate
asserts the values actually loaded at runtime plus the presence of the required extensions
(`mbstring`, `intl`, `gd`, `zip`, `mysqli`, `pdo_mysql`, `redis`). It caught two real defects
during `L01-G6-01`: a `php.ini` rejected for its comment syntax, and a missing build package.

### What CI cannot cover

**WordPress core is not committed to this repository**, so `/wp-json/...` is unreachable in CI.
CI therefore smoke tests only the nginx-level `/health` endpoint. REST and CORS behaviour is
covered by the CLI suites plus the manual checks below, which must be run against a real
instance before accepting `L01-G6-01`.

The load test has the same constraint. It runs on a machine with WordPress installed and
self-reports when `/wp-json` returns 404, so a missing WordPress install is never mistaken for a
performance regression.

### Manual CORS checks (frontend ↔ backend handshake)

These cannot be covered by the CLI suite because they need a running WordPress instance.
Run them before accepting `L01-G6-01` and capture the output as delivery evidence.

| # | Request | Expected |
| --- | --- | --- |
| 1 | `OPTIONS /wp-json/kleer/v1/health` with whitelisted `Origin` | HTTP 204, `Access-Control-Allow-Origin`, `Access-Control-Max-Age` |
| 2 | `GET /wp-json/kleer/v1/health` with whitelisted `Origin` | `Access-Control-Allow-Origin` + `Vary: Origin` |
| 3 | `GET /wp-json/kleer/v1/health` with unlisted `Origin` | No CORS headers at all |
| 4 | `OPTIONS` with `Access-Control-Request-Headers: content-type, x-evil` | Response allow-headers contains `content-type`, not `x-evil` |
| 5 | `POST /wp-json/kleer/v1/skin-quiz` with payload missing a required field | HTTP 400, code `kleer_invalid_payload`, errors carry JSON Pointer |

## Severity

| Severity | Definition                                               | Example                  |
| -------- | -------------------------------------------------------- | ------------------------ |
| Critical | Blocks startup, data safety, checkout, or authentication | Database cannot start    |
| Major    | Breaks a core workflow without a practical workaround    | Product page returns 500 |
| Minor    | Limited impact or cosmetic defect                        | Incorrect helper text    |

## Definition of Done for Week 1 coding

- [ ] Compose renders from `.env.example`.
- [ ] WordPress core is present after setup and the bootstrap is repeatable.
- [x] All PHP files pass `php -l`.
- [ ] Crawler fixture test extracts five products.
- [ ] CI runs static checks and smoke checks.
- [ ] Human acceptance confirms ports, branch protection, and team setup.

## Definition of Done for L01-G6-01 (frontend ↔ backend integration)

- [x] CORS applies to every `/wp-json/kleer/v1/...` route without per-endpoint wiring.
- [x] Preflight `OPTIONS` returns HTTP 204 and does not run `permission_callback`.
- [x] Unlisted origins receive no CORS headers; `Vary: Origin` is always sent.
- [x] Wildcard is never echoed when credentials are enabled.
- [x] Quiz request/response contracts live in `wp-content/plugins/kleer-plugin/schemas/` as JSON Schema.
- [x] Unsupported schema keywords fail loudly instead of silently passing.
- [x] Controller boundary returns `kleer_invalid_payload` with HTTP 400 and JSON Pointer paths.
- [x] Plugin suite passes 135/135.
- [x] `verify_php_ini.php` confirms the shipped `php.ini` is actually applied.
- [x] CI resolves `docker compose` variables from a committed-safe file, so no developer `.env` is required.
- [ ] Manual CORS checks 1–5 executed against a running WordPress instance, output archived.
