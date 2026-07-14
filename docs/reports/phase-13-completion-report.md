# Phase 13 Completion Report — Payments

## Objective

Give the `pending` `Payment` row every order has carried since Phase 11 a
real gateway behind it, per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 13 completion gate: "at least one live-ready gateway (Paystack)
fully working, webhook signatures verified, duplicate callbacks can't
duplicate payments, failed payments stay unpaid, refunds update financial
records, payment logs, tests pass."

## Scope decisions

1. **Raw HTTP integration against Paystack's REST API, not a third-party
   SDK.** No Paystack package was installed in this repo yet (checked
   `composer.json`/`composer.lock`). `App\Services\Payment\PaystackGateway`
   calls `/transaction/initialize`, `/transaction/verify/{reference}`, and
   `/refund` directly via Laravel's `Http` facade. This gives full control
   over exactly the guarantees this phase's gate cares about — server-side
   amount computation, server-to-server verification, signature checking —
   and makes every code path trivially testable with `Http::fake()`,
   without taking on a dependency whose internals I'd otherwise have to
   trust or work around.
2. **No `PaymentGatewayContract` interface.** This codebase has never used
   interfaces/service contracts anywhere (checked — no `app/Contracts`
   directory existed before this phase), and only one gateway is being
   built. Introducing a polymorphic abstraction for a single concrete
   implementation would be exactly the "abstraction for a hypothetical
   future requirement" this project has consistently avoided; if a second
   gateway is ever built, that's the point to extract an interface from
   the one that already works.
3. **Refunds are scoped to "refunds update financial records" — this
   phase's own gate text — not the customer-facing return workflow.**
   `04-models-and-migrations.md` models a full `refunds` table inside
   "Batch 15 — Returns/Refunds/Disputes," alongside `return_requests`,
   `return_evidence`, etc., which is Phase 18's gate ("customer return
   requests, vendor responses, ... refunds updating payment/wallet
   records"). Building that whole workflow now — before any of the
   surrounding return-request tables exist — would be building ahead of
   its own dependencies. What Phase 13 actually needs is narrower: an
   admin-initiated refund of an already-paid payment that correctly
   updates that payment's own record. `refunded_amount` lives directly on
   `payments` for the same reason Phase 11 kept `payments` itself minimal
   — nothing yet needs more than "how much of this payment has been given
   back," and a dedicated `refunds` table can be introduced additively
   when Phase 18 actually needs one (reconciled against this column,
   not duplicating it).
4. **No wallet/commission side effects on payment success.** `09-lifecycles.md`'s
   "On verified success" step lists "commission recording, vendor wallet
   credit as pending balance" alongside stock deduction and status
   transition — but those ledgers don't exist until Phase 14, the same
   "can't void a commission that isn't recorded yet" reasoning Phase 12
   already applied to cancellation. `VerifyPaymentAction` does the two
   parts of that step that Phase 13 *can* honestly do: converting the
   stock reservation to a hard deduction, and confirming the vendor
   orders.
5. **JSON `/api/v1/payments/{order}/initialize` and `/verify` endpoints
   from `08-api-endpoints.md` were not built — only the webhook receiver
   was, at its documented path.** The storefront checkout flow built in
   Phase 11 is server-rendered Blade, not a JSON/SPA client, and no phase
   to date has built out the parallel `/api/v1/*` surface for anything
   storefront-facing (only auth exists there, from Phase 3) — the docs
   describe a JSON API surface that isn't otherwise under active
   construction. Building unused JSON initialize/verify endpoints
   alongside working session-based ones would be exactly the kind of
   speculative, never-consumed surface this project avoids. The one
   webhook endpoint *was* built at its documented path
   (`POST /api/v1/webhooks/payments/{gateway}`) because it's inherently
   third-party-consumed — Paystack's servers, not this app's own frontend
   — so a JSON receiver is the only sensible shape for it regardless of
   how the rest of checkout is built.

## What was built

### Data model

Three new tables — `payment_gateways` (one row per gateway; `secret_key`/
`public_key`/`webhook_secret` are `encrypted` casts, never returned by any
API/Filament read), `payment_logs` (insert-only audit trail of every
gateway interaction — request, response, webhook, or error — same
"immutable ledger" convention as `audit_logs`: no `updated_at`), and
`webhook_events` (the dedupe mechanism: a unique `(gateway, event_id)` pair
inserted inside the same transaction that processes a webhook) — plus an
additive migration on `payments` adding `reference` (a public UUID,
following the same pattern as `orders.public_id`), `idempotency_key`,
`meta`, `refunded_amount`, `paid_at`, and `failed_at`.

