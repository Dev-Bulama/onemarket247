import apiClient from './client';
import { ApiResponse, PaginatedResponse } from '../types';
import {
  VendorApplicationReceipt,
  VendorDocumentItem,
  VendorEarningsSummary,
  VendorInventoryItem,
  VendorOrder,
  VendorProductItem,
  VendorStaffMember,
  VendorStoreProfile,
  VendorSubscriptionItem,
  VendorSubscriptionPlanItem,
  VendorWalletTransaction,
  VendorWithdrawal,
  VendorWithdrawalMethod,
} from '../types/vendor';

export type PickedFile = { uri: string; name: string; type: string };

function appendFile(form: FormData, key: string, file?: PickedFile) {
  if (file) form.append(key, file as unknown as Blob);
}

// Same {uri, name, type} → FormData shape React Native accepts directly,
// see api/products.ts's storeReview().
function appendFields(form: FormData, data: object, skip: string[] = []) {
  Object.entries(data).forEach(([key, value]) => {
    if (skip.includes(key) || value === undefined || value === null) return;
    if (Array.isArray(value)) {
      value.forEach(v => form.append(`${key}[]`, String(v)));
      return;
    }
    if (typeof value === 'boolean') {
      form.append(key, value ? '1' : '0');
      return;
    }
    form.append(key, String(value));
  });
}

export interface VendorApplicationPayload {
  full_name: string;
  email: string;
  phone?: string;
  business_name: string;
  registration_number?: string;
  tax_identification_number?: string;
  agent_id_number?: string;
  agent_full_name?: string;
  agent_phone?: string;
  store_name: string;
  store_category?: string;
  store_description?: string;
  country_id?: number;
  state_id?: number;
  city_id?: number;
  postal_code?: string;
  address?: string;
  website?: string;
  bank_name: string;
  bank_account_name: string;
  bank_account_number: string;
  identity_document: PickedFile;
  business_registration_document: PickedFile;
  tax_certificate_document?: PickedFile;
  terms: boolean;
}

const APPLICATION_FILE_KEYS = ['identity_document', 'business_registration_document', 'tax_certificate_document'];

