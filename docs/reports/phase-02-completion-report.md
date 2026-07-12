# Phase 2 Completion Report — Database Foundation & Core Models

## Objective

Build the core normalized schema explicitly scoped to Phase 2 by the project brief:
identity extensions, vendor/store skeleton, customer profile, addresses, and platform
primitives (geography, settings, languages, currencies) — with models, relationships,
enums, factories, seeders, ownership policies, and tests.

Catalog, inventory, tax, and shipping schemas are **intentionally out of scope** here —
those are Phases 6, 7, 15, and 16's own deliverables per
[docs/architecture/13-development-roadmap.md](../architecture/13-development-roadmap.md).
Phase 2 only builds what the phase brief explicitly lists: Users, User profiles, Roles,
Permissions, Vendors, Vendor applications, Vendor documents, Stores, Store staff,
Customer profiles, Addresses, Countries, States, Cities, Settings, Languages,
Currencies, Exchange rates, Media, Notifications, Activity logs, Audit logs.

## What was built

### Migrations (18 new, in dependency order)

`countries`, `states`, `cities`, `languages`, `currencies`, `exchange_rates`,
`settings`, `users` (extended with `user_type`, `status`, `phone`,
`phone_verified_at`), `user_profiles`, `vendors`, `vendor_applications`,
`vendor_documents`, `stores`, `store_staff`, `customer_profiles`, `addresses`
(polymorphic), `audit_logs` (insert-only, no `updated_at`), `notifications`
(Laravel's native table). `roles`/`permissions`/`activity_log`/`media` already
existed from Phase 1's package installs and are wired up this phase instead of
re-created.

### Enums (11, `app/Enums/`)

`UserType`, `UserStatus`, `VendorStatus`, `VendorApplicationStatus`,
`VendorDocumentType`, `VendorDocumentStatus`, `StoreStatus`, `StoreStaffStatus`,
`Gender`, `LanguageDirection`, `CurrencySymbolPosition`, `SettingType` — backed PHP
enums implementing Filament's `HasLabel`/`HasColor` where they'll drive admin
badges later, per the status-management standard in
[docs/CODING_STANDARDS.md](../CODING_STANDARDS.md).

### Models (16 new + `User` extended)

`Country`, `State`, `City`, `Language`, `Currency`, `ExchangeRate`, `Setting`,
`UserProfile`, `Vendor`, `VendorApplication`, `VendorDocument`, `Store`,
`StoreStaff`, `CustomerProfile`, `Address`, `AuditLog`. `User` gained
`HasRoles` (Spatie), typed casts for the new columns, and relationships to
`profile`, `vendor`, `customerProfile`, `storeStaff`, `addresses` (polymorphic),
`auditLogs`.

Notable design points:
- `Vendor.bank_account_name` / `bank_account_number` use Laravel's `encrypted`
  cast — verified round-trips correctly and is unreadable in raw DB storage
  (see `VendorTest`).
- `Setting` exposes a `typed_value` accessor that decodes the raw text column
  according to its `type` (`string`/`boolean`/`integer`/`json`) so callers
  never hand-parse settings.
- `Address.ownerUserId()` resolves the owning `User` id across all three
  polymorphic `addressable` types (`User`, `Vendor`, `Store` via its vendor) —
  the single place ownership resolution lives, consumed by `AddressPolicy`.
- `AuditLog` sets `UPDATED_AT = null` and is only ever inserted, never
  updated, matching the immutable-ledger rule in
  [docs/architecture/02-database-erd.md](../architecture/02-database-erd.md) §3.

### Factories & Seeders

Factories for every new testable model. Seeders: `RolePermissionSeeder`
(seeds the full permission vocabulary and role presets from
[docs/architecture/03-modules-and-roles.md](../architecture/03-modules-and-roles.md),
idempotent via `findOrCreate`), `CountryStateCitySeeder` (a small real
representative geography set — 5 countries/9 states/14 cities — not full
world data; a production deployment imports complete geography via the
Phase 23 import tooling), `CurrencySeeder` (6 starter currencies with static
exchange rates — live rate sync is Phase 16), `LanguageSeeder` (English
default + Arabic as the RTL example + French), `SettingsSeeder` (baseline
`app.*`/`vendor.*` keys). `DatabaseSeeder` orchestrates all of the above plus
a seeded super-admin test user.

### Policies

`VendorPolicy`, `StorePolicy`, `CustomerProfilePolicy`, `AddressPolicy` —
encode the ownership + admin-permission-override pattern from
[docs/architecture/01-system-architecture.md](../architecture/01-system-architecture.md) §4
and [docs/architecture/10-security-architecture.md](../architecture/10-security-architecture.md) §2.
All four auto-discovered correctly by Laravel's naming convention (verified via
`Gate::getPolicyFor()`). `StorePolicy` additionally checks active
`store_staff` membership plus a store-scoped permission, matching the
vendor-staff sub-role design.

