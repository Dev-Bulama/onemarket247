# 13 — Full Phased Development Roadmap (Phase 0 → Phase 37)

This is the authoritative phase order for OneMarket247. No phase begins until
the previous phase's completion gate is satisfied and documented. Phases
1–27 build the complete web platform; the **Web Completion Gate** at the end
of Phase 27 must pass, producing a **Web Marketplace Completion Report**,
before Phase 28 (API finalization) starts. Phase 29 (first mobile code) does
not start until Phase 28 also produces an **API Finalization Report**.

## Phase Index

| Phase | Name | Primary Output |
|---|---|---|
| 0 | Requirements, architecture, dev plan | This document set |
| 1 | Laravel project foundation | Installed app, base config, package scaffolding |
| 2 | Database foundation & core models | Batches 1–8 migrations/models/factories/seeders |
| 3 | Authentication, authorization, account security | Multi-guard auth, roles/permissions, 2FA |
| 4 | Filament administration panel | Admin resources for every completed module |
| 5 | Vendor registration, approval, store management | Vendor onboarding + store dashboard shell |
| 6 | Product catalog, categories, brands, attributes, media | Full catalog module |
| 7 | Inventory & warehouse management | Stock engine, overselling prevention |
| 8 | Customer-facing web marketplace | Storefront pages, search, discovery |
| 9 | Customer account, wishlist, compare, reviews, questions | Account module |
| 10 | Cart system | Multi-vendor cart |
| 11 | Single-page checkout | Idempotent order creation |
| 12 | Order management | Parent/vendor sub-orders |
| 13 | Payment gateways & payment security | Paystack + gateway abstraction |
| 14 | Commission, vendor earnings, wallet, withdrawals | Marketplace finance engine |
| 15 | Shipping, delivery, order tracking | Shipping/delivery module |
| 16 | Taxes, currencies, languages | i18n + money engine |
| 17 | Discounts, coupons, promotions, flash sales | Promotions engine |
| 18 | Returns, refunds, exchanges, disputes | Post-purchase resolution module |
| 19 | CMS, blog, menus, homepage builder | Content management |
| 20 | SEO, analytics, newsletter, marketing | Growth/marketing tooling |
| 21 | SMTP, email templates, SMS, push, notifications | Communication layer |
| 22 | Optional advanced marketplace features | Wallet, rewards, referrals, gift cards, affiliates, support |
| 23 | Import, export, backups, logs, system health | Platform operations |
| 24 | Security audit and hardening | Verified threat-model coverage |
| 25 | Performance optimization | Query/cache/index tuning |
| 26 | Complete web testing & QA | Full automated + critical-scenario coverage |
| 27 | Web documentation, installation, deployment | Installer, docs, CI/CD |
| — | **WEB COMPLETION GATE** | **Web Marketplace Completion Report** |
| 28 | REST API finalization for mobile | API Finalization Report |
| 29 | React Native project foundation | Mobile scaffolding |
| 30 | Mobile authentication & onboarding | |
| 31 | Mobile customer marketplace | |
| 32 | Mobile cart, checkout, payments | |
| 33 | Mobile orders, tracking, returns, support | |
| 34 | Mobile profile, wallet, rewards, notifications | |
| 35 | Mobile vendor features | |
| 36 | Final cross-platform testing | Cross-Platform Acceptance Report |
| 37 | Mobile build, store preparation, final delivery | Mobile Application Completion Report |

## Working Method (applies to every phase 1–37)

Before code, each phase states: objective; required modules; files to
create/modify; database changes; security considerations; dependencies;
tests; completion criteria. During implementation: complete, connected code
only — no snippets, no "implement later," no placeholder pages, no
non-functional buttons, no skipped validation/authorization/tests. After
implementation: run migrations, run tests, check authorization,
responsiveness, database integrity, broken links, non-working buttons, JS
errors, server errors; document setup/testing; produce a phase completion
report; fix all discovered bugs before advancing.

## Completion Gate Summary (condensed — full detail in the phase's own
future documentation once that phase starts)

- **Phase 1**: app boots, DB connects, migrations run, Filament loads,
  queue processes a test job, scheduler runs a test command, storage works,
  tests run, zero install errors.
- **Phase 2**: all migrations run and roll back cleanly, FKs valid,
  relationships tested, seeders work, no duplicate/conflicting relations,
  core indexes present.
- **Phase 3**: every role logs in through its correct surface, permissions
  restrict correctly, vendors can't reach admin, customers can't reach
  vendor pages, staff access is permission-based, unauthorized API calls
  rejected, password reset/email verification work, auth tests pass.
- **Phase 4**: every completed module has a working, permission-gated admin
  UI; every form validates; bulk actions/search/filters work; responsive +
  RTL; no empty resources.
- **Phase 5**: registration, manual/automatic approval, secure document
  upload, rejection/suspension, store creation, vendor isolation, vendor
  staff permissions, public store pages, subscription rules all work.
- **Phase 6**: vendors create products (all types), approval rules,
  variation stock, media, categories/subcategories, brands, attributes,
  swatches, protected digital products, vendor product isolation, tests
  pass.
- **Phase 7**: accurate stock math, no overselling, variation stock,
  reservation, cancellation/return restoration, warehouse transfers,
  low-stock alerts, complete movement history.
- **Phase 8**: all public pages work, search/filters/sort work, vendor and
  product pages work, fully responsive, RTL, working nav, no broken links,
  no placeholder sections.
- **Phase 9**: profiles, addresses, wishlist, compare, verified reviews,
  moderation, Q&A, notifications work; no cross-customer data leakage.
- **Phase 10**: guest/auth cart, multi-vendor cart, merge-on-login, stock/
  price validation, coupons, save-for-later, accurate totals, tests pass.
