# 03 — Module Breakdown & Permission Matrix

## 1. Module → Sub-feature Breakdown

| Module | Sub-features |
|---|---|
| Identity & Access | Registration, login/logout, password reset, email/phone verification, 2FA, device sessions, login history, social login, API tokens |
| Vendor Management | Application, document verification, approval workflow, statuses, subscription plans, store profile, store staff, vacation mode |
| Catalog | Products (all types), variations/attributes, categories, brands, tags, collections, media |
| Inventory | Stock per product/variation/warehouse, reservations, transfers, movement history, low-stock alerts |
| Storefront | Homepage builder, search, filters, sorting, discovery (related/trending/recently viewed) |
| Customer Account | Profile, addresses, wishlist, compare, reviews, Q&A, notification preferences |
| Cart & Checkout | Guest/auth cart, multi-vendor grouping, coupons, single-page checkout, idempotent order creation |
| Order Management | Parent/sub-orders, statuses, invoices, packing slips, notes, history |
| Payments | Gateway abstraction, webhooks, refunds, reconciliation, logs |
| Finance | Commission engine, vendor wallet, withdrawals, customer wallet, gift cards, rewards |
| Shipping & Delivery | Zones/rates/classes, carriers, pickup, tracking, delivery personnel |
| Tax/Currency/Language | Tax engine, multi-currency, i18n, RTL |
| Promotions | Coupons, flash sales, automatic discounts |
| Returns/Refunds/Disputes | Return workflow, resolutions, dispute escalation |
| CMS & Marketing | Pages, blog, menus, homepage sections, SEO, sitemap, analytics, newsletter, abandoned cart |
| Communication | SMTP config, email templates, SMS/push/WhatsApp adapters, notification center |
| Optional/Advanced | Wallet, rewards, referrals, gift cards, affiliates, support tickets |
| Platform Ops | Import/export, backups, system health, audit logs |
| Security | AuthZ, rate limiting, encryption, headers, audit trail |
| REST API | Versioned endpoints for web + mobile |

## 2. Role Capability Matrix (high level)

| Capability | Super Admin | Admin (permissioned) | Staff (permissioned) | Vendor Owner | Vendor Staff | Customer |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Manage administrators/roles | ✅ | permission-gated | ❌ | ❌ | ❌ | ❌ |
| Approve/reject/suspend vendors | ✅ | permission-gated | permission-gated | ❌ | ❌ | ❌ |
| Manage own store settings | n/a | n/a | n/a | ✅ | permission-gated | ❌ |
| Manage own store staff | n/a | n/a | n/a | ✅ | ❌ | ❌ |
| Create/edit own products | via override | permission-gated | permission-gated | ✅ | permission-gated | ❌ |
| Approve products | ✅ | permission-gated | permission-gated | ❌ (own store auto-status only) | ❌ | ❌ |
| View all orders | ✅ | permission-gated | permission-gated | own vendor_orders only | own vendor_orders only (scoped) | own orders only |
| Manage payments/gateways | ✅ | permission-gated | ❌ | ❌ | ❌ | ❌ |
| Approve/process withdrawals | ✅ | permission-gated | ❌ | request only | ❌ | ❌ |
| Manage global settings/CMS | ✅ | permission-gated | ❌ | ❌ | ❌ | ❌ |
| Manage own profile/addresses | n/a | n/a | n/a | ✅ | ✅ | ✅ |
| Leave reviews | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (verified purchase) |
| Respond to reviews | ✅ | permission-gated | permission-gated | ✅ (own store) | permission-gated | ❌ |

## 3. Permission Naming Convention

Spatie `permission` package, format `{module}.{action}` (e.g. `products.approve`,
`vendors.suspend`, `withdrawals.approve`, `settings.manage`, `cms.manage`,
`reports.view`, `translations.manage`, `support.manage`). Permissions are grouped
into role presets seeded per Phase 3 (Super Admin, Admin, Catalog Staff, Support
Staff, Finance Staff, Vendor Owner, Vendor Staff — Products, Vendor Staff — Orders).

Full canonical permission list (seed data, not exhaustive of every future addition):

```
admins.manage            staff.manage              roles.manage
vendors.view             vendors.approve           vendors.suspend           vendors.terminate
vendors.manage_commission
stores.manage
customers.view           customers.manage
products.view            products.create           products.update           products.delete
products.approve         products.feature
categories.manage        brands.manage             attributes.manage
inventory.manage         warehouses.manage
orders.view              orders.manage             orders.export
payments.view            payments.manage           refunds.manage
commissions.manage       withdrawals.view          withdrawals.approve
shipping.manage          taxes.manage
coupons.manage           flash_sales.manage
returns.manage           disputes.manage
cms.manage                blog.manage               menus.manage
seo.manage                analytics.view
newsletter.manage
translations.manage      currencies.manage         languages.manage
notifications.manage     email_templates.manage    smtp.manage
support.manage
reports.view
settings.manage          security.manage
backups.manage           logs.view                 system_health.view
```

## 4. Vendor Staff Sub-roles (store-scoped)

Vendor owners can grant their staff any combination of store-scoped permissions,
mirroring the admin convention but implicitly restricted to `vendor_id = owner's
vendor`:

```
store.products.manage    store.inventory.manage   store.orders.manage
store.orders.fulfil      store.coupons.manage     store.reviews.respond
store.settings.manage    store.staff.manage       store.reports.view
store.withdrawals.request
```

These are enforced by Policies checking both the permission *and* the `vendor_id`
match — a vendor staff member can never be granted cross-store access even by
mistake, because the scope check is structural, not permission-based.
