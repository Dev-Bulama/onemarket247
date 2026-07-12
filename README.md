# OneMarket247

OneMarket247 is a multi-vendor e-commerce marketplace platform: a Laravel + Filament web
application (customer marketplace, vendor dashboard, administration panel, and REST API)
that will later be extended with a React Native mobile application.

## Project Status: Phase 3 — Authentication, Authorization & Account Security

Phases 0–2 (architecture, Laravel foundation, core schema) are complete — see
[`docs/architecture/`](docs/architecture/README.md) and [`docs/reports/`](docs/reports/).
Phase 3 has wired up guard-isolated authentication for administrators, vendors, and
customers: registration, login/logout, password reset, email verification, 2FA,
device-session management, login-history/suspicious-login alerts, API tokens
(Sanctum, ability-scoped per actor type), and social login (Google working, Facebook
config-gated, Apple architecture-only). A vendor session cannot satisfy the admin
guard (or vice versa) by construction, not just by convention.

The full multi-vendor e-commerce **web platform** (Phases 1–27) must be built, tested,
documented, and pass the **Web Completion Gate** before any REST API finalization
(Phase 28) or React Native mobile development (Phases 29–37) begins. See
[`docs/architecture/13-development-roadmap.md`](docs/architecture/13-development-roadmap.md).

## Tech stack

- PHP 8.3+, Laravel 13 (latest stable)
- MySQL 8+ (production/staging); SQLite is acceptable for quick local smoke-testing
- Redis (cache, sessions, queues)
- Filament 5 (administration panel)
- Pest (testing), Laravel Pint (PSR-12 formatting)
- Spatie packages: permission, activitylog, medialibrary, backup, translatable, sluggable
- brick/money (money handling), barryvdh/laravel-dompdf (PDF), maatwebsite/excel (import/export),
  intervention/image (image processing)

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build   # or `npm run dev` for local asset watching
php artisan serve
```

Visit `http://localhost:8000` for the storefront shell and `http://localhost:8000/admin`
for the Filament administration panel (no resources yet — those land in Phase 4).

`.env.example` targets MySQL 8+ and Redis per the required production stack (see
[`docs/architecture/12-deployment-roadmap.md`](docs/architecture/12-deployment-roadmap.md)).
For a quick local run without MySQL/Redis installed, set `DB_CONNECTION=sqlite`,
`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.

### Tests

```bash
./vendor/bin/pest
./vendor/bin/pint --test
```

`phpunit.xml` pins the test environment to an in-memory SQLite database and array
cache/session, independent of whichever driver local development uses.

## Coding standards

See [`docs/CODING_STANDARDS.md`](docs/CODING_STANDARDS.md).

## Where to start reading

All Phase 0 planning documents live in [`docs/architecture/`](docs/architecture/README.md):

- System architecture, modules, and roles
- Database ERD and table plan
- Model and migration plan
- Filament admin resource list
- Customer website and vendor dashboard page lists
- REST API endpoint map
- Order, payment, commission, wallet, withdrawal, and refund lifecycles
- Security architecture
- Testing roadmap
- Deployment roadmap
- Full phased development roadmap (Phase 0 → Phase 37) with completion gates

## Next step

Phase 4 — Filament Administration Panel — builds admin resources for every module
completed so far (vendors, stores, customers, geography/settings) on top of the
now-working `admin` guard.