### Paystack gateway service

`App\Services\Payment\PaystackGateway` exposes `initialize()`, `verify()`,
`refund()`, `verifyWebhookSignature()` (HMAC-SHA512 over the raw request
body via `hash_equals`, matching Paystack's actual signature scheme), and
two small webhook-payload helpers. It always resolves its API key from the
active `payment_gateways` row in the database, never from `config()`
directly — `config('services.paystack.*')` exists only as the seed source
`PaymentGatewaySeeder` reads once, so an admin can rotate a compromised key
via the panel without a redeploy.

### Payment lifecycle actions

`InitializePaymentAction` always recomputes the amount from the order's
own `total` (never trusts client input, per
`docs/architecture/10-security-architecture.md`), reuses a still-untouched
`Pending` payment row if one exists, and otherwise creates a fresh one —
because Paystack rejects a duplicate reference, "retry" always means a new
`payments` row, matching `09-lifecycles.md`'s own description of the
retry path. `VerifyPaymentAction` is the one action both the browser
callback and the webhook call into: it row-locks the payment first
(`SELECT ... FOR UPDATE`, the identical idempotency shape as Phase 11's
`CompleteCheckoutAction`), short-circuits if the payment is already in a
terminal state, re-checks the gateway's reported amount against the
payment's own recorded amount (a gateway-reported "success" for the wrong
amount is treated as a failure, not a payment — the mitigation for
payment/price manipulation), and on genuine success both confirms every
still-`PendingPayment` vendor order (reusing Phase 12's
`UpdateVendorOrderStatusAction`, which in turn reuses `OrderStatusAggregator`
and fires the Phase 12 status-change notification for free) and converts
each item's stock reservation into a hard deduction via Phase 7's
`DeductStockAction` — built back in Phase 7 with a docblock literally
anticipating this exact call site. `HandlePaymentWebhookAction` verifies
the signature, then inserts the `webhook_events` dedupe row inside the
same transaction that calls `VerifyPaymentAction` — a replayed delivery
hits the unique constraint and returns having done nothing further.
`RefundPaymentAction` validates the payment is refundable and the amount
doesn't exceed what's left, calls Paystack's refund endpoint, and updates
`refunded_amount`/`status` accordingly.

### Storefront checkout integration

The Phase 11 order confirmation page now reflects real payment state
instead of "payment collection isn't live yet": a "Pay now" button when a
payment is still pending, a success message once paid, and a "Try again"
button (which mints a fresh payment attempt) after a failed one.
`POST /checkout/{order}/pay` calls `InitializePaymentAction` and redirects
to Paystack's hosted page; `GET /checkout/{order}/pay/callback` — where
Paystack redirects the browser back — triggers a server-to-server
`VerifyPaymentAction` call rather than trusting the redirect itself, per
the security doc's explicit rule that a frontend "success" is never
sufficient alone.

### Admin Filament resources

