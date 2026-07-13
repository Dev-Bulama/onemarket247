# Phase 11 Completion Report — Guest & Registered Checkout

## Objective

Turn the cart Phase 10 built into a real order: a single-page checkout
that works for both guests and registered customers, splits a
multi-vendor cart into one order per vendor, and is safe against the
things that actually break checkouts in production — stale prices, a
sold-out item, and a double-clicked submit button — per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 11 completion gate: "guest/registered/multi-vendor checkout,
accurate totals, no duplicate orders, safe stock reservation, invalid
prices rejected, idempotency verified, tests pass."

## Scope decisions

1. **`checkout_sessions` is built now, but `invoices`/`packing_slips`/
   `order_notes`/`order_status_histories` are deferred to Phase 12.**
   `04-models-and-migrations.md`'s Batch 11 groups all of these together
   with `orders`/`vendor_orders`/`order_items`, but the phase-gate roadmap
   draws the line differently: Phase 11 is checkout (this phase), Phase 12
   is "parent/vendor sub-orders correct... unified customer view, **status
   history**, **invoices**, partial fulfilment, cancellation rules,
   notifications." None of those four tables have a real trigger event
   until Phase 12 adds the actions that generate an invoice, transition a
   status, or leave a note — building them now would be exactly the
   placeholder-table pattern this project has consistently avoided since
   Phase 8 (blog) and Phase 9 (vendor/delivery reviews). `checkout_sessions`
   is different: it's the mechanism idempotency itself depends on, and
   idempotency is explicitly *this* phase's own gate.
2. **`payments` is a deliberately minimal table, not the Payments module.**
   `payments` technically lives in Batch 12 (a separate batch from Orders)
   per the ERD, and Phase 13 is explicitly "at least one live-ready gateway
   (Paystack) fully working, webhook signatures verified..." — the real
   Payments module. But `09-lifecycles.md`'s own checkout flow requires a
   `payments` row in `pending` status to exist the moment an order is
   created (`orders.status` starts at `pending_payment`, which is
   meaningless without something representing the payment that hasn't
   happened yet). This phase's `Payment` model has exactly four columns
   beyond its status/amount (`gateway`, `gateway_reference`, both
   nullable) — no gateway config, webhook handling, or reconciliation.
   Phase 13 adds all of that additively.
3. **One shipping-address snapshot per order, not separate shipping/
   billing addresses.** No architecture doc calls for billing-vs-shipping
   distinction to matter before invoicing exists (Phase 12) or tax
   calculation needs it (Phase 16). The order snapshots the address given
   at checkout directly onto its own columns — never a live FK to the
   customer's saved address book — so editing or deleting a saved address
   later can never corrupt a historical order, the same "snapshot, don't
   reference" rule already applied to commission rules.
4. **The coupon discount is applied only at the parent-order level; each
   `vendor_order.total` is gross (pre-discount).** Proportionally
   attributing a cart-level discount across vendors only matters once
   commission calculation (Phase 14) needs to know each vendor's true net
   contribution; splitting it correctly here would be speculative
   precision with no consumer yet.
5. **Stock reservation picks a single warehouse per line item; it does not
   split a quantity across multiple warehouses.** If no single warehouse
   holds enough available stock for a line, checkout fails with an
   honest "not enough stock" error rather than silently under-reserving.
   Multi-warehouse allocation optimization is a real inventory feature but
   isn't in this phase's gate ("safe stock reservation" — which this
   satisfies — not "optimal allocation").

## What was built

### Data model

Five new tables: `checkout_sessions` (idempotency key + a totals snapshot
+ `expires_at`, resolving to an `order_id` once checkout completes),
`orders` (a UUID `public_id` for URLs — never the enumerable auto-
increment id — plus a plain sequential `order_number` for receipts,
nullable `customer_id` with `guest_name`/`guest_email`/`guest_phone`
alongside it, the shipping-address snapshot, and `subtotal`/
`discount_amount`/`shipping_amount`/`tax_amount`/`total`, the last two
hardcoded to 0 pending Phases 15/16), `vendor_orders` (one per vendor
represented in the order, its own gross subtotal/total, `ON DELETE
RESTRICT` to `orders`/`vendors` per the ERD's "never cascade-delete an
order" rule), `order_items` (product name/SKU/price snapshotted so a
later rename can never change what a receipt says was bought), and
`payments` (see Scope Decision 2). `order_number`/`vendor_order_number`
are assigned in a `created` model event from the row's own auto-increment
id — collision-free without a locking round-trip, at the cost of being
briefly nullable between insert and that follow-up update within the same
transaction. `VendorOrder` got the same `BelongsToVendorScope` global
scope every other vendor-owned table has carried since Phase 5.

