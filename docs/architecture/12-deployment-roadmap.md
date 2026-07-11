# 12 — Deployment Roadmap

## 1. Environments

| Environment | Purpose |
|---|---|
| Local | Docker/Sail or Herd/Valet dev setup, `.env.local`, Tailwind CDN allowed |
| CI | Ephemeral, runs on every push (tests, lint, static analysis) |
| Staging | Production-parity, seeded with demo data, used for phase completion sign-off and pre-release QA |
| Production | Live traffic, compiled/minified assets, no debug mode, real gateway keys |

## 2. Server Requirements

- PHP 8.3+ with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`,
  `json`, `mbstring`, `openssl`, `pdo_mysql`, `redis`, `tokenizer`, `xml`,
  `gd`/`imagick`, `zip`, `intl`.
- MySQL 8+.
- Redis 6+ (cache, queues, sessions, rate limiting).
- Nginx (or Apache) + PHP-FPM.
- Supervisor (queue workers) + cron (Laravel Scheduler's single entry).
- SSL via Let's Encrypt/managed certificate.
- Object storage (S3 or S3-compatible, e.g. DigitalOcean Spaces/MinIO) for
  media at production scale, with CDN in front.

## 3. Release Pipeline (per phase, and for final release)

1. Feature branch → PR → CI (lint, static analysis, full test suite) → review.
2. Merge to integration branch → deploy to Staging automatically.
3. Manual QA pass against the phase's completion criteria on Staging.
4. Tag release → deploy to Production (zero-downtime: `php artisan down`
   only as a last resort; prefer atomic symlink swap / Envoyer-style deploy,
   `migrate --force` before traffic switch, `queue:restart` after deploy).

## 4. CI/CD (GitHub Actions)

Workflow stages: install dependencies (composer, npm) → `pint --test` →
`phpstan analyse` → `pest`/`phpunit` (with MySQL + Redis service
containers) → build/compile frontend assets → (on tag) deploy job. A failing
stage blocks merge/deploy — no phase's code lands on `main` red.

## 5. Configuration & Secrets

- `.env.example` maintained in lockstep with every new config key introduced
  by a phase; production secrets live only in the hosting platform's secret
  manager / CI secrets store, never in the repo.
- Feature flags (`settings` table, cached) allow enabling optional modules
  (wallet, rewards, referrals, gift cards, affiliates) per environment
  without a redeploy.

## 6. Queues & Scheduler

- Named queues by priority/domain: `default`, `emails`, `webhooks`,
  `imports-exports`, `reports` — isolated Supervisor worker pools so a slow
  import job never delays payment webhook processing.
- Scheduler (`php artisan schedule:run` via a single cron entry) drives:
  exchange-rate refresh, abandoned-cart reminders, subscription
  expiry/grace-period checks, scheduled backups, scheduled CMS publishing,
  withdrawal batch processing (if automatic payout is enabled), stale
  checkout-session cleanup.

## 7. Backups & Disaster Recovery

- Automated daily database backup + file/media backup to remote storage
  (separate from the primary object storage bucket/region), retention
  policy configurable from the admin System Health / Backups resource.
- Documented restore procedure (`docs/` runbook produced in Phase 27)
  tested at least once against Staging before go-live.

## 8. Monitoring & Observability

- Application log channel to a persistent, rotated log store; error
  tracking (e.g. Sentry-compatible) wired for uncaught exceptions in
  production.
- `System Health` Filament page (Phase 23) surfaces queue depth, failed
  jobs, scheduler last-run, Redis/DB connectivity, disk usage, last backup
  time — the operational dashboard for on-call checks.

## 9. Scaling Considerations (documented now, applied as needed)

- Stateless app servers behind a load balancer; sessions/cache/queues in
  Redis so any app instance can serve any request.
- Read-heavy catalog/search endpoints are cache-fronted (Redis) with
  observer-driven invalidation on product/category writes; full-text
  search may graduate from MySQL FULLTEXT to a dedicated search engine
  (Meilisearch/Typesense) if catalog size/query complexity warrants it —
  abstracted behind a `Searchable` contract so the swap doesn't touch
  calling code.
- Media served via CDN; signed URLs for private files bypass CDN caching
  by design.

## 10. Mobile-readiness Note

No mobile build/release pipeline (Phase 37) is set up until Phases 1–28 are
complete — see [13-development-roadmap.md](13-development-roadmap.md). This
document will gain an "Android/iOS build & store submission" section at that
point; it is intentionally absent here.
