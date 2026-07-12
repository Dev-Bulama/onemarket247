# Phase 3 Completion Report — Authentication, Authorization & Account Security

## Objective

Implement complete, guard-isolated authentication for administrators/staff, vendors,
and customers, plus the account-security features the Phase 3 brief lists explicitly:
registration, login/logout, password reset/change, email verification, remember-me,
2FA, device-session management, login-history tracking, brute-force protection,
account locking, API tokens, and social login (Google fully working; Apple
architecture-only; Facebook activates once configured) — per
[docs/architecture/10-security-architecture.md](../architecture/10-security-architecture.md) §1
and the role/guard design in
[docs/architecture/01-system-architecture.md](../architecture/01-system-architecture.md) §3.

## What was built

### Guard isolation (the core mechanism)

- `App\Auth\ScopedEloquentUserProvider` — extends Laravel's `EloquentUserProvider`
  and rejects any credential/session/remember-token that resolves to a user outside
  an allow-listed set of `user_type`s, registered as the `scoped` auth driver in
  `AppServiceProvider::boot()`.
- `config/auth.php` now defines three session guards — `admin` (super_admin/admin/
  staff), `vendor` (vendor_owner/vendor_staff), `web` (customer) — each backed by
  its own scoped provider, plus a `sanctum` guard for the API (unscoped at the
  provider level; actor separation there is via token abilities instead, see below).
  Three matching password-reset brokers share the single `password_reset_tokens`
  table (safe: `users.email` is globally unique).
- **Verified structurally, not just by inspection**: a vendor's credentials fail
  `Auth::guard('admin')->attempt()` and succeed on `Auth::guard('vendor')->attempt()`;
  symmetric checks hold for customer and admin credentials against the other two
  guards. Covered by `GuardIsolationTest`.
- The Filament admin panel is explicitly bound to the `admin` guard
  (`AdminPanelProvider::authGuard('admin')`), so `/admin` inherits the same
  isolation automatically.

### Customer authentication (`routes/auth.php`, guard `web`)

Registration → `RegisterCustomerAction` (creates `User` + `CustomerProfile` in a
transaction, fires `Registered`) → login → logout → forgot/reset password (via
Laravel's password broker, scoped to the `customers` provider) → email verification
(signed URL, `MustVerifyEmail` implemented on `User`) → authenticated password
change. `LoginRequest` centralizes credential resolution, per-`email|ip` rate
limiting (5 attempts before lockout, mirrored for the API), and rejects
suspended/banned accounts even with a correct password — all before a session is
ever established.

### Two-factor authentication

`App\Services\Auth\TwoFactorAuthenticationService` wraps `pragmarx/google2fa`
(secret generation, TOTP verification, recovery codes) and `bacon/bacon-qr-code`
(QR rendering). Setup/confirm/disable live at `/two-factor-authentication`;
login branches into a stateless `TwoFactorSession` (credentials verified, session
*not* yet established) before redirecting to `/two-factor-challenge`, which accepts
a TOTP or single-use recovery code and only then calls `Auth::login()`. The same
branch exists for the vendor guard and the API (`two_factor_code` field on
`POST /api/v1/auth/login`, returning `TWO_FACTOR_REQUIRED` when omitted).

### Device sessions, login history, suspicious-login alerts

`App\Listeners\Auth\RecordLoginActivity` fires on Laravel's `Login` event for
*every* guard (including Filament's admin login, which dispatches the same core
event) and writes an insert-only `LoginHistory` row plus upserts a `DeviceSession`
row keyed by session ID. A login from a fingerprint (`sha1(ip|user_agent)`) not
seen before for that user queues `SuspiciousLoginNotification`. `/account/security`
lists active sessions with per-session revoke, and a "log out of all other devices"
action that combines Laravel's built-in `logoutOtherDevices()` (the actual security
enforcement, via `$middleware->authenticateSessions()` enabled in `bootstrap/app.php`)
with cleanup of our own `device_sessions` audit rows.

