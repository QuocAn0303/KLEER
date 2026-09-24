# KLEER Plugin Architecture

This document records the coding boundary for the custom plugin. It is an implementation scaffold, not a claim that the business rules have been approved.

```text
wp-content/plugins/kleer-plugin/
├── kleer-plugin.php                 # WordPress entry point and hook registration
└── src/
    ├── Contracts/                   # Interfaces for replaceable data access
    ├── Controllers/                 # REST/request adapters and response shaping
    ├── Models/                      # Domain data objects (add when requirements exist)
    └── Services/                    # Business use cases, independent of HTTP
```

## Rules

- Controllers validate input and translate WordPress requests; they do not contain business rules.
- Services coordinate use cases and depend on contracts rather than WordPress globals where practical.
- Models represent domain data and must not perform HTTP or database I/O.
- Endpoints are registered by controllers under the `kleer/v1` namespace.
- External integrations and persistence belong behind interfaces in `Contracts`.
- Theme code must not be required by the plugin. The plugin may expose hooks for presentation.

The current scaffold exposes `GET /wp-json/kleer/v1/health` as a smoke endpoint.
