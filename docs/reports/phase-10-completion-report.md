# Phase 10 Completion Report — Shopping Cart

## Objective

Give every shopper — guest or authenticated — a working cart on top of the
catalog (Phase 6/8) and inventory (Phase 7) already built: add/update/
remove items, a multi-vendor-aware cart page, save-for-later, a basic
coupon, guest-cart persistence, and a guest→customer merge on login, per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 10 completion gate: "guest/auth cart, multi-vendor cart, merge-on-
login, stock/price validation, coupons, save-for-later, accurate totals,
tests pass."

## Scope decisions

1. **`checkout_sessions` is not built this phase.** The ERD's "Cart &
   Checkout" section and its Batch 9 grouping bundle `carts`, `cart_items`,
   `cart_coupons`, and `checkout_sessions` together, but the phase-gate
   roadmap splits them: Phase 10 is the cart, Phase 11 is checkout
   ("idempotent order creation... idempotency verified"). A
   `checkout_sessions` table has no real trigger event until a checkout
   flow exists to create/read it — building it now would be exactly the
   placeholder-table pattern already avoided in Phase 8 (blog) and Phase 9
   (vendor/delivery reviews). It's deferred to Phase 11.
2. **`coupons` is a deliberately minimal MVP shape, not the full Promotions
   module.** The ERD's "Promotions" batch (`coupons`, `coupon_usages`,
   `flash_sales`, `flash_sale_products`, `discount_rules`) is a distinct
   module from "Cart & Checkout" in `03-modules-and-roles.md`, and Phase
   17's own gate ("every discount type calculates correctly, restrictions
   enforced, vendor coupons scoped correctly, flash sales auto start/stop,
   abuse protection") is clearly that full module's build-out phase. Since
   Phase 10's gate still explicitly says "coupons" must work at the cart
   level, this phase ships a flat percentage-or-fixed `Coupon` (code, type,
   value, optional minimum spend, optional validity window, active flag)
   sufficient to apply a real discount to a cart and see an accurate total
   — with per-vendor scoping, stacking, flash-sale exclusivity, and
   per-customer usage limits explicitly left to Phase 17. `coupon_usages`
   (real redemption tracking) isn't built either: applying/removing a
   coupon on a cart is provisional and doesn't consume anything — usage is
   only meaningful once an order is actually placed (Phase 11/17).