## Database changes

18 new tables (see above) plus 4 new columns on `users`. Full detail in
[docs/architecture/02-database-erd.md](../architecture/02-database-erd.md).

## Security considerations addressed this phase

- `Vendor` bank fields encrypted at rest (`encrypted` cast), verified via test
  that the raw DB column value differs from the plaintext.
- Ownership policies are the concrete implementation of the vendor-isolation
  architecture decision — not just documented, but tested: an unrelated user
  cannot view/update another vendor's record, another store's settings, or
  another customer's profile/address (`VendorPolicyTest`, `StorePolicyTest`,
  `CustomerProfilePolicyTest`, `AddressPolicyTest`).
- `audit_logs` is schema-enforced insert-only (no `updated_at` column,
  `AuditLog::UPDATED_AT = null`).
- `RolePermissionSeeder` is idempotent (`findOrCreate`), safe to re-run in
  any environment without duplicating roles/permissions — verified by test.

## Dependencies / notes

- Multi-guard authentication (`admin`, `vendor`, `customer` guards in
  `config/auth.php`) is **not** configured yet — that is explicitly Phase 3
  scope. `RolePermissionSeeder` seeds permissions under `admin`/`vendor`
  guard names now so the vocabulary exists; guard wiring in Phase 3 makes
  `$user->can(...)` checks resolve against them for real. Phase 2's policy
  tests validate the ownership branch of every policy directly and validate
  the permission-override branch using ad-hoc `web`-guarded permissions
  (Laravel's current default guard) so they don't depend on Phase 3 landing
  first.
- The known Phase 1 sandbox limitations (no MySQL server, Larastan skipped)
  still apply; see
  [docs/reports/phase-01-completion-report.md](phase-01-completion-report.md).

## Tests

- `./vendor/bin/pest` — 36/36 passing (Phase 1's 2 + 34 new: geography,
  vendor, store, address, setting model tests; vendor/store/customer-profile/
  address policy tests; seeder tests including an idempotency check).
- `./vendor/bin/pint --test` — passing, zero style violations.
- `php artisan migrate:fresh --seed` — clean end-to-end run.
- `php artisan migrate:rollback --step=24` → `migrate:status` (24 pending) →
  `migrate` — full round-trip verified clean.
- Manual smoke test: `php artisan serve` + `curl` against `/` and
  `/admin/login` both return `200` after the new migrations/models land.

## Completion Gate Check (Phase 2)

| Criterion | Status |
|---|---|
| All migrations run successfully | ✅ |
| Rollbacks work | ✅ full-stack rollback/re-migrate verified |
| Foreign keys are valid | ✅ (geography, vendor→user, store→vendor, addresses polymorphic all exercised in tests) |
| Model relationships are tested | ✅ |
| Seeders work | ✅ including idempotency |
| No duplicate or conflicting relationships exist | ✅ |
| Core database indexes are present | ✅ status/type columns and FK columns indexed per migration |

## Known limitations carried forward

1. Geography data is a small representative seed set, not a complete world
   dataset — flagged above, not a blocker for Phase 3+.
2. Guard configuration (`admin`/`vendor`/`customer`) is deferred to Phase 3
   by design; permission checks in policies are structurally correct and
   tested but won't resolve against real authenticated sessions until then.
3. MySQL connectivity remains unverified in this sandbox (carried from
   Phase 1); schema uses no MySQL-specific syntax so this is not expected to
   surface issues in CI/staging.
