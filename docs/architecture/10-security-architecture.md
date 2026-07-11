# 10 — Security Architecture

## 1. Authentication

- Separate guards per surface: `admin`, `vendor`, `web` (customer,
  session-based storefront), `sanctum` (token-based, API/mobile). A
  compromised or expired token in one guard cannot satisfy another guard's
  checks.
- Passwords: bcrypt/argon2id via Laravel's default hasher, breach-list check
  on registration (`Password::default()->uncompromised()`).
- 2FA: TOTP-based, recovery codes, enforced optionally per role (recommended
  mandatory for admins).
- Brute-force protection: Laravel's login throttle
  (`RateLimiter::for('login', ...)`) keyed by email+IP, exponential lockout,
  CAPTCHA after N failed attempts, account-lock notification email.
- Device/session management: `device_sessions` table records device
  fingerprint, IP, last-seen; "log out of all other devices" revokes all
  Sanctum tokens/sessions except the current one.
- Suspicious login alerts: new-device or new-country login triggers an email
  notification with a "this wasn't me" revoke link.

## 2. Authorization

- **Policy-first**: every model with restricted access has a corresponding
  Laravel Policy; controllers/Filament resources/API controllers call
  `$this->authorize()` — never inline role checks scattered through
  controllers.
- **Defense in depth against IDOR**: route-model binding + Policy check on
  every show/update/delete, *and* a global vendor-scope Eloquent scope (see
  [01-system-architecture.md](01-system-architecture.md) §4) so even a
  forgotten Policy call fails closed rather than open.
- **Mass assignment**: every model declares an explicit `$fillable` (never
  `$guarded = []`); Form Requests define the exact validated field set that
  reaches the Service layer — request input never flows into `::create()`
  unfiltered.
- **Privilege escalation guard**: role/permission assignment endpoints
  require `roles.manage`; a non-super-admin can never grant a permission
  they do not themselves hold (checked explicitly, not just gated by a
  generic "manage roles" permission).

## 3. API Security

- Sanctum abilities scoped per token (`customer:*`, `vendor:*`); a stolen
  customer token cannot call vendor-prefixed routes even if the same user ID
  existed on both guards (they don't — separate user rows/guards).
- Rate limiting tiers: auth endpoints (5/min), write endpoints (60/min),
  read/catalog endpoints (300/min), all Redis-backed and IP+user keyed.
- CORS locked to known web/mobile origins; no wildcard `*` with credentials.
- Every list/detail API Resource explicitly whitelists output fields —
  internal fields (cost price, vendor margin, other customers' data) are
  never accidentally serialized via `Model::toArray()` fallthrough.

## 4. Payment Security (see also [09-lifecycles.md](09-lifecycles.md) §2)

- Transaction initialization and verification happen **server-side only**;
  a frontend "payment succeeded" event is never sufficient to mark an order
  paid.
- Webhook signature verification is mandatory per gateway (HMAC secret
  comparison using `hash_equals`), invalid signatures rejected with 401 and
  logged.
- Webhook idempotency via `webhook_events (gateway, event_id)` unique
  constraint — replayed/duplicated webhooks produce zero additional
  side effects.
- Gateway secret keys (`payment_gateways.secret_key`, `webhook_secret`) are
  stored using Laravel's encrypted casts (`AES-256-CBC` via `APP_KEY`),
  never returned by any API/Filament read (write-only fields, masked on
  display).
- Amounts are always recomputed server-side from the order/cart, never
  accepted from client input on the payment-initialize call — this is the
  mitigation for price manipulation.

## 5. File Upload Security

- MIME-type verification via actual content inspection (`finfo`), not just
  file extension.
- Extension allow-list per upload context (images: jpg/png/webp; documents:
  pdf; no executable/script extensions ever accepted).
- Filenames are re-generated server-side (UUID + safe extension) — the
  original filename is stored as metadata only, never used as the storage
  path (path traversal mitigation).
- Vendor documents / private files stored in a non-public disk; served via
  signed, time-limited URLs (`Storage::temporaryUrl()`), never a public path.
- Uploaded images pass through a re-encode step (via the image-processing
  library) before storage, stripping embedded scripts/EXIF payloads.
- Digital product downloads are gated by entitlement checks
  (`product_downloads`) + signed URLs with expiry and download-count limits.

## 6. Application-layer Hardening

- **SQL injection**: exclusively parameterized queries via Eloquent/query
  builder; raw SQL (rare — complex reports) always uses parameter binding,
  never string interpolation.
- **XSS**: Blade's `{{ }}` auto-escaping by default; any `{!! !!}` usage
  (CMS/blog rich text) passes through a server-side HTML sanitizer
  (allow-list of tags/attributes) before storage or render.
- **CSRF**: Laravel's CSRF middleware on all session-based (web/admin/vendor)
  state-changing routes; API routes use Sanctum tokens instead (no cookies
  ⇒ no CSRF surface) with `SameSite=Lax/Strict` cookies where sessions are
  used at all.
- **Security headers**: CSP, `X-Frame-Options: DENY` (or `SAMEORIGIN` where
  iframes are needed), `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: strict-origin-when-cross-origin`, HSTS in production.
- **Session security**: `session.secure = true` + `http_only = true` +
  `same_site = lax` in production, session ID regenerated on login/privilege
  change (session fixation mitigation).
- **Coupon/inventory race conditions**: coupon usage counters and stock
  reservations are updated inside row-locking transactions
  (`lockForUpdate()`), never read-then-write without a lock.
- **Rich audit trail**: `audit_logs` records every sensitive admin action
  (role change, commission-rule change, manual wallet adjustment, refund
  override, settings change) with actor, before/after diff, IP, timestamp —
  immutable, never editable/deletable through the app.

## 7. Data Protection & Compliance

- PII fields (national ID numbers, bank account numbers) encrypted at rest
  via encrypted Eloquent casts.
- Customer "export my data" / "delete my account" endpoints support basic
  data-portability/right-to-erasure workflows (with financial records
  retained per legal requirement rather than hard-deleted, using
  anonymization instead of row deletion where regulation requires
  retention).
- Environment secrets (`.env`) are never committed; `System Health` page
  displays connectivity status only, never raw secret values.

## 8. Threat Coverage Checklist (validated in Phase 24)

SQL injection · XSS · CSRF · IDOR · broken access control · file-upload
attacks · path traversal · mass assignment · brute-force / credential
stuffing · session hijacking/fixation · payment manipulation · price
manipulation · coupon abuse · inventory race conditions · duplicate payment
callbacks · malicious/replayed webhooks · API abuse (rate limiting) ·
sensitive-data exposure.
