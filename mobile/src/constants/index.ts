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

export const ORDER_STATUSES: Record<string, { label: string; color: string }> = {
  pending_payment: { label: 'Pending Payment', color: '#F39C12' },
  processing: { label: 'Processing', color: '#3498DB' },
  partially_shipped: { label: 'Partially Shipped', color: '#9B59B6' },
  shipped: { label: 'Shipped', color: '#1ABC9C' },
  delivered: { label: 'Delivered', color: '#16A34A' },
  completed: { label: 'Completed', color: '#16A34A' },
  cancelled: { label: 'Cancelled', color: '#E74C3C' },
  refunded: { label: 'Refunded', color: '#95A5A6' },
};

export const PAYMENT_METHOD_LABELS: Record<string, string> = {
  paystack: 'Paystack (Card, Bank Transfer)',
  bank_transfer: 'Direct Bank Transfer',
};
