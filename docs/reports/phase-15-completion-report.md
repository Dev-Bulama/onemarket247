# Phase 15 Completion Report — Shipping, Delivery, Order Tracking

## Objective

Give the platform real shipping cost calculation, carrier/tracking-based
shipment fulfilment, and customer-visible order tracking, per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 15 completion gate: "shipping calculation (incl. multi-vendor),
pickup, tracking, shipment events, delivery assignment, accurate ETAs,
tests pass."

## Scope decisions

1. **Both the shipping half of "Batch 8 — Tax & Shipping config" and all of
   "Batch 14 — Shipping fulfilment" land in this phase.**
   `04-models-and-migrations.md` documents Batch 8
   (`shipping_zones`/`shipping_zone_locations`/`shipping_classes`/
   `shipping_rates`/`shipping_carriers`/`pickup_stations`, alongside
   `tax_classes`/`tax_rates`) as a "Phase 2 prerequisite," but no shipping
   or tax migration was ever actually built in Phase 2 — verified directly
   against `database/migrations/`. Since no other phase claims the
   shipping-config tables (tax tables are explicitly Phase 16's job), and
   Phase 15's own gate requires "shipping calculation," this phase builds
   both halves: the config layer (zones/classes/rates/carriers/pickup
   stations) and the fulfilment layer (shipments/shipment_events/
   delivery_assignments/delivery_evidence).
2. **A zone with no locations at all is the "rest of world" catch-all**,
   rather than adding a dedicated `is_default`/worldwide flag. Neither the
   ERD nor the models doc names an explicit worldwide-zone mechanism, and
   "no locations = matches everything not otherwise claimed" is a natural,
   already-expressible state given the existing schema — no new column
   needed. `ResolveShippingZoneAction` checks specific locations first
   (city > state > country) and falls back to any active zone with zero
   `shipping_zone_locations` rows.
3. **Shipping classes attach to `products`, not `product_variations`.** A
   new nullable `products.shipping_class_id` column was added; variations
   inherit their parent product's class rather than getting their own
   column, since nothing in the docs calls for per-variation shipping
   classing and it would be a speculative addition beyond what any gate
   requires.
4. **Rate resolution falls back to the zone's general (class-less) rate
   whenever a vendor order's line items span more than one shipping
   class**, rather than trying to blend two class-specific rates into one
   shipment cost — there's no sensible way to combine two different
   flat/per-weight rates into a single number, and falling back to the
   general rate (which every zone is expected to have) is the same
   "most-specific-match-or-fall-through" resolution shape used everywhere
   else in this codebase (commission resolution, this same zone lookup).
5. **Shipment creation and the `Shipped`/`OutForDelivery`/`Delivered`
   vendor-order transitions are removed from the generic "Mark as X"
   action list** on both the admin and vendor `ViewVendorOrder` pages (the
   Phase 12 mechanism that dynamically renders one Filament action per
   `UpdateVendorOrderStatusAction::nextStatusesFor()` entry) and are
   instead only reachable through the new Shipments relation
   manager/`/vendor/shipments` page's `CreateShipmentAction`/
   `RecordShipmentEventAction`, which carry real carrier/tracking/event
   data alongside the status change instead of a bare optional note. The
   status transitions themselves still go through the exact same
   `UpdateVendorOrderStatusAction` transition guard either way — this is a
   UI-routing decision, not a new business rule.
6. **No automated carrier tracking-API integration** (e.g., polling a real
   carrier's API for live tracking updates) — `ShipmentEvent` rows are
   recorded manually by an admin or vendor through the Filament actions
   built this phase. The gate asks for "shipment events" and "tracking,"
   not third-party carrier API integration, and no specific carrier is
   named anywhere in the architecture docs the way Paystack was for
   payments.
7. **Delivery assignment models a single delivery-person name/phone pair
   per shipment (`assignee_name`/`assignee_phone`), not a link to a `User`
   record with a dedicated "delivery personnel" account type.** No such
   user type or role exists anywhere in the docs (`UserType` only lists
   Customer/VendorOwner/VendorStaff/Admin-tier values), so inventing one
   for this phase would be speculative; the plain name/phone fields are
   sufficient for "delivery assignment" and evidence capture, and a
   `assigned_by`/`created_by` FK to the acting `User` (admin or vendor
   staff) is recorded on every relevant row for accountability.

## What was built

### Data model

Ten new tables (`shipping_zones`, `shipping_zone_locations`,
`shipping_classes`, `shipping_rates`, `shipping_carriers`,
`pickup_stations`, `shipments`, `shipment_events`, `delivery_assignments`,
`delivery_evidence`) plus a nullable `products.shipping_class_id` column.
`shipment_events` and `order_status_histories`-style tables use full
`timestamps()` (not the stricter financial-ledger `created_at`-only
convention), matching the precedent set by `order_status_histories`
itself. New enums: `ShippingRateType` (flat/per-weight/free),
`ShipmentStatus` (pending → packed → shipped → in_transit →
out_for_delivery → delivered, branches failed/returned),
`DeliveryAssignmentStatus` (assigned → picked_up → in_transit →
delivered, branch failed), `DeliveryEvidenceType` (signature/photo).

### Shipping rate calculation

`ResolveShippingZoneAction` — specificity-ordered zone lookup (city >
state > country > "rest of world" catch-all), only ever returning active
zones. `CalculateShippingCostAction` — resolves the zone, sums line-item
weight, resolves the most specific matching active rate (shared shipping
class, else the zone's general rate), and delegates the actual cost math
to `ShippingRate::computeCost()` (flat/per-weight/free, with an optional
free-shipping subtotal threshold that overrides any rate type). Both
throw `ShippingUnavailableException` when nothing can be resolved.

### Checkout integration

`CompleteCheckoutAction` (Phase 11) now computes a real shipping cost per
vendor order — via each vendor's own line items and the checkout's
destination address — before creating the parent `Order` and its
`VendorOrder` rows, replacing the previously-hardcoded `shipping_amount =
0`. Both the parent order and each vendor sub-order carry their own real
shipping cost; the parent's is the sum of its vendor orders' shares, and a
resolution failure (`ShippingUnavailableException`) is caught and
re-thrown as the same `CheckoutValidationException` the storefront
controller already handles gracefully. Existing Phase 10/11 checkout tests
were updated to seed a free (`$0`) shipping rate for their test country so
their pre-existing exact-total assertions remain valid while still
exercising a real zone/rate lookup instead of silently skipping shipping
calculation.

### Shipment and delivery lifecycle

`CreateShipmentAction` creates a `Shipment` (with carrier, tracking
number, pickup station, and ETA) and, in the same transaction, advances
the vendor order to `Shipped` by calling `UpdateVendorOrderStatusAction`
directly — reusing its existing `ready_for_pickup → shipped` transition
guard rather than duplicating it, so attempting to ship an order that
isn't ready for pickup fails exactly like any other illegal status
transition. `RecordShipmentEventAction` appends a `ShipmentEvent`,
updates the shipment's own status, and — for `out_for_delivery`/
`delivered` specifically — advances the vendor order in step through the
same existing transition guard (`shipped → out_for_delivery → delivered`);
other shipment statuses (`packed`/`in_transit`/`failed`/`returned`) have
no vendor-order equivalent and only touch the shipment. `AssignDeliveryAction`
creates exactly one `DeliveryAssignment` per shipment (a second attempt
throws the new `ShipmentAlreadyAssignedException`), `UpdateDeliveryAssignmentStatusAction`
transitions it and stamps `delivered_at`, and `RecordDeliveryEvidenceAction`
stores an uploaded signature/photo file (mirroring `VendorDocument`'s own
`store($path, 'local')` convention) alongside optional recipient name and
notes.

### Admin Filament resources

`ShippingZoneResource` (with `LocationsRelationManager` and
`RatesRelationManager` — the latter's form reactively shows/hides
per-kg-amount and the free-shipping-threshold fields based on the
selected rate type), `ShippingClassResource`, `ShippingCarrierResource`,
and `PickupStationResource` — all gated on `shipping.manage` via the
existing `GatedByPermission` trait, matching `BrandResource`'s reference-data
pattern exactly. `VendorOrderResource` gained a `ShipmentsRelationManager`
with a "Create shipment" header action (visible only while the vendor
order is `ReadyForPickup`) and a "Record event" row action per shipment.

### Vendor panel and customer tracking

`/vendor/shipments` — a table of the vendor's own shipments (across all
their orders; no explicit `vendor_id` filter is needed since
`BelongsToVendorScope` already scopes the `vendorOrder()` relationship's
own query whenever it's touched under the vendor guard) with the same
create-shipment/record-event actions as the admin relation manager, gated
the same way `Inventory`'s page is (vendor owner always has access, staff
need `store.orders.fulfil`). `/account/orders/{order}/track` — a new
customer-facing page (added to the existing `Gate::authorize('view',
$order)`-protected account-orders route group) showing, per vendor
sub-order, its shipment(s), a clickable tracking link built from
`ShippingCarrier::trackingUrlFor()`, the ETA, and a full event timeline;
vendor orders with no shipment yet show "Not yet shipped." A "Track
package" link was added to the existing order-detail page, and a
previously-missing "Shipping" line was added to that page's totals
summary (harmless before this phase since shipping was always $0, now
meaningful).

## Bugs found and fixed during this phase

1. **A real, previously-latent Filament bug, carried over from the same
   root cause documented in the Phase 14 report, found again while
   writing `RatesRelationManager`'s reactive fields**: comparing
   `$get('rate_type')` against `ShippingRateType::Free->value` /
   `::PerWeight->value` instead of the enum case itself. Caught and fixed
   before it ever shipped broken this time, by applying the same
   enum-not-value comparison fix proactively across every new reactive
   closure in this phase.
2. **Filament's `Select::options(callable)` (non-relationship) fields
   silently validate the submitted value against the options list and
   reject anything else with a form error, rather than the action closure
   ever seeing an invalid vendor order.** Discovered while writing the
   cross-vendor-isolation test for `/vendor/shipments`' "Create shipment"
   action: submitting another vendor's `vendor_order_id` (which
   `BelongsToVendorScope` already excludes from the dropdown's own scoped
   query) produces a `mountedActions.0.data.vendor_order_id` validation
   error ("The selected order is invalid") rather than an exception — a
   real, working security boundary, just enforced one layer earlier than
   initially assumed.

## Tests

- `./vendor/bin/pest` — **504/504 passing** (469 carried from Phases 1–14,
  35 new): shipping rate calculation — city/state/country specificity
  ordering, an inactive zone is never resolved, the no-locations
  rest-of-world fallback, class-specific vs. general rate resolution
  (including the multi-class fallback), per-weight cost math, the
  free-shipping threshold override, and both "no zone" and "zone but no
  rate" rejection (10); checkout wiring — a real flat-rate cost is
  computed and added to both parent and vendor-order totals, a
  multi-vendor checkout charges each vendor order its own cost, rejection
  with a clean message when nothing can be resolved, the rest-of-world
  fallback applies at checkout, and a combined per-weight-plus-threshold
  scenario (5); shipment/delivery lifecycle — creating a shipment advances
  `ready_for_pickup → shipped`, shipping an order that isn't ready for
  pickup is rejected, an in-transit event updates only the shipment,
  out_for_delivery/delivered advance the vendor order in step and stamp
  `delivered_at`, a shipment can only ever get one delivery assignment,
  transitioning an assignment to delivered stamps its own `delivered_at`,
  and photo evidence is stored and linked (7); admin Filament — all four
  resource index/create pages load, permission gating, adding a zone
  location and a rate through the relation managers, creating a shipment
  from the Shipments relation manager (advancing the order), a full
  create → out-for-delivery → delivered event sequence, and the
  create-shipment action being hidden once an order is no longer ready
  for pickup (6); vendor panel — the shipments page loads and creates a
  real shipment, cross-vendor shipment isolation, and a cross-vendor
  create attempt being rejected by the scoped dropdown's own validation
  (3); customer tracking — the tracking page renders carrier/tracking
  link/event timeline for the owner, "Not yet shipped" for an unshipped
  vendor order, 403 for another customer, and a guest redirect to login
  (4).
- `./vendor/bin/pint --dirty` — clean.
- `migrate:fresh --seed` verified clean end-to-end, including the new
  `ShippingSeeder` (idempotently seeds one "Rest of World" zone + a $5
  flat rate) running after `CommissionRuleSeeder`.
- A rollback/migrate round-trip on all eleven new/altered migrations
  verified clean in both directions.
- `php artisan route:list` verified all new named routes
  (`account.orders.track`,
  `filament.admin.resources.{shipping-zones,shipping-classes,shipping-carriers,pickup-stations}.*`,
  `filament.vendor.pages.shipments`) resolve correctly.

## Completion Gate Check (Phase 15)

| Criterion | Status |
|---|---|
| Shipping calculation (incl. multi-vendor) | ✅ zone/class-specificity-ordered rate resolution, each vendor order priced independently at checkout, tested |
| Pickup | ✅ `PickupStation` model + admin resource, optional per-shipment pickup-station assignment |
| Tracking | ✅ carrier + tracking number per shipment, customer-facing tracking page with a clickable carrier link |
| Shipment events | ✅ `ShipmentEvent` insert-only timeline, recorded via admin/vendor actions, rendered on the tracking page |
| Delivery assignment | ✅ `DeliveryAssignment` (one per shipment) + status transitions + photo/signature evidence |
| Accurate ETAs | ✅ `estimated_delivery_at` captured at shipment creation, shown to the customer |
| Tests pass | ✅ 504/504 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–14 (no MySQL server, no
   Larastan) remain unchanged.
2. No automated carrier tracking-API integration (Scope Decision 6) —
   `ShipmentEvent` rows are recorded manually by an admin or vendor.
3. Tax calculation remains Phase 16's job — `orders.tax_amount`/
   `vendor_orders.tax_amount` still exist and default to 0, untouched by
   this phase.
4. Delivery assignment is a plain name/phone pair, not linked to any
   delivery-personnel user account (Scope Decision 7) — there is no
   delivery-person login or mobile flow; that's implicitly a Phase 29+
   (mobile) concern if it's ever built, well outside this project's
   current web-only scope.
5. The same `$get(...) === Enum::Case->value` comparison bug flagged in
   the Phase 14 report as still present in Phase 6/8's `ProductForm`
   remains unfixed there — out of scope for this phase's own gate, noted
   again for a future cross-phase audit.

None of these block Phase 16, which is the next phase in the roadmap.