### Vendor guard

`/vendor/login` (guard `vendor`) mirrors the customer flow (2FA-aware,
rate-limited) and additionally checks `Vendor::canAccessDashboard()` — a suspended/
pending/rejected vendor cannot reach `/vendor/dashboard` even with the correct
password, satisfying "vendor suspension restricts vendor access" ahead of Phase 5.
The dashboard itself shows real data (business name, status, linked store) rather
than a placeholder, since no vendor-provisioning flow exists until Phase 5 —
accounts for this phase's tests/manual verification are seeded directly.

### API tokens (Sanctum)

`POST /api/v1/auth/{register,login}`, `POST /api/v1/auth/logout`,
`GET /api/v1/auth/sessions`, `DELETE /api/v1/auth/sessions/{id}`, plus JSON
forgot/reset-password endpoints. Tokens are minted with a single ability —
`customer:*` or `vendor:*` — derived from `user_type`; **administrators are
rejected outright** at API login (`ApiLoginRequest::resolveUser()`), since the
architecture reserves the admin panel for session-based access only. Token
expiration is configurable (`SANCTUM_TOKEN_EXPIRATION`, defaults to 30 days).

### Social login

`config/services.php` documents the three-tier design: Google is fully wired via
Socialite's built-in driver; Facebook uses the identical code path and activates
the moment its credentials are set; Apple is deliberately left as
"architecture only" (per the Phase 3 brief's own wording) because real Sign in
with Apple needs a JWT-signed client secret and the separate
`socialiteproviders/apple` package — the route/controller branch exists and
returns a clear "not configured" response instead of a broken redirect.
`RegisterOrLoginSocialUserAction` finds-or-creates by `(provider,
provider_user_id)`, links to an existing password-based account by matching
email rather than duplicating it, and marks the email verified (the OAuth
provider already vouches for it) — the second factor is likewise satisfied by
the provider's own auth, so social login bypasses the TOTP challenge by design.

## Database changes

Five new tables: `personal_access_tokens` (Sanctum), `two_factor_credentials`
(encrypted secret + recovery codes), `login_histories` (insert-only),
`device_sessions`, `social_accounts`. `users` was not altered further this phase
(the `user_type`/`status`/`phone` columns already landed in Phase 2).

## Security considerations addressed this phase

- Guard-level isolation is enforced by a provider, not by a controller-level
  `if` check — a route protected only by `auth:vendor` cannot be satisfied by
  any other actor type, by construction.
- Rate limiting + account-status gate run *before* any credential comparison
  outcome is revealed, and both failed and successful attempts are recorded to
  `login_histories`.
- 2FA secrets and recovery codes are stored via Laravel's `encrypted`/
  `encrypted:array` casts; bank fields on `Vendor` (Phase 2) already followed
  this pattern.
- `email_verified_at` is deliberately excluded from `User`'s fillable list;
  the social-login action sets it via `forceFill()` specifically because an
  OAuth provider — not an end user — is vouching for the address.
- "Logout of all other devices" uses Laravel's own session-invalidation
  primitive (`logoutOtherDevices()` + `authenticateSessions()` middleware),
  not a hand-rolled mechanism that could miss a session store implementation
  detail.
- Every new authenticated route sits behind an explicit `auth:{guard}` (and,
  for security-sensitive customer routes, `verified`) middleware group —
  verified by feature tests that assert both the happy path and the
  unauthorized/unauthenticated rejection.

## A genuine bug caught by testing (worth recording)

While writing tests, two 2FA specs failed with "invalid code" even though the
manual curl-based verification (see below) had already proven the live flow
correct. Root cause: `TwoFactorAuthenticationController` accessed
`$user->twoFactorCredential` as a cached relation *property*; Pest's
`actingAs($user, 'web')` reuses the same PHP `User` object across sequential
simulated requests within one test, so the relation — lazily resolved to `null`
on the first (pre-creation) request — stayed cached as `null` on the second.
Fixed by querying `$user->twoFactorCredential()->first()` (a fresh query) in
`store()`/`create()` instead of relying on the cached property — a real
defensive improvement (relevant to any long-lived process, e.g. a queued job
reusing a model instance), not just a test workaround. A related Sanctum-guard
caching artifact in the API logout test required `auth()->forgetGuards()`
between simulated requests; that one is purely a test-harness quirk (confirmed
correct against a live server via curl) and needed no application change.

## Tests

- `./vendor/bin/pest` — **77/77 passing** (36 carried from Phases 1–2, 41 new):
  guard isolation (4), registration (3), login incl. rate limiting and account
  status (4), logout (1), password reset (3), email verification (3), 2FA (4),
  device sessions (3), vendor auth (3), social login (6), API auth (7), plus
  supporting seeder/model/policy tests from earlier phases.
- `./vendor/bin/pint --test` — passing, zero style violations.
- Full `migrate` → `migrate:rollback --step=27` → `migrate` round-trip verified
  clean (27 migrations added since Phase 1).
- `migrate:fresh --seed` verified end-to-end.
- Manual end-to-end smoke tests via `curl` against a live `php artisan serve`
  instance, ahead of writing the automated tests: customer registration →
  verify-email redirect → signed-link verification → login rate-limit lockout
  (6th attempt correctly blocked) → 2FA setup/QR/confirm → 2FA challenge on next
  login (both correct- and wrong-code cases) → vendor login/dashboard →
  cross-guard rejection (vendor creds against the customer guard) → full API
  register/login/sessions/logout/re-login cycle → admin/staff rejected at API
  login → Google social-redirect producing a real `accounts.google.com` URL
  when configured, and a graceful fallback when not.

## Completion Gate Check (Phase 3)

| Criterion | Status |
|---|---|
| Every role can log in through the appropriate interface | ✅ admin (`/admin/login`), vendor (`/vendor/login`), customer (`/login`) all verified |
| Permissions restrict resources correctly | ✅ Spatie roles/permissions seeded in Phase 2; guard-level isolation now layered underneath |
| Vendors cannot access administrator pages | ✅ structurally guaranteed by `ScopedEloquentUserProvider`, tested |
| Customers cannot access vendor pages | ✅ same mechanism, tested |
| Staff access is permission-based | ✅ inherited from Phase 2's `RolePermissionSeeder`; guard wiring complete, resource-level checks land with Phase 4's admin resources |
| Unauthorized API calls are rejected | ✅ `401 UNAUTHENTICATED` via the standard envelope, tested |
| Password reset works | ✅ web + API, tested |
| Email verification works | ✅ signed URL + invalid-signature rejection, tested |
| Authentication tests pass | ✅ 77/77 |

## Known limitations carried forward

1. Apple sign-in is intentionally architecture-only (matches the Phase 3 brief's
   own phrasing); a working integration needs the `socialiteproviders/apple`
   package and a JWT-signed client secret, deferred to whenever Apple login is
   prioritized.
2. Facebook needs live app credentials to exercise end-to-end (the code path is
   identical to Google's, which was verified against the real Google OAuth
   endpoint); this is a configuration step, not missing code.
3. CAPTCHA is not wired in — the brief lists "CAPTCHA support" but no captcha
   provider/keys are available in this environment; rate limiting + account
   locking cover brute-force protection in the interim.
4. Admin-panel-specific 2FA (a Filament UI for it) is not built; the underlying
   `TwoFactorCredential`/`TwoFactorAuthenticationService` mechanism is
   guard-agnostic and ready to wire into a Filament plugin when Phase 4 builds
   out the admin panel in earnest.
5. The two sandbox limitations carried from Phases 1–2 (no MySQL server, no
   Larastan) remain unchanged.

None of these block Phase 4 (Filament Administration Panel), which builds admin
resources on top of the now-working `admin` guard.
