# Phase 12 Completion Report — Order Management

## Objective

Give the orders Phase 11's checkout creates a real lifecycle, per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 12 completion gate: "parent/vendor sub-orders correct, vendor
isolation on order items, unified customer view, status history,
invoices, partial fulfilment, cancellation rules, notifications, tests
pass."

## Scope decisions

1. **Shipments/tracking and wallet/commission-voiding stay out of this
   phase.** `07-vendor-dashboard.md` loosely bundles "orders, shipments"
   under one nav entry, but its own text says "Phase 15: shipments," and
   Phase 14 is where wallet/commission ledgers are actually built — voiding
   a commission on cancellation can't be implemented against a ledger that
   doesn't exist yet. Cancellation still does the one side effect that
   *is* this phase's responsibility: releasing reserved stock.
2. **`Returned`/`Refunded`/`Disputed` stay unreachable.** Both
   `OrderStatus` and `VendorOrderStatus` already declare these cases (built
   in Phase 11), but nothing in this phase's `ALLOWED_TRANSITIONS` map ever
   produces them — that needs Phase 18's real return/dispute workflow to
   mean anything, not a status a fulfilment action can jump to by fiat.
3. **`order_items.warehouse_id` is an additive migration, not an edit to
   Phase 11's migration.** Building cancellation revealed Phase 11 never
   persisted *which* warehouse a line's stock was reserved from, so there
   was nothing for a cancellation to release stock back to. Fixed with a
   new nullable, `restrictOnDelete` column rather than touching
   already-shipped history, matching this project's established
   additive-migration convention (e.g. Phase 5's vendor-application
   alterations).
4. **Invoices and packing slips are rendered on demand from live data, not
   stored as files.** The `invoices`/`packing_slips` rows exist purely to
   hold a reference number and timestamp; an order's contents never change
   after placement, so there is nothing to gain from persisting a PDF
   alongside them and every download always reflects the current, correct
   data.
5. **Two actions for two different kinds of transition, not one action
   with a special case.** `UpdateVendorOrderStatusAction` only ever moves
   a vendor order forward through fulfilment (plus an on-hold branch) and
   explicitly rejects any attempt to jump straight to `Cancelled`;
   `CancelVendorOrderAction` is the only path to `Cancelled` and is the one
   place that bundles the stock-release side effect atomically with the
   status change and history entry. Reusing Phase 7's already-built
   `ReleaseStockReservationAction` here is exactly the case its own
   docblock anticipated back when it was written.

## What was built

### Data model

Four new tables — `order_status_histories` (polymorphic `historyable`, so
it can attach to `Order` or `VendorOrder`; only `VendorOrder` ever writes
to it in this phase, since the parent's status is always derived, never
hand-set), `order_notes` (`customer`/`vendor`/`internal` visibility, so a
support note never leaks to a customer and a customer-visible update never
leaks a vendor's internal remark), `invoices` (unique per order,
`restrictOnDelete`), `packing_slips` (unique per vendor order,
`restrictOnDelete`) — plus one additive column, `order_items.warehouse_id`
(Scope Decision 3).

### Order lifecycle actions

`UpdateVendorOrderStatusAction` guards every transition against a
`private const ALLOWED_TRANSITIONS` map keyed by current status, throwing
`InvalidOrderTransitionException` for anything not explicitly listed, then
atomically updates the status, writes a `order_status_histories` row, and
calls `OrderStatusAggregator::recompute()` so the parent `Order`'s status
is always derived fresh from its children — never independently drifted.
A public `nextStatusesFor()` helper exposes the same map so Filament's
view pages (and, if needed later, Blade) can compute "what buttons should
show here" without duplicating the transition table. `CancelVendorOrderAction`
exposes its own eligibility set as `public const CANCELLABLE_FROM`
(mirroring the `nextStatusesFor()` reasoning) and, inside one transaction,
releases every reserved line's stock back to its warehouse via
`ReleaseStockReservationAction`, flips the status, writes history, and
recomputes the parent. `CancelOrderAction` cancels every child still
eligible and leaves the rest alone — a multi-vendor order can be partially
cancelled exactly as it can be partially delivered — and throws if nothing
was eligible rather than silently no-op'ing. `AddOrderNoteAction` is a
thin wrapper that lets the visibility argument decide who ever sees a
given note.

### Notifications

Both lifecycle actions now notify the customer: `VendorOrderStatusUpdatedNotification`
on every successful status change, `VendorOrderCancelledNotification` on
cancellation, each linking back to the same `checkout.confirmation` page
built in Phase 11 (the one order-detail view that already works for both
guests and registered customers). A new `Order::notifiable()` helper
resolves the right recipient either way: the registered `customer` model,
or — for a guest order, which has no account to notify through — Laravel's
on-demand `Notification::route('mail', $guest_email)`. Both paths are
covered by `Notification::fake()` tests.

### Invoice and packing-slip downloads

