# 04 — Model List & Migration Plan

## 1. Eloquent Model List (grouped by domain)

**Identity & Access**: `User`, `UserProfile`, `Role`, `Permission`, `TwoFactorCredential`,
`LoginHistory`, `DeviceSession`, `SocialAccount`

**Vendor & Store**: `Vendor`, `VendorApplication`, `VendorDocument`,
`VendorSubscriptionPlan`, `VendorSubscription`, `Store`, `StoreStaff`,
`StoreSetting`, `StoreFollow`

**Customer**: `CustomerProfile`, `Address`, `Wishlist`, `WishlistItem`,
`CompareList`, `CompareListItem`, `RecentlyViewedProduct`

**Catalog**: `Category`, `Brand`, `Attribute`, `AttributeValue`, `AttributeGroup`,
`Product`, `ProductVariation`, `ProductImage`, `ProductVideo`, `ProductDocument`,
`ProductTag`, `Collection`, `ProductDigitalFile`, `ProductDownload`, `RelatedProduct`

**Inventory**: `Warehouse`, `WarehouseStock`, `StockMovement`, `StockTransfer`,
`StockTransferItem`

**Cart & Checkout**: `Cart`, `CartItem`, `CartCoupon`, `CheckoutSession`

**Orders**: `Order`, `VendorOrder`, `OrderItem`, `OrderStatusHistory`, `OrderNote`,
`Invoice`, `PackingSlip`

**Payments**: `PaymentGateway`, `Payment`, `PaymentLog`, `WebhookEvent`

**Finance**: `CommissionRule`, `OrderItemCommission`, `VendorWallet`,
`VendorWalletTransaction`, `WithdrawalMethod`, `Withdrawal`, `CustomerWallet`,
`CustomerWalletTransaction`, `GiftCard`, `GiftCardRedemption`, `RewardPointLedger`

**Shipping**: `ShippingZone`, `ShippingZoneLocation`, `ShippingClass`,
`ShippingRate`, `ShippingCarrier`, `PickupStation`, `Shipment`, `ShipmentEvent`,
`DeliveryAssignment`, `DeliveryEvidence`

**Tax**: `TaxClass`, `TaxRate`, `OrderItemTaxSnapshot`

**Promotions**: `Coupon`, `CouponUsage`, `FlashSale`, `FlashSaleProduct`,
`DiscountRule`

**Reviews & Engagement**: `ProductReview`, `ReviewImage`, `VendorReview`,
`DeliveryReview`, `ProductQuestion`, `ProductAnswer`, `ReviewVote`, `Report`

**Returns/Refunds/Disputes**: `ReturnRequest`, `ReturnRequestItem`,
`ReturnEvidence`, `Refund`, `Dispute`, `DisputeMessage`, `DisputeEvidence`

**CMS & Marketing**: `Page`, `PageRevision`, `BlogPost`, `BlogCategory`, `BlogTag`,
`BlogComment`, `Menu`, `MenuItem`, `HomepageSection`, `Redirect`,
`NewsletterSubscriber`, `NewsletterCampaign`, `AbandonedCart`

**Communication**: `EmailTemplate`, `NotificationLog`, `SmsLog`, `PushLog`,
`DeviceToken`

**Optional/Advanced**: `Referral`, `ReferralCode`, `Affiliate`, `AffiliateClick`,
`AffiliateConversion`, `AffiliatePayout`, `SupportTicket`,
`SupportTicketMessage`, `SupportTicketAttachment`

**Platform Ops**: `ImportJob`, `ImportJobError`, `ExportJob`, `Backup`

**Cross-cutting**: `Country`, `State`, `City`, `Setting`, `Language`, `Currency`,
`ExchangeRate`, `ActivityLog`, `AuditLog`

~140 models total. Each ships with: relationships, casts (money → integer,
status → enum), scopes (`vendor()`, `published()`, `active()`), and a matching
`Factory` for testing/seeding.

## 2. Shared Enums / Status Classes (PHP 8.1 backed enums)

```
UserType, VendorStatus, StoreStatus, ProductType, ProductStatus,
ApprovalStatus, StockStatus, CartStatus, OrderStatus, VendorOrderStatus,
PaymentStatus, PaymentMethodType, WithdrawalStatus, ReturnStatus,
RefundMethod, DisputeStatus, ShipmentStatus, DeliveryAssignmentStatus,
CommissionType, DiscountType, CouponType, NotificationChannel,
SubscriptionPlanStatus, ImportJobStatus, ExportJobStatus, BackupStatus
```

Each enum implements `Filament\Support\Contracts\HasLabel` and `HasColor` so
Filament badges and API Resources render identically from one definition.

## 3. Migration Plan & Ordering

Migrations run in dependency order, grouped into batches so `php artisan migrate`
never hits an undefined-FK error and `migrate:rollback` is safe batch-by-batch:

1. **Batch 1 — Platform primitives**: `countries`, `states`, `cities`, `languages`,
   `currencies`, `exchange_rates`, `settings`, `media`
2. **Batch 2 — Identity**: `users`, `user_profiles`, permission package tables
   (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`,
   `role_has_permissions`), `personal_access_tokens`, `two_factor_credentials`,
   `login_histories`, `device_sessions`, `social_accounts`
3. **Batch 3 — Vendor & Store**: `vendor_subscription_plans`, `vendors`,
   `vendor_applications`, `vendor_documents`, `vendor_subscriptions`, `stores`,
   `store_staff`, `store_settings`, `store_follows`
4. **Batch 4 — Customer**: `customer_profiles`, `addresses`, `wishlists`,
   `wishlist_items`, `compare_lists`, `compare_list_items`,
   `recently_viewed_products`
5. **Batch 5 — Catalog core**: `categories`, `brands`, `attribute_groups`,
   `attributes`, `attribute_values`, `collections`, `product_tags`
6. **Batch 6 — Products**: `products`, `product_categories`, `product_variations`,
   `product_variation_attribute_value`, `product_images`, `product_videos`,
   `product_documents`, `product_digital_files`, `product_downloads`,
   `collection_products`, `product_tag_pivot`, `related_products`
7. **Batch 7 — Inventory**: `warehouses`, `warehouse_stocks`, `stock_movements`,
   `stock_transfers`, `stock_transfer_items`
8. **Batch 8 — Tax & Shipping config**: `tax_classes`, `tax_rates`,
   `shipping_zones`, `shipping_zone_locations`, `shipping_classes`,
   `shipping_rates`, `shipping_carriers`, `pickup_stations`
9. **Batch 9 — Cart**: `carts`, `cart_items`, `cart_coupons`, `checkout_sessions`
10. **Batch 10 — Promotions**: `coupons`, `coupon_usages`, `flash_sales`,
    `flash_sale_products`, `discount_rules`
11. **Batch 11 — Orders**: `orders`, `vendor_orders`, `order_items`,
    `order_item_tax_snapshots`, `order_status_histories`, `order_notes`,
    `invoices`, `packing_slips`
12. **Batch 12 — Payments**: `payment_gateways`, `payments`, `payment_logs`,
    `webhook_events`
13. **Batch 13 — Finance**: `commission_rules`, `order_item_commissions`,
    `vendor_wallets`, `vendor_wallet_transactions`, `withdrawal_methods`,
    `withdrawals`, `customer_wallets`, `customer_wallet_transactions`,
    `gift_cards`, `gift_card_redemptions`, `reward_point_ledgers`
14. **Batch 14 — Shipping fulfilment**: `shipments`, `shipment_events`,
    `delivery_assignments`, `delivery_evidence`
15. **Batch 15 — Returns/Refunds/Disputes**: `return_requests`,
    `return_request_items`, `return_evidence`, `refunds`, `disputes`,
    `dispute_messages`, `dispute_evidence`
16. **Batch 16 — Engagement**: `product_reviews`, `review_images`,
    `vendor_reviews`, `delivery_reviews`, `product_questions`,
    `product_answers`, `review_votes`, `reports`
17. **Batch 17 — CMS**: `pages`, `page_revisions`, `blog_posts`,
    `blog_categories`, `blog_tags`, `blog_comments`, `menus`, `menu_items`,
    `homepage_sections`, `redirects`
18. **Batch 18 — Marketing/Comms**: `newsletter_subscribers`,
    `newsletter_campaigns`, `abandoned_carts`, `email_templates`,
    `notification_logs`, `sms_logs`, `push_logs`, `device_tokens`
19. **Batch 19 — Optional modules**: `referrals`, `referral_codes`,
    `affiliates`, `affiliate_clicks`, `affiliate_conversions`,
    `affiliate_payouts`, `support_tickets`, `support_ticket_messages`,
    `support_ticket_attachments`
20. **Batch 20 — Platform ops & audit**: `activity_logs`, `audit_logs`,
    `import_jobs`, `import_job_errors`, `export_jobs`, `backups`

Batches 1–8 are prerequisites for Phase 2. Batches 9–11 land in Phases 10–12.
Batches 12–13 land in Phases 13–14. Remaining batches land in their matching
phase (see [13-development-roadmap.md](13-development-roadmap.md)).

## 4. Factories & Seeders

Every model in batches 1–8 gets a `Factory` in Phase 2 so later phases (cart,
checkout, orders) can be developed and tested against realistic seeded data
without waiting on real vendor/admin data entry. Baseline seeders:
`RolePermissionSeeder`, `CountryStateCitySeeder`, `CurrencySeeder`,
`LanguageSeeder`, `SettingsSeeder`, `DemoVendorSeeder`, `DemoProductSeeder`
(dev/staging only — never run in production).
