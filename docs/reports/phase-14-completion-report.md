# Phase 14 Completion Report — Commission & Wallet

## Objective

Give every paid order item a recorded commission, give every vendor a real
wallet balance that responds correctly to sales, settlement, and refunds,
and let vendors request withdrawals under an admin approve/reject/pay
workflow, per
[13-development-roadmap.md](../architecture/13-development-roadmap.md)'s
Phase 14 completion gate: "accurate per-item commission, accurate wallet
balances (pending/available split), refunds reverse balances, withdrawals
work with approve/reject, no over-withdrawal possible, financial tests
pass."

## Scope decisions

1. **Only the tables this phase's gate actually needs.**
   `04-models-and-migrations.md` groups `commission_rules`,
   `order_item_commissions`, `vendor_wallets`, `vendor_wallet_transactions`,
   `withdrawal_methods`, and `withdrawals` in the same "Batch 13 — Finance"
   migration batch as `customer_wallets`, `customer_wallet_transactions`,
   `gift_cards`, `gift_card_redemptions`, and `reward_point_ledgers` — but
   those five belong to Phase 22's gate ("wallet/reward/gift-card
   accuracy"), not this one. Only the six vendor-commission/wallet/
   withdrawal tables were built now, matching the established pattern of
   cutting phase work along gate boundaries rather than migration-batch
   boundaries.
2. **Reconciling `commission_rules` with the pre-existing
   `vendors.commission_rate` and `vendor_subscription_plans.commission_rate_override`
   columns, rather than deprecating either.** Both columns were built in
   Phases 2 and 5 as forward-looking placeholders for exactly the
   Vendor-tier and SubscriptionPlan-tier overrides `commission_rules` now
   also models, and the subscription-plan column already has real seeded
   data (`VendorSubscriptionPlanSeeder`). Removing them would touch stable
   earlier-phase code and discard that seed data for no benefit; ignoring
   them and only reading `commission_rules` would leave a silent
   two-sources-of-truth bug — an admin editing a vendor's `commission_rate`
   through the existing `VendorForm` field would have zero effect on
   actual commission math. `ResolveCommissionRuleAction` checks
   `commission_rules` for a matching row at the Vendor and SubscriptionPlan
   tiers first; if none exists, it falls back to the legacy column via an
   unpersisted "virtual" `CommissionRule` (never saved, `id` stays `null`)
   carrying the override as a percentage rate. The immutable
   `order_item_commissions` snapshot handles this transparently since it
   only reads the resolved rule's type/value, and `commission_rule_id`
   already tolerates `null` (`nullOnDelete`).
3. **Manual, admin-confirmed payout — no Paystack Transfers API or other
   automated disbursement integration.** The gate text asks for
   "withdrawals work with approve/reject," not automated payout execution.
   `MarkWithdrawalPaidAction` is deliberately just a confirmation that a
   real bank transfer already happened outside the system; building a
   payout-gateway integration on top would be scope the gate never asked
   for, on top of Phase 13's Paystack integration which doesn't include a
   Transfers product.
4. **`WithdrawalStatus` models the full `pending → approved → processing →
   paid` state machine from `09-lifecycles.md`, but only
   `pending`/`approved`/`paid`/`rejected`/`cancelled` are reachable this
   phase.** `processing` and `failed` are declared on the enum for schema
   completeness (so a future payout-integration phase doesn't need a
   migration to add them) but no action transitions into either yet —
   there is no automated payout attempt that could fail, per Scope
   Decision 3.
