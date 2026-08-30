// Vendor-side (app\Http\Resources\Api\V1\Vendor*) resource shapes — the
// vendor's own view of their store, mirrored from the Filament /vendor
// panel. See mobile/src/types/index.ts's header comment for the general
// convention; VendorOrder already lives there (shared 1:1 with the
// customer-facing order-tracking shape) and is re-exported here so vendor
// screens can import everything from one place.

import { Money, VendorOrder } from './index';

export type { VendorOrder };

export interface VendorStoreProfile {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  email?: string | null;
  phone?: string | null;
  status: string;
  is_verified: boolean;
  is_featured: boolean;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  working_hours?: unknown;
  vacation_message?: string | null;
}

export interface VendorProductItem {
  id: number;
  name: string;
  slug: string;
  sku?: string | null;
  status: string;
  status_label: string;
  rejection_reason?: string | null;
  thumbnail?: string | null;
  short_description?: string | null;
  description?: string | null;
  price: Money | null;
  compare_at_price: Money | null;
  manage_stock: boolean;
  stock_quantity?: number | null;
  stock_status: string;
  low_stock_threshold?: number | null;
  is_featured: boolean;
  created_at: string;
}

export interface VendorInventoryItem {
  id: number;
  warehouse?: string | null;
  product?: { id: number; name: string } | null;
  variation_id?: number | null;
  on_hand: number;
  reserved: number;
  damaged: number;
  incoming: number;
  available: number;
}

export interface VendorEarningsSummary {
  pending_balance: Money;
  available_balance: Money;
  reserved_balance: Money;
  withdrawn_balance: Money;
}

export interface VendorWalletTransaction {
  id: number;
  type: string;
  balance_bucket: string;
  amount: Money;
  order_number?: string | null;
  reason?: string | null;
  created_at: string;
}

export interface VendorWithdrawal {
  id: string; // reference, not a numeric id — see WithdrawalResource
  amount: Money;
  status: string;
  status_label: string;
  bank_name?: string | null;
  requested_at: string;
  paid_at?: string | null;
  rejection_reason?: string | null;
}

export interface VendorWithdrawalMethod {
  id: number;
  bank_name: string;
  account_name: string;
  account_number: string;
  is_default: boolean;
}

export interface VendorStaffMember {
  id: number;
  name?: string | null;
  email?: string | null;
  status: string;
  status_label: string;
  permissions: string[];
  invited_at?: string | null;
  joined_at?: string | null;
}

export interface VendorSubscriptionPlanItem {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  price: Money;
  is_free: boolean;
  billing_period?: string | null;
  max_products?: number | null;
  features?: unknown;
  is_default: boolean;
}

export interface VendorSubscriptionItem {
  id: number;
  plan: VendorSubscriptionPlanItem;
  status: string;
  status_label: string;
  starts_at?: string | null;
  ends_at?: string | null;
  cancelled_at?: string | null;
}

export interface VendorDocumentItem {
  id: number;
  type: string;
  type_label: string;
  status: string;
  status_label: string;
  rejection_reason?: string | null;
  created_at: string;
}

export interface VendorApplicationReceipt {
  id: number;
  status: string;
  status_label: string;
  created_at: string;
}
