# 02 — Database Entity-Relationship Plan

MySQL 8+. All tables: `id` (bigint, PK) unless UUID is called out, `created_at`,
`updated_at`, and `deleted_at` (soft deletes) where noted. Money columns are integer
minor units (`*_minor` suffix conceptually, referred to here as e.g. `price`).

## 1. Table List by Domain

### Identity & Access
- `users` (polymorphic base: super admin, admin, staff, vendor owner, vendor staff, customer, delivery, affiliate — differentiated by `user_type` + guard tables below)
- `user_profiles`
- `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions` (Spatie permission package tables)
- `personal_access_tokens` (Sanctum)
- `two_factor_credentials`
- `login_histories`
- `device_sessions`
- `password_reset_tokens`
- `email_verification_tokens` (or Laravel's native verified_at column)
- `social_accounts` (google/apple/facebook provider links)

### Geography & Settings
- `countries`, `states`, `cities`
- `settings` (key/value + typed casting, group-scoped)
- `languages`
- `currencies`, `exchange_rates`
- `media` (Spatie Media Library table)
- `activity_logs` (Spatie Activitylog)
- `audit_logs` (financial/admin-sensitive actions, immutable)

### Vendor & Store
- `vendors`
- `vendor_applications`
- `vendor_documents`
- `vendor_subscription_plans`
- `vendor_subscriptions`
- `stores`
- `store_staff`
- `store_settings` (policies, working hours, vacation mode)
- `store_follows` (customer follows store)

### Customer
- `customer_profiles`
- `addresses` (polymorphic: customer/vendor/store)
- `wishlists`, `wishlist_items`
- `compare_lists`, `compare_list_items`
- `recently_viewed_products`

### Catalog
- `categories` (nested set or adjacency list w/ `parent_id` + `path`)
- `brands`
- `attributes`, `attribute_values`, `attribute_groups`
- `products`
- `product_translations`
- `product_categories` (pivot, primary + secondary categories)
- `product_variations`
- `product_variation_attribute_value` (pivot)
- `product_images`, `product_videos`, `product_documents`
- `product_tags`, `product_tag_pivot`
- `collections`, `collection_products`
- `product_digital_files`
- `product_downloads` (entitlement + download-count/expiry tracking)
- `related_products` (self-referential pivot: related/upsell/cross-sell/fbt, `relation_type`)

### Inventory
- `warehouses`
- `warehouse_stocks` (product/variation × warehouse: on_hand, reserved, damaged, incoming)
- `stock_movements` (immutable ledger: reason, quantity_delta, reference)
- `stock_transfers`, `stock_transfer_items`

### Cart & Checkout
- `carts` (guest via session/device token, or `customer_id`)
- `cart_items`
- `cart_coupons`
- `checkout_sessions` (idempotency key, snapshot of totals, expires_at)

### Orders
- `orders` (parent marketplace order)
- `vendor_orders` (per-vendor sub-order, FK to `orders` + `vendor_id`)
- `order_items` (FK to `vendor_orders`, not directly to `orders`)
- `order_status_histories` (polymorphic: order or vendor_order)
- `order_notes` (customer/vendor/internal, visibility flag)
- `invoices`, `packing_slips`

### Payments
- `payment_gateways` (config per gateway, encrypted secrets)
- `payments` (FK to `orders`, gateway reference, status, idempotency key)
- `payment_logs` (raw request/response/webhook audit trail)
- `webhook_events` (dedupe table: `gateway`, `event_id` unique, `processed_at`)

### Finance
- `commission_rules` (global/category/product/vendor/plan scoped)
- `order_item_commissions` (immutable snapshot per `order_item`)
- `vendor_wallets`
- `vendor_wallet_transactions` (immutable ledger)
- `withdrawal_methods`
- `withdrawals`
- `customer_wallets`, `customer_wallet_transactions`
- `gift_cards`, `gift_card_redemptions`
- `reward_point_ledgers`

### Shipping & Delivery
- `shipping_zones`, `shipping_zone_locations`
- `shipping_classes`
- `shipping_rates` (zone × class × method)
- `shipping_carriers`
- `pickup_stations`
- `shipments` (FK `vendor_order_id`, carrier, tracking_number)
- `shipment_events` (tracking timeline)
- `delivery_assignments` (delivery personnel)
- `delivery_evidence` (signature/photo)

### Tax
- `tax_classes`
- `tax_rates` (country/state/city/postal scoped)
- `order_item_tax_snapshots`

### Promotions
- `coupons`
- `coupon_usages`
- `flash_sales`, `flash_sale_products`
- `discount_rules` (automatic discounts, tiered pricing, BOGO)

### Reviews & Engagement
- `product_reviews`, `review_images`
- `vendor_reviews`
- `delivery_reviews`
- `product_questions`, `product_answers`
- `review_votes` (helpful voting, polymorphic)
- `reports` (polymorphic content reporting/moderation)

### Returns, Refunds, Disputes
- `return_requests`, `return_request_items`
- `return_evidence` (images/video)
- `refunds` (FK payment, amount, method)
- `disputes`, `dispute_messages`, `dispute_evidence`

### CMS & Marketing
- `pages`, `page_revisions`
- `blog_posts`, `blog_categories`, `blog_tags`, `blog_comments`
- `menus`, `menu_items`
- `homepage_sections` (orderable, typed, config JSON)
- `redirects`
- `newsletter_subscribers`, `newsletter_campaigns`
- `abandoned_carts` (materialized from `carts` + reminder schedule)

### Communication
- `email_templates`
- `notification_logs`
- `notifications` (Laravel's native table)
- `sms_logs`, `push_logs`
- `device_tokens` (FCM/APNs registration, polymorphic owner)

### Optional/Advanced
- `referrals`, `referral_codes`
- `affiliates`, `affiliate_clicks`, `affiliate_conversions`, `affiliate_payouts`
- `support_tickets`, `support_ticket_messages`, `support_ticket_attachments`

### Platform Operations
- `import_jobs`, `import_job_errors`
- `export_jobs`
- `backups`
- `failed_jobs`, `jobs`, `job_batches` (Laravel queue tables)

## 2. Key Relationships

```
users 1───1 vendors (vendor owner)
vendors 1───1 stores
vendors 1───N vendor_documents
vendors 1───N vendor_subscriptions N───1 vendor_subscription_plans
stores 1───N store_staff N───1 users

vendors 1───N products
products 1───N product_variations
products N───N categories (via product_categories, one primary)
products N───1 brands
product_variations N───N attribute_values (via pivot)

products 1───N warehouse_stocks (product or variation level)
warehouses 1───N warehouse_stocks

customers(users) 1───N carts
carts 1───N cart_items N───1 product_variations

orders 1───N vendor_orders N───1 vendors
vendor_orders 1───N order_items N───1 product_variations
orders 1───N payments
order_items 1───1 order_item_commissions
vendor_orders 1───N shipments
vendor_orders 1───N order_status_histories

vendors 1───1 vendor_wallets
vendor_wallets 1───N vendor_wallet_transactions
vendors 1───N withdrawals

order_items 1───N return_request_items N───1 return_requests
return_requests 1───N refunds
orders/vendor_orders 1───N disputes

products 1───N product_reviews N───1 customers(users)
products 1───N product_questions 1───N product_answers
```

## 3. Constraints, Indexing & Data-Integrity Rules

- **Foreign keys**: `ON DELETE RESTRICT` for financial/order-linked records (never
  cascade-delete an order, payment, or wallet transaction). `ON DELETE CASCADE` only
  for true child/detail rows scoped to a soft-deletable parent (e.g.
  `product_images` → `products`), and even then the parent uses soft deletes so hard
  cascade rarely fires.
- **Unique constraints**: `products.slug` (per-store or global depending on
  `settings.slug_scope`), `product_variations.sku`, `orders.order_number`,
  `vendor_orders.vendor_order_number`, `payments.gateway_reference` +
  `payment_gateways.code`, `webhook_events.(gateway, event_id)`, `coupons.code`,
  `stores.slug`, `categories.slug`, `users.email`.
- **Composite indexes**: `(vendor_id, status)` on `products`, `vendor_orders`,
  `withdrawals`; `(product_id, warehouse_id)` on `warehouse_stocks`;
  `(order_id)` + `(vendor_id)` on `vendor_orders`; `(customer_id, created_at)` on
  `orders`; full-text index on `products.name` + `products.description` for search
  fallback (primary search may later move to a search engine — see
  [12-deployment-roadmap.md](12-deployment-roadmap.md)).
- **Soft deletes**: `products`, `categories`, `brands`, `stores`, `vendors`,
  `customer profiles`, `pages`, `blog_posts` — anything an admin/vendor might restore.
  **Never** soft-delete `orders`, `payments`, `vendor_wallet_transactions`,
  `order_item_commissions`, `audit_logs` — these are immutable/append-only.
- **UUIDs**: used as a *public-facing* secondary identifier
  (`orders.public_id`, `payments.reference`) to avoid enumerable sequential IDs in
  URLs/APIs, while internal FKs stay on fast bigint `id` for join performance.
- **Human-readable references**: `order_number` (e.g. `OM-2026-000123`),
  `vendor_order_number` (`OM-2026-000123-V2`), `withdrawal_reference`,
  `return_reference` — generated server-side, never client-supplied.
- **Concurrency-safe stock**: `warehouse_stocks` updates use `SELECT ... FOR UPDATE`
  inside the checkout/order-confirmation transaction (see
  [09-lifecycles.md](09-lifecycles.md) §Order Lifecycle) plus a DB-level
  `CHECK (quantity_available >= 0)` constraint as a last-resort guard against
  overselling.
- **Immutable ledgers**: `vendor_wallet_transactions`, `customer_wallet_transactions`,
  `stock_movements`, `order_item_commissions`, `audit_logs` are insert-only; balance
  columns on `vendor_wallets`/`customer_wallets` are derived/cached and reconciled
  from the ledger, never the other way around.
- **Currency/tax snapshots**: `orders.currency_code`, `orders.exchange_rate_snapshot`,
  `order_item_tax_snapshots` freeze values at purchase time — changing
  `currencies.exchange_rate` or `tax_rates` later never mutates historical orders.