`InvoiceDownloadController` and `PackingSlipDownloadController` render
`pdf.invoice`/`pdf.packing-slip` Blade views through `barryvdh/laravel-dompdf`
on demand and stream the PDF as a download — no MediaLibrary storage
involved. The invoice route deliberately carries no `auth:{guard}`
middleware (a guest must be able to reach it with only their
unguessable order link, exactly like the Phase 11 confirmation page), which
surfaced a real cross-guard authorization bug — see below.

### Customer account order pages

`/account/orders` (paginated history) and `/account/orders/{order}`
(vendor sub-order/item breakdown, customer-visible notes, shipping
address, totals, invoice download link, and a conditional cancel form that
only renders when `CancelVendorOrderAction::CANCELLABLE_FROM` says
something is still eligible) reuse the exact `OrderPolicy::view()` gate
built in Phase 11 — no new policy needed, since "owner or admin" already
covered this case.

### Admin and vendor Filament resources

`OrderResource` (admin) is read-only + action-driven, matching
`StockTransferResource`'s established pattern: a `VendorOrdersRelationManager`
(read-only, links through to each sub-order) and a `NotesRelationManager`
(creatable, auto-stamps `author_id` from the logged-in admin) sit alongside
an infolist of order/shipping/totals detail. `VendorOrderResource` (admin)
is the one that actually carries the fulfilment UI: `ViewVendorOrder`'s
header actions are generated from `UpdateVendorOrderStatusAction::nextStatusesFor()`
(so only the currently-valid next statuses ever show a button) plus a
`cancel` action gated on `CancelVendorOrderAction::CANCELLABLE_FROM`, each
wrapping the underlying action call in a try/catch that turns
`InvalidOrderTransitionException` into a Filament notification instead of
a 500. The vendor panel gets its own `VendorOrderResource` — same
`ViewVendorOrder` pattern, scoped automatically by the `BelongsToVendorScope`
already registered on `VendorOrder`, with its header actions additionally
gated behind `Gate::allows('update', $record)` so a store staff member
without the right permission never even sees a transition button, let
alone can call one.

## A real bug found while wiring this phase up (not a pre-existing issue)

**`InvoiceDownloadController` denied a logged-in admin or vendor because
nothing ever flips the request's default guard.** The invoice route has no
`auth:{guard}` middleware by design (Scope Decision above — guests need
it). `Gate::authorize()` resolves "the current user" through Laravel's
*default* guard (`config('auth.defaults.guard')`, `'web'`), and Spatie
Permission's `hasPermissionTo()` resolves *which guard's permissions to
check* the same way. Normally, `Illuminate\Auth\Middleware\Authenticate`
(the `auth:admin,vendor` middleware every other cross-guard download route
in this app carries) finds whichever guard is actually authenticated and
calls `Auth::shouldUse()` to make it the default for the rest of the
request — which is also incidentally why Spatie's guard-scoped permission
lookups work correctly everywhere else in this codebase. Because this one
route can't carry that middleware (it must stay reachable by guests), an
admin or vendor visiting it — authenticated only on their own guard, never
on `web` — was treated as an anonymous guest and denied unless the order
happened to be a guest order. Confirmed with a test that deliberately
avoids `actingAs()`/`be()` (both of which call `Auth::shouldUse()`
themselves and would have silently masked exactly this bug) by logging
into the `admin` guard directly. Fixed by having the controller do what
`Authenticate` does — check `web`, then `admin`, then `vendor`, and call
`Auth::shouldUse()` on the first one that's actually signed in — before
authorizing. `tests/Feature/Order/InvoiceDownloadTest.php` locks this in
for both the guard-resolution case and the ordinary guest/owner/other-
customer cases.

## Bugs and test-writing pitfalls caught (worth recording)

1. **Two wrong expectations about `OrderStatusAggregator`, not two
   framework bugs.** While building the admin Filament tests, a single
   vendor order moving `pending_payment → confirmed` was asserted to leave
   the parent `Order` at `Confirmed` — it doesn't: `Confirmed` is inside
   the aggregator's own `IN_PROGRESS` set, so a lone child reaching it
   rolls the parent straight to `Processing` (per
   `App\Services\Order\OrderStatusAggregator`, built and correct since
   Phase 11). Separately, a *mixed* two-child order (one still
   `pending_payment`, one `confirmed`) was asserted to also read
   `Processing` — but the aggregator's `every IN_PROGRESS` check requires
   *all* children to qualify, so a still-pending sibling correctly falls
   through to the `Confirmed` fallback instead. Both were fixed by
   correcting the test's expectations against the aggregator's actual
   (and correct) rules, not by touching the aggregator.
2. **A SQLite same-second timestamp tie, not a lost field.** An early
   version of a Filament action test wrote two `order_status_histories`
   rows in the same test and read the "latest" one back with `->latest()`
   (`ORDER BY created_at DESC`, no tiebreaker). Because SQLite's default
   timestamp precision is one second, both rows can share the same
   `created_at`, making which one comes back on a tie genuinely
   unspecified — so the assertion sometimes read the *other* row's (empty)
   note and looked like the action had silently dropped the field. Fixed
   by ordering `->latest('id')` instead, and by restructuring the
   multi-step Filament tests to keep one status transition per test
   function rather than chaining several through the same test, which is
   also just clearer to read.
