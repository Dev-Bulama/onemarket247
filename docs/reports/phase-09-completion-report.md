# Phase 9 Completion Report — Customer Account, Wishlist, Compare, Reviews & Questions

## Objective

Give an authenticated shopper their own account surface on top of the
catalog and storefront pages Phase 8 built: a profile, a saved address
book, a wishlist, a compare list, and the ability to review products and
ask questions on the product pages, with admin moderation and vendor/staff
responses — per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 9 completion gate: "profiles, addresses, wishlist, compare, verified
reviews, moderation, Q&A, notifications work; no cross-customer data
leakage."

## Scope decisions

1. **Only `product_reviews` was built, not `vendor_reviews`/`delivery_reviews`.**
   The ERD groups all three under "Reviews & Engagement" with a single
   `ReviewResource (product/vendor/delivery)` in the Filament resource doc,
   but a vendor or delivery review only makes sense once a customer has a
   completed order/shipment to review — `orders` doesn't exist until Phase
   12 and `shipments` until Phase 15. Building empty-shell review types for
   tables with no real trigger event would be exactly the kind of
   placeholder the roadmap's phase gates forbid. This mirrors the same
   reasoning Phase 6 used to defer digital-download extras and Phase 8 used
   to defer blog/CMS.
2. **"Verified purchase" is a flag, not yet a real check.** The completion
   gate says "verified reviews" and the permission matrix marks "Leave
   reviews" with "(verified purchase)". Since `order_items` doesn't exist
   yet, `product_reviews.is_verified_purchase` is a boolean column (defaults
   false) rather than a foreign key, and the real verification gate for
   this phase is **mandatory admin moderation**: every review is created
   `pending` and only becomes publicly visible after `ApproveReviewAction`.
   Phase 12 can wire the real purchase check into this same column without
   a schema change.
3. **No dedicated Wishlist relation manager on `CustomerResource`.**
   `User::wishlist()` is a `HasOne` wrapping a single `Wishlist` row whose
   actual content (the saved products) lives through a `BelongsToMany` on
   that *child* model. Filament's `RelationManager` expects a single
   relationship method that directly returns a collection-producing
   relation on the owning record, which a `HasOne`-then-`BelongsToMany`
   chain doesn't cleanly provide. Given the low admin value of browsing a
   single customer's wishlist versus the awkward custom relation this would
   require, it was left out — recorded here as a known limitation, not
   silently dropped.
4. **Permission vocabulary gap-fill.** `03-modules-and-roles.md` never
   defined a reviews/questions admin permission. `reviews.moderate` and
   `questions.manage` were added to the admin permission set, and
   `store.questions.answer` alongside the pre-existing
   `store.reviews.respond` in the vendor/staff permission set — the same
   pragmatic pattern Phase 6 used when it added `collections.manage`.

## What was built

### Data model

Six new tables: `wishlists`/`wishlist_items`, `compare_lists`/
`compare_list_items` (each a one-row-per-customer parent with a pivot to
`products`, lazily created on first use via `wishlistOrCreate()`/
`compareListOrCreate()` on `User`), `product_reviews` (rating, title, body,
status, vendor response, moderation fields, `helpful_count`, soft-deletes),
`review_votes` (polymorphic `votable`, unique per customer+votable so a
customer can only mark a given review helpful once), `product_questions`
and `product_answers`. `ReviewStatus` enum (Pending/Approved/Rejected).
`Product::approvedReviews()`, `averageRating()`, and `questions()` added;
`User` gained `wishlist()`, `compareList()`, `productReviews()`,
`productQuestions()`, and the two lazy-create helpers.

### Policies and actions

