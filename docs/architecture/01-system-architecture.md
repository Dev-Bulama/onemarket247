# 01 — System Architecture

## 1. Overview

OneMarket247 is a single Laravel monolith serving three distinct front-ends plus a
versioned REST API, all backed by one MySQL database:

```
                                ┌─────────────────────────────┐
                                │        MySQL 8 Database      │
                                │   (single source of truth)   │
                                └───────────────┬───────────────┘
                                                │
                    ┌───────────────────────────┼───────────────────────────┐
                    │                            │                            │
          ┌─────────▼─────────┐        ┌─────────▼─────────┐        ┌─────────▼─────────┐
          │  Laravel Backend   │        │  Redis (cache,     │        │  Queue Workers /   │
          │  (Application Core)│◄──────►│  queues, sessions, │◄──────►│  Scheduler          │
          │                    │        │  rate limiting)     │        │  (Supervisor)       │
          └─────────┬─────────┘        └─────────────────────┘        └─────────────────────┘
                    │
    ┌────────────────┼────────────────┬─────────────────────────┐
    │                │                │                         │
┌───▼────┐   ┌────────▼───────┐  ┌─────▼──────┐        ┌─────────▼─────────┐
│ Filament│   │ Vendor Dashboard│  │ Customer   │        │  REST API          │
│ Admin   │   │ (Filament panel │  │ Website    │        │  /api/v1/*         │
│ Panel   │   │  or Blade)      │  │ (Blade +   │        │  (Sanctum tokens)  │
│ /admin  │   │  /vendor        │  │  Alpine.js)│        │                    │
└─────────┘   └────────────────┘  └────────────┘        └─────────┬─────────┘
                                                                    │
                                                          (future, Phase 29+)
                                                          ┌─────────▼─────────┐
                                                          │ React Native App   │
                                                          │ (Android / iOS)    │
                                                          └────────────────────┘
```

**Key principle:** the React Native app is a pure API consumer. It is never a second
backend and never owns a second database — this is enforced structurally by not
starting mobile work until Phase 29, long after the API contract in
[08-api-endpoints.md](08-api-endpoints.md) is frozen and verified.

## 2. Application Modules (top level)

1. **Identity & Access** — authentication, authorization, roles/permissions, sessions, 2FA
2. **Vendor Management** — onboarding, approval, stores, staff, subscriptions
3. **Catalog** — products, variations, categories, brands, attributes, media
4. **Inventory** — stock, warehouses, reservations, transfers
5. **Marketplace Storefront** — public browsing, search, discovery, SEO
6. **Customer Account** — profile, addresses, wishlist, compare, reviews, Q&A
7. **Cart & Checkout** — multi-vendor cart, single-page checkout, idempotent order creation
8. **Order Management** — parent orders, vendor sub-orders, fulfilment, invoices
9. **Payments** — gateway abstraction, webhooks, reconciliation
10. **Finance** — commission engine, vendor wallets, withdrawals, ledgers
11. **Shipping & Delivery** — zones/rates, carriers, tracking, delivery personnel
12. **Tax, Currency, Language** — tax engine, multi-currency, i18n/RTL
13. **Promotions** — coupons, flash sales, discounts
14. **Returns, Refunds & Disputes**
15. **CMS & Marketing** — pages, blog, menus, homepage builder, SEO, analytics, newsletter
16. **Communication** — SMTP, templated email, SMS/push/WhatsApp adapters, notification center
17. **Advanced/Optional** — wallet top-ups, rewards, referrals, gift cards, affiliates, support tickets
18. **Platform Operations** — import/export, backups, system health, audit logs
19. **Security & Compliance**
20. **REST API** — versioned, Sanctum-authenticated, consumed by web JS and future mobile

Each module maps to one or more Laravel domains under `app/Domain/{Module}` (services,
actions, DTOs) with thin HTTP/Filament layers on top. See
[03-modules-and-roles.md](03-modules-and-roles.md) for the full breakdown.

## 3. User Roles

| Role | Guard/Panel | Notes |
|---|---|---|
| Super Administrator | `admin` (Filament) | Full access, cannot be restricted by permissions |
| Administrator | `admin` (Filament) | Permission-gated via Spatie roles/permissions |
| Staff (back-office) | `admin` (Filament) | Scoped permissions, e.g. support-only, catalog-only |
| Vendor Owner | `vendor` | Owns one store; full store-scoped access |
| Vendor Staff | `vendor` | Store-scoped, permission-gated (products, orders, etc.) |
| Customer | `web` / `sanctum` | Storefront + account + future mobile |
| Delivery Personnel | `delivery` (optional module) | Assigned shipments only |
| Affiliate | `affiliate` (optional module) | Referral links, commission, payouts |
| Guest | none | Browsing, guest cart, guest checkout |

