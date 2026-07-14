# Phase 16 Completion Report — Taxes, Currencies, Languages

## Objective

Give the platform correct tax calculation with immutable order-item
snapshots, accurate currency conversion/money math with historical
exchange-rate snapshots, real language switching with RTL support, and a
first pass at translatable product content, per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 16 completion gate: "correct tax calculation + order snapshots,
accurate currency conversion/money math, exchange rates, language
switching, translatable content, RTL across the platform."

## Scope decisions

1. **No matching tax rate means 0% tax, never an exception** — a
   deliberate divergence from Phase 15's shipping-resolution precedent
   (where no matching rate is checkout-blocking). Zero tax is a valid
   real-world state (tax-exempt jurisdiction, no nexus); an undeliverable
   order is not. `CalculateTaxAction` always returns a result, never
   throws.
2. **Single settlement currency, converted-display-only multi-currency.**
   Checkout, payment, wallet, and commission continue to transact
   exclusively in whichever `currencies.is_default` currency is active —
   this was not redesigned to be multi-currency-native, since doing so
   would ripple through three already-shipped phases disproportionately.
   What this phase actually delivers: (a) an `orders.exchange_rate_snapshot`
   column capturing the rate in effect at order-creation time for
   historical auditability, and (b) a session-persisted display-currency
   preference that converts prices shown on shop/category/product
   browsing pages only — deliberately not on cart/checkout/account/order
   pages, since a converted "display" price at checkout would be
   misleading. The customer sees the real currency they're being charged
   once they commit to buying.
3. **Container-scoped display-currency state instead of static
   properties.** `PriceDisplay` binds the active display currency into
   the container (`App::instance('display.currency', ...)`) rather than a
   raw `private static` property, so it resets correctly between requests
   and between Pest test cases in the same process.
4. **A real, pre-existing Phase 15 gap fixed in this phase**:
   `products.shipping_class_id` (added in Phase 15) was never actually
   exposed on either the admin or vendor product forms, making the
   shipping-class-specific rate tier structurally unreachable. Caught
   while adding the analogous `tax_class_id` field and fixed in the same
   edit on both forms.
5. **No automated exchange-rate fetching.** Rates are managed manually
   (or via `CurrencySeeder`'s static defaults) through the admin
   `CurrencyResource`, which now also manages the currency's one-to-one
   `ExchangeRate` row inline (see below) — matching `CurrencySeeder`'s own
   comment that live rate refresh is out of scope. The gate asks for
   "accurate currency conversion/money math, exchange rates," not a
   live-rate integration.
6. **`ExchangeRate` is a `HasOne`, not a `HasMany`** (`exchange_rates.currency_id`
   is unique) — so rather than force a table-based relation manager onto
   a relation that only ever has 0 or 1 rows, the rate/manual-flag fields
   are embedded directly in `CurrencyForm` and upserted in
   `CreateCurrency`/`EditCurrency`'s create/save hooks, the same
   strip-then-attach pattern already used for staged product media.
7. **`TranslationManagerPage`'s "missing-translation report, import/export"**
   is scoped to `ProductTranslation` specifically — the only translation
   table the architecture docs name anywhere (`02-database-erd.md`) — not
   a general UI-string translation system. A full retrofit of the
   existing 8-phases'-worth of hardcoded Blade strings to `__()` calls was
   judged disproportionate for one phase and deferred; only the
   locale-switching *infrastructure* was built. New UI text going forward
   should use `__()`; the retrofit itself is a candidate for a future
   cleanup pass.
8. **CSV import/export over a queued job or Excel package.** The report
   page reads/writes plain CSV via native `fgetcsv`/`fputcsv` rather than
   pulling in the already-installed-but-unused `maatwebsite/excel`
   package, since the format is simple tabular data and a full Import/Export
   class adds no real value here.

## What was built

### Data model

Four new tables (`tax_classes`, `tax_rates`, `order_item_tax_snapshots`,
`product_translations`) plus two nullable/defaulted columns:
`products.tax_class_id` and `orders.exchange_rate_snapshot`
(`decimal(20,10)`, default 1). `order_item_tax_snapshots` uses
`created_at` only (no `updated_at`), matching `order_item_commissions`'
immutable-ledger convention exactly. `product_translations` is unique on
`[product_id, language_id]` and scoped to `Product` only, matching the
ERD's own naming (no analogous table exists for Category/Brand/CMS
content).

