# Phase 5 Completion Report — Vendor Registration, Approval & Store Management

## Objective

Build the flow that turns an applicant into a real vendor: a public
multi-step application wizard, manual/automatic admin review, provisioning
of the `User`/`Vendor`/`Store`/`VendorSubscription` records that Phase 4's
admin panel already manages, a real vendor-guard Filament panel (per
[docs/architecture/07-vendor-dashboard.md](../architecture/07-vendor-dashboard.md)),
subscription plans, staff invites, three-layer vendor data isolation, and a
minimal public store page — per
[docs/architecture/13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 5 completion gate: "registration, manual/automatic approval, secure
document upload, rejection/suspension, store creation, vendor isolation,
vendor staff permissions, public store pages, subscription rules all work."

## What was built

### Schema additions (discovered mid-phase, not anticipated by Phase 2)

Phase 0's original Batch 3 migration plan listed `vendor_subscription_plans`/
`vendor_subscriptions` alongside `vendors`/`vendor_applications`, but Phase 2
didn't build them since no phase needed them yet. This phase adds them, plus
two migrations correcting a modeling gap Phase 2 couldn't have anticipated:
documents are uploaded *during* the public application, before a `Vendor`
row exists, so `vendor_documents.vendor_id` becomes nullable with a new
`vendor_application_id` link; `vendor_applications` gains encrypted banking
fields (mirroring `Vendor`'s own encrypted bank columns) and a
`vendor_subscription_plan_id` so an applicant can pick a plan up front.

### Public onboarding (`routes/vendor.php`, `App\Http\Controllers\Vendor\RegistrationController`)

`/vendor/register` is a single-page, four-section form (business → store →
banking → documents & terms) with plain progressive-enhancement JS for
step navigation — no Livewire dependency, so the whole payload validates and
submits as one request and is fully testable with ordinary HTTP Pest tests.
Country/state/city cascading selects are populated client-side from a small
embedded dataset (5 countries / 9 states / 14 cities from Phase 2's seeder)
rather than a server round trip. Identity and business-registration
documents are required; tax certificate is optional. Files land on the
private `local` disk under `vendor-documents/{applicationId}/` — never a
publicly reachable URL.

`App\Actions\Vendor\SubmitVendorApplicationAction` creates the
`VendorApplication` + `VendorDocument` rows in a transaction, then checks
the `vendor.approval_mode` setting (seeded in Phase 2, unused until now):
`manual` leaves the application `pending` for admin review; `automatic`
immediately calls the same approval action an admin's "approve" button uses.

### Approval (`App\Actions\Vendor\ApproveVendorApplicationAction`)

One action, two callers (auto-approval and the admin "approve" button) —
provisions `User` (vendor_owner, random password, `forceFill`d
`email_verified_at` since the platform is vouching for the address, same
pattern as Phase 3's social login), `Vendor` (copying business/banking/
address fields from the application), `Store` (slug de-duplicated against
existing stores), assigns the `Vendor Owner` role, re-parents any
`VendorDocument` rows from `vendor_application_id` to the new `vendor_id`,
creates a `VendorSubscription` on the applicant's chosen plan (or the
platform default), and sends a "set your password" email via
`Password::broker('vendors')->sendResetLink()` — reusing Phase 3's
per-guard password-reset infrastructure as the invite mechanism rather than
building a separate one. `App\Actions\Vendor\RejectVendorApplicationAction`
records the reason and emails the applicant (`Notification::route('mail',
...)`, since a rejected applicant never gets a `User` row to notify).

### Admin review (`App\Filament\Resources\VendorApplications`)

Read-only + action-driven, like Phase 4's `AuditLogResource`: no create/edit
routes, an `infolist()` for full detail viewing, and `approve`/`reject`
table+page actions gated on `vendors.approve` and visible only while the
application is `pending`. `App\Filament\Resources\VendorSubscriptionPlans`
uses Phase 4's `GatedByPermission` trait (`subscription_plans.manage`,
newly added to `RolePermissionSeeder`), matching the reference-data pattern
already used for `Country`/`Language`/`Currency`.

### Vendor Filament panel (`App\Providers\Filament\VendorPanelProvider`)

The architecture doc calls for the vendor dashboard to be a second Filament
panel (`id('vendor')`, `path('vendor')`, `authGuard('vendor')`), but
authentication stays on Phase 3's `/vendor/login` controller (2FA +
vendor-status gate, already built and tested) rather than Filament's own
Livewire login form. Making that work took real investigation: Filament's
`Authenticate` middleware computes its guest-redirect target via
`Panel::getLoginUrl()` *before* any custom middleware placed alongside it
gets a chance to run (confirmed empirically — even the admin panel's own
working middleware stack skips half its declared list on the initial
full-page Livewire GET, landing straight on `Authenticate`), so a panel
with no `->login()` at all has no redirect target and guests silently land
on the *customer* login page. The fix uses Filament's supported extension
point instead of fighting the pipeline: `->login(PanelLoginRedirect::class)`
with a distinct `loginRouteSlug('panel-login')`, where `PanelLoginRedirect`
extends Filament's own `Login` page and does nothing but
`$this->redirect(route('vendor.login'))` in `mount()`. This satisfies
Filament's internal requirement for a valid `getLoginUrl()` without a second
real login form ever being shown, and the logout menu item — which
similarly hard-resolves `filament.vendor.auth.logout` regardless of a
custom user-menu override — is served by registering that exact route name
against the same Phase 3 logout controller action.