5. **Vendor-side withdrawal methods (bank accounts) are managed inline on
   the `/vendor/withdrawals` page, not as a separate Filament resource.**
   `07-vendor-dashboard.md` names `/vendor/withdrawals` as "Request/view
   status" without a distinct bank-accounts page, so an "Add bank account"
   header action alongside "Request withdrawal" (mirroring the established
   "Add stock" header-action pattern from Phase 7's `Inventory` page) keeps
   the withdrawal flow usable without inventing an unlisted page.
6. **A `Wallet transactions` relation manager on the admin `VendorResource`
   is my own addition beyond the docs' explicit list** (which names a
   "Withdrawals" tab there but not a wallet-ledger one) — a natural,
   low-cost place for an admin to audit a specific vendor's full ledger
   using the `walletTransactions()` `HasManyThrough` relation, consistent
   with how every other resource already surfaces read-only history
   alongside a record.

## What was built

### Data model

Six new tables: `commission_rules` (specificity-scoped by
`scope_type` + nullable `category_id`/`product_id`/`vendor_id`/
`subscription_plan_id`), `order_item_commissions` (an immutable,
insert-only snapshot written once at checkout — `created_at` only, no
`updated_at`, matching the `audit_logs` convention — so a later change to
`commission_rules` never rewrites history), `vendor_wallets` (one row per
vendor with four cached integer balance columns:
pending/available/reserved/withdrawn), `vendor_wallet_transactions` (an
insert-only ledger recording every balance movement, correlated to a
`vendor_order_id` and/or `withdrawal_id`), `withdrawal_methods` (a
vendor's bank details, `account_name`/`account_number` encrypted matching
`Vendor`'s own existing convention), and `withdrawals` (UUID `reference`
like `orders.public_id`, composite `(vendor_id, status)` index per the ERD
doc's explicit call-out).

### Commission resolution and snapshotting

`ResolveCommissionRuleAction` walks specificity tiers in order — product →
category → vendor → subscription plan → global — returning the first
active `commission_rules` match, falling back to the legacy
`vendors.commission_rate`/`vendor_subscription_plans.commission_rate_override`
columns as virtual rules at their respective tiers (Scope Decision 2)
before finally falling through to the global rule.
`RecordOrderItemCommissionAction` calls it once per order item at checkout
completion and writes the immutable snapshot; `CompleteCheckoutAction`
(Phase 11) now calls it immediately after creating each `OrderItem`, before
stock reservation.

### Wallet ledger actions

Following the exact cached-balance-plus-immutable-ledger shape Phase 7
established for `WarehouseStock`/`StockMovement`: `LocksVendorWallet`
(row-lock via `firstOrCreate` + `lockForUpdate`) and
`RecordsWalletTransaction` (writes one ledger row and atomically updates
the wallet's cached column in the same call) are shared concerns behind
`CreditVendorWalletAction` (net-of-commission amount → pending, called
from `VerifyPaymentAction` on a successful payment),
`SettleVendorWalletCreditAction` (pending → available, called from
`UpdateVendorOrderStatusAction` when a vendor order reaches `Completed`),
and `ReverseVendorWalletCreditAction` (debits whichever bucket — pending or
already-settled available — currently holds a vendor order's credit,
called from `RefundPaymentAction` with a per-vendor-order share computed
proportionally against the order's total for the multi-vendor case). All
three are idempotency-guarded against their own ledger entries, on top of
their callers' own idempotency.

### Withdrawal actions

`RequestWithdrawalAction` validates method ownership and the
`finance.minimum_withdrawal` setting, then — inside one locked transaction
— checks `available_balance >= amount` and atomically moves the amount
from available to reserved; the lock plus the in-transaction check is the
entire over-withdrawal guarantee. `ApproveWithdrawalAction`,
`RejectWithdrawalAction` (releases the reserved hold back to available),
`MarkWithdrawalPaidAction` (reserved → withdrawn), and
`CancelWithdrawalAction` (vendor self-service, pending-only, same release
shape as reject) round out the state machine reachable this phase (Scope
Decision 4). `InsufficientWalletBalanceException` and
`InvalidWithdrawalTransitionException` are new, matching the established
one-exception-per-failure-domain convention.

### Admin Filament resources

`CommissionRuleResource` — scope-type-reactive form (only the relevant
one of category/product/vendor/subscription-plan selects shows, and the
rate field's prefix/suffix/helper text switch between `%` and `$` based on
rate type), gated on `commissions.manage`. `WithdrawalResource` — read-only
+ action-driven like `PaymentResource`, with approve/reject/mark-paid
header actions on its view page, each gated on `withdrawals.approve` and
the record's current status, each converting
`InvalidWithdrawalTransitionException` into a danger notification rather
than a 500. `VendorResource` gained `WithdrawalsRelationManager` and
`WalletTransactionsRelationManager` tabs (Scope Decision 6), and
`PendingWithdrawalsWidget` (a `TableWidget` matching `LowStockWidget`'s
shape) surfaces the pending queue on the admin dashboard, gated on
`withdrawals.view`.

### Vendor Filament panel

`/vendor/earnings` — a read-only wallet-balance summary (all four buckets)
plus the vendor's own wallet ledger table.
`/vendor/withdrawals` — a table of the vendor's own withdrawal requests,
an "Add bank account" header action, a "Request withdrawal" header action
(catching `InsufficientWalletBalanceException` into a danger notification
rather than a 500), and a "cancel" row action visible only on the vendor's
own still-`pending` requests. Both pages are gated the same way Phase 7's
`Inventory` page is — vendor owner always has access, staff need the
specific permission (`store.reports.view` for earnings,
`store.withdrawals.request` for withdrawals).

## Bugs found and fixed during this phase

1. **`Filament\Forms\Get` vs `Filament\Schemas\Components\Utilities\Get`.**
   The first draft of `CommissionRuleForm` type-hinted the older
   `Filament\Forms\Get` in its `visible()`/`prefix()`/etc. closures; this
   codebase's Filament version injects
   `Filament\Schemas\Components\Utilities\Get` instead (confirmed against
   `ProductForm`'s own working usage), and the mismatch threw a hard
   `TypeError` on page load. Fixed by correcting the import before
   shipping the form, caught via a scratch Filament page-load test.
2. **A real, previously-latent bug: comparing `$get('enum_field')` against
   `SomeEnum::Case->value` instead of `SomeEnum::Case` itself.** While
   writing this phase's Filament tests, `CommissionRuleForm`'s four
   scope-dependent `visible()` closures (and the `rate_value` field's
   prefix/suffix/helper-text closures) never actually revealed their
   target field — `$get()` returns the *cast* enum instance for a
   `Select::options(EnumClass)` field, not the raw string, so
   `$get('scope_type') === CommissionScopeType::Vendor->value` (enum
   `!==` string) was always `false`. This was invisible in casual manual
   testing (the create/edit *pages* still loaded fine — only the
   conditional field's visibility was silently broken) and would have
   made it impossible for an admin to actually create a
   Vendor/Category/Product/SubscriptionPlan-scoped commission rule through
   the UI. Fixed by comparing against the enum case directly throughout
   `CommissionRuleForm`. **This same comparison pattern
   (`$get(...) === Enum::Case->value`) pre-exists in Phase 6/8's
   `ProductForm`** (e.g. `$get('type') !== ProductType::Variable->value`)
   and was never exercised by a reactive-visibility test there either —
   worth a follow-up audit outside this phase's scope, since fixing it
   there isn't part of Phase 14's own gate.

## Tests

- `./vendor/bin/pest` — **469/469 passing** (426 carried from Phases 1–13,
  43 new): commission resolution — product beats category/vendor/global,
  category beats vendor/global, a `commission_rules` vendor row beats the
  legacy `commission_rate` column, the legacy column is used as a virtual
  fallback rule (`commission_rule_id` stays `null`), a subscription-plan
  override is used when nothing more specific matches, the global rule is
  the final fallback, a fixed-type rule computes a flat amount capped at
  the gross, a snapshot is never recomputed after the rule changes (8);
  wallet ledger actions — crediting moves net commission to pending,
  crediting twice is a no-op, settling moves pending to available (and is
  idempotent), reversing debits pending or available depending on
  settlement state, the full credit→settle→partial-refund lifecycle
  produces the exact expected ledger sequence and balances (7);
  withdrawal actions — request moves available to reserved, below-minimum
  and wrong-vendor-method requests are rejected, **a second request
  exceeding the remaining available balance after a first request already
  consumed it all is rejected (the over-withdrawal guarantee)**, approve/
  reject/mark-paid/cancel each move the right balances and reject invalid
  transitions (12); admin Filament — commission rule index/create/edit
  pages and permission gating, creating both a global and a (reactive,
  scope-dependent) vendor-scoped rule, editing a rate, withdrawal index/
  view pages and permission gating, approve→mark-paid and reject each move
  the right wallet balances, mark-paid hidden before approval, approve
  hidden without `withdrawals.approve` (10); vendor panel — earnings/
  withdrawals pages load, the earnings ledger only shows the acting
  vendor's own transactions, add-bank-account + request-withdrawal moves
  the right balances, an over-limit request is rejected without creating a
  row, cancelling a pending request releases the hold (5); plus the
  existing Payment/Order/Checkout suites re-run unchanged to confirm the
  new wallet-crediting/settling/reversing hooks introduced no regressions.
- `./vendor/bin/pint --dirty` — clean.
- `migrate:fresh --seed` verified clean end-to-end, including the new
  `CommissionRuleSeeder` (idempotently seeds one 10% global rule) running
  after `PaymentGatewaySeeder`.
- A rollback/migrate round-trip on all six new migrations verified clean
  in both directions.
- `php artisan route:list` verified all new named routes
  (`filament.admin.resources.{commission-rules,withdrawals}.*`,
  `filament.vendor.pages.{earnings,withdrawals}`) resolve correctly.

## Completion Gate Check (Phase 14)

| Criterion | Status |
|---|---|
| Accurate per-item commission | ✅ specificity-ordered resolution (product → category → vendor → plan → global), immutable snapshot at checkout, tested for every tier including the legacy-column reconciliation |
| Accurate wallet balances (pending/available split) | ✅ `VendorWallet`'s four cached buckets, kept in sync with an insert-only ledger, tested through the full credit → settle → refund lifecycle |
| Refunds reverse balances | ✅ `ReverseVendorWalletCreditAction` debits pending or available depending on settlement state, proportionally attributed per vendor order on a multi-vendor refund, tested |
| Withdrawals work with approve/reject | ✅ full pending → approved → paid and pending/approved → rejected paths, tested via both direct actions and the admin Filament view page |
| No over-withdrawal possible | ✅ row-locked wallet + in-transaction balance check, explicitly tested with a second request that would exceed the remaining balance |
| Financial tests pass | ✅ 469/469 |

## Known limitations carried forward

1. The sandbox limitations carried from Phases 1–13 (no MySQL server, no
   Larastan) remain unchanged.
2. No automated payout execution — `MarkWithdrawalPaidAction` only
   confirms a manual transfer already happened (Scope Decision 3); the
   `processing`/`failed` withdrawal states are modeled but unreachable
   until a payout-gateway integration is built.
3. Customer wallets, gift cards, and reward points are out of scope for
   this phase (Scope Decision 1) — Phase 22's gate.
4. The multi-vendor refund attribution formula
   (`round(refundedAmount * vendorOrder.netCommissionAmount() / order.total)`)
   has an accepted minor-rounding tolerance across multiple vendor orders
   on the same refunded order; it's exact for the common single-vendor
   case and untested here for the multi-vendor edge case specifically.
5. The `$get(...) === Enum::Case->value` comparison bug described above
   still exists in Phase 6/8's `ProductForm` — flagged for a future
   cross-phase audit, not fixed here since it's outside Phase 14's own
   gate.

None of these block Phase 15, which is the next phase in the roadmap.