### Tax calculation

`ResolveTaxRateAction` combines two independent specificity dimensions —
location (postal code > city > state > country) and tax class (the
product's own class, else the general/class-less rate) — by trying each
location tier from most-to-least specific, preferring a class-specific
match within each tier before falling back to the general one.
`CalculateTaxAction` wraps it and always returns a `{rate, taxAmount}`
result, defaulting to zero tax when nothing resolves.

### Currency conversion

`ConvertCurrencyAction` converts between any two currencies via the
default currency as a pivot (A → default → B), correctly accounting for
differing `decimal_places` between currencies (e.g. 2dp USD vs. 0dp JPY)
rather than assuming minor units are directly comparable.

### Checkout integration

`CompleteCheckoutAction` (Phase 11) now precomputes per-cart-item tax
into a keyed collection before creating any `Order`/`VendorOrder` rows —
mirroring exactly how Phase 15 already precomputed per-item shipping
costs, for the same reason: the running total has to be known before the
parent rows exist. Those same precomputed values are reused for the
order/vendor-order `tax_amount` totals and for each `OrderItemTaxSnapshot`
row, avoiding a redundant second resolution per item. The order also now
snapshots `exchange_rate_snapshot` from the transacting currency's
`ExchangeRate` at checkout time.

### Locale switching, RTL, and price display

`SetLocale` middleware resolves the active `Language` (session choice →
default → any active), calls `App::setLocale()`, and shares it as
`$currentLanguage` to every view. `SetDisplayCurrency` middleware does the
same for a session-persisted display-currency preference, binding it into
the container via `PriceDisplay::setDisplayCurrency()`. `LocaleController`/
`CurrencyController` handle the switch (`POST /locale/{code}`,
`POST /currency/{code}`), rejecting inactive or unknown codes with a 404.
A previously-hardcoded `app()->isLocale('ar')` RTL check in four Blade
layouts was replaced with `$currentLanguage?->isRtl()`. A new `@price()`
Blade directive (`PriceDisplay::format()`) renders an amount in the
session's display currency (falling back to the default currency),
wired into the shop listing and product-detail pages only, per the scope
decision above.

### Product translations

`Product::translationFor()`/`translatedName()`/`translatedShortDescription()`/
`translatedDescription()` resolve a translation for a given (or current)
locale, falling back to the base untranslated column when none exists. A
`TranslationsRelationManager` (full CRUD, language uniquely constrained
per product) was added to both the vendor and admin `ProductResource`,
letting a vendor or admin manage their own product's per-language
name/short_description/description/seo_title/seo_description.

### Admin Filament resources

`TaxClassResource` (with a `RatesRelationManager` mirroring
`ShippingZoneResource`'s own rates relation manager) and `TaxRateResource`
(standalone, since a rate belongs to a location as much as a class) — both
gated on `taxes.manage`. `CurrencyResource` gained inline exchange-rate
management (see Scope Decision 6). `TranslationManagerPage` — a custom
page (gated on `products.update`) listing every product with which active
languages it's missing a translation for, plus CSV export
(`GET /admin/translation-report/export`, streamed via a real controller
route matching the existing invoice/packing-slip download convention) and
CSV import (bulk `ProductTranslation` upsert matched on SKU + language
code).

## Bugs found and fixed during this phase

1. **The Phase 15 `shipping_class_id` form gap** described in Scope
   Decision 4 — a real, previously-shipped defect, not something
   introduced this phase, caught and fixed while adding the analogous
   `tax_class_id` field.
2. **An unsafe `private static` design for `PriceDisplay`**, caught via
   proactive reasoning about Pest's per-test container reset (not by a
   failing test) before any test was written against it, and corrected to
   container-instance binding.

## Tests

- `./vendor/bin/pest` — **538/538 passing** (505 carried from Phases
  1–15, 33 new): tax calculation — location-specificity ordering
  (postal > city > state > country), tax-class-specific rate preferred
  over the general rate at the same tier, fallback to the general rate
  when no class-specific rate exists, `computeTax` rounding, a full
  checkout computing real per-item tax across two tax classes and rolling
  it into order/vendor-order totals with a correct per-item
  `OrderItemTaxSnapshot`, and a checkout with no configured tax rate still
  succeeding at zero tax (7); currency conversion — same-currency no-op,
  cross-decimal-places conversion via the default-currency pivot,
  round-trip symmetry, a currency with no exchange rate treated as rate 1,
  and the order snapshotting the exchange rate in effect at checkout (5);
  locale/currency switching — default locale + RTL direction, session
  override, fallback to any active language, the switch routes
  persisting to session and rejecting inactive/unknown codes, `@price()`
  rendering in the default and a session-selected display currency, and a
  real shop-listing page rendering a converted price (10); product
  translation model — translated name/short_description/description
  returned when a translation exists, falling back to the base column
  when it doesn't (3); admin Filament — tax-classes/tax-rates/currencies
  index and create pages loading, `taxes.manage` gating, adding a rate to
  a tax class through its relation manager, creating a currency also
  creating its exchange rate row, editing a currency updating its existing
  rate row (never creating a duplicate), the translation manager report
  showing missing languages, importing translations from a CSV, and
  `products.update` gating on the translation manager page (8).
- `./vendor/bin/pint --test` — clean across the entire repo.
- `migrate:fresh --seed` verified clean end-to-end (all six new/altered
  migrations plus every existing seeder, including the new tax tables).
- `php artisan route:list` verified all new named routes
  (`locale.switch`, `currency.switch`,
  `filament.admin.resources.{tax-classes,tax-rates}.*`,
  `filament.admin.pages.translation-manager-page`,
  `admin.translation-report.export`) resolve correctly.

## Completion Gate Check (Phase 16)

| Criterion | Status |
|---|---|
| Correct tax calculation + order snapshots | ✅ specificity-ordered resolution (location × tax class), immutable per-item `OrderItemTaxSnapshot`, tested |
| Accurate currency conversion/money math | ✅ default-currency-pivot conversion correctly handling differing decimal places, tested |
| Exchange rates | ✅ manual rate management via `CurrencyResource`, `orders.exchange_rate_snapshot` captured at checkout |
| Language switching | ✅ session-persisted, `Language`-model-driven, validated against active languages |
| Translatable content | ✅ `ProductTranslation` with fallback-to-base-locale, managed via vendor/admin relation managers, CSV import/export report |
| RTL across the platform | ✅ `Language::isRtl()`-driven `dir` attribute across all storefront layouts |
| Tests pass | ✅ 538/538 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–15 (no MySQL server, no
   Larastan, no local Redis — `migrate:fresh` verified with
   `CACHE_STORE=array` since the test suite itself already runs against
   the array cache driver) remain unchanged.
2. Multi-currency remains single-settlement-currency-plus-converted-display
   (Scope Decision 2) — cart, checkout, wallet, and commission still
   transact only in the default currency. Making the platform genuinely
   multi-currency-native at checkout is a larger, disproportionate change
   not required by this phase's gate.
3. No automated exchange-rate fetching (Scope Decision 5) — rates are set
   manually through the admin panel.
4. The Blade-string-to-`__()` retrofit (Scope Decision 7) was
   deliberately deferred — only new UI text and the locale-switching
   infrastructure follow the translation convention going forward.
5. No security audit yet (Phase 24), no customer-facing refunds/returns
   workflow (Phase 18), and no deployment/ops guide yet (Phase 27) — all
   still block real production traffic, unchanged from the Phase 15
   report's deployment-readiness note.

None of these block Phase 17, which is the next phase in the roadmap.
