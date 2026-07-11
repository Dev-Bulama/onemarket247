# 09 — Core Lifecycles (State Machines)

## 1. Order Lifecycle

### Structure: Parent Order / Vendor Sub-Order

A checkout that spans multiple vendors creates **one `orders` row** (the
customer-facing unified order, with its own `order_number`) and **one
`vendor_orders` row per vendor represented in the cart** (each with its own
`vendor_order_number`, e.g. `OM-2026-000123-V1`, `OM-2026-000123-V2`).
`order_items` belong to `vendor_orders`, never directly to `orders`. This is
what structurally guarantees:
- **Customers** query `orders` (eager-loading all child `vendor_orders` +
  `order_items`) and see one unified purchase.
- **Vendors** query `vendor_orders` scoped to `vendor_id` and structurally
  cannot see another vendor's items, pricing, or customer notes meant for a
  different vendor.

### Statuses

**Parent `orders.status`** (derived/aggregated from child sub-order statuses,
plus payment state):
```
pending_payment → payment_processing → paid → confirmed → processing
   → partially_delivered → delivered → completed
   (branches: on_hold, cancelled, failed, returned, partially_returned,
              refunded, partially_refunded, disputed)
```

**Child `vendor_orders.status`** (independent per vendor — one vendor
shipping while another is still processing is expected):
```
pending_payment → confirmed → processing → ready_for_pickup → shipped
   → out_for_delivery → delivered → completed
   (branches: on_hold, cancelled, returned, refunded, disputed)
```