export const vendorApplicationApi = {
  apply: (data: VendorApplicationPayload) => {
    const form = new FormData();
    appendFields(form, data, APPLICATION_FILE_KEYS);
    appendFile(form, 'identity_document', data.identity_document);
    appendFile(form, 'business_registration_document', data.business_registration_document);
    appendFile(form, 'tax_certificate_document', data.tax_certificate_document);
    return apiClient.post<ApiResponse<VendorApplicationReceipt>>('/vendor/apply', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};

export interface VendorStoreUpdatePayload {
  name: string;
  description?: string;
  email?: string;
  phone?: string;
  address?: string;
  status: 'active' | 'vacation';
  vacation_message?: string;
  seo_title?: string;
  seo_description?: string;
}

export const vendorStoreApi = {
  show: () => apiClient.get<ApiResponse<VendorStoreProfile>>('/vendor/store'),
  update: (data: VendorStoreUpdatePayload) => apiClient.patch<ApiResponse<VendorStoreProfile>>('/vendor/store', data),
};

export interface VendorProductListFilters {
  status?: string;
  page?: number;
}

// type is restricted to 'simple' | 'digital' here — 'variable' products
// need per-variation price/stock management, and there is no vendor API
// endpoint for variations yet, so offering that type would create products
// the app can never finish configuring.
export interface VendorProductCreatePayload {
  name: string;
  slug?: string;
  sku?: string;
  type?: 'simple' | 'digital';
  brand_id?: number;
  categories?: number[];
  short_description?: string;
  description?: string;
  price?: number;
  compare_at_price?: number;
  manage_stock?: boolean;
  stock_quantity?: number;
  stock_status: 'in_stock' | 'out_of_stock' | 'on_backorder';
  low_stock_threshold?: number;
  weight?: number;
  length?: number;
  width?: number;
  height?: number;
  seo_title?: string;
  seo_description?: string;
  images?: PickedFile[];
}

export interface VendorProductUpdatePayload {
  short_description?: string;
  description?: string;
  price: number;
  compare_at_price?: number;
  manage_stock: boolean;
  stock_quantity?: number;
  stock_status: 'in_stock' | 'out_of_stock' | 'on_backorder';
  low_stock_threshold?: number;
}

export const vendorProductsApi = {
  list: (filters: VendorProductListFilters = {}) =>
    apiClient.get<PaginatedResponse<VendorProductItem>>('/vendor/products', { params: filters }),

  show: (id: number) => apiClient.get<ApiResponse<VendorProductItem>>(`/vendor/products/${id}`),

  create: (data: VendorProductCreatePayload) => {
    const form = new FormData();
    appendFields(form, data, ['images']);
    (data.images ?? []).forEach(image => form.append('images[]', image as unknown as Blob));
    return apiClient.post<ApiResponse<VendorProductItem>>('/vendor/products', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  update: (id: number, data: VendorProductUpdatePayload) =>
    apiClient.patch<ApiResponse<VendorProductItem>>(`/vendor/products/${id}`, data),

  destroy: (id: number) => apiClient.delete<ApiResponse<null>>(`/vendor/products/${id}`),

  submit: (id: number) => apiClient.post<ApiResponse<VendorProductItem>>(`/vendor/products/${id}/submit`),
};

export const vendorInventoryApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<VendorInventoryItem>>('/vendor/inventory', { params: { page } }),

  // delta is a relative adjustment (+/-), not an absolute new value.
  adjust: (id: number, delta: number, reason: string) =>
    apiClient.patch<ApiResponse<VendorInventoryItem>>(`/vendor/inventory/${id}`, { delta, reason }),
};

export const vendorOrdersApi = {
  list: (status?: string, page = 1) =>
    apiClient.get<PaginatedResponse<VendorOrder>>('/vendor/orders', { params: { status, page } }),

  show: (id: number) => apiClient.get<ApiResponse<VendorOrder>>(`/vendor/orders/${id}`),

  updateStatus: (id: number, status: string, note?: string) =>
    apiClient.patch<ApiResponse<VendorOrder>>(`/vendor/orders/${id}/status`, { status, note }),

  cancel: (id: number, reason: string) =>
    apiClient.post<ApiResponse<VendorOrder>>(`/vendor/orders/${id}/cancel`, { reason }),
};

export const vendorEarningsApi = {
  summary: () => apiClient.get<ApiResponse<VendorEarningsSummary>>('/vendor/earnings'),

  transactions: (page = 1) =>
    apiClient.get<PaginatedResponse<VendorWalletTransaction>>('/vendor/earnings/transactions', { params: { page } }),
};

export const vendorWithdrawalsApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<VendorWithdrawal>>('/vendor/withdrawals', { params: { page } }),

  methods: () => apiClient.get<ApiResponse<VendorWithdrawalMethod[]>>('/vendor/withdrawals/methods'),

  addMethod: (data: { bank_name: string; account_name: string; account_number: string; is_default?: boolean }) =>
    apiClient.post<ApiResponse<VendorWithdrawalMethod>>('/vendor/withdrawals/methods', data),

  request: (withdrawalMethodId: number, amount: number) =>
    apiClient.post<ApiResponse<VendorWithdrawal>>('/vendor/withdrawals', { withdrawal_method_id: withdrawalMethodId, amount }),

  cancel: (id: number) => apiClient.post<ApiResponse<VendorWithdrawal>>(`/vendor/withdrawals/${id}/cancel`),
};

export const vendorStaffApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<VendorStaffMember>>('/vendor/staff', { params: { page } }),

  invite: (data: { name: string; email: string; permissions: string[] }) =>
    apiClient.post<ApiResponse<VendorStaffMember>>('/vendor/staff', data),

  update: (id: number, data: { status: 'active' | 'suspended'; permissions: string[] }) =>
    apiClient.patch<ApiResponse<VendorStaffMember>>(`/vendor/staff/${id}`, data),

  destroy: (id: number) => apiClient.delete<ApiResponse<null>>(`/vendor/staff/${id}`),
};

export const vendorSubscriptionApi = {
  index: () =>
    apiClient.get<ApiResponse<{ plans: VendorSubscriptionPlanItem[]; current: VendorSubscriptionItem | null }>>('/vendor/subscription'),

  switchTo: (planId: number) =>
    apiClient.post<ApiResponse<{ switched: boolean; requires_contact_support?: boolean }>>('/vendor/subscription/switch', { plan_id: planId }),
};

export const vendorDocumentsApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<VendorDocumentItem>>('/vendor/documents', { params: { page } }),

  upload: (type: string, file: PickedFile) => {
    const form = new FormData();
    form.append('type', type);
    form.append('file', file as unknown as Blob);
    return apiClient.post<ApiResponse<VendorDocumentItem>>('/vendor/documents', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};