### Order status aggregation

`App\Services\Order\OrderStatusAggregator` computes the parent `Order`'s
status from its child `VendorOrder` statuses — per
`09-lifecycles.md`'s explicit rule that the parent status is "never
hand-set independently of children, preventing drift." This phase only
ever creates orders whose children all start at `pending_payment`, so
that's the only case genuinely exercised end-to-end right now; the richer
transitions (`partially_delivered`, `completed`, etc.) are implemented and
unit-tested against the aggregator directly, ready for Phase 12's status-
transition actions to call `recompute()` without needing this class
rewritten.

### Checkout actions

`InitiateCheckoutAction` gets-or-creates the one live `CheckoutSession`
for a cart — reusing an existing unresolved, unexpired session rather than
minting a new idempotency key every time `/checkout` loads is what makes
a page reload or a second browser tab resolve to the same eventual order.
`CompleteCheckoutAction` is the guarded transaction from
`09-lifecycles.md`'s "Checkout → Order Creation": locks the checkout
session row first (`SELECT ... FOR UPDATE`) and returns the already-
resolved order immediately if a concurrent/replayed call already
completed it; re-validates every active cart line's live price and stock
before touching anything, throwing a dedicated `CheckoutValidationException`
(price drift, empty cart) or the existing `InsufficientStockException`
(stock) rather than ever silently substituting a different price or
quantity; splits items into one `VendorOrder` per vendor; reserves stock
by reusing Phase 7's own `ReserveStockAction` per line (never deducts —
deduction on payment success is Phase 13's job); creates the pending
`Payment` row; and marks the cart `Converted`.

### Storefront pages

`/checkout`: an address form (prefilled from the customer's default
address when logged in, with the same cascading country→state→city
selects used in the account address book and vendor registration wizard),
an order summary, and an honest note that payment collection isn't live
yet rather than a fake "Pay now" button with nothing behind it. `/checkout`
POSTs back to itself and redirects to `/checkout/confirmation/{order}`
(bound by `public_id`) on success, or back to `/cart` with a
`checkout` error on price drift or insufficient stock. The confirmation
page is reachable without login for a guest order (the unguessable
`public_id` is the credential, matching the ERD's own reasoning for using
UUIDs in URLs) but is policy-gated to the owning customer (or an admin
with `orders.view`) for a registered order — verified by a test that a
different logged-in customer gets a 403. `/cart` gained a real "Proceed to
checkout" button in place of Phase 10's honest placeholder note.

## Bugs and test-writing pitfalls caught (worth recording)

1. **A "double submit" test that actually proved the wrong thing.** The
   first version of the idempotency test posted the checkout form twice in
   a row with the same cookie and asserted both responses redirected to
   the same place. It failed — but not because of a real bug: the *first*
   POST genuinely succeeds and converts the cart to `Converted`; the
   *second* POST's `CartResolver` then finds no `Active` cart for that
   guest cookie and creates a **brand-new** cart (with a new cookie),
   which has no relationship to the original `CheckoutSession` at all —
   so the controller correctly reports "checkout session not found" and
   redirects to `/checkout`, not back to the same confirmation page. That
   is correct behavior for "resubmitting a stale form after success," not
   a test of concurrent double-submission. The real guarantee — two
   requests racing to complete the *same still-open* checkout session only
   ever produce one order — can't be exercised through sequential HTTP
   calls in a single-threaded test process (the first call fully
   completes, cart and all, before the second begins), so it's tested
   directly at the action level instead: calling
   `CompleteCheckoutAction::handle()` twice with the same session object
   and asserting both calls return the identical order. That's exactly
   the code path the row lock and `order_id !== null` check protect.
