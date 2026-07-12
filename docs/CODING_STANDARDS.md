# OneMarket247 — Coding Standards

These standards apply to all application code from Phase 1 onward. They operationalize
the conventions already described in [docs/architecture/01-system-architecture.md](architecture/01-system-architecture.md) §7.

## Style

- **PSR-12**, enforced by [Laravel Pint](https://laravel.com/docs/pint) using the
  `laravel` preset (see `pint.json`). Run `./vendor/bin/pint` before committing;
  CI runs `./vendor/bin/pint --test` and fails the build on drift.
- Strict, descriptive naming. No abbreviations that aren't immediately obvious.
- Constructor property promotion and `readonly` where the value never changes
  after construction (see `App\Support\Money`).

## Architecture rules

- **Thin controllers.** A controller resolves a Form Request, calls a Service/Action,
  and returns a Resource (API) or a View (Blade). No business logic in controllers.
- **Form Requests** own validation and `authorize()` (which delegates to a Policy).
  Never validate inline in a controller method.
- **Policies** are the single source of truth for "can actor X do Y to record Z."
  Every restricted model gets one before its first mutating route ships.
- **Services / Actions** under `app/Domain/{Module}/Actions` hold business logic.
  Wrap any multi-table write — and anything touching money, stock, or orders — in
  `DB::transaction()`.
- **Repositories** are not a blanket layer. Use Eloquent directly for standard CRUD;
  reach for a repository only where it earns its keep (complex catalog/search
  queries, report aggregation).
- **Events/Listeners** carry cross-module side effects (e.g. `OrderPlaced` →
  inventory deduction, email, commission recording) so modules stay decoupled.

## Money

Never use `float`/`double` for money. All monetary values are integer minor units
at the database and business-logic layer, wrapped by `App\Support\Money`
(`app/Support/Money.php`, backed by `brick/money`). Format only at the
presentation edge (Blade view or API Resource) via `Money::format()`.

## Status / enums

Stateful entities (`Order`, `VendorOrder`, `Payment`, `Withdrawal`, `ReturnRequest`,
`Vendor`, `Product`, …) use native PHP backed `enum`s implementing Filament's
`HasLabel`/`HasColor` contracts, so the same enum definition drives both admin
badges and API Resource output — never a raw string/int status column compared
with magic values.

## API responses

Every `/api/v1/*` response goes through `App\Support\Api\ApiResponse` for a
consistent envelope (`data`/`meta`/`message` on success; `message`/`errors`/
`error_code` on failure — see [docs/architecture/01-system-architecture.md](architecture/01-system-architecture.md) §6
and [docs/architecture/08-api-endpoints.md](architecture/08-api-endpoints.md)).
Exception-to-response mapping is centralized in `bootstrap/app.php` via
`ApiResponse::exception()` — do not hand-roll error JSON in individual controllers.

## Database

- Every model with restricted access gets an explicit `$fillable` — never
  `$guarded = []`.
- Wrap financial/order/inventory writes in `DB::transaction()`; use
  `lockForUpdate()` for any read-then-write against stock, coupon usage counts,
  or wallet balances.
- Ledger tables (`vendor_wallet_transactions`, `stock_movements`,
  `order_item_commissions`, `audit_logs`, …) are insert-only. Never update or
  delete a ledger row — write an offsetting entry instead.
- Every migration that creates a table also implements `down()` that drops it.
  Migrations must round-trip cleanly: `migrate` → `migrate:rollback` → `migrate`.

## Testing

- [Pest](https://pestphp.com) is the test framework (`tests/Pest.php`). New
  Feature tests extend the base `Tests\TestCase`; pure Unit tests (no framework
  bootstrap) stay plain Pest functions.
- `phpunit.xml` pins the test environment to an in-memory SQLite database,
  array cache/session, sync queue — independent of whatever driver local
  development uses.
- No route ships without at least one Feature test asserting both the happy
  path and the authorization boundary (unauthenticated/unauthorized access is
  rejected).

## Git / commits

- Small, reviewable commits with descriptive messages explaining *why*, not
  just *what*.
- Never commit `.env`, real credentials, or generated caches
  (`bootstrap/cache/*.php`, `storage/framework/*`).
