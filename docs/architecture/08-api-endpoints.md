# 08 — REST API Endpoint Map (`/api/v1/*`)

Auth: Laravel Sanctum (bearer tokens for mobile/SPA-style consumption; the
web Blade frontend calls the same endpoints via same-origin fetch using
Sanctum's SPA cookie mode). Every response uses the standard envelope defined
in [01-system-architecture.md](01-system-architecture.md) §6. Rate limiting
via Redis-backed throttles, tiered by endpoint sensitivity (auth: strict,
catalog read: generous, payments: strict + idempotency-key required).

This endpoint map is designed and built incrementally (as each web feature
ships, its API is built alongside it — see per-phase notes) and is hardened
before mobile screens are built against it. ✅ marks what's actually
implemented as of the mobile-readiness audit (26 Aug 2026); everything else
here is still spec, not code.

## 1. Auth & Session

```
POST   /api/v1/auth/register               ✅
POST   /api/v1/auth/login                  ✅
POST   /api/v1/auth/logout                 ✅
POST   /api/v1/auth/forgot-password        ✅
POST   /api/v1/auth/reset-password         ✅
POST   /api/v1/auth/verify-email
POST   /api/v1/auth/resend-verification
POST   /api/v1/auth/2fa/challenge
POST   /api/v1/auth/social/{provider}
GET    /api/v1/auth/sessions               ✅
DELETE /api/v1/auth/sessions/{id}          ✅
POST   /api/v1/auth/device-tokens          (push notification registration)
```

## 2. Config / Reference Data

```
GET /api/v1/config                 ✅ (default currency/language, live payment methods)
GET /api/v1/languages              ✅
GET /api/v1/currencies             ✅
GET /api/v1/countries              ✅
GET /api/v1/countries/{id}/states  ✅
GET /api/v1/states/{id}/cities     ✅
```

## 3. Storefront / Catalog

```
GET /api/v1/home                   ✅ (mirrors Storefront\HomeController's sections exactly)
GET /api/v1/categories             ✅
GET /api/v1/categories/{slug}      ✅
GET /api/v1/brands                 ✅
GET /api/v1/brands/{slug}          ✅
GET /api/v1/products               ✅
GET /api/v1/products/{slug}        ✅
GET /api/v1/products/{slug}/reviews
GET /api/v1/products/{slug}/questions
GET /api/v1/stores                 ✅
GET /api/v1/stores/{slug}          ✅
GET /api/v1/stores/{slug}/products ✅
GET /api/v1/search                 ✅
GET /api/v1/search/suggestions
```
All list endpoints support `page`, `per_page`, `sort`, `filter[...]`, `q`
(same params as the corresponding storefront web page — see
`App\Http\Controllers\Storefront\Concerns\FiltersProducts`, reused as-is by
every `Api\V1\*` controller above so web and mobile filter identically).
The `/vendors` alias from the original spec was dropped — `Store` is the
real public-facing entity, "vendor" isn't a separate storefront concept.

## 4. Cart & Checkout

```
GET    /api/v1/cart                  ✅
POST   /api/v1/cart/items            ✅
PATCH  /api/v1/cart/items/{id}       ✅
DELETE /api/v1/cart/items/{id}       ✅
POST   /api/v1/cart/coupons          ✅
DELETE /api/v1/cart/coupons          ✅ (no {code} — a cart only ever has one coupon, matching CartCoupon's HasOne)
POST   /api/v1/cart/merge            ✅ (guest → authenticated merge, auth required)

POST   /api/v1/checkout/init         (returns idempotency key + revalidated totals)
POST   /api/v1/checkout/complete     (Idempotency-Key header required)
GET    /api/v1/checkout/{session}/status
```

Guest cart identity: the web's cart_token cookie doesn't work for a
bearer-token mobile client (no cookie jar Laravel can rely on there), so
every cart response includes `guest_token` when the caller isn't
authenticated — the app persists it locally and replays it as
`cart_token` (query param or body field) on every subsequent cart call.
See `App\Support\Cart\CartResolver`'s docblock.

## 4b. Checkout

```
POST /api/v1/checkout/init                        ✅ (revalidates cart, mints/reuses idempotency key)
POST /api/v1/checkout/complete                     ✅
GET  /api/v1/checkout/{checkout_session_key}/status ✅
```
Open to guests and Sanctum customers alike, same as cart. `complete`
reuses `CompleteCheckoutAction` — the exact same code path
`Storefront\CheckoutController` runs, so an order created from the app is
never a different shape than one created on the web.

## 5. Orders

```
GET  /api/v1/orders                ✅ (auth:sanctum required — a guest has no account to list)
GET  /api/v1/orders/{order}        ✅ (public_id UUID is the credential for a guest order, no auth needed)
GET  /api/v1/orders/{order}/invoice
GET  /api/v1/orders/{order}/track  ✅
POST /api/v1/orders/{order}/cancel ✅
POST /api/v1/orders/{order}/reorder
```
`/invoice` and `/reorder` are deferred — invoice is a PDF stream
(`InvoiceDownloadController`), a different response shape than the rest of
this API and lower priority than a working order history; reorder has no
existing business logic anywhere to reuse (no such feature exists on web
either), so building it now would mean inventing new checkout behaviour
rather than exposing something that already works.

## 6. Payments

```
POST /api/v1/payments/{order}/initialize  ✅ (returns authorization_url as data — a mobile SDK opens it itself, no redirect())
POST /api/v1/payments/{order}/verify      ✅ (same server-to-server VerifyPaymentAction the web callback triggers)
POST /api/v1/webhooks/payments/{gateway}  ✅ (unauthenticated, signature-verified — already existed)
```
`GET /payments/methods` was dropped — `GET /config`'s `payment_methods`
already answers this; a second endpoint for the same fact would be a
second place for it to drift out of sync.

## 7. Wishlist / Compare / Reviews / Questions

```
GET/POST/DELETE /api/v1/wishlist                    ✅
GET/POST/DELETE /api/v1/compare                     ✅
GET/POST        /api/v1/products/{slug}/reviews     ✅ (GET public; POST requires auth)
POST            /api/v1/reviews/{id}/helpful        ✅
GET/POST        /api/v1/products/{slug}/questions   ✅ (GET public; POST requires auth)
POST            /api/v1/questions/{id}/answers      ✅ (any Sanctum user — ProductQuestionPolicy::answer decides if they may, same as web: the product's own vendor/staff, or an admin)
```

## 8. Returns / Refunds / Disputes

```
GET  /api/v1/returns
POST /api/v1/returns
GET  /api/v1/returns/{id}
POST /api/v1/returns/{id}/evidence
GET  /api/v1/refunds/{id}
GET  /api/v1/disputes
POST /api/v1/disputes
POST /api/v1/disputes/{id}/messages
```

## 9. Wallet / Rewards / Gift Cards

```
GET  /api/v1/wallet
GET  /api/v1/wallet/transactions
GET  /api/v1/rewards
GET  /api/v1/rewards/history
POST /api/v1/gift-cards/redeem
GET  /api/v1/gift-cards/{code}/balance
```

## 10. Notifications / Support

```
GET   /api/v1/notifications
PATCH /api/v1/notifications/{id}/read
GET   /api/v1/support
POST  /api/v1/support
GET   /api/v1/support/{ticket}
POST  /api/v1/support/{ticket}/messages
POST  /api/v1/support/{ticket}/attachments
```

## 11. Profile / Addresses

```
GET/PATCH       /api/v1/profile           ✅
POST            /api/v1/profile/password  ✅
GET/POST        /api/v1/addresses         ✅
PATCH/DELETE    /api/v1/addresses/{id}    ✅
```
This whole section — plus wishlist/compare/reviews/questions writes above
— requires `auth:sanctum`, unlike cart/checkout which stay guest-
accessible. Gating the route group (rather than resolving the user
per-controller like Cart/Checkout do) means every controller here reads
`$request->user()` / uses `Gate::authorize()` exactly like its web
equivalent, since the middleware makes "sanctum" the default guard for
the rest of that request.

## 12. Vendor API (prefix `/api/v1/vendor`, middleware: `auth:sanctum`, `vendor.access`)

```
GET/PATCH  /api/v1/vendor/products/{id}         ✅ (see note — no POST)
GET        /api/v1/vendor/products              ✅
GET/PATCH  /api/v1/vendor/inventory(/{id})       ✅ (PATCH = adjust, via AdjustStockAction)
GET        /api/v1/vendor/orders                ✅
GET        /api/v1/vendor/orders/{id}            ✅
PATCH      /api/v1/vendor/orders/{id}/status     ✅
POST       /api/v1/vendor/orders/{id}/cancel     ✅ (not in the original spec, added — mirrors the Filament vendor panel's own cancel action)
GET        /api/v1/vendor/earnings               ✅ (wallet balances)
GET        /api/v1/vendor/earnings/transactions  ✅ (ledger, not in the original spec — the balances alone aren't enough to show a vendor where money came from)
GET/POST   /api/v1/vendor/withdrawals            ✅
POST       /api/v1/vendor/withdrawals/methods    ✅ (add a bank account — not in the original spec; withdrawals can't be requested without one)
POST       /api/v1/vendor/withdrawals/{id}/cancel ✅
GET/PATCH  /api/v1/vendor/store                  ✅
GET        /api/v1/vendor/analytics
```
Guard is `sanctum`, not a separate `vendor` guard — a vendor authenticates
through the exact same `POST /auth/login` every customer uses (it already
issues a `vendor:*`-scoped token when `user_type` is
VendorOwner/VendorStaff). Access is gated by the `vendor.access`
middleware (`App\Http\Middleware\EnsureVendorAccess`), which mirrors
`User::canAccessPanel('vendor')` exactly — the same business rule that
gates the Filament vendor panel, so nobody can reach through the API
who couldn't already reach through the panel.

Product creation isn't exposed yet: it needs image/variation upload
handling that has no equivalent already built for a JSON API (the
Filament form's FileUpload fields expect multipart uploads staged
through Livewire) — building it half-done (no images) would ship a
"create product" endpoint vendors can't actually use for a real
listing. `/analytics` is deferred — there's no existing analytics
computation anywhere (web included) to expose.

## 13. Standards Applied Uniformly

- **Versioning**: `/api/v1` namespace; breaking changes ship as `/api/v2`
  rather than mutating v1 contracts once mobile depends on them.
- **Pagination**: cursor or page-based per `meta.pagination` block, consistent
  key names across every list endpoint.
- **Idempotency**: `Idempotency-Key` header required on
  `POST /checkout/complete` and payment-initializing endpoints; the server
  persists the key (`checkout_sessions`, `payments`) and returns the original
  response on retry rather than creating a duplicate order/payment.
- **Errors**: RFC-inspired consistent shape (see
  [01-system-architecture.md](01-system-architecture.md) §6);
  `error_code` is a stable machine-readable string mobile can switch on.
- **Authorization**: every endpoint maps to the same Policy classes used by
  Filament/Blade — one authorization source of truth across all three
  surfaces plus the API.
- **Caching**: `ETag`/`Last-Modified` on cacheable catalog GETs
  (`/products`, `/categories`), cache invalidated via model observers when
  the underlying admin/vendor edit happens — this is the mechanism that
  satisfies the "web edits must appear via API" sync rule for the future
  mobile app.