2. **Tests that set `stock_quantity` directly without a real
   `WarehouseStock` row.** Several early test drafts created products with
   `manage_stock: true, stock_quantity: 10` (the *cached* column Phase 7's
   `RecalculatesSellableStock` maintains) but never created an actual
   `warehouse_stocks` row for them. `CompleteCheckoutAction`'s warehouse
   selection correctly found nothing to reserve from and rejected the
   checkout with "no single warehouse has enough stock" — which is the
   system working as designed, not a bug. Fixed by seeding real stock via
   `AdjustStockAction` (as Phase 7's own tests do) everywhere a test
   expects checkout to actually succeed.

## Tests

- `./vendor/bin/pest` — **359/359 passing** (333 carried from Phases 1–10,
  26 new): checkout actions — session reuse on re-initiation, a full
  multi-vendor checkout with correct per-vendor totals/items/stock
  reservation/payment row, idempotent replay producing one order, price-
  drift rejection, insufficient-stock rejection, empty-cart rejection,
  registered-customer attribution, saved-for-later exclusion (8); vendor
  isolation — `VendorOrder` added to the shared `BelongsToVendorScopeTest`
  suite (1 new case in that file); `OrderPolicy` — owner/stranger/admin/
  guest-order/unauthenticated-guest access (5); `VendorOrderPolicy` —
  owner, permissioned staff, unpermissioned staff, other vendor, admin (5);
  storefront checkout — empty-cart redirect, guest end-to-end with
  confirmation content, registered end-to-end with cross-customer 403,
  guest-requires-email validation, price-drift and stock-exhaustion
  rejection with the right redirect and error bag, and the cart page's
  conditional checkout link (7).
- `./vendor/bin/pint --test` — clean.
- `migrate:fresh --seed` and a rollback/migrate round-trip on all five new
  migrations verified clean in both directions; confirmed the real
  `CurrencySeeder` output has exactly one `is_default` currency, which is
  what `CompleteCheckoutAction` requires outside of tests.
- `php artisan route:list --name=checkout` verified all three named routes
  resolve to real controller methods.

## Completion Gate Check (Phase 11)

| Criterion | Status |
|---|---|
| Guest checkout | ✅ inline contact + address capture, no account required, tested |
| Registered checkout | ✅ attaches to the customer's account, prefills their default address, tested |
| Multi-vendor checkout | ✅ one `VendorOrder` + its own `OrderItem`s per vendor represented in the cart, tested |
| Accurate totals | ✅ subtotal/discount computed from live cart state at completion time, order and vendor-order totals verified in tests |
| No duplicate orders | ✅ idempotency-key session reuse + row-locked replay-safe completion, tested |
| Safe stock reservation | ✅ reuses Phase 7's `ReserveStockAction`, single-warehouse-per-line, rejects rather than over-reserves, tested |
| Invalid prices rejected | ✅ live price re-check at completion, `CheckoutValidationException`, cart never silently adjusted, tested |
| Idempotency verified | ✅ tested at both the action level (guaranteed mechanism) and via HTTP (realistic guest/customer flows) |
| Full test suite passes | ✅ 359/359 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–10 (no MySQL server, no
   Larastan) remain unchanged.
2. No real payment gateway — every order is created with a `pending`
   `Payment` row and nothing collects money yet. Phase 13's job.
3. `invoices`, `packing_slips`, `order_notes`, `order_status_histories`,
   and any order-status-transition actions beyond initial creation are
   Phase 12's job (see Scope Decision 1); there is no order-management UI
   (admin or vendor) yet, only the checkout flow that creates orders and
   the customer-facing confirmation page.
4. Tax and shipping are hardcoded to 0 on every order — Phases 16 and 15
   respectively.
5. Stock reservation does not split a line across multiple warehouses
   (Scope Decision 5) — a real limitation for vendors with fragmented
   inventory across many small warehouses, not expected to matter at this
   stage.
6. The coupon discount is not proportionally attributed across
   `vendor_orders` (Scope Decision 4) — will need addressing before Phase
   14's commission calculations if a coupon is present on a multi-vendor
   order.

None of these block Phase 12 (Order Management), which is the next phase
in the roadmap and is where the orders this phase creates get a real
lifecycle — status transitions, invoices, packing slips, notes, partial
fulfilment, and cancellation.