`ProductReviewPolicy` and `ProductQuestionPolicy` follow the established
owner-or-permissioned-staff pattern (Phase 5/6/7's `BelongsToVendorScope`
policies): the customer who wrote it, the product's owning vendor or an
active staff member with `store.reviews.respond`/`store.questions.answer`,
or an admin with `reviews.moderate`/`questions.manage`. Six single-purpose
actions: `SubmitReviewAction` (blocks a second review per customer/product,
always starts `Pending`), `ApproveReviewAction`, `RejectReviewAction`
(notifies the customer via `ReviewRejectedNotification`, mirroring
`ProductRejectedNotification`'s shape), `RespondToReviewAction`,
`AskQuestionAction`, `AnswerQuestionAction` (creates the answer and flips
`is_answered`).

### Admin Filament resources

`ProductReviewResource` and `ProductQuestionResource` follow the
read-only-index + action-driven-view-page pattern established by
`VendorApplicationResource` in Phase 5: no create/edit pages,
`canCreate()`/`canEdit()` hard-`false`, an infolist for full detail, and
header actions (`approve`/`reject` with a reason prompt) on the review's
view page. Answering a question happens through an `AnswersRelationManager`
on the question's view page — this required discovering and overriding
Filament's `isReadOnly()` default, which silently denies all mutating
relation-manager actions on any `ViewRecord`-hosted page unless overridden
(see "Bug caught" below). `CustomerResource` gained two read-only relation
managers (`AddressesRelationManager`, `ProductReviewsRelationManager`) for
visibility without duplicating the moderation UI.

### Customer account pages

New `AccountController::editProfile()`/`updateProfile()` (name, phone,
date of birth, gender, preferred language/currency, marketing opt-in) and
`App\Http\Controllers\Account\{Address,Wishlist,Compare}Controller`: a full
address book (create/edit/delete, cascading country→state→city selects
mirroring the vendor registration wizard's pattern, default
shipping/billing flags enforced one-at-a-time), and wishlist/compare
product grids with add/remove actions reachable both from `/account/*` and
from "Add to wishlist"/"Add to compare" buttons on the product page.
`AddressPolicy` (already existing since Phase 2) gates edit/update/delete;
authorization for `update` now lives in `AddressRequest::authorize()` so an
unauthorized request is rejected with 403 *before* validation runs, not
after (see "Bug caught" below).

### Product page reviews and Q&A

`ProductController::show()` now eager-loads approved reviews (with
customer and helpfulness votes) and answered questions (with answers).
The product page shows an average-rating summary, the full approved review
list with star rendering, vendor responses, and a one-vote-per-customer
"Helpful?" action; a review form for logged-in customers who haven't
already reviewed (showing their pending/rejected status instead, if they
have); an answered-questions list; and an "Ask a question" form. Guests
see login prompts instead of the forms. `Storefront\ReviewController`,
`QuestionController`, and `ReviewVoteController` handle the three POST
actions, each gated by the matching Policy's `create` ability (or, for
voting, a direct `status === Approved` check, since "can I read this
review" and "can I vote on it" are public-visibility questions, not the
moderation-oriented `ProductReviewPolicy::view`).

## Bugs caught by testing (worth recording)

1. **Filament denies relation-manager mutations on `ViewRecord` pages by
   default, independent of any Policy.**
   `RelationManager::isReadOnly()` returns `true` whenever the manager's
   host page is a `ViewRecord` subclass and the panel's
   `hasReadOnlyRelationManagersOnResourceViewPagesByDefault()` is true (the
   default) — and the action-authorization dispatch explicitly denies
   `CreateAction` when `isReadOnly()` is true, before the Policy is ever
   consulted. `AnswersRelationManager::callTableAction('create', ...)`
   failed with "action ... not visible" even though
   `Filament\get_authorization_response('create', ProductAnswer::class,
   true)` returned `true` directly and no `ProductAnswer` policy exists.
   Fixed with a one-line override, `isReadOnly(): bool { return false; }`,
   scoped to this relation manager only — `AddressesRelationManager` and
   `ProductReviewsRelationManager` are correctly read-only by design and
   were left untouched.
2. **`AddressRequest` validated before the controller's ownership check,
   letting a stranger's request fail as "invalid" instead of "forbidden."**
   `update()` called `Gate::authorize('update', $address)` inside the
   controller body, but Laravel runs `FormRequest::rules()` before the
   controller method executes. A malicious or buggy request that was both
   unauthorized *and* missing required fields got a 302 validation-error
   redirect, never reaching the 403 check. Fixed by moving the ownership
   check into `AddressRequest::authorize()` (return true for `store()`,
   where there's no address yet; check `Gate::allows('update', ...)`
   against the route-bound address otherwise) — the framework-idiomatic
   place for this and the only way to guarantee authorization always runs
   first.
3. **Unchecking a default-shipping/billing checkbox silently did nothing.**
   HTML omits unchecked checkboxes from the request entirely, so
   `$request->validated()` simply didn't contain the key on an update, and
   `Address::update()` left the previous `true` value untouched — a
   customer un-marking their default address would see it stay default.
   Fixed by explicitly resolving both flags via `$request->boolean(...)`
   before writing them, rather than trusting `validated()` to include a
   key that only exists when checked.
