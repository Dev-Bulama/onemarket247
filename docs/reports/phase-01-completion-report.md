# Phase 1 Completion Report — Laravel Project Foundation

## Objective

Stand up the Laravel application skeleton, environment configuration, and base
package set that every later phase builds on, per
[docs/architecture/13-development-roadmap.md](../architecture/13-development-roadmap.md).

## What was built

- **Framework**: Laravel 13.19.0 (latest stable at time of build), PHP `^8.3`
  (running on PHP 8.4.19). Originally scaffolded against `^11.0`; rebuilt on
  latest stable after discovering the 11.x branch has an unpatched CVE
  (CVE-2026-48019, CRLF injection in the default email validation rule) with
  no backport released for 11.x — see "Decisions" below.
- **Environment**: `.env` / `.env.example` configured for `APP_NAME=OneMarket247`,
  Redis-backed session/cache/queue, MySQL 8+ as the documented
  production/staging database driver, S3-compatible object storage placeholders,
  and Paystack credential placeholders for Phase 13.
- **Packages installed**:
  - `filament/filament` ^5.6 (latest stable; v5.7 is still beta, intentionally
    avoided)
  - `spatie/laravel-permission`, `spatie/laravel-activitylog`,
    `spatie/laravel-medialibrary`, `spatie/laravel-backup`,
    `spatie/laravel-translatable`, `spatie/laravel-sluggable`
  - `brick/money` (money handling), `barryvdh/laravel-dompdf` (PDF),
    `maatwebsite/excel` (import/export), `intervention/image` (image processing)
  - `pestphp/pest` + `pestphp/pest-plugin-laravel` (testing)
- **Base app scaffolding**:
  - `App\Support\Api\ApiResponse` — the standard `/api/v1/*` response envelope
    and a central `exception()` mapper wired into `bootstrap/app.php`'s
    exception handler (validation, auth, authorization, not-found, rate-limit,
    generic HTTP, and 500 all map to `{message, errors, error_code}`).
  - `App\Support\Money` — integer-minor-units money value object wrapping
    `brick/money`, per the money-handling standard in
    [docs/architecture/01-system-architecture.md](../architecture/01-system-architecture.md) §6.
  - Filament `admin` panel installed and booting at `/admin` (no resources yet
    — those land in Phase 4).
  - `pint.json` (PSR-12/Laravel preset) and `docs/CODING_STANDARDS.md`
    codifying the architecture doc's service/policy/transaction conventions.
  - A baseline scheduled task (`queue:prune-failed`) registered in
    `routes/console.php` to prove out the scheduler mechanism; domain-specific
    scheduled jobs are added as their owning phases land.
  - Package migrations published for `spatie/laravel-permission`,
    `spatie/laravel-activitylog`, and `spatie/laravel-medialibrary`. The
    activitylog and media migration stubs shipped without a `down()` method;
    added `Schema::dropIfExists()` to both so `migrate` → `migrate:rollback` →
    `migrate` round-trips cleanly project-wide.

## Database changes

Only framework/package-owned tables exist at this point: `users`, `cache`,
`jobs` (Laravel base), `activity_log`, `permissions`/`roles`/`model_has_*`
(Spatie), `media` (Spatie). OneMarket247's own domain schema (vendors, stores,
customers, catalog, etc.) is Phase 2 scope.

## Security considerations addressed this phase

- Rebuilt on Laravel 13 specifically to avoid shipping a framework version
  with a known, unpatched CVE.
- API exception handling never leaks internal exception messages in
  non-debug mode (`ApiResponse::exception()` returns a generic "Server Error."
  for uncaught exceptions unless `app()->hasDebugModeEnabled()`).
- Gateway/API secret placeholders added to `.env.example` with no real values;
  `.env` itself remains gitignored.
- `database/database.sqlite` (local-only smoke-test artifact) is gitignored;
  it is not part of the committed source tree.

## Dependencies / environment notes

- **MySQL is not installable in this sandbox** (the environment's apt mirror
  returned 404s for both `mysql-server` and `mariadb-server` packages).
  `.env.example` documents MySQL 8+ as the production/staging target per the
  required stack; local verification in this session used SQLite, which
  `phpunit.xml` already pins as the test-suite database independent of
  whatever the developer's local `.env` points at. This does not block later
  phases — Laravel's schema builder and the migrations written from Phase 2
  onward avoid MySQL-only syntax; the first real MySQL run should happen in
  CI/staging (see [docs/architecture/12-deployment-roadmap.md](../architecture/12-deployment-roadmap.md)).
- **`larastan/larastan` (PHPStan for Laravel) was not installed.** This
  session's GitHub access is scoped to `dev-bulama/onemarket247` only; `phpstan/phpstan`
  ships as a dist-only (no git source) package, and its zipball download goes
  through `api.github.com`, which this session's proxy blocks for
  out-of-scope repositories. `pestphp/pest` and its plugins *do* have git
  source and installed successfully via the source fallback. Static analysis
  tooling should be added in a full CI environment with unrestricted
  Packagist/GitHub access: `composer require --dev larastan/larastan`. Noted
  in `docs/CODING_STANDARDS.md`/testing roadmap as a follow-up, not a blocker.

## Tests

- `./vendor/bin/pest` — 2/2 passing (example Unit + Feature tests, converted
  to Pest syntax).
- `./vendor/bin/pint --test` — passing, zero style violations.
- `php artisan migrate` → `migrate:rollback` → `migrate` — verified clean
  round-trip on SQLite.
- Manual smoke tests: `php artisan serve` + `curl` against `/`, `/admin/login`,
  and `/up` all return `200`.
- Queue: dispatched a real `ShouldQueue` job through the Redis queue
  connection and confirmed execution via `queue:work --once` and the
  application log.
- Scheduler: `schedule:list` confirms the registered task and its next-run
  time; `schedule:run` correctly reports nothing due yet; the underlying
  command (`queue:prune-failed`) was also run directly and succeeded.

## Completion Gate Check (Phase 1)

| Criterion | Status |
|---|---|
| Laravel runs successfully | ✅ |
| MySQL connects successfully | ⚠️ Not verifiable in this sandbox (no MySQL server available); SQLite verified instead, MySQL configured as the documented target — see note above |
| Migrations run | ✅ (including clean rollback/re-migrate) |
| Filament loads | ✅ `/admin/login` returns 200, panel routes registered |
| Queue processes a test job | ✅ verified via Redis queue connection |
| Scheduler runs a test command | ✅ verified via `schedule:list` / `schedule:run` / direct command run |
| Storage works | ✅ `storage:link` verified, public/private disks configured |
| Tests run | ✅ Pest suite passing |
| No installation error remains | ✅ |

## Known limitations carried forward

1. MySQL connectivity is unverified in this sandbox environment (documented
   above); must be validated against a real MySQL 8+ instance before Phase 2's
   own completion gate is signed off.
2. Static analysis (Larastan/PHPStan) is not yet part of this repo's dev
   dependencies due to a sandbox-specific GitHub access restriction; add it
   in an environment with full Packagist/GitHub access before Phase 24
   (Security Audit) relies on it.

Neither limitation blocks Phase 2 (Database Foundation and Core Models), which
is schema/model work independent of the underlying RDBMS driver.
