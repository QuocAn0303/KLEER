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
| Plugin architecture + integration | `docker compose run --rm php php wp-content/plugins/kleer-plugin/tests/run_tests.php` | `130 tests, 0 failures` |
| Crawler fixture | `python -m pytest tools/crawler` | Five products extracted |

The plugin suite covers the architecture layers plus the `L01-G6-01` integration layer:
CORS whitelist decisions, preflight handling, JSON Schema validation, and the Controller
payload boundary. It needs no database and no web server, so it is safe to run in CI.

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
- [x] Plugin suite passes 130/130.
- [ ] Manual CORS checks 1–5 executed against a running WordPress instance, output archived.
