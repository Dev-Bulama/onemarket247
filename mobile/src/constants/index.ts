// Matches the platform's admin-configurable brand color default (#FF6600,
// see App\Models\MailSetting) — orange primary, green as the marketplace's
// secondary "24/7 · in stock · savings" accent color used across mockups.
export const COLORS = {
  primary: '#FF6600',
  primaryDark: '#E55A00',
  primaryLight: '#FF8533',
  secondary: '#1A1A2E',
  accent: '#16A34A',
  success: '#16A34A',
  warning: '#F39C12',
  danger: '#E74C3C',
  info: '#3498DB',
  star: '#F5A623',
  white: '#FFFFFF',
  black: '#000000',
  gray: '#9E9E9E',
  grayLight: '#F5F5F5',
  grayMedium: '#E0E0E0',
  grayDark: '#616161',
  background: '#F8F8F8',
  surface: '#FFFFFF',
  text: '#1A1A1A',
  textSecondary: '#757575',
  textMuted: '#BDBDBD',
  border: '#EEEEEE',
  divider: '#F0F0F0',
  placeholder: '#BDBDBD',
  overlay: 'rgba(0,0,0,0.5)',
  cardShadow: 'rgba(0,0,0,0.08)',
};

export const SIZES = {
  xs: 4,
  sm: 8,
  md: 12,
  base: 16,
  lg: 20,
  xl: 24,
  xxl: 32,
  xxxl: 40,
  screenPadding: 16,
  borderRadius: 12,
  borderRadiusSm: 8,
  borderRadiusLg: 20,
};

// Extended below (not replaced) to also cover App\Enums\VendorOrderStatus's
// full case list, which is a superset of the customer-facing order statuses
// above it (confirmed/ready_for_pickup/out_for_delivery/on_hold/returned/
// disputed only exist on the vendor side) — see VendorOrdersScreen /
// VendorOrderDetailScreen, which key off the same map.
export const ORDER_STATUSES: Record<string, { label: string; color: string }> = {
  pending_payment: { label: 'Pending Payment', color: '#F39C12' },
  confirmed: { label: 'Confirmed', color: '#3498DB' },
  processing: { label: 'Processing', color: '#3498DB' },
  partially_shipped: { label: 'Partially Shipped', color: '#9B59B6' },
  ready_for_pickup: { label: 'Ready for Pickup', color: '#9B59B6' },
  shipped: { label: 'Shipped', color: '#1ABC9C' },
  out_for_delivery: { label: 'Out for Delivery', color: '#1ABC9C' },
  delivered: { label: 'Delivered', color: '#16A34A' },
  completed: { label: 'Completed', color: '#16A34A' },
  on_hold: { label: 'On Hold', color: '#F39C12' },
  cancelled: { label: 'Cancelled', color: '#E74C3C' },
  returned: { label: 'Returned', color: '#95A5A6' },
  refunded: { label: 'Refunded', color: '#95A5A6' },
  disputed: { label: 'Disputed', color: '#F39C12' },
};

export const PAYMENT_METHOD_LABELS: Record<string, string> = {
  paystack: 'Paystack (Card, Bank Transfer)',
  bank_transfer: 'Direct Bank Transfer',
};

// Vendor-only status→color maps, same {label, color} shape as
// ORDER_STATUSES above, one per App\Enums\* the vendor screens surface as a
// pill badge. Colors mirror each enum's own getColor() Filament mapping
// (warning/success/danger/info/gray) onto this app's palette.
export const PRODUCT_STATUSES: Record<string, { label: string; color: string }> = {
  draft: { label: 'Draft', color: COLORS.gray },
  pending_approval: { label: 'Pending Approval', color: COLORS.warning },
  published: { label: 'Published', color: COLORS.accent },
  rejected: { label: 'Rejected', color: COLORS.danger },
  archived: { label: 'Archived', color: COLORS.gray },
};

export const STOCK_STATUSES: Record<string, { label: string; color: string }> = {
  in_stock: { label: 'In Stock', color: COLORS.accent },
  out_of_stock: { label: 'Out of Stock', color: COLORS.danger },
  on_backorder: { label: 'On Backorder', color: COLORS.warning },
};

export const DOCUMENT_STATUSES: Record<string, { label: string; color: string }> = {
  pending: { label: 'Pending', color: COLORS.warning },
  verified: { label: 'Verified', color: COLORS.accent },
  rejected: { label: 'Rejected', color: COLORS.danger },
};

export const WITHDRAWAL_STATUSES: Record<string, { label: string; color: string }> = {
  pending: { label: 'Pending', color: COLORS.warning },
  approved: { label: 'Approved', color: COLORS.info },
  processing: { label: 'Processing', color: COLORS.info },
  paid: { label: 'Paid', color: COLORS.accent },
  rejected: { label: 'Rejected', color: COLORS.danger },
  cancelled: { label: 'Cancelled', color: COLORS.gray },
  failed: { label: 'Failed', color: COLORS.danger },
};

export const STAFF_STATUSES: Record<string, { label: string; color: string }> = {
  invited: { label: 'Invited', color: COLORS.warning },
  active: { label: 'Active', color: COLORS.accent },
  suspended: { label: 'Suspended', color: COLORS.danger },
};

export const SUBSCRIPTION_STATUSES: Record<string, { label: string; color: string }> = {
  active: { label: 'Active', color: COLORS.accent },
  expired: { label: 'Expired', color: COLORS.warning },
  cancelled: { label: 'Cancelled', color: COLORS.danger },
};

// Human-readable labels for the "vendor" guard permission names StoreStaff
// invites/edits grant — see App\Http\Controllers\Api\V1\Vendor\
// StoreStaffController for the canonical name list this must match.
export const STAFF_PERMISSIONS: { name: string; label: string }[] = [
  { name: 'store.products.manage', label: 'Manage Products' },
  { name: 'store.inventory.manage', label: 'Manage Inventory' },
  { name: 'store.orders.manage', label: 'Manage Orders' },
  { name: 'store.orders.fulfil', label: 'Fulfil Orders' },
  { name: 'store.coupons.manage', label: 'Manage Coupons' },
  { name: 'store.reviews.respond', label: 'Respond to Reviews' },
  { name: 'store.questions.answer', label: 'Answer Questions' },
  { name: 'store.settings.manage', label: 'Manage Settings' },
  { name: 'store.staff.manage', label: 'Manage Staff' },
  { name: 'store.reports.view', label: 'View Reports' },
  { name: 'store.withdrawals.request', label: 'Request Withdrawals' },
];
