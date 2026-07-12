# Phase 4 Completion Report — Filament Administration Panel

## Objective

Build the full administrator-facing back office on top of Phase 3's `admin`
guard: a Filament 5.6 panel exposing every resource the Phase 4 brief lists —
administrators, roles/permissions, customers, vendors, stores, geography
(countries/states/cities), languages, currencies/exchange rates, settings,
and a read-only audit log — each gated by the permission vocabulary Phase 2's
`RolePermissionSeeder` already established, per
[docs/architecture/05-filament-resources-and-panels.md](../architecture/05-filament-resources-and-panels.md)
and the role/permission design in
[docs/architecture/03-modules-and-roles.md](../architecture/03-modules-and-roles.md).

## What was built

### Panel wiring

`App\Providers\Filament\AdminPanelProvider` — `id('admin')`, `path('admin')`,
`authGuard('admin')` (Phase 3's admin-only guard), auto-discovers resources/
widgets from `app/Filament/{Resources,Widgets}`. `User` now implements
`Filament\Models\Contracts\FilamentUser` with `canAccessPanel(Panel $panel)`
scoped to `SuperAdmin`/`Admin`/`Staff` user types who are `isActive()` — this
is required by Filament itself (outside `APP_ENV=local` every panel request
403s without it) and sits as defense-in-depth alongside the guard-level
isolation `ScopedEloquentUserProvider` already enforces.

### Authorization pattern

Two patterns, chosen per resource:

- **`App\Filament\Concerns\GatedByPermission`** — a reusable trait for
  resources with a single "manage" permission and no ownership concept
  (`Country`, `State`, `City`, `Language`, `Currency`, `ExchangeRate`,
  `Setting`). `canViewAny/canCreate/canEdit/canDelete/canDeleteAny` all
  resolve to `auth()->user()?->can(static::$managePermission)`.
- **Hand-written `can*()` methods directly on the Resource** — used
  wherever Filament's automatic policy discovery can't apply cleanly:
  `AdministratorResource` and `CustomerResource` both back onto the shared
  `User` model but need different scopes and different permissions, so each
  overrides `getEloquentQuery()` (filtering by `user_type`) and its own
  `can*()` set rather than sharing one `UserPolicy`. `VendorResource` and
  `StoreResource` instead defer entirely to the Phase 2 `VendorPolicy`/
  `StorePolicy` (Filament's default policy-discovery), since those already
  encode the owner-or-permission rule correctly and adding resource-level
  overrides would just duplicate it. `RoleResource` scopes
  `getEloquentQuery()` to `guard_name = 'admin'` so vendor-guard roles never
  leak into the admin UI.

### Resources delivered

| Resource | Nav group | Gate | Notes |
|---|---|---|---|
| Administrators | User Management | `admins.manage` | Shares `User` model with Customers via `getEloquentQuery()` scoping; self-delete blocked (`$record->id !== auth()->id()`); `CreateAdministrator::handleRecordCreation()` force-fills `email_verified_at` (admin-created accounts are pre-verified) |
| Roles | User Management | `roles.manage` | Spatie `Role`, `CheckboxList` of admin-guard permissions |
| Customers | User Management | `customers.view`/`customers.manage` | `getEloquentQuery()` scoped to `user_type = Customer`; `canCreate()` hard-false (customers self-register only) |
| Vendors | Vendor Management | `VendorPolicy` | No create page — vendors are provisioned via `VendorApplication` approval in Phase 5, not created directly; five status-transition `Action`s (approve/reject/suspend/reactivate/terminate), each visible only when the acting user has the matching permission *and* the record is in a valid source status |
| Stores | Vendor Management | `StorePolicy` | Same no-create-page rationale; `social_links`/`working_hours` edited via `KeyValue` components |
| Countries / States / Cities | Platform | `settings.manage` | Cascading `Select`s (state depends on country, city depends on state) |
| Languages | Platform | `languages.manage` | Single-default enforcement (see below) |
| Currencies / Exchange Rates | Platform | `currencies.manage` | Exchange rates are a standalone top-level resource rather than a relation manager (the hasOne relation-manager generator was not usable non-interactively in this environment) |
| Settings | Platform | `settings.manage` | Table renders `Setting::typed_value` (Phase 2 accessor) rather than the raw stored string |
| Audit Logs | Security | `logs.view` | Fully read-only: no form, no create/edit/delete — `audit_logs` is an insert-only ledger by design (Phase 2) |

### Dashboard widget

`App\Filament\Widgets\StatsOverview` — four `Stat`s: total customers, active
(approved) vendors, pending/under-review vendors, total stores.

### Single-default enforcement

`Language` and `Currency` both gained a `static::saving()` hook: setting
`is_default = true` on any row unsets it on every other row of that model in
the same query, guarding the `exists` check so an `INSERT` doesn't produce a
no-op `WHERE id != NULL`. Applied identically to both models rather than
factored into a shared trait, since each model's hook is three lines and a
trait would add more indirection than it removes for two call sites.

## A genuine bug caught by testing (worth recording)

Writing `AdminPanelAccessTest` surfaced two distinct, unrelated defects:

1. **Missing `FilamentUser` contract (application bug).** Every admin-panel
   request 403'd, including for a `Super Admin` hitting `/admin` itself.
   Filament's `Authenticate` middleware runs
   `abort_if($user instanceof FilamentUser ? !$user->canAccessPanel($panel) : config('app.env') !== 'local', 403)`
   — since `User` didn't implement `FilamentUser`, every request fell through
   to the `app.env !== 'local'` branch, which is `true` in both the test
   environment (`testing`) and production. Fixed by implementing the
   contract (see "Panel wiring" above). This would have taken production
   down entirely on first deploy; caught here only because the test asserted
   real HTTP responses rather than checking permission logic in isolation.
2. **Test fixture gap, not an app bug.** After fixing (1), Super Admin still
   403'd on every resource because `AdminPanelAccessTest` never seeded
   `RolePermissionSeeder`, so `Role::where('name', 'Super Admin')->first()`
   returned `null` and `assignRole(null)` silently assigned nothing. Fixed by
   adding a `beforeEach()` seed call — the same pattern already used in
   `tests/Feature/Seeders/CoreSeedersTest.php` and now the standard for any
   test exercising role/permission-gated behavior.

A third, unrelated defect was caught incidentally while writing the
single-default tests: `LanguageFactory` called `fake()->languageName()`,
which doesn't exist in `fakerphp/faker` (only `languageCode()` does) — the
factory had never actually been exercised by a test until this phase. Fixed
by using `fake()->unique()->word()` instead.

## Tests

- `./vendor/bin/pest` — **97/97 passing** (77 carried from Phases 1–3, 20 new):
  - `AdminPanelAccessTest` (5): guest/customer redirect-away, a super admin
    reaching every Phase 4 resource index page, a staff role without the
    relevant permission getting `403`, a staff preset with a partial
    permission set reaching only its scoped resource.
  - `VendorResourceActionsTest` (7, via `Livewire::test()` +
    `callTableAction`): approve/reject/suspend/reactivate/terminate each
    produce the correct status transition and side-effect field, plus two
    visibility checks (action hidden without permission; action hidden for
    an out-of-range source status).
  - `AdministratorResourceTest` (3): self-delete blocked, deleting a
    different administrator allowed, panel-created administrators end up
    with `email_verified_at` set.
  - `SingleDefaultEnforcementTest` (5): create-new-default unsets the old
    one, update-to-default unsets siblings, saving a non-default record
    doesn't disturb the existing default — each asserted for both `Language`
    and `Currency`.
- `./vendor/bin/pint --test` — passing, zero style violations.
- `migrate:fresh --seed` verified end-to-end against a clean SQLite database.
- Manual smoke test via `php artisan serve`: guest `GET /admin` redirects
  302 to `/admin/login`, which returns `200`.

## Completion Gate Check (Phase 4)

| Criterion | Status |
|---|---|
| Every listed resource is manageable through the panel | ✅ 13 resources, table above |
| Staff access is permission-based, not role-name-based | ✅ every `can*()` check resolves through Spatie permissions, tested for both an allowed and a forbidden preset |
| Vendors/Stores cannot be created directly from the panel | ✅ enforced by `VendorPolicy::create()`/`StorePolicy::create()` returning `false`, no create route registered |
| Administrators cannot delete themselves | ✅ tested |
| Audit log is read-only | ✅ no form/create/edit/delete registered |
| Single-default invariant holds for Language/Currency | ✅ tested for both create and update paths |
| Admin panel requires the `admin` guard specifically | ✅ inherited from Phase 3, `FilamentUser::canAccessPanel()` adds a second, independent check |
| Full test suite passes | ✅ 97/97 |

## Known limitations carried forward

1. The two sandbox limitations carried from Phases 1–3 (no MySQL server, no
   Larastan) remain unchanged.
2. Vendor/Store provisioning (the flow that actually creates these records)
   is Phase 5's responsibility — this phase only builds the admin-side
   management surface for records that already exist.
3. `ExchangeRateResource` is a standalone top-level resource rather than a
   relation manager nested under `CurrencyResource`, because
   `make:filament-relation-manager` could not be driven non-interactively
   for a `hasOne` relation in this environment; functionally equivalent for
   an admin audience, revisit if a nested UX is later required.

None of these block Phase 5 (Vendor Registration, Approval & Store
Management), which is the next phase in the roadmap.
