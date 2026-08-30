import apiClient from './client';
import { ApiResponse, Order, PaginatedResponse } from '../types';

export const ordersApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<Order>>('/orders', { params: { page } }),

  show: (id: string) => apiClient.get<ApiResponse<Order>>(`/orders/${id}`),

  track: (id: string) => apiClient.get<ApiResponse<Order>>(`/orders/${id}/track`),

  cancel: (id: string, reason: string) => apiClient.post<ApiResponse<Order>>(`/orders/${id}/cancel`, { reason }),
};

export const paymentsApi = {
  initialize: (orderId: string, callbackUrl?: string) =>
    apiClient.post<ApiResponse<{ authorization_url: string; reference: string }>>(`/payments/${orderId}/initialize`, {
      callback_url: callbackUrl,
    }),

  verify: (orderId: string) =>
    apiClient.post<ApiResponse<{ status: string; paid_at: string | null }>>(`/payments/${orderId}/verify`),
};
