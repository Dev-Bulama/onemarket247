# OneMarket247

OneMarket247 is a multi-vendor e-commerce marketplace platform: a Laravel + Filament web
application (customer marketplace, vendor dashboard, administration panel, and REST API)
that will later be extended with a React Native mobile application.

## Project Status: Phase 4 — Filament Administration Panel

Phases 0–3 (architecture, Laravel foundation, core schema, guard-isolated
authentication) are complete — see [`docs/architecture/`](docs/architecture/README.md)
and [`docs/reports/`](docs/reports/). Phase 4 has built the full Filament back office
on top of the `admin` guard: administrators, roles/permissions, customers, vendors
(with approve/reject/suspend/reactivate/terminate actions), stores, geography
(countries/states/cities), languages, currencies/exchange rates, settings, and a
read-only audit log — every resource gated by Spatie permissions, not role names.

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
for the Filament administration panel.

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

Phase 5 — Vendor Registration, Approval & Store Management — builds the flow that
actually creates `Vendor`/`Store` records (application submission, document upload,
admin review) that Phase 4's admin panel now manages.
