# 11 — Testing Roadmap

## 1. Test Layers

| Layer | Tooling | Scope |
|---|---|---|
| Unit | PHPUnit/Pest | Value objects (Money), services, enums, calculators (commission, tax, discount) |
| Feature | PHPUnit/Pest + Laravel testing helpers | HTTP endpoints (web routes, Filament resources via `livewire()`, API routes), auth, authorization |
| Integration | Pest | Multi-step flows across modules (checkout → order → payment → commission → wallet) |
| Browser/UI | Laravel Dusk or Playwright | Real browser flows: forms, modals, nav, checkout, RTL, responsive breakpoints |
| API contract | Pest + Postman/Newman | `/api/v1/*` request/response shape, versioning stability |
| Security | PHPUnit + manual/automated scans (see Phase 24) | AuthZ boundaries, injection, upload restrictions |
| Load/Performance | k6 or Laravel Benchmark tooling | Homepage, search, checkout under concurrent load |

## 2. Per-Domain Test Requirements

- **Auth**: registration, login, logout, password reset, email verification,
  2FA challenge, throttling triggers lockout, token revocation, guard
  isolation (vendor token rejected on admin routes and vice versa).
- **Vendor isolation**: vendor A cannot read/update/delete vendor B's
  products, orders, withdrawals, documents via any route (Filament, Blade,
  or API) — parameterized test run against every vendor-scoped resource.
- **Product/Catalog**: simple + variable product creation, variation stock
  independence, approval workflow transitions, media upload validation.
- **Inventory**: concurrent-order stock test (two simultaneous checkouts
  against last unit of stock — exactly one succeeds), reservation →
  deduction → restoration cycle, warehouse transfer integrity.
- **Cart**: guest cart persistence, merge-on-login (no item loss/duplication),
  multi-vendor grouping, stale-price/stock detection.
- **Checkout**: idempotency-key replay produces one order, double-submit
  produces one order, invalid/expired coupon rejected, tax/shipping totals
  match a hand-computed fixture.
- **Orders**: parent/sub-order creation matches cart vendor split, vendor
  query never returns another vendor's `order_items`, status aggregation
  rules (partially_delivered, etc.).
- **Payments**: successful verify marks paid exactly once, failed verify
  never marks paid, duplicate webhook delivery is a no-op the second time,
  refund reduces `payments`/wallet/commission consistently.
- **Commission/Wallet/Withdrawal**: commission snapshot immutability
  (changing a rule does not alter historical `order_item_commissions`),
  wallet balance always reconstructable from ledger sum, withdrawal cannot
  exceed available balance even under concurrent requests
  (parallel-request test against `lockForUpdate`).
- **Returns/Refunds/Disputes**: partial return recomputes totals correctly,
  refund reverses commission proportionally, inventory restored, dispute
  audit trail complete.
- **Coupons/Discounts**: usage-limit enforcement under concurrency, expired
  coupon rejected, vendor coupon does not discount another vendor's items.
- **i18n/Currency**: order snapshots remain correct after a currency's
  exchange rate later changes; RTL layout smoke test per locale.
- **Uploads**: rejected MIME/extension, path-traversal filename rejected,
  signed URL required for private files.

## 3. Critical End-to-End Scenarios (must pass before Phase 26 gate)

1. Customer buys products from multiple vendors → correct vendor sub-orders.
2. Each vendor sees only their own order items.
3. Commission calculated and recorded correctly per item.
4. Vendor wallet updates correctly (pending → available on settlement).
5. Stock cannot be oversold under concurrent checkout.
6. Duplicate webhooks cannot duplicate payments.
7. Duplicate checkout submissions cannot duplicate orders.
8. Failed payments never mark an order paid.
9. Partial refunds update payment, commission, and wallet ledgers together.
10. Vendor suspension immediately blocks vendor dashboard/API access.
11. Product approval workflow gates storefront visibility correctly.
12. Guest cart merges into account cart on login without loss/duplication.
13. RTL renders correctly on storefront, admin, and vendor dashboard.
14. Currency snapshots on old orders remain accurate after rate changes.
15. Unauthorized users/tokens cannot access another actor's protected
    records (IDOR sweep across all resource routes).

## 4. Responsive/Browser Matrix

Widths: 320, 360, 375, 390, 414, 768, 1024, 1280, 1440, 1920px. Checked for:
no horizontal overflow, no broken/overlapping menus, no cut-off text, modals
render correctly, images aren't distorted, touch targets ≥ 44px, tables
degrade to scrollable/stacked layout on narrow widths. Run against
storefront, admin panel, and vendor dashboard.

## 5. CI Gate

Every PR/merge to the working branch runs: static analysis (PHPStan/Larastan),
code style (Pint), full Pest suite, and a smoke Dusk/Playwright run — a
failing run blocks merge (see [12-deployment-roadmap.md](12-deployment-roadmap.md)
§CI/CD). No phase is marked complete while its associated tests are red.
