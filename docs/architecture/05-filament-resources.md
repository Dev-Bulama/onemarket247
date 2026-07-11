# 05 — Filament Administration Panel Resource List

Filament panel ID: `admin`, path `/admin`. Uses `spatie/laravel-permission` plugin
integration for navigation visibility gating, RTL-aware theme, light/dark mode.

## 1. Filament Resources (one per primary entity)

| Resource | Relation Managers | Notes |
|---|---|---|
| `AdministratorResource` (`User` scoped to admin/staff) | Roles, LoginHistory | Super admin only for create/delete |
| `RoleResource` | Permissions | |
| `VendorResource` | Documents, Staff, Products, Orders, Withdrawals, Subscription | Approve/Reject/Suspend/Terminate actions |
| `VendorApplicationResource` | Documents | Review queue, bulk approve/reject |
| `VendorSubscriptionPlanResource` | Subscriptions | |
| `StoreResource` | Staff, Settings | |
| `CustomerResource` (`User` scoped to customer) | Addresses, Orders, Wishlist, Reviews | |
| `CustomerGroupResource` | Customers | |
| `ProductResource` | Variations, Images, Reviews, Questions | Approval action, bulk publish/unpublish |
| `ProductApprovalResource` (custom filtered view) | — | Pending-approval queue |
| `CategoryResource` | Products, Children | Tree/nested UI |
| `BrandResource` | Products | |
| `AttributeResource` | Values | |
| `CollectionResource` | Products | |
| `WarehouseResource` | Stocks, Transfers | |
| `StockTransferResource` | Items | |
| `OrderResource` (parent) | VendorOrders, Payments, Notes, StatusHistory | Read-mostly; status overridable with audit |
| `VendorOrderResource` | Items, Shipments | |
| `PaymentResource` | Logs | |
| `PaymentGatewayResource` | — | Encrypted secret fields |
| `RefundResource` | — | |
| `ReturnRequestResource` | Items, Evidence | |
| `DisputeResource` | Messages, Evidence | |
| `ShippingZoneResource` | Locations, Rates | |
| `ShippingClassResource` | Rates | |
| `ShippingCarrierResource` | — | |
| `PickupStationResource` | — | |
| `TaxClassResource` | Rates | |
| `TaxRateResource` | — | |
| `CommissionRuleResource` | — | Scope selector (global/category/product/vendor/plan) |
| `WithdrawalResource` | — | Approve/Reject/Mark-Paid actions |
| `CustomerWalletResource` | Transactions | Manual adjustment action |
| `CouponResource` | Usages | |
| `FlashSaleResource` | Products | |
| `GiftCardResource` | Redemptions | |
| `ReviewResource` (product/vendor/delivery) | — | Moderation actions |
| `ProductQuestionResource` | Answers | |
| `PageResource` | Revisions | |
| `BlogPostResource` | Comments | |
| `BlogCategoryResource` / `BlogTagResource` | — | |
| `MenuResource` | Items (drag-reorder) | |
| `RedirectResource` | — | |
| `NewsletterSubscriberResource` / `NewsletterCampaignResource` | — | |
| `EmailTemplateResource` | — | Live preview |
| `LanguageResource` | — | |
| `CurrencyResource` | Exchange Rates | |
| `SupportTicketResource` | Messages, Attachments | SLA badges |
| `AffiliateResource` | Clicks, Conversions, Payouts | |
| `ReferralResource` | — | |
| `ImportJobResource` / `ExportJobResource` | Errors | Progress bar widget |
| `BackupResource` | — | Download/Delete actions |
| `AuditLogResource` | — | Read-only |
| `ActivityLogResource` | — | Read-only |

## 2. Custom Pages (non-resource)

- `Dashboard` (default, with widgets below)
- `HomepageBuilderPage` — drag-reorder homepage sections
- `GeneralSettingsPage`
- `SecuritySettingsPage`
- `SmtpSettingsPage` (with "Send test email" action)
- `SeoSettingsPage`
- `SitemapStatusPage`
- `SystemHealthPage` (read-only diagnostics, no secrets)
- `ReportsPage` (Sales / Commission / Tax / Inventory / Vendor performance tabs)
- `TranslationManagerPage` (missing-translation report, import/export)

## 3. Dashboard Widgets

`StatsOverviewWidget` (GMV, orders, active vendors, pending approvals),
`SalesTrendChart`, `TopVendorsWidget`, `TopProductsWidget`,
`PendingVendorApplicationsWidget`, `PendingProductApprovalsWidget`,
`PendingWithdrawalsWidget`, `RecentOrdersWidget`, `LowStockWidget`,
`FailedJobsWidget`, `SystemHealthWidget`.

## 4. Cross-cutting Filament Conventions

- Every resource's navigation item is wrapped in a `canViewAny()` check tied to
  the permission matrix in [03-modules-and-roles.md](03-modules-and-roles.md) —
  a resource with no visible action for the current admin does not appear at all
  (no empty resources).
- Tables: search, column filters (status/date range/vendor), bulk actions
  (bulk approve, bulk export, bulk soft-delete/restore), sortable columns,
  status badge columns backed by the shared enums from
  [04-models-and-migrations.md](04-models-and-migrations.md).
- Forms: Form Request-equivalent validation rules are shared with the API via a
  common `Rules::` class per entity so admin and API validation never drift.
  Every "financial" or multi-step form action (approve vendor, approve
  withdrawal, process refund) is wrapped in `DB::transaction()` inside the
  Filament Action's `action()` closure.
- RTL and dark mode are enabled panel-wide via the Filament theme config;
  colors/typography defined once and shared with the customer/vendor Blade
  layouts via a common Tailwind design-token file.
