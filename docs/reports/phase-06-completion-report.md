# Phase 6 Completion Report — Product Catalog, Categories, Brands, Attributes & Media

## Objective

Give the vendors and stores Phase 5 now provisions something to actually
sell: a reference-data catalog (categories, brands, attributes/attribute
values, collections, tags) managed from the admin panel; a vendor-facing
product resource covering all three product types (simple, variable,
digital) with variations, media, and staged uploads; a manual/automatic
approval workflow mirroring Phase 5's vendor-application pattern; vendor
data isolation extended to products; and protected digital-file downloads —
per
[docs/architecture/13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 6 completion gate: "vendors create products (all types), approval
rules, variation stock, media, categories/subcategories, brands, attributes,
swatches, protected digital products, vendor product isolation, tests pass."

## What was built

### Schema (15 new migrations)

`categories` (self-referential `parent_id`, a materialized-path `path`
column recomputed on every save), `brands`, `attribute_groups`/`attributes`/
`attribute_values` (with an optional `color_code` for swatch-type
attributes), `collections`, `product_tags`, and `products` itself (vendor-
and brand-scoped, `type`/`status` enums, money fields as unsigned-integer
minor units, physical dimensions, review/approval fields), plus the pivot
and child tables: `product_categories` (with an `is_primary` flag),
`product_variations` and their attribute-value pivot,
`product_digital_files`, `collection_products`, `product_tag_pivot`, and
`related_products`. Deliberately **not** built this phase:
`product_downloads` (a customer entitlement/download-count table) — an
entitlement is created from a purchased `order_item`, which doesn't exist
until Phase 12, so "protected digital products" here means "files live on a
private disk behind a policy check," not yet "a customer who bought it can
redownload it." The ERD's separate `product_images`/`product_videos`/
`product_documents` tables were consolidated into Spatie MediaLibrary's
existing polymorphic `media` table (already created in Phase 2) using named
collections (`images`/`videos`/`documents`) instead of three near-duplicate
bespoke tables.

### Models, enums, factories

`Category` (self-join, `booted()` recomputes `path` from the parent chain
on every save — including when `parent_id` changes on an already-loaded
instance, see the bug note below), `Brand`, `AttributeGroup`, `Attribute`,
`AttributeValue`, `Collection`, `ProductTag`, `Product` (implements
`HasMedia`; `booted()` applies `BelongsToVendorScope`; a 300×300 `thumb`
media conversion), `ProductVariation` (its own `images` media collection),
`ProductDigitalFile`. Five new enums (`ProductType`, `ProductStatus`,
`StockStatus`, `AttributeInputType`, `RelatedProductType`). Every model has
a matching factory with the states exercised by tests (`draft()`,
`pendingApproval()`, `variable()`, `digital()`, `childOf()`, `swatch()`).

### Vendor isolation and authorization

`Product` picked up Phase 5's `BelongsToVendorScope` (layer 1) directly.
`App\Policies\ProductPolicy` mirrors `StorePolicy`'s owner-or-permissioned-
staff pattern exactly (layer 2): the vendor owner always has full access to
their own products; a staff member needs both active status and the
matching `store.products.manage` permission; admin access is entirely
permission-gated (`products.view/update/delete/approve/feature`),
independent of ownership.

### Approval workflow (`App\Actions\Product\*`)

`SubmitProductForApprovalAction` mirrors Phase 5's vendor-application
auto/manual split exactly: checks the new `products.approval_mode` setting
and either publishes immediately or parks the product in
`pending_approval`. `ApproveProductAction` publishes (preserving an
existing `published_at` on re-approval rather than overwriting it) and
records the reviewer; `RejectProductAction` records the reason and emails
the vendor via `ProductRejectedNotification`.

### Admin Filament resources (`App\Filament\Resources\{Categories,Brands,Attributes,Collections,Products}`)

The four reference-data resources reuse Phase 4's `GatedByPermission` trait
(`categories.manage`, `brands.manage`, `attributes.manage`, a newly-added
`collections.manage`); `AttributeResource` registers a `ValuesRelationManager`
with a `ColorPicker` shown only when `input_type` is `swatch`.
`ProductResource` has no create page — like `VendorResource`/`StoreResource`
in Phase 4, products originate from vendors, not the admin panel — and
relies entirely on the auto-discovered `ProductPolicy` rather than
`GatedByPermission`. Its table carries `approve`/`reject`/`toggleFeatured`
row actions, each visible only when the acting admin can perform that
specific ability, plus a `DigitalFilesRelationManager` for admin review
downloads (read-only — files are owned by the vendor's staged-upload flow).

### Vendor Filament product resource (`App\Filament\Vendor\Resources\Products`)

Covers all three product types through one reactive form (`Get`-driven
`visible()`/`required()` toggles hide price/stock fields for variable
products and reveal the digital-file uploader only for digital ones).
Categories and tags use `CheckboxList::make('categories')->relationship(...)`
— Filament's built-in relationship binding handles the `BelongsToMany`
sync automatically on both create and edit, the same pattern already
proven by Phase 4's `RoleForm`, so no manual pivot-sync code is needed in
`CreateProduct`; `Product::primaryCategory()` falls back to the first
attached category when none is explicitly marked primary, since this
binding has no way to set that flag itself. A `HandlesProductMedia` trait
shared by `CreateProduct`/`EditProduct` moves images and digital files from
temporary staging directories (`tmp-product-media` public,
`tmp-product-digital-files` private — necessary because the owning
`Product` doesn't exist yet at form-fill time) into their permanent homes
(a MediaLibrary collection, or `product-digital-files/{id}/` on the private
disk) once the record exists. `VariationsRelationManager` lets a vendor
build out a variable product's SKUs, each tied to one or more
variation-type attribute values via another relationship-bound
`CheckboxList`, filtered to attributes where `is_variation = true`.
`ProductsTable` replaces the admin's approve/reject actions with a single
`submitForReview` action (visible only for draft/rejected products) that
calls `SubmitProductForApprovalAction`.

### Protected digital-file downloads

`GET /product-digital-files/{productDigitalFile}/download` (`auth:admin,
vendor` middleware) mirrors Phase 5's `VendorDocumentDownloadController`
exactly: `Gate::authorize('view', $product)` — loading the product without
`BelongsToVendorScope` first, since that scope is a query-time isolation
filter, not the authorization check itself, and `ProductPolicy::view()`
already performs the real ownership check for whichever guard is
authenticated. Deleting a `ProductDigitalFile` record now also deletes the
underlying file from disk (a `deleting` model event), so removing a file
from the vendor relation manager doesn't leave an orphaned private file
behind.

## Two genuine bugs caught by testing (worth recording)

1. **`Storage::disk()->download()` return type.** Writing the first digital-
   download test produced a 500, not a 403/200 — `TypeError: ...
   __invoke(): Return value must be of type Illuminate\Http\Response,
   Symfony\Component\HttpFoundation\StreamedResponse returned`. Laravel's
   `FilesystemAdapter::download()` has always returned a `StreamedResponse`,
   never a `Response`; the type hint was simply wrong. This affected not
   only this phase's new `ProductDigitalFileDownloadController` but also
   Phase 5's `VendorDocumentDownloadController`, which carried the same
   incorrect hint — invisible until now because that endpoint had no tests
   yet. Both are fixed as part of this phase.
2. **Stale cached `parent` relation on `Category`.** A "moving a category to
   a new parent recomputes its `path`" test failed: updating `parent_id` on
   an already-loaded `Category` instance left `path` pointing at the *old*
   parent, because Eloquent caches a loaded `BelongsTo` relation and the
   `saving` hook was reading that stale cache rather than re-querying.
   Harmless in the normal Filament edit flow (a fresh model instance is
   bound per request), but a real bug for any code path that loads a
   category once and mutates it later (e.g. a future bulk re-parenting
   job). Fixed by calling `unsetRelation('parent')` in the `saving` hook
   whenever `parent_id` is dirty, forcing a fresh lookup.

## Tests

- `./vendor/bin/pest` — **174/174 passing** (135 carried from Phases 1–5, 39
  new): category tree/path computation including the re-parenting fix (4),
  vendor isolation extended to products (1, added to the existing
  `BelongsToVendorScopeTest`), product model behavior — primary-category
  fallback, stock rules for products and variations, attribute-value color
  codes, digital-file disk cleanup on delete (7), `ProductPolicy` — owner,
  permissioned/suspended staff, unrelated vendor, admin `approve`/`feature`
  abilities (7), the approval/rejection actions directly including the
  auto-approval-mode setting (6), admin catalog resources — all pages load,
  approve/reject/feature table actions and their visibility rules,
  permission-gated access (7), the vendor product resource end-to-end —
  page loads, cross-vendor isolation, create with categories/tags/images via
  the real Livewire form, submit-for-review, variations with attribute
  values (6), digital-file download access control — owner, other vendor,
  permissioned/unpermissioned admin, guest (5).
- `./vendor/bin/pint` — clean (fixed import ordering on five generated
  files as part of this phase; zero violations after).
- `migrate:fresh --seed` verified end-to-end; the 15 new migrations were
  also individually verified to roll back and re-run cleanly during
  development.
- Manual verification via Livewire component tests exercised the actual
  Filament form-fill → validate → persist pipeline for both the admin and
  vendor panels (not just direct model/action calls), including real
  `FileUpload` staging with `Storage::fake()`.

## Completion Gate Check (Phase 6)

| Criterion | Status |
|---|---|
| Vendors create products (all types) | ✅ simple/variable/digital, reactive form, tested |
| Approval rules | ✅ manual/automatic modes, submit/approve/reject, tested |
| Variation stock | ✅ `manage_stock`/`stock_status` rules identical for products and variations, tested |
| Media | ✅ Spatie MediaLibrary collections (images/videos/documents), staged-upload pattern, tested |
| Categories/subcategories | ✅ materialized-path tree, unlimited depth, re-parenting recomputes paths correctly, tested |
| Brands | ✅ reference-data resource, gated |
| Attributes | ✅ groups, values, `is_filterable`/`is_variation` flags |
| Swatches | ✅ `color_code` + `ColorPicker`, shown only for swatch-type attributes, tested |
| Protected digital products | ✅ private disk, policy-gated download endpoint, tested against owner/other-vendor/admin/guest |
| Vendor product isolation | ✅ `BelongsToVendorScope` + `ProductPolicy`, tested including cross-vendor edit-page 404 |
| Full test suite passes | ✅ 174/174 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–5 (no MySQL server, no
   Larastan) remain unchanged.
2. Digital-file downloads are vendor/admin-only by design this phase — a
   customer-facing entitlement (buy once, redownload anytime) cannot exist
   until `order_items` is built in Phase 12. This is documented directly in
   the `product_digital_files` migration.
3. The vendor product edit form's `images`/`digital_files` fields are
   staged-upload-only: editing a product can *add* new files but cannot
   display or reorder previously attached media inline (no
   `filament/spatie-laravel-media-library-plugin` dependency was added this
   phase). Existing files remain manageable via the dedicated relation
   managers (variations' images, and the digital-files list with its own
   download/delete actions).
4. `related_products` (up-sell/cross-sell/frequently-bought-together) has a
   schema and model relation but no UI yet — nothing in the Phase 6 gate
   requires it, and it is naturally revisited once storefront product pages
   (Phase 8) need to render it.

None of these block Phase 7 (Inventory, Warehouses & Stock Management),
which is the next phase in the roadmap and builds directly on the
`stock_quantity`/`manage_stock`/`stock_status` fields this phase already
established on both `products` and `product_variations`.