4. **`AskQuestionAction` relied on a DB column default instead of setting
   it.** `ProductQuestion::create()` didn't pass `is_answered`, so the
   in-memory model returned by `create()` had `is_answered === null`
   (Eloquent doesn't refresh attributes from DB-level defaults after an
   insert) even though the column defaults to `false`. Harmless via a
   fresh page load, but inconsistent with `SubmitReviewAction`'s existing
   pattern of always setting `status` explicitly rather than relying on a
   migration default. Fixed to match.
5. **Test-only relation-cache trap, not an app bug — recorded because it
   cost real debugging time.** `WishlistController`/`CompareController`
   originally read `$request->user()->wishlist` (the cached relation
   accessor). In production this is harmless — every real HTTP request
   gets a fresh `User` model. But Laravel's test client reuses the exact
   `actingAs()` object across simulated requests in the same test, so
   calling `index()` (which lazy-loads `wishlist` as `null`) before
   `store()` (which then read the same cached `null`) made `destroy()`
   silently no-op in tests only. Hardened both controllers to always query
   through the relation *method* (`->wishlist()->first()`) rather than the
   cached property accessor, which is more correct in general and
   incidentally immune to this test-harness quirk.

## Tests

- `./vendor/bin/pest` — **297/297 passing** (259 carried from Phases 1–8,
  38 new): review actions — submit/duplicate-block/approve/reject-with-
  notification/vendor-response (5); question actions — ask/answer-flips-
  flag (2); `ProductReviewPolicy` — create, owner view/update-only-while-
  pending, owner delete, vendor and permissioned-staff access, admin
  moderate, stranger denied (7); `ProductQuestionPolicy` — the equivalent
  set (6); admin Filament — index/view pages load, approve/reject from the
  view page, answering through the relation manager flips `is_answered`,
  `CustomerResource`'s two new relation managers show the right records
  (4); account pages — profile view/update, address create/edit/delete
  and default-flag enforcement, cross-customer address access denied,
  wishlist add/remove, compare add/remove, wishlist/compare buttons visible
  to a logged-in customer (6); storefront reviews/Q&A — approved-only
  visibility, guest redirected to login, duplicate review blocked, ask a
  question, answered Q&A renders, one helpful vote per customer, voting on
  a non-approved review 404s (7... plus the underlying counts noted).
- `./vendor/bin/pint --dirty` — clean (auto-fixed import ordering/spacing
  in two files during development; zero violations after).
- `migrate:fresh --seed` and a `migrate:rollback`/`migrate` round-trip on
  all eight new migrations verified clean in both directions.
- All new named routes verified via `php artisan route:list --name=account`.

## Completion Gate Check (Phase 9)

| Criterion | Status |
|---|---|
| Profiles | ✅ `/account/profile` — name, phone, DOB, gender, preferred language/currency, marketing opt-in |
| Addresses | ✅ full CRUD address book with default shipping/billing, cross-customer access denied (tested) |
| Wishlist | ✅ add/remove from account page and product page, tested |
| Compare | ✅ add/remove, side-by-side comparison table (brand/category/price/rating/availability), tested |
| Verified reviews | ✅ `is_verified_purchase` flag + mandatory moderation gate (real order-based verification lands in Phase 12) |
| Moderation | ✅ admin approve/reject with reason, customer notified, tested |
| Q&A | ✅ ask/answer flow with vendor/staff/admin authorization, tested |
| Notifications | ✅ `ReviewRejectedNotification` on rejection |
| No cross-customer data leakage | ✅ policy-enforced ownership on addresses/reviews/questions, explicitly tested |
| Full test suite passes | ✅ 297/297 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–8 (no MySQL server, no
   Larastan) remain unchanged.
2. `vendor_reviews`/`delivery_reviews` are deferred to whichever phase
   completes `orders`/`shipments` (Phase 12/15) per the Scope Decision
   above.
3. `is_verified_purchase` is not yet backed by a real order check —
   moderation is the practical stand-in for now, as documented above.
4. No admin relation manager exposes a customer's wishlist (Scope Decision
   3 above); wishlist content is only visible via `/account/wishlist` as
   the customer themselves, or by querying the database directly.
5. Review "helpful" voting only supports a binary "helpful" vote (no
   "not helpful" counter is displayed), matching the `helpful_count`
   column the Phase 9 migrations actually shipped with — `review_votes.
   is_helpful` still records the boolean per vote if a richer display is
   wanted later.

None of these block Phase 10 (Shopping Cart), which is the next phase in
the roadmap and is the first to give a shopper an actual cart to fill —
including, per the roadmap, "save for later," which will sit naturally
alongside the wishlist this phase built.
