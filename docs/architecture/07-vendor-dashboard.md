# 07 — Vendor Dashboard Page List

Vendor dashboard runs as a second Filament panel: panel ID `vendor`, path
`/vendor`, guard `vendor`. Using Filament (rather than bespoke Blade) for the
vendor dashboard gives store-scoped CRUD, tables, and forms for free while
reusing the same component library and design tokens as `/admin`, and every
resource query is auto-scoped by the `BelongsToVendorScope` described in
[01-system-architecture.md](01-system-architecture.md).

## 1. Pages / Resources

| Route (within `/vendor`) | Page | Notes |
|---|---|---|
| `/vendor` | Dashboard (sales overview, pending orders, low stock, earnings widgets) | |
| `/vendor/products` | Product list | Vendor-scoped `ProductResource` |
| `/vendor/products/create` | Create product | All product types, variation builder |
| `/vendor/products/{id}/edit` | Edit product | |
| `/vendor/inventory` | Inventory / stock levels | Adjustments, transfer requests |
| `/vendor/orders` | Vendor sub-orders list | Only this vendor's `vendor_orders` |
| `/vendor/orders/{id}` | Order detail | Update fulfilment status, print packing slip |
| `/vendor/shipments` | Shipments | Assign carrier/tracking number |
| `/vendor/coupons` | Vendor-scoped coupons | |
| `/vendor/reviews` | Reviews on own store/products | Respond action |
| `/vendor/questions` | Product Q&A | Answer action |
| `/vendor/returns` | Return requests on own items | Approve/reject/respond |
| `/vendor/disputes` | Disputes involving own store | Submit statement/evidence |
| `/vendor/earnings` | Earnings overview | Commission breakdown, wallet balance |
| `/vendor/withdrawals` | Withdrawal requests | Request/view status |
| `/vendor/analytics` | Store analytics | Sales, top products, traffic (if enabled) |
| `/vendor/store-settings` | Store profile | Logo, banner, policies, socials, SEO |
| `/vendor/store-settings/hours` | Working hours & vacation mode | |
| `/vendor/staff` | Store staff | Owner only; invite/permission assignment |
| `/vendor/subscription` | Subscription plan | Upgrade/downgrade/cancel, usage limits |
| `/vendor/notifications` | Notification center | |
| `/vendor/support` | Support tickets | |
| `/vendor/documents` | Verification documents | Upload/resubmit |

## 2. Onboarding Flow (outside the panel, public Blade)

`/vendor/register` (multi-step form: business info → store info → banking →
documents → terms) → `vendor_applications` record created with status
`pending` → admin review (or auto-approval per settings) → on approval, a
`Vendor` + `Store` + vendor-owner `User` (guard `vendor`) are provisioned and
the applicant is emailed panel access.

## 3. Access Rules

- Vendor Owner: full access to all pages above for their own store.
- Vendor Staff: sees only the resources their granted store-scoped permissions
  allow (see [03-modules-and-roles.md](03-modules-and-roles.md) §4); `/staff`
  and `/store-settings` (banking fields) are owner-only regardless of granted
  permissions.
- A suspended/deactivated/banned vendor is denied panel login entirely
  (guard-level check), not just individual resource actions — this is the
  Phase 5 completion-gate requirement that "vendor suspension restricts vendor
  access."

## 4. Phase Mapping

- Phase 5: dashboard shell, store-settings, staff, documents, onboarding
- Phase 6: products, inventory (basic)
- Phase 7: inventory (warehouses/transfers)
- Phase 9: reviews, questions
- Phase 12: orders, shipments
- Phase 14: earnings, withdrawals
- Phase 17: coupons
- Phase 18: returns, disputes
- Phase 20: analytics
- Phase 22: support tickets