Each role authenticates through a dedicated guard so that, e.g., a vendor session token
can never satisfy an admin policy check. Sanctum is used for API/token auth (customers
today, mobile app of every role in Phase 28+); session-based guards are used for the
three server-rendered surfaces (`admin`, `vendor`, `web`).

## 4. Vendor Isolation Strategy

Vendor data isolation is enforced at **three layers**, not just the UI:

1. **Data layer** — every vendor-owned table (`products`, `product_variations`,
   `vendor_orders`, `stores`, `withdrawals`, etc.) carries a `vendor_id` foreign key.
   A global Eloquent scope (`BelongsToVendorScope`) auto-filters queries when the
   authenticated actor is a vendor guard, so a missing `->where()` cannot leak data.
2. **Authorization layer** — Laravel Policies check `record->vendor_id ===
   auth()->user()->vendor_id` (or the equivalent store-staff relationship) on every
   `view`, `update`, `delete` gate, independent of the scope above (defense in depth
   against raw queries / relation managers that bypass global scopes).
3. **API layer** — Sanctum ability tokens are minted per-actor-type
   (`vendor:*`, `customer:*`) and API Resources never expose foreign vendors' internal
   fields (cost price, margins, other vendors' order items) regardless of which vendor
   is asking.

Order data isolation is structurally guaranteed by the **parent order / vendor
sub-order** split (see [09-lifecycles.md](09-lifecycles.md)): a vendor is never queried
against the `orders` table directly, only against `vendor_orders` scoped to their
`vendor_id`, while a customer only ever sees the parent `orders` record with an
eager-loaded, unified view across all vendor sub-orders.

## 5. Multi-Currency / Multi-Language / RTL

- **Currency:** all monetary values are stored as integer minor units (cents) using a
  dedicated `Money` value object (backed by `brick/money` or equivalent) — never
  floats. Orders snapshot the currency code, symbol, and exchange rate at time of
  purchase in `orders.currency_code` / `orders.exchange_rate_snapshot` so historical
  orders are immune to later rate changes.
- **Language:** Laravel's translation strings for UI chrome; database-content
  translation (`products`, `categories`, `pages`, etc.) via a `translations` JSON
  column pattern (`spatie/laravel-translatable`) — one row per entity, translations
  co-located, no join needed for a single-locale render.
  RTL is a `direction` attribute on the `languages` table and controls a `dir="rtl"`
  attribute at the root of every server-rendered layout (admin, vendor, storefront)
  plus a mirrored Tailwind config.

## 6. Money, Status, and API Response Standards

- **Money:** integer minor units + `Money` value object; formatting only at the
  presentation edge (Blade/API Resource), never in the database or business logic.
- **Status:** every stateful entity (`Order`, `VendorOrder`, `Payment`, `Withdrawal`,
  `ReturnRequest`, `Vendor`, `Product`) uses a native PHP 8.1+ backed `enum` implementing
  a shared `HasLabel`/`HasColor` contract consumed identically by Filament badges and
  API Resources — one definition, two renderings.
- **API responses:** every `/api/v1/*` response follows one envelope:
  ```json
  { "data": {}, "meta": {}, "message": null }
  ```
  and every error follows:
  ```json
  { "message": "...", "errors": {}, "error_code": "VALIDATION_FAILED" }
  ```
  with standard HTTP status codes (422 validation, 401/403 auth, 404, 409 conflict/
  idempotency, 429 rate limit, 5xx). Defined once in
  [08-api-endpoints.md](08-api-endpoints.md) and enforced via a base `ApiController` /
  exception handler renderer — not re-implemented per controller.

## 7. Service/Repository Boundaries

- **Controllers** — HTTP concerns only: resolve Form Request, call a Service/Action,
  return a Resource/Blade view. No business logic.
- **Form Requests** — validation + `authorize()` (delegates to Policies).
- **Services / Actions** (`app/Domain/{Module}/Actions/*`) — the actual business logic,
  wrapped in `DB::transaction()` for anything financial or multi-table
  (checkout→order, payment→wallet, refund→ledger).
- **Policies** — the single source of truth for "can actor X do Y to record Z."
- **Repositories** — used only where they add real value: complex catalog/search
  queries, report aggregation. Simple CRUD uses Eloquent directly — no blanket
  repository-per-model layer.
- **Events/Listeners** — cross-module side effects (`OrderPlaced` →
  `DeductInventory`, `SendOrderConfirmationEmail`, `RecordCommission`) so modules stay
  decoupled and each listener is independently testable/queueable.

## 8. Mobile API Strategy (forward-looking, not built until Phase 28+)

The API surface consumed by Blade/Alpine today (via internal Fetch calls) and the
future mobile app are the **same versioned `/api/v1/*` routes** — there is no
"internal API" vs "mobile API" split. This guarantees that by the time Phase 28
(API Finalization) starts, the endpoints are already battle-tested by the web
frontend's own usage throughout Phases 8–22, rather than being designed from
scratch for mobile. See [08-api-endpoints.md](08-api-endpoints.md).