Panel contents: `Dashboard` with a `StoreOverview` stats widget (business
name, vendor status, subscription plan — real data, zero placeholders);
`StoreSettings` (a settings-style Page, not a Resource, since a store is a
singleton per vendor — `Vendor::store(): HasOne`); `StoreStaffResource`
(owner-only per the architecture doc, invite-by-email create form instead of
a raw `store_id`/`user_id` select); `VendorDocumentResource` (view/download
own documents, upload new ones — immutable once submitted, no edit);
`Subscription` (a custom Page showing the current plan and every active
plan; switching to a free plan applies immediately and for real — switching
to a paid one shows "contact support," since no payment collection exists
yet ahead of Phase 13, and a button that couldn't actually charge anyone
would be exactly the kind of non-functional button the project brief
forbids).

### Staff invites (`App\Actions\Vendor\InviteStoreStaffAction`, `App\Listeners\Vendor\ActivateInvitedStoreStaff`)

An owner invites by name + email + a `store.*` permission checklist: finds-
or-creates the `User` (vendor_staff), creates `StoreStaff` (status
`invited`), grants the chosen permissions, and sends the same
`vendors`-broker password-reset email as the vendor-approval flow. The
`StoreStaff` row only earns `active` (and a `joined_at` timestamp) the first
time that account successfully logs into the vendor guard — a new listener
on Laravel's generic `Login` event (the same event Phase 3's
`RecordLoginActivity` already listens to for every guard) flips it,
confirming the invite actually reached a real person rather than trusting
the invite email send alone.

### Vendor data isolation (`App\Models\Scopes\BelongsToVendorScope`)