`PaymentGatewayResource` is edit-only (no create/delete — only `paystack`
has a working implementation behind it, so letting an admin create
arbitrary new gateway codes would produce rows that do nothing); its
secret fields always load blank and a blank submission leaves the stored
value untouched, the same "leave blank to keep current value" pattern this
codebase already used for admin passwords. `PaymentResource` is read-only
+ action-driven like `OrderResource`, with a `LogsRelationManager` showing
the full `payment_logs` trail and a `refund` header action gated
specifically on `refunds.manage` (distinct from `payments.view`/
`payments.manage`, per `03-modules-and-roles.md`'s permission list) and
only visible while the payment is actually refundable.

## Bugs and test-writing notes (worth recording)

1. **A same-second `PaymentGatewaySeeder`/permission-guard gotcha, not a
   real bug.** Tests granting a specific permission to a staff admin
   before `actingAs()` runs (e.g. "an admin without `refunds.manage`")
   need to pass an explicit `Permission::findOrCreate('x', 'admin')`
   instance rather than a bare string — the same guard-resolution
   footgun documented in the Phase 12 report, now recurring because
   Spatie Permission resolves an ambiguous string against whichever guard
   `config('auth.defaults.guard')` currently is, and nothing has flipped
   it to `'admin'` yet at that point in the test.
2. **This sandbox's Redis was simply not running**, not unavailable —
   `redis-server` is installed but wasn't started, so a full
   `migrate:fresh --seed` outside the test harness (which forces
   `CACHE_STORE=array` via `phpunit.xml`) failed on the permission
   seeder's cache-clear call. Starting `redis-server` resolved it
   immediately; this isn't a new limitation, just a one-time environment
   setup step this verification pass needed that the test suite itself
   never does.

## Tests

- `./vendor/bin/pest` — **426/426 passing** (396 carried from Phases 1–12,
  30 new): `PaystackGateway` — initialize sends the right email/amount/
  reference/callback and returns the authorization URL, initialize throws
  on a gateway rejection, verify reports success/failure correctly from
  the gateway's own transaction status, refund posts the right reference/
  amount, webhook signature verification accepts a correctly-signed body
  and rejects a tampered one or a missing header, the webhook dedupe key
  combines event type and reference (6); payment actions — initialize
  logs request/response and moves to `Processing`, rejects re-initializing
  an already-paid order, a retry after `Failed` creates a fresh payment
  row, a successful verify confirms vendor orders + deducts stock +
  notifies the customer, verify is idempotent (a second call after
  `Paid` doesn't re-deduct stock), a failed verify marks `Failed` and
  leaves the vendor order unconfirmed, a gateway-reported success with a
  mismatched amount is treated as failed (not paid), a webhook delivered
  twice only applies its side effect once, an invalid signature is
  rejected and logged, a paid payment can be partially then fully
  refunded, over-refunding and refunding an unpaid payment are both
  rejected (12); storefront flow — confirmation page offers to pay/shows
  paid/offers a retry, the full initialize→callback→paid round trip,
  cross-customer 403, guest can pay their own guest order (5); admin
  Filament — index/view pages, permission gating, secret rotation without
  ever exposing the current value, leaving a field blank preserves it,
  refund from the view page, refund hidden on an unpaid payment, refund
  hidden without `refunds.manage` (7).
- `./vendor/bin/pint --dirty` — clean.
- `migrate:fresh --seed` verified clean end-to-end, including
  `PaymentGatewaySeeder` correctly seeding an inactive, secret-less
  `paystack` row when no `PAYSTACK_*` env vars are set.
- A rollback/migrate round-trip on all four new migrations verified clean
  in both directions (including an SQLite-specific fix: dropping multiple
  unique-indexed columns in one `dropColumn()` call needs the unique
  indexes dropped explicitly first, the same pattern already established
  in `add_fields_to_users_table`).
- `php artisan route:list` verified all new named routes
  (`checkout.payment.initialize`, `checkout.payment.callback`,
  `api.webhooks.payments`, `filament.admin.resources.{payments,payment-gateways}.*`)
  resolve to real controllers.

## Completion Gate Check (Phase 13)

| Criterion | Status |
|---|---|
| At least one live-ready gateway (Paystack) fully working | ✅ initialize → hosted redirect → callback/webhook verify → paid, tested end-to-end |
| Webhook signatures verified | ✅ HMAC-SHA512 via `hash_equals`, invalid/missing signatures rejected and logged, tested |
| Duplicate callbacks can't duplicate payments | ✅ `webhook_events` unique `(gateway, event_id)` dedupe + `VerifyPaymentAction`'s row-locked idempotency, tested for both the webhook and the "verify called twice" case |
| Failed payments stay unpaid | ✅ a failed or amount-mismatched verification marks the payment `Failed` and leaves the vendor order unconfirmed, tested |
| Refunds update financial records | ✅ `RefundPaymentAction` updates `payments.refunded_amount`/`status` (partial and full), tested |
| Payment logs | ✅ `payment_logs` row for every initialize/verify/webhook/refund/error interaction |
| Full test suite passes | ✅ 426/426 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–12 (no MySQL server, no
   Larastan) remain unchanged.
2. No wallet/commission recording on payment success (Scope Decision 4) —
   Phase 14's job; `VerifyPaymentAction` is the natural place to add it.
3. Refunds don't yet have a dedicated `refunds` table or touch any
   wallet/commission ledger (Scope Decision 3) — Phase 18's customer
   return-request workflow will need to reconcile against
   `payments.refunded_amount`, not duplicate it.
4. Only Paystack is wired to a real implementation; `payment_gateways`
   supports more rows structurally, but `PaymentGatewayResource` won't let
   an admin create one until a second gateway actually has code behind it
   (Scope Decision 2).
5. No scheduled reconciliation job cross-checking Paystack's own
   transaction listing against local `payments` rows — `09-lifecycles.md`
   mentions this as a defense against a missed webhook, but with both the
   browser callback and the webhook independently able to trigger the same
   idempotent verify, a genuinely missed delivery is already a narrow edge
   case; worth adding if it proves necessary in practice.

None of these block Phase 14 (Commission & Wallet), which is the next
phase in the roadmap and is where `VerifyPaymentAction`'s "on success"
path gets its commission-recording and wallet-crediting side effects.