- **Phase 11**: guest/registered/multi-vendor checkout, accurate totals, no
  duplicate orders, safe stock reservation, invalid prices rejected,
  idempotency verified, tests pass.
- **Phase 12**: parent/vendor sub-orders correct, vendor isolation on order
  items, unified customer view, status history, invoices, partial
  fulfilment, cancellation rules, notifications, tests pass.
- **Phase 13**: at least one live-ready gateway (Paystack) fully working,
  webhook signatures verified, duplicate callbacks can't duplicate
  payments, failed payments stay unpaid, refunds update financial records,
  payment logs, tests pass.
- **Phase 14**: accurate per-item commission, accurate wallet balances,
  pending/available split, refunds reverse balances, withdrawals work with
  approve/reject, no over-withdrawal possible, financial tests pass.
- **Phase 15**: shipping calculation (incl. multi-vendor), pickup, tracking,
  shipment events, delivery assignment, accurate ETAs, tests pass.
- **Phase 16**: correct tax calculation + order snapshots, accurate
  currency conversion/money math, exchange rates, language switching,
  translatable content, RTL across the platform.
- **Phase 17**: every discount type calculates correctly, restrictions
  enforced, vendor coupons scoped correctly, flash sales auto start/stop,
  abuse protection, tests pass.
- **Phase 18**: customer return requests, vendor responses, admin
  intervention, partial returns, refunds updating payment/wallet records,
  inventory restoration, audited disputes, tests pass.
- **Phase 19**: pages, blog, menus, reorderable homepage sections,
  scheduling, SEO fields, no static content where admin management is
  required.
- **Phase 20**: valid sitemap/structured data, working analytics settings,
  accurate reports, newsletter + unsubscribe, abandoned-cart recovery.
- **Phase 21**: SMTP saved securely + test email, templates render, order/
  vendor/customer notifications work, retry on failure, preferences
  respected.
- **Phase 22**: each enabled optional module fully works and disabled ones
  disappear safely; wallet/reward/gift-card accuracy; referral abuse
  resistance; tickets work; permissions enforced.
- **Phase 23**: validated imports (queued for large files), working
  exports, working scheduled backups with failure alerts, accurate system
  health, no secret exposure.
- **Phase 24**: security tests pass; no unauthorized resource access;
  upload restrictions hold; no secret exposure; payment/price manipulation
  fails; vendor isolation secure; audit logs work.
- **Phase 25**: no major N+1 remains; core pages performant; large tables
  paginate; reports/imports/exports don't block requests; images
  optimized; cache invalidates correctly.
- **Phase 26**: all critical and major integration tests pass; no known
  critical bug, fake button, placeholder page, or broken nav; no unhandled
  exception in normal workflows.
- **Phase 27**: a new developer can install from docs alone; production
  deployment/queue/scheduler/backup instructions verified; no secrets
  committed; API docs complete.

## WEB COMPLETION GATE

The React Native mobile application (Phase 29+) does not begin until **all**
of the following are true and documented in the **Web Marketplace Completion
Report**:

Laravel backend complete · Filament admin complete · vendor dashboard
complete · customer website complete · vendor registration + manual/
automatic approval work · vendor stores work · product creation/variations/
approval work · inventory + warehouses work · search/filters/wishlist/
compare work · cart works · guest/registered/multi-vendor checkout work ·
payments work (Paystack live-ready) · parent orders + vendor sub-orders work
· commission + vendor wallets + withdrawals work · shipping + tracking work
· taxes + multi-currency + multi-language + RTL work · coupons + flash sales
work · returns + refunds + disputes work · CMS + blog + homepage builder
work · SMTP + email templates + notifications work · SEO + sitemap +
analytics + reports work · imports/exports + backups work · permissions +
vendor isolation work · security testing passes · critical automated tests
pass · documentation complete · deployment guide complete · zero critical
bugs, placeholder modules, or non-functional buttons remain.

**Web Marketplace Completion Report** (produced once, at gate time) must
contain: completed modules; test results; known limitations; security
results; performance results; deployment readiness; API readiness;
explicit confirmation that mobile development may begin.

## Phase 28 → API Finalization Report

Verifies/hardens the already-in-use `/api/v1/*` surface (see
[08-api-endpoints.md](08-api-endpoints.md)) specifically for mobile
consumption: complete API docs, passing Postman/Newman collection,
authentication (customer + vendor) verified, pagination/filtering verified,
consistent error responses, secure payment endpoints, correct mobile-shaped
responses. Produces the **API Finalization Report** — the second and final
prerequisite before any mobile code is written.

## Phases 29–37 (mobile) — gated, not started in this repository yet

These phases (React Native foundation; auth/onboarding; customer
marketplace; cart/checkout/payments; orders/tracking/returns/support;
profile/wallet/rewards/notifications; vendor features; cross-platform
testing; store build & delivery) are fully specified in the original project
brief and will be elaborated with the same before/during/after working
method once Phase 28's API Finalization Report exists. **No mobile project
files, screens, or code are created as part of Phase 0.**

## Final Required Deliverables (end state, tracked against this list)

Laravel backend · Filament admin panel · vendor dashboard · customer
marketplace website · MySQL migrations · seeders/factories · REST API ·
API documentation · Postman collection · React Native app · Android config ·
iOS config · push notifications · automated backend/API/mobile tests ·
installation wizard · deployment docs · administrator/vendor/customer
manuals · mobile build instructions · production `.env.example` · GitHub
Actions workflow · queue/scheduler/backup configuration · security checklist
· production-readiness checklist · Web Marketplace Completion Report · API
Finalization Report · Mobile Application Completion Report · Final
Cross-Platform Acceptance Report.
