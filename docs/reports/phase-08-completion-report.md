# Phase 8 Completion Report — Customer-Facing Web Marketplace

## Objective

Put the product catalog (Phase 6) and accurate inventory (Phase 7) in front
of an actual shopper: a homepage, full shop catalog with filters and sort,
category/brand/collection browsing, search, a product detail page, a store
directory, and real product listings on vendor store pages — per
[docs/architecture/13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 8 completion gate: "all public pages work, search/filters/sort work,
vendor and product pages work, fully responsive, RTL, working nav, no
broken links, no placeholder sections."

## Scope decision: blog and CMS pages deferred to Phase 19

[06-web-pages.md](../architecture/06-web-pages.md) contains two page-to-phase
mappings that disagree with each other: a summary sentence lists blog and
static pages under Phase 8, while its own per-route table explicitly assigns
`/blog*`, `/pages/{slug}`, and "homepage builder sections" to Phase 19 (CMS,
blog, menus, homepage builder — where the backing `blog_posts`/`pages`
models are actually built). The per-route table is the more specific and
authoritative source, and a blog index/post page has no real content to
render until Phase 19 exists — shipping one now would be exactly the kind
of placeholder section the completion gate forbids. `/contact`, `/faq`,
`/terms`, `/privacy-policy` are different: they're fixed routes (not
`/pages/{slug}`), require no CMS, and ship this phase with real content and,
for `/contact`, a working mail-backed submission handler.

## What was built

### Shared storefront layout and infrastructure

