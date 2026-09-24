# KLEER Test Plan

## Scope

This first test plan covers Docker configuration, WordPress bootstrap, plugin loading, REST health behavior, and crawler parsing. Payment sandbox behavior, UX research, and production performance are out of scope until their requirements are approved.

## Strategy

- Static checks: PowerShell parse, PHP lint, YAML/Compose render, conflict-marker scan.
- Unit checks: crawler fixture extraction and plugin service tests when the WordPress/PHP test harness is available.
- Smoke checks: build images, start services, check container health, request `/health` and `/wp-json/kleer/v1/health`.
- Regression checks: run the same commands in CI for every pull request to `main` and `develop`.

## Severity

| Severity | Definition                                               | Example                  |
| -------- | -------------------------------------------------------- | ------------------------ |
| Critical | Blocks startup, data safety, checkout, or authentication | Database cannot start    |
| Major    | Breaks a core workflow without a practical workaround    | Product page returns 500 |
| Minor    | Limited impact or cosmetic defect                        | Incorrect helper text    |

## Definition of Done for Week 1 coding

- [ ] Compose renders from `.env.example`.
- [ ] WordPress core is present after setup and the bootstrap is repeatable.
- [ ] All PHP files pass `php -l`.
- [ ] Crawler fixture test extracts five products.
- [ ] CI runs static checks and smoke checks.
- [ ] Human acceptance confirms ports, branch protection, and team setup.
