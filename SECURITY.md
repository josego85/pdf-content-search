# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| latest (`main`) | ✅ |
| older branches | ❌ |

Only the latest commit on `main` receives security fixes. There are no versioned releases with backported patches.

---

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Report security issues privately by email to the repository maintainer via the contact on the [GitHub profile](https://github.com/josego85). Include:

- A description of the vulnerability and affected component
- Steps to reproduce
- Potential impact and attack scenario
- Any suggested fix (optional)

You will receive an acknowledgement within **72 hours**. If the issue is confirmed, a fix will be prioritized and a patched commit will be published to `main` as soon as possible. You will be credited in the changelog unless you request otherwise.

---

## Security Measures in Place

### Static Analysis & CI

| Check | Tool | Frequency |
|-------|------|-----------|
| SAST (PHP + JS/TS) | CodeQL | Every push, weekly |
| PHP static analysis | PHPStan Level 8 | Every push |
| PHP dependency CVEs | `composer audit` | Every push + daily |
| JS dependency CVEs | `npm audit --audit-level=high` | Every push + daily |
| Dependency review | `actions/dependency-review-action` | Every pull request |
| Supply-chain score | OpenSSF Scorecard | Push to `main`, weekly |
| Workflow actions | Pinned to SHA (not floating tags) | — |

### Application Security

- **SQL injection:** Doctrine ORM used exclusively — all queries are parameterized. No raw SQL strings.
- **XSS:** Twig auto-escaping enabled globally. JavaScript context values use `|escape('js')` explicitly.
- **CSRF:** Symfony CSRF protection enabled. State-changing routes do not accept `GET`.
- **IP anonymization (GDPR):** IPv4 last octet zeroed (`/24`); IPv6 last 80 bits zeroed (`/48`). Implemented in `src/MessageHandler/LogSearchAnalyticsHandler.php` using `inet_pton`/`inet_ntop`.
- **Shell command safety:** All external processes (`pdftotext`, `ocrmypdf`, `pdfinfo`) run via `Symfony\Component\Process\Process` with arguments as arrays — no shell interpolation.
- **Secrets management:** No secrets committed to the repository. `.env.local` and `.env.prod.local` are gitignored. Production credentials are injected via environment variables.
- **Elasticsearch authentication:** `xpack.security.enabled=true` enforced in production. Password required via `ELASTIC_PASSWORD` environment variable (fails at startup if unset).
- **Container network isolation:** Database and Elasticsearch ports are not published in production — only accessible on the internal Docker bridge network.
- **Production resource limits:** CPU and memory limits set on all containers via `docker-compose.prod.yml`.
- **Code coverage:** 85% line coverage minimum enforced in CI — security-relevant logic has test coverage.

### Dependency Pinning

All GitHub Actions `uses:` references are pinned to commit SHA with a version comment, e.g.:

```yaml
uses: actions/checkout@de0fac2e4500dabe0009e67214ff5f5447ce83dd # v5.0.0
```

This prevents tag-mutable supply-chain attacks.

---

## Known Security Gaps — Pre-Production Checklist

The following items are **not yet implemented** and must be resolved before exposing this application publicly. They are tracked in [`TODO.md`](TODO.md).

### Critical

| # | Issue | Location | Notes |
|---|-------|----------|-------|
| 1 | **No API authentication** | All `/api/*` endpoints | `/api/search`, `/api/analytics/*`, `/api/translations/*` are publicly accessible. Implement token-based or session auth. |
| 2 | **No authorization rules** | `config/packages/security.yaml` | `access_control` is commented out. No roles or route guards enforced. |
| 3 | **GDPR data retention not enforced** | `src/Entity/SearchAnalytics.php` | Analytics records are never deleted. Requires a scheduled cleanup command to purge records older than 90 days (GDPR Art. 5(1)(e)). |

### High

| # | Issue | Location | Notes |
|---|-------|----------|-------|
| 4 | **No rate limiting** | `src/Controller/SearchController.php`, `TranslationController.php`, `AnalyticsController.php` | Unlimited requests accepted. Risk of DoS and Ollama overload. Implement Symfony RateLimiter. |
| 5 | **HTTPS not enforced** | Apache vhost / Symfony config | No HTTP → HTTPS redirect. No `Strict-Transport-Security` header. Required before production. |
| 6 | **Error details exposed to clients** | `src/Controller/SearchController.php:115`, `TranslationController.php:50` | `$e->getMessage()` returned in JSON responses. Disable in production (`APP_ENV=prod` suppresses stack traces in Twig; verify API responses too). |

### Medium

| # | Issue | Location | Notes |
|---|-------|----------|-------|
| 7 | **Path traversal hardening** | `src/Service/TranslationRequestValidator.php:45` | Filename is validated via `file_exists()` against the pdfs directory, but `realpath()` comparison is not used to canonicalize and block `../` sequences explicitly. |
| 8 | **No session timeout configured** | `config/packages/framework.yaml` | Symfony defaults to a 1-hour session lifetime. Set explicitly for production. |

---

## Development vs Production Security Posture

This project uses a layered Docker Compose configuration. Some security controls are intentionally relaxed in development.

| Control | Development | Production |
|---------|------------|------------|
| Elasticsearch auth | Disabled (`xpack.security.enabled=false`) | Enabled, password required |
| Database port | Published (`5432:5432`) | Internal only |
| Elasticsearch port | Published (`9200:9200`) | Internal only |
| Ollama port | Published (`11435:11434`) | Internal only |
| Resource limits | None | CPU + memory limits on all containers |
| `APP_ENV` | `dev` (debug on, profiler active) | `prod` (debug off) |
| Symfony Profiler | Active — injects `X-Robots-Tag: noindex` | Disabled |

**Never use `docker-compose.dev.yml` overrides in production.**

---

## Infrastructure Security Notes

### Secrets

Required production secrets — never store in `.env`:

| Variable | Description |
|----------|-------------|
| `APP_SECRET` | Symfony cryptographic secret (32+ random bytes) |
| `POSTGRES_PASSWORD` | PostgreSQL password |
| `ELASTIC_PASSWORD` | Elasticsearch `elastic` user password |

Store in `.env.prod.local` (gitignored) or inject via your hosting platform's secret manager.

### Elasticsearch

- Index name: `pdf_pages` (configurable via `ELASTICSEARCH_INDEX_PDFS`)
- Max results hard-capped at `ELASTICSEARCH_MAX_RESULTS=100` — do not raise without load testing
- Vector embeddings use `int8_hnsw` quantization — do not switch to `float32` (4× RAM increase)

### Queue Security

- Symfony Messenger uses a Doctrine (database-backed) transport — no external broker exposed
- Messages stored in `messenger_messages` table inside PostgreSQL
- 3 Supervisor workers consume the queue in production

---

## Security Contacts

| Role | Contact |
|------|---------|
| Maintainer | [josego85](https://github.com/josego85) |
| Security issues | Email via GitHub profile (private — do not use issues) |