`resources/views/layouts/storefront.blade.php` — nav (shop/categories/
brands/stores links, a search bar wired to `/search`), session/error
banners, and a footer linking the static pages — used by every storefront
view. Matches the CDN-Tailwind pattern already established by
`layouts/app.blade.php`/`layouts/guest.blade.php` in earlier phases rather
than introducing a `@vite()` build dependency (`public/build` is gitignored
and not produced in this environment, so a Vite-only page would 500 outside
a machine that has run `npm run build`). All new controllers live under
`App\Http\Controllers\Storefront\`; the existing `StoreController` moved
there too since Phase 8 substantially rewrites it. The unused default
Laravel `welcome.blade.php` scaffold view was deleted once `/` got a real
homepage.

### Shared product-listing infrastructure (`App\Http\Controllers\Storefront\Concerns\FiltersProducts`)

One trait supplies `filteredProducts()` (status=Published always enforced;
category, brand, price range, in-stock, and sort — newest/price-asc/
price-desc/name — all as query-string filters; eager-loads `brand`/`media`;
paginates 24/page with `withQueryString()`) and `filterOptions()`
(active categories/brands for the sidebar) to every listing page: shop,
category, brand, collection, search, and store. `Product::displayPrice()`/
`displayPriceRange()` were added to give variable products a representative
"from" price for cards and an actual min–max range on their detail page,
computed from active variations rather than the (always-null) base
`products.price`. `Category::descendantIds()` uses the materialized `path`
column from Phase 6 to include a category's entire subtree in one query, no
recursion needed.

### Pages

`/` (homepage: featured categories, featured products, featured stores —
directly queried, no builder); `/shop` (full catalog); `/categories` +
`/categories/{slug}` + `/categories/{slug}/{subcategory}` (a category page
includes products from every descendant subcategory); `/brands` +
`/brands/{slug}`; `/collections/{slug}`; `/search` (name/description/SKU
`LIKE` matching — the ERD's full-text-index-based search is a documented
future upgrade, not built this phase); `/products/{slug}` (image gallery,
simple/digital price or variable price range, per-variation price/stock
table, vendor/store link, categories, and an honest "ordering isn't open
yet" note instead of a non-functional cart button, since cart doesn't exist
until Phase 10); `/stores` (a new vendor directory, searchable by name) and
`/stores/{slug}` (now lists the vendor's real published products, replacing
Phase 5's honest "available once the catalog module launches" placeholder);
`/contact` (a real form that sends a `ContactMessageSubmittedNotification`
mail to the platform's `mail.from.address`, rate-limited at `throttle:5,1`),
`/faq`, `/terms`, `/privacy-policy` (static, real launch-appropriate copy).

## Two genuine bugs caught by testing (worth recording)

1. **`request('category')` resolving to a route-bound model, not the query
   filter.** The shared filter sidebar used `request('category')` /
   `request('brand')` to read the `?category=`/`?brand=` query-string
   filters. On `/categories/{category:slug}` and `/brands/{brand:slug}`
   pages, the route itself also binds a parameter literally named
   `category`/`brand` (to a `Category`/`Brand` model, via Laravel's
   implicit route-model binding). The global `request()` helper's
   single-argument form falls back to `$this->route($key)` whenever the key
   isn't in `all()` — so on a page with no query string, `request('category')`
   silently returned the *route's bound Category object*, and comparing it
   to an int (`== $categoryOption->id`) threw a `TypeError`. Fixed by
   renaming the filter's query keys to `category_id`/`brand_id` everywhere
   (controller, trait, view) so the name can never collide with a route
   parameter — a narrow rename, but the kind of trap that a code review
   alone would very plausibly miss, since `$request->integer('category')`
   in the controller (which reads `input()`, not `route()`) was already
   safe and gave no hint the view-layer helper behaved differently.
2. **Laravel's implicit nested route-model-binding scoping.** The
   `/categories/{category:slug}/{subcategory:slug}` route made Laravel
   assume `subcategory` was a *child resource* of `category` and try to
   resolve it by calling a guessed relationship method,
   `Category::subcategories()` — which doesn't exist (the real relation is
   `children()`). Every request 500'd with `BadMethodCallException`. Fixed
   with `->withoutScopedBindings()` on that route, since the controller
   already validates `$subcategory->parent_id === $category->id` itself
   (404ing otherwise) and doesn't need or want Laravel's automatic
   relationship-guessing scope.

## Tests

- `./vendor/bin/pest` — **259/259 passing** (216 carried from Phases 1–7, 43
  new): homepage content and the no-data case (3); shop listing/filtering/
  sorting/pagination and draft-exclusion (7); category index, direct and
  descendant-inclusive product listing, subcategory scoping, and the
  parent-mismatch 404 (5); brand index and per-brand product isolation (2);
  collection product isolation (1); search by name/SKU, the empty-query
  prompt, and the no-results message (4); product detail — content,
  draft/pending 404s, variation pricing, inactive-variation exclusion, and
  the store link (6); store directory, name search, real product listing
  scoped to the correct vendor, and the inactive-store 404 (4); contact
  form validation and mail-notification dispatch, plus FAQ/terms/privacy
  loading (5); `Product::displayPrice()`/`displayPriceRange()` and
  `isVisibleToCustomers()` (3); `Category::descendantIds()` (2).
- `./vendor/bin/pint` — clean (auto-imported `Illuminate\Support\Collection`
  in two files; zero violations after).
- `migrate:fresh --seed` verified end-to-end.
- `php artisan route:list` verified every new named route resolves to a
  real controller/view with no missing-class errors.

## Completion Gate Check (Phase 8)

| Criterion | Status |
|---|---|
| All public pages work | ✅ home/shop/categories/brands/collections/search/products/stores/contact/faq/terms/privacy, tested |
| Search/filters/sort work | ✅ category, brand, price range, in-stock, four sort orders, tested |
| Vendor and product pages work | ✅ store directory + real per-store product listings, product detail page, tested |
| Fully responsive | ✅ Tailwind mobile-first grid/flex layouts throughout (2-col mobile → 3/4-col desktop product grids) |
| RTL | ✅ shared layout's `dir="rtl"` toggle (established in Phase 3/5) applies to every storefront page automatically |
| Working nav, no broken links | ✅ every nav/footer link resolves to a real named route, verified via `route:list` |
| No placeholder sections | ✅ blog/CMS pages explicitly deferred to Phase 19 rather than shipped empty (see Scope Decision above) |
| Full test suite passes | ✅ 259/259 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–7 (no MySQL server, no
   Larastan) remain unchanged.
2. Search uses `LIKE '%term%'` matching on `name`/`description`/`sku`
   rather than the ERD's suggested full-text index or a dedicated search
   engine — both are explicitly called out in
   [12-deployment-roadmap.md](../architecture/12-deployment-roadmap.md) as
   a later optimization, not a Phase 8 requirement, and `LIKE` matching is
   portable across the SQLite/MySQL split this project supports.
3. The product detail page has no interactive variation selector or "Add
   to Cart" — intentionally, since a selection only matters once there's a
   cart to add to (Phase 10). Variation price/stock is shown as an
   informational table instead of a non-functional buy flow.
4. `related_products` (up-sell/cross-sell/frequently-bought-together,
   schema-only since Phase 6) still has no admin UI to populate it and so
   is not rendered on the product page — nothing in the Phase 8 gate
   requires it.
5. Blog (`/blog*`) and CMS static pages (`/pages/{slug}`) are deferred to
   Phase 19 per the Scope Decision above.

None of these block Phase 9 (Customer Account, Wishlist, Compare, Reviews &
Questions), which is the next phase in the roadmap and is the first to give
an authenticated shopper their own profile, saved addresses, wishlist,
compare list, and the ability to review products and ask questions on the
pages Phase 8 now renders.