The parent status is computed by a `OrderStatusAggregator` service
(e.g., "delivered" only when *all* vendor_orders are delivered/completed;
"partially_delivered" when some are and some aren't) — it is never hand-set
independently of children, preventing drift.

### Checkout → Order Creation (concurrency-safe, idempotent)

```
1. POST /checkout/complete (Idempotency-Key header)
2. If a checkout_session with this key already resolved to an order → return
   that order (no duplicate creation), satisfying double-click / refreshed
   webhook / repeated callback protection.
3. DB::transaction():
     a. Re-fetch cart, re-validate every item's price + stock
        (SELECT ... FOR UPDATE on warehouse_stocks rows).
     b. Reject if price drifted or stock insufficient (422, cart flagged for
        client-side re-sync — never silently substitute a different price).
     c. Reserve stock (increment `reserved`, this is not yet a deduction).
     d. Create `orders` + one `vendor_orders` per vendor + `order_items`.
     e. Snapshot currency, exchange rate, tax, and commission-rule references
        onto the order/items (see Commission Lifecycle below).
     f. Create `payments` row in `pending` status.
     g. Mark checkout_session resolved → order_id.
4. Commit. Dispatch OrderPlaced event (queued listeners: email, admin
   notification, abandoned-cart cancellation).
5. Redirect/return to payment step (see Payment Lifecycle).
```

### Cancellation / Stock Restoration

- Customer/vendor/admin cancellation before fulfilment → `StockMovement`
  reverses the reservation, `vendor_orders.status = cancelled`, wallet/
  commission entries for that vendor_order are voided (not deleted —
  immutable ledger gets an offsetting entry).
- Approved return → same reversal path via the Return/Refund lifecycle below,
  referencing the original `stock_movements` row for traceability.

## 2. Payment Lifecycle

```
payments.status: pending → processing → paid
                    ↘ failed
                    ↘ cancelled
   paid → refunded / partially_refunded (via Refund records)
```

1. **Initialize** (`POST /payments/{order}/initialize`): server creates the
   gateway transaction server-side (never trusts a client-computed amount),
   persists `payments.gateway_reference`, returns the redirect/SDK payload.
2. **Verify** (`POST /payments/{order}/verify` and/or gateway webhook at
   `POST /webhooks/payments/{gateway}`): server calls the gateway's
   server-to-server verify API — **frontend "success" callbacks are never
   trusted alone**.
3. **Webhook dedupe**: every inbound webhook is first checked against
   `webhook_events (gateway, event_id)` unique constraint inside a
   transaction; a duplicate delivery is acknowledged (200 OK, required by
   gateways) but produces zero additional side effects.
4. **On verified success**: `DB::transaction()` — `payments.status = paid`,
   parent `orders.status` recomputed, stock reservation converted to a hard
   deduction, `OrderPaid` event dispatched (commission recording, vendor
   wallet credit as *pending* balance, emails).
5. **On failure/timeout**: `payments.status = failed`; order stays
   `pending_payment`/`payment_processing` — **never marked paid** — customer
   can retry with a fresh payment attempt (new `payments` row, same order).
6. **Reconciliation job** (scheduled): cross-checks gateway transaction
   listings against local `payments` rows to catch missed webhooks.

## 3. Commission Lifecycle

1. At order-item creation time, the applicable `commission_rules` row is
   resolved by specificity (product > category > vendor > subscription-plan
   > global) and a snapshot is written to `order_item_commissions`
   (`rate_type`, `rate_value`, `computed_amount`) — **immutable**.
2. **Historical commission is never recomputed** when `commission_rules`
   change later; only new orders use the new rule.
3. On `OrderPaid`, the commission amount is what gets *subtracted* when
   crediting the vendor wallet (see Wallet Lifecycle) — gross sale amount
   minus commission minus payment fee minus applicable shipping/tax splits
   (per settings) = vendor's net credit.
4. On refund/cancellation, an offsetting `order_item_commissions` adjustment
   entry (not a mutation of the original row) reverses the commission
   proportionally to the refunded amount.

## 4. Vendor Wallet Lifecycle

```
vendor_wallet_transactions (immutable ledger; balance columns on
vendor_wallets are derived, never authoritative):

  sale_credit (pending)   — on OrderPaid, net-of-commission amount, held as
                             "pending" until the settlement delay elapses
                             or the order reaches "completed"
  → sale_credit (available) — moved from pending to available balance after
                             settlement delay / delivery confirmation
  refund_debit            — on approved refund, reverses the credit
                             (proportional to refunded amount)
  adjustment               — manual admin correction, always with a reason
                             and audit_log entry
  withdrawal_hold          — on withdrawal request, moves amount from
                             available → reserved
  withdrawal_paid           — on withdrawal completion, removes from reserved
  withdrawal_reversed       — on withdrawal rejection, reserved → available
```

Balances (`pending_balance`, `available_balance`, `reserved_balance`,
`withdrawn_balance`) are recalculated from the ledger via a database
transaction on every mutating event — never hand-edited directly — so the
wallet is always reconstructable/auditable from `vendor_wallet_transactions`
alone.

## 5. Withdrawal Lifecycle

```
withdrawals.status:
  pending → approved → processing → paid
     ↘ rejected
     ↘ cancelled (by vendor, only while still pending)
     ↘ failed (payout attempt failed, returns to pending for retry)
```

1. Vendor requests a withdrawal ≤ `available_balance`, ≥
   `settings.minimum_withdrawal`; the requested amount is immediately moved
   to `reserved_balance` (`withdrawal_hold` ledger entry) so a vendor cannot
   request the same funds twice even if they spam the button — this,
   combined with the transaction-wrapped balance check, is what prevents
   over-withdrawal.
2. Admin reviews → **approve** (moves to `approved`/`processing`, triggers
   payout via bank transfer / Paystack transfer / manual) or **reject**
   (funds released back to `available_balance` via `withdrawal_reversed`,
   reason required).
3. On payout confirmation → `paid`, `withdrawal_paid` ledger entry, wallet's
   `withdrawn_balance` increments.
4. Failed payout attempts do not silently disappear — `failed` status keeps
   funds reserved and surfaces the withdrawal for admin retry/manual
   intervention.

## 6. Return & Refund Lifecycle

```
return_requests.status:
  requested → under_review → approved → pickup_scheduled → in_transit
     → received → inspected → refund_processing → refunded / replaced
     → closed
  (branch: rejected at under_review)
```

1. Customer requests a return on specific `order_items` (partial or full),
   with reason, description, and evidence images/video.
2. Vendor (and/or admin, per settings) reviews; on approval, a pickup or
   drop-off flow is scheduled if required by the resolution type.
3. On `inspected` (goods received and verified), the chosen resolution
   (full refund / partial refund / replacement / exchange / wallet credit /
   store credit) drives a `refunds` row referencing the original `payments`
   row.
4. `DB::transaction()` on refund confirmation:
   - `refunds` row created (`amount`, `method`, `status`)
   - Original payment gateway refund API called server-side (for
     original-payment-method refunds) or `customer_wallet_transactions`
     credited (for wallet/store-credit resolutions)
   - `order_item_commissions` adjustment entry (see Commission Lifecycle §4)
   - `vendor_wallet_transactions` `refund_debit` entry
   - `stock_movements` restoration entry, `warehouse_stocks` incremented
   - `vendor_orders`/`orders` status recomputed
     (`refunded`/`partially_refunded`/`returned`/`partially_returned`)
5. Disputes escalate from an unresolved/contested return: `disputes` row
   with statements + evidence from both sides, admin decision recorded with
   `resolution_notes` and, if it changes money movement, its own
   transaction-wrapped ledger adjustment — never a silent balance edit.

## 7. Cross-Lifecycle Invariant

Every financial state transition above (payment confirmation, commission
recording, wallet credit/debit, refund, withdrawal) is wrapped in a single
`DB::transaction()` per operation, writes to an **immutable, append-only
ledger table**, and derives any cached "balance" column from that ledger —
this is the mechanism (not a policy statement) that satisfies the completion
gates for Phases 11–14 and 18 ("duplicate orders/payments impossible",
"refunds update all ledgers", "vendors cannot over-withdraw").