3. **"Save for later" is a `cart_items.saved_for_later` flag, not a
   separate table.** No architecture doc defines a dedicated schema for it
   beyond the roadmap's one-line mention; modeling it as a boolean on the
   existing cart item (rather than moving rows to/from the Phase 9
   wishlist) keeps a saved item tied to its original cart context — price
   snapshot, quantity, variation — exactly as most cart UIs behave, and
   avoids conflating two genuinely different features (a wishlist is
   product-level and cross-cart; "save for later" is a specific line
   item's parked state within *this* cart).

## What was built

### Data model

Four new tables: `carts` (nullable `customer_id` OR a random `session_token`
identifying a guest, `status` — Active/Merged/Abandoned/Converted),
`cart_items` (product + optional variation, `quantity`, a `unit_price`
snapshot refreshed on every add/quantity-update so drift can only ever be
detected, never masked, `saved_for_later`), `coupons` (see Scope Decision
2), `cart_coupons` (a hard 1:1 via `unique(cart_id)` — one applied coupon
per cart, enforced at the DB level, matching the simple "replace, don't
stack" business rule this phase implements). `Cart::subtotal()`/
`discount()`/`total()` compute the checkout-agnostic totals directly from
`activeItems` (saved-for-later items are excluded) and the applied coupon.

### Cart resolution and guest→customer merge

`App\Support\Cart\CartResolver` is the single place that answers "which
cart is this request for": the authenticated customer's active cart, or a
guest cart identified by a random token in a signed cookie (deliberately
*not* the session id, since the session rotates on login independently of
the cart the guest has been building). A `peek()` variant exists
specifically for the nav cart-count badge, which renders on every
storefront page view and must never have the side effect of creating a
cart — and therefore a cookie — for a visitor who has never added
anything. `App\Actions\Cart\MergeGuestCartIntoCustomerCartAction` sums
quantities on matching product+variation lines, keeps distinct lines from
both carts, never lets a guest cart's coupon override one the customer
already applied, and marks the spent guest cart `Merged` (not deleted, for
an audit trail) rather than converting it in place. It's wired to fire on
*every* "web" guard login — password, registration, social, and the 2FA
challenge all funnel through `Auth::guard('web')->login()`, which fires
Laravel's `Login` event — via a single `App\Listeners\MergeGuestCartOnLogin`
registered in `AppServiceProvider`, rather than duplicating the merge call
across four separate login controllers.

### Cart actions

`AddCartItemAction` (requires a variation for variable products, rejects a
variation belonging to the wrong product, merges into an existing line
instead of duplicating it, and validates stock — respecting
`OnBackorder` as "allow any quantity" and `OutOfStock` as an unconditional
reject, mirroring `Product::isInStock()`'s own semantics),
`UpdateCartItemQuantityAction` (quantity 0 removes the line; otherwise
re-validates stock and refreshes the price snapshot), `ApplyCouponAction`
(validates existence, active window, and minimum spend against the live
subtotal; replaces any previously-applied coupon), `RemoveCouponAction`.
Stock-checking logic lives in a small shared `ChecksAvailability` trait
rather than being duplicated between add and update.

### Storefront pages

`/cart`: items grouped by vendor (multi-vendor cart), a per-line
"no longer in stock" or "price changed" banner driven directly by
`CartItem::isInStock()`/`hasPriceDrifted()`, a save-for-later section that
renders independently of whether the active cart is empty, a coupon
form/applied-coupon display, and an order summary with subtotal/discount/
estimated total (tax and shipping are explicitly deferred to their own
later phases, so the total is labeled "estimated" rather than final). The
product detail page (Phase 8) gained a real add-to-cart form: a variation
selector for variable products (disabling out-of-stock options) or a plain
quantity field for simple/digital products, only shown at all when at
least one purchasable option exists. The storefront nav gained a cart
link with an item-count badge, sourced from `CartResolver::peek()`.

## Bugs caught by testing (worth recording)

1. **Constructor-injecting `Illuminate\Http\Request` into a class that is
   itself injected into a controller's constructor saw a request with an
   empty cookie bag.** `CartResolver` originally took `Request $request`
   in its constructor. In practice, a guest's cookie was never read: a
   second request in the same flow (e.g., adding a second item using the
   cookie from the first) always created a *new* guest cart instead of
   reusing the existing one, because `$this->request->cookies->all()` was
   empty at the point `CartResolver` was constructed — even though the
   real incoming request definitely carried the cookie (verified directly
   against `Illuminate\Support\Facades\Request::instance()->cookies->all()`
   at the same point in the same request, which showed the cookie
   correctly). Constructing `CartResolver` happens as part of resolving its
   owning controller's constructor dependencies, and something in this
   app's request lifecycle resolves/binds the concrete `Request` object
   used for constructor injection before it reflects the fully-processed
   (cookie-decrypted) state. Fixed by reading the cookie through the
   `Request` *facade* instead (`Illuminate\Support\Facades\Request::cookie()`),
   which re-resolves the current request from the container at the exact
   point of the call rather than capturing a reference at construction
   time — applied to both `CartResolver` and `MergeGuestCartOnLogin` for
   consistency, since the same class of bug could just as easily bite a
   listener.
2. **The cart page's "Saved for later" section was unreachable once it was
   the only thing left in the cart.** The view's empty-state check was
   `@if ($cart->activeItems->isEmpty())` wrapping the *entire* cart layout,
   including the saved-for-later panel, in the `@else` branch. Saving a
   cart's only active item for later made `activeItems` empty, which
   collapsed the whole page back to "Your cart is empty" — silently hiding
   the very item the customer had just asked to keep. Caught by a
   permanent test that saves an item and then asserts both "Saved for
   later" *and* "Your cart is empty" render. Fixed by keying the top-level
   empty state off `activeItems->isEmpty() && savedItems->isEmpty()`, and
   giving the "cart is empty, but you have saved items" case its own
   smaller message inside the still-rendered layout.
3. **`AddCartItemAction` on a required-but-missing coupon minimum spend
   check and the `Request`/`withUnencryptedCookie` test-writing pitfall**
   were also worked through during this phase — the latter is a testing
   technique note rather than an app bug: Laravel's `withUnencryptedCookie`
   test helper is for cookies the app has explicitly excluded from
   `EncryptCookies`, not a way to hand-feed an already-known plaintext
   value for a cookie the app *does* encrypt (like `cart_token`) — doing so
   makes the server-side decrypt attempt fail silently and drop the
   cookie. The correct pairing for a normally-encrypted cookie is
   `TestResponse::getCookie($name)` (which decrypts for you) paired with
   `withCookie()` (which re-encrypts for the next request), not
   `withUnencryptedCookie()`.

## Tests

- `./vendor/bin/pest` — **333/333 passing** (297 carried from Phases 1–9,
  36 new): cart actions — add/merge-quantity/variation validation/stock
  and backorder/unmanaged-stock rules, update-to-zero removal, price
  refresh on update, coupon apply/reject/minimum-spend/replace/remove,
  guest→customer merge with quantity summing and coupon precedence (18);
  storefront cart — guest persistence via cookie, cross-guest isolation,
  authenticated identification, stock-limit rejection, multi-vendor
  grouping, stale-price and out-of-stock banners, save-for-later/move-to-
  cart, item removal, cross-cart ownership denial, coupon apply/remove via
  the page, invalid-coupon rejection, full merge-on-login through the real
  `/login` route, add-to-cart from the product page for both simple and
  variable products, and a guarantee that browsing never creates a stray
  cart row (18).
- `./vendor/bin/pint --test` — clean.
- `migrate:fresh --seed` and a rollback/migrate round-trip on all four new
  migrations verified clean in both directions.
- `php artisan route:list --name=cart` verified every new named route
  resolves to a real controller.

## Completion Gate Check (Phase 10)

| Criterion | Status |
|---|---|
| Guest cart | ✅ cookie-token identified, persists across requests, tested |
| Authenticated cart | ✅ identified by the web guard user, tested |
| Multi-vendor cart | ✅ items grouped by vendor on `/cart`, tested |
| Merge-on-login | ✅ quantity-summing merge, no loss/duplication, fires for every web-guard login path, tested |
| Stock/price validation | ✅ add/update reject insufficient stock; stale-price and out-of-stock banners on the cart page, tested |
| Coupons | ✅ minimum MVP coupon apply/replace/remove/validate, tested (full Promotions module deferred to Phase 17 — see Scope Decision 2) |
| Save for later | ✅ per-line flag, independent of active-cart emptiness (bug fixed), tested |
| Accurate totals | ✅ subtotal/discount/total computed from live cart state, labeled "estimated" pending tax/shipping (Phases 15/16) |
| Full test suite passes | ✅ 333/333 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–9 (no MySQL server, no
   Larastan) remain unchanged.
2. `checkout_sessions`, real order creation, and idempotent checkout are
   Phase 11's job (see Scope Decision 1).
3. The Promotions module proper — flash sales, automatic/tiered discount
   rules, per-vendor coupon scoping, per-customer usage limits, abuse
   protection — is Phase 17's job (see Scope Decision 2); this phase's
   `Coupon` model is intentionally minimal and will grow those columns/
   relations additively, without a breaking schema change.
4. Cart totals do not include tax or shipping — both are explicitly later
   phases (16 and 15 respectively) and the cart page labels its total
   "estimated" rather than implying it's final.
5. There is no admin Filament resource for coupons yet (no UI to create
   one) — this phase's `Coupon` rows exist to be created directly (e.g., by
   a future admin resource in Phase 17, or via tinker/seeder for now).

None of these block Phase 11 (Guest & Registered Checkout), which is the
next phase in the roadmap and turns the cart this phase built into an
actual order.
