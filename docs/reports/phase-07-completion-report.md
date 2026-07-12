# Phase 7 Completion Report — Inventory & Warehouse Management

## Objective

Replace Phase 6's placeholder per-product `stock_quantity`/`stock_status`
columns with a real multi-warehouse inventory engine: warehouses, stock
tracked per (warehouse, product-or-variation), an immutable movement
ledger, a concurrency-safe reserve → deduct → restore lifecycle with no
overselling, warehouse-to-warehouse transfers, and admin/vendor management
surfaces — per
[docs/architecture/13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 7 completion gate: "accurate stock math, no overselling, variation
stock, reservation, cancellation/return restoration, warehouse transfers,
low-stock alerts, complete movement history."

## What was built

### Schema (5 new migrations)

`warehouses` (vendor-owned — see "Design decision" below), `warehouse_stocks`
(product-or-variation × warehouse: `on_hand`/`reserved`/`damaged`/`incoming`
— matching the ERD's "product or variation level" split exactly),
`stock_movements` (an insert-only ledger: `type`, `bucket`, signed
`quantity_delta`, a nullable polymorphic `reference` so a future
`Order`/`OrderItem` can be attached without a schema change, `created_by`),
`stock_transfers` and `stock_transfer_items` (a four-state lifecycle:
pending → in_transit → completed, with cancel from either of the first two).
`warehouse_stocks` deliberately has **no** database-level uniqueness on
`(warehouse_id, product_id, product_variation_id)` — MySQL/SQLite both treat
NULL as distinct in unique indexes, so a constraint including the
mutually-exclusive nullable `product_id`/`product_variation_id` pair
wouldn't actually deduplicate the product-level rows; uniqueness is instead
guaranteed by every write going through `LocksWarehouseStock`'s
`firstOrCreate()` + immediate `lockForUpdate()` re-select, documented
directly in the migration.

### Design decision: warehouses are vendor-owned

The ERD doesn't state whether warehouses are platform-shared or
vendor-specific. Resolved by cross-referencing
[07-vendor-dashboard.md](../architecture/07-vendor-dashboard.md) (a
`/vendor/inventory` page exists, but there is no vendor-facing
warehouse-CRUD page — only "adjustments, transfer requests") against
[05-filament-resources.md](../architecture/05-filament-resources.md) (the
admin panel gets a full `WarehouseResource`): warehouses have a required
`vendor_id` and are created/edited only from the admin panel, mirroring how
vendors already can't create their own `Store` row directly — but every
stock/transfer *operation* on a warehouse is available to its owning
vendor (or permissioned staff) via the vendor inventory page, exactly like
how the admin creates a `Vendor`'s `Store` but the vendor manages its
day-to-day settings themselves.

### Enums, models, factories

`StockMovementType` (adjustment/reservation/release/deduction/restoration/
transfer_out/transfer_in/transfer_cancelled/damage_reported),
`StockMovementBucket` (on_hand/reserved/damaged/incoming),
`StockTransferStatus`. `Warehouse` (picks up `BelongsToVendorScope` —
layer 1 isolation, same as `Store`/`Product`), `WarehouseStock` (an
`available(): int` helper — `max(0, on_hand - reserved)`), `StockMovement`,
`StockTransfer`, `StockTransferItem`. `Product`/`ProductVariation` gained a
`warehouseStocks(): HasMany`; `Vendor` gained `warehouses(): HasMany`.

### Vendor isolation and authorization

`WarehousePolicy` and `StockTransferPolicy` mirror `ProductPolicy`'s
owner-or-permissioned-staff pattern exactly (`store.inventory.manage` for
staff, `warehouses.manage`/`inventory.manage` for admin, independent of
ownership). A transfer's access check resolves through its *source*
warehouse's vendor, since `RequestStockTransferAction` structurally
guarantees both warehouses belong to the same vendor.

### The stock engine (`App\Actions\Inventory\*`)

Ten action classes, each a single DB transaction locking its
`WarehouseStock` row(s) via `lockForUpdate()` before reading or writing —
the app-level equivalent of the ERD's "SELECT ... FOR UPDATE" guidance,
since Eloquent has no first-class syntax for combining `firstOrCreate()`
with a row lock:

- `AdjustStockAction` — manual on-hand correction (recount/restock/
  shrinkage), the only action allowing an arbitrary signed delta.
- `ReserveStockAction` / `ReleaseStockReservationAction` — the checkout
  "hold" step from
  [09-lifecycles.md](../architecture/09-lifecycles.md) and its reversal;
  increments/decrements `reserved` only, `on_hand` untouched. Reserving
  throws `InsufficientStockException` when the requested quantity exceeds
  `on_hand - reserved` — this is the no-overselling guarantee.
- `DeductStockAction` — converts a reservation into a hard removal (payment
  confirmed), removing the quantity from both `reserved` and `on_hand`
  together.
- `RestoreStockAction` — a return or post-payment cancellation; increments
  `on_hand` only.
- `ReportDamagedStockAction` — moves quantity from `on_hand` to `damaged`.
- `RequestStockTransferAction` / `DispatchStockTransferAction` /
  `CompleteStockTransferAction` / `CancelStockTransferAction` — the
  four-state transfer lifecycle: requesting moves no stock (a paper trail
  only); dispatching moves quantity from the source's `on_hand` to the
  destination's `incoming`; completing moves it from `incoming` to the
  destination's `on_hand`; cancelling a still-pending transfer is a pure
  status change, cancelling an in-transit one reverses the dispatch.

Every mutation writes one `StockMovement` row per bucket it touches (a
deduction touches both `on_hand` and `reserved`, so it writes two), and
calls a shared `RecalculatesSellableStock` trait afterward to keep
`products`/`product_variations.stock_quantity` in sync as a derived cache
(the same pattern the ERD already uses for wallet balances) — skipped
entirely when `manage_stock` is false. Replenishing stock always clears a
stale `out_of_stock` status back to `in_stock`; hitting zero only
downgrades an `in_stock` status to `out_of_stock` — an explicit
`on_backorder` choice is never silently overwritten by the recalculation,
only by the vendor changing it directly or by a fresh replenishment.

### Admin Filament resources (`App\Filament\Resources\{Warehouses,StockTransfers}`, `App\Filament\Widgets\LowStockWidget`)

`WarehouseResource` (relies on the auto-discovered `WarehousePolicy`, no
create/edit route removal needed since admin *is* the intended creator)
registers two relation managers matching
[05-filament-resources.md](../architecture/05-filament-resources.md)'s
literal spec — "Stocks, Transfers": `StocksRelationManager` (an "Add stock"
header action plus per-row "Adjust"/"Report damage" actions, all backed by
the actions above) and `OutgoingTransfersRelationManager` (labelled
"Transfers" — a "Request transfer" header action plus
dispatch/complete/cancel row actions, visibility-gated by the transfer's
current status). `StockTransferResource` is the platform-wide, read-only +
action-driven oversight view (no create/edit page, matching
`ProductResource`'s "originates elsewhere" pattern from Phase 6) with an
`ItemsRelationManager` and the same dispatch/complete/cancel actions on its
view page. `LowStockWidget` (a `TableWidget`) lists products where
`stock_quantity <= low_stock_threshold` with `manage_stock` enabled;
variation-level low stock shares the same product threshold and is
surfaced in the inventory tables themselves rather than duplicated in the
widget, since a single table widget can't cleanly union `Product` and
`ProductVariation` rows.

### Vendor inventory page (`App\Filament\Vendor\Pages\Inventory`, `/vendor/inventory`)

A custom table-based Filament Page (`Tables\Concerns\InteractsWithTable` +
`Tables\Contracts\HasTable` — there is no "list" Resource here since
`WarehouseStock` rows are never vendor-creatable, only warehouse-scoped)
listing every `WarehouseStock` row across the vendor's own warehouses, with
the same "Add stock"/"Adjust"/"Report damage"/"Request transfer" actions as
the admin relation managers, scoped to `Auth::guard('vendor')->user()->
actingVendorId()`.

## A genuine bug caught by testing (worth recording)

An "explicit backorder status survives hitting zero stock" test initially
failed — not because the recalculation logic was wrong, but because the
test reused the same in-memory `Product` PHP object across three
sequential action calls. The first call (a restock) legitimately flipped
`stock_status` to `in_stock` in that same object; by the time the test
reserved-then-deducted back to zero, the "was this already `on_backorder`"
check correctly saw `in_stock` (its now-current state) and downgraded to
`out_of_stock` rather than `on_backorder` — exactly per the recalculation
rule's own stated semantics ("replenishing always clears a stale status").
The fix was to the test's scenario, not the implementation: a status can
only "survive" zero stock if it never passed through a replenishment step
first, which is the economically correct behavior for a real backorder
flag.

## Tests

- `./vendor/bin/pest` — **216/216 passing** (174 carried from Phases 1–6, 42
  new): the stock engine directly — adjust/reserve/release/deduct/restore/
  damage, overselling rejection, cached-quantity and status recomputation
  including the backorder-preservation rule, immutable movement ledger
  entries (14); the full transfer lifecycle including cross-vendor
  rejection, same-warehouse rejection, insufficient-stock-at-dispatch, and
  cancel-from-both-states (9); `WarehousePolicy`/`StockTransferPolicy` —
  owner, permissioned/unpermissioned staff, unrelated vendor, admin
  permission (9); warehouse vendor isolation (1, added to the existing
  `BelongsToVendorScopeTest`); admin Filament resources — pages load,
  permission-gated access, add-stock and full transfer cycle through the
  real Livewire relation managers, low-stock widget filtering (5); the
  vendor inventory page — loads, add/adjust stock, request a transfer,
  cross-vendor row-level isolation (4).
- `./vendor/bin/pint` — clean (fixed import ordering on three files as part
  of this phase; zero violations after).
- `migrate:fresh --seed` verified end-to-end; the 5 new migrations were
  individually verified to roll back and re-run cleanly during development.
- Manual verification via `php artisan tinker`: a full adjust → reserve →
  deduct → restore → damage → transfer(request/dispatch/complete) cycle
  against real database queries, confirming the cached
  `products.stock_quantity` tracks the underlying ledger correctly at every
  step (100 → 90 → 90 → 95 → 92 → dispatch 20 → 72 → complete → 92).

## Completion Gate Check (Phase 7)

| Criterion | Status |
|---|---|
| Accurate stock math | ✅ ledger-backed, cached quantity always derived from `warehouse_stocks`, tested |
| No overselling | ✅ `InsufficientStockException` on any operation that would go negative, tested |
| Variation stock | ✅ `warehouse_stocks` supports product-or-variation rows identically |
| Reservation | ✅ `ReserveStockAction`/`ReleaseStockReservationAction`, tested |
| Cancellation/return restoration | ✅ `RestoreStockAction`, tested |
| Warehouse transfers | ✅ full request → dispatch → complete/cancel lifecycle, tested |
| Low-stock alerts | ✅ `LowStockWidget` on the admin dashboard, tested |
| Complete movement history | ✅ `stock_movements` insert-only ledger, one row per bucket touched, tested |
| Full test suite passes | ✅ 216/216 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–6 (no MySQL server, no
   Larastan) remain unchanged.
2. `ReserveStockAction`/`DeductStockAction`/`RestoreStockAction` accept an
   optional polymorphic `$reference` so a future `OrderItem` can be
   attached to a movement, but nothing calls them with a real order yet —
   `orders`/`order_items` don't exist until Phase 12. Checkout (Phase 11)
   and order management (Phase 12) are the natural first callers.
3. `warehouse_stocks` has no database-level uniqueness on
   `(warehouse_id, product_id, product_variation_id)` for the reason
   documented in the migration and above — a genuine first-ever-write race
   on the exact same location is a known, narrow, documented limitation
   rather than a silent gap.
4. The low-stock widget only surfaces product-level thresholds; a variable
   product's individual variations share the parent product's
   `low_stock_threshold` and are visible in the inventory tables, not
   duplicated into the dashboard widget.

None of these block Phase 8 (Customer-Facing Web Marketplace), which is the
next phase in the roadmap and is the first to put the catalog (Phase 6) and
now-accurate inventory (Phase 7) in front of an actual shopper.