3. **`Permission::findByName`/`givePermissionTo` need the right guard
   passed explicitly outside of an `actingAs()` context.** A vendor-panel
   staff-permission test called `$staff->givePermissionTo('store.orders.manage')`
   before ever calling `actingAs()`, so Spatie resolved the ambiguous
   string against the process's still-default `'web'` guard and failed to
   find the `'vendor'`-guarded permission `RolePermissionSeeder` had
   actually created. Fixed by passing an explicit
   `Permission::findOrCreate('store.orders.manage', 'vendor')` instance
   instead of a bare string.
4. **Filament's `assertActionHidden` requires the action to exist (just
   hidden); an action array that omits unauthorized actions entirely needs
   `assertActionDoesNotExist` instead.** `ViewVendorOrder`'s dynamically
   generated status-transition actions return an empty array up front when
   the viewer fails `Gate::allows('update', $record)`, while the `cancel`
   action is always present and merely toggles `->visible()` — two
   different (both valid) ways to hide an action from an unauthorized
   user, which needed two different assertions in the corresponding test.

## Tests

- `./vendor/bin/pest` — **396/396 passing** (359 carried from Phases 1–11,
  37 new): order lifecycle actions — forward transitions, invalid-transition
  rejection, parent-status recomputation across single and mixed-child
  orders, cancellation releasing reserved stock and recomputing the parent,
  rejection of cancelling a dispatched order, partial order cancellation,
  cancel-order-with-nothing-eligible rejection, adding a note, and
  notification dispatch to both a registered customer and an on-demand
  guest email on both status-update and cancellation (11); customer
  account order pages — index, detail with correct note-visibility
  filtering, cross-customer 403, successful cancellation, and
  cancel-rejected-when-nothing-eligible (6); invoice download — guest,
  owner, cross-customer 403, admin-guard-only authorization (the bug
  above), and permission-denied admin (5); packing-slip download — owning
  vendor, cross-vendor (enumeration-safe 404 via `BelongsToVendorScope`,
  not 403), admin, guest-redirected (4); admin Filament — index/view pages,
  permission gating, adding a note, advancing/cancelling a vendor order
  through the UI, aggregation onto the parent (6); vendor Filament —
  index/view pages, cross-vendor 404, owner advance-and-cancel, staff with
  `store.orders.fulfil` can advance, staff without it sees no transition
  actions and a hidden cancel action (5).
- `./vendor/bin/pint --dirty` — clean.
- `migrate:fresh` and a rollback/migrate round-trip on all five new
  migrations verified clean in both directions.
- `php artisan route:list` verified all new named routes
  (`account.orders.*`, `orders.invoice`, `packing-slips.download`,
  `filament.admin.resources.{orders,vendor-orders}.*`,
  `filament.vendor.resources.vendor-orders.*`) resolve to real controllers.

## Completion Gate Check (Phase 12)

| Criterion | Status |
|---|---|
| Parent/vendor sub-orders correct | ✅ `OrderStatusAggregator` derives the parent from its children on every transition, tested across single- and multi-child scenarios |
| Vendor isolation on order items | ✅ `BelongsToVendorScope` on `VendorOrder` (carried from Phase 11) plus the additive `order_items.warehouse_id`/`restrictOnDelete`, tested via the vendor Filament resource's cross-vendor 404 |
| Unified customer view | ✅ `/account/orders` + `/account/orders/{order}` reuse Phase 11's `OrderPolicy`, tested |
| Status history | ✅ `order_status_histories`, written by both lifecycle actions, surfaced read-only in both admin and vendor Filament panels |
| Invoices | ✅ on-demand PDF via `InvoiceDownloadController`, correctly authorized across all three guards (bug found and fixed), tested |
| Partial fulfilment | ✅ `CancelOrderAction` cancels only what's still eligible per vendor order, tested |
| Cancellation rules | ✅ `CancelVendorOrderAction::CANCELLABLE_FROM` gate, stock release verified, dispatched-order rejection tested |
| Notifications | ✅ `VendorOrderStatusUpdatedNotification`/`VendorOrderCancelledNotification` on every transition and cancellation, both registered-customer and on-demand guest-email paths tested |
| Full test suite passes | ✅ 396/396 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–11 (no MySQL server, no
   Larastan) remain unchanged.
2. `Returned`/`Refunded`/`Disputed` are declared enum cases with no
   reachable transition yet (Scope Decision 2) — Phase 18's job.
3. Cancellation does not yet void any commission or wallet entry (Scope
   Decision 1) — those ledgers don't exist until Phase 14, at which point
   `CancelVendorOrderAction` is the natural place to add that side effect.
4. Shipment tracking, carrier integration, and delivery events are not
   part of this phase (Scope Decision 1) — Phase 15's job.
5. Notifications are mail-only (no SMS/push) and unqueued — acceptable
   for this phase's synchronous, single-request lifecycle actions, but
   worth revisiting once order volume makes a queued dispatch worthwhile.

None of these block Phase 13 (Payments), which is the next phase in the
roadmap and is where the `pending` `Payment` row this project has carried
since Phase 11 finally gets a real gateway behind it.