Implements layer 1 of the three-layer isolation strategy from
[01-system-architecture.md](../architecture/01-system-architecture.md) §4: a
global Eloquent scope applied to `Store` and `VendorDocument` that filters
to `vendor_id = actingVendorId()` whenever the current actor authenticated
through the `vendor` guard, and is completely inert for every other guard.
`User::actingVendorId()` resolves the acting vendor for both an owner (their
own `Vendor`) and staff (their store's owning vendor) — layer 2
(policy-level ownership checks independent of the scope) was already built
in Phase 2's `StorePolicy`; this phase adds the matching `StoreStaffPolicy`
(owner-only, matching the "staff management is owner-only regardless of
granted permissions" rule) and `VendorDocumentPolicy`.

### Public store page (`App\Http\Controllers\StoreController`, `GET /stores/{slug}`)

Minimal, real, and honest about what's not built yet: store name,
description, address, verification badge, vacation-mode banner, working
hours, and a note that products will appear once the catalog module
(Phase 6) exists — no placeholder product grid.

## A genuine bug caught by testing (worth recording)

Writing `BelongsToVendorScopeTest`'s vendor-staff case hung the test suite —
not failed, *hung*, until the harness killed it. Root cause:
`User::actingVendorId()` for a `VendorStaff` loaded their `StoreStaff::store`
relation to read `vendor_id`; `Store` carries `BelongsToVendorScope`, so
that very eager-load triggered the scope's `apply()`, which calls
`actingVendorId()` again to compute its filter — infinite recursion, one
`Store` query per stack frame. Fixed by having `actingVendorId()` (and the
identically-shaped `canAccessVendorDashboard()`) load `Store` via
`->with(['store' => fn ($q) => $q->withoutGlobalScopes()])`, since a method
that exists to *compute* the scope's input can never depend on the scope
itself. This is exactly the class of bug the project's testing philosophy
(prefer real query execution over reasoning about SQL by inspection) exists
to catch — a code review alone would very plausibly have missed it, since
each individual line is correct in isolation.

## Tests

- `./vendor/bin/pest` — **131/131 passing** (97 carried from Phases 1–4, 34
  new): onboarding wizard (5: manual submission, automatic approval, missing
  terms, duplicate email, missing required documents), the approval/rejection
  actions directly (3, including the slug-collision case), vendor data
  isolation (4, including the recursion regression above), staff invite +
  first-login activation (5), subscription plan switching (3, including the
  paid-plan self-service block), the public store page (4), admin review via
  Livewire table actions (4), vendor-document policy (4), single-default
  enforcement extended to `VendorSubscriptionPlan` (2).
- `./vendor/bin/pint --test` — passing, zero style violations.
- `migrate:fresh --seed` verified end-to-end; new migrations verified to
  roll back and re-run cleanly.
- Manual smoke test via `php artisan serve`: `/vendor/register` and
  `/vendor/login` both `200`; guest `GET /vendor` redirects to
  `/vendor/panel-login`, which immediately redirects again to the real
  `/vendor/login` — confirming the login-routing fix works over real HTTP,
  not just in the test client.

## Completion Gate Check (Phase 5)

| Criterion | Status |
|---|---|
| Vendor registration works | ✅ public wizard, tested |
| Manual and automatic approval both work | ✅ same provisioning action, tested for both paths |
| Secure document upload | ✅ private disk, policy-gated download endpoint, tested |
| Rejection works | ✅ reason recorded, applicant notified, tested |
| Store creation | ✅ part of the approval action, tested |
| Vendor isolation | ✅ three-layer (scope + policy + no cross-vendor Sanctum exposure yet, since the API layer isn't vendor-facing until later phases), tested including the guard-inertness case |
| Vendor staff permissions | ✅ invite/activate flow, staff cannot manage other staff even with a matching permission grant, tested |
| Public store pages work | ✅ active + vacation-mode cases, inactive correctly 404s, tested |
| Subscription rules work | ✅ plan selection at application time, free-plan self-service switching, paid-plan upgrade honestly deferred (no fake button), tested |
| Full test suite passes | ✅ 131/131 |

## Known limitations carried forward

1. The two sandbox limitations carried from Phases 1–4 (no MySQL server, no
   Larastan) remain unchanged.
2. Paid subscription plan upgrades are not self-service — no payment
   gateway exists until Phase 13. The UI is honest about this rather than
   showing a button that can't work.
3. Vendor staff document upload/document-type permissions are not
   delegable (owner-only) — this matches the given permission vocabulary
   (`store.*` permissions have no `store.documents.manage` entry), not an
   oversight.
4. `ExchangeRateResource`'s standalone-resource-instead-of-relation-manager
   limitation from Phase 4 is unchanged.

None of these block Phase 6 (Product Catalog, Categories, Brands,
Attributes, Media), which is the next phase in the roadmap and the first to
give vendors something to actually sell through the stores this phase now
provisions.
