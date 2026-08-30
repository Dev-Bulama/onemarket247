import apiClient from './client';
import { ApiResponse, CheckoutSession, Order } from '../types';

export interface CompleteCheckoutPayload {
  checkout_session_key: string;
  email?: string;
  full_name: string;
  phone?: string;
  address_line_1: string;
  address_line_2?: string;
  country_id: number;
  state_id?: number;
  city_id?: number;
  postal_code?: string;
  payment_method?: 'paystack' | 'bank_transfer';
  cart_token?: string;
}

export const checkoutApi = {
  init: (guestToken: string | null) =>
    apiClient.post<ApiResponse<CheckoutSession>>('/checkout/init', guestToken ? { cart_token: guestToken } : {}),

  complete: (data: CompleteCheckoutPayload) => apiClient.post<ApiResponse<Order>>('/checkout/complete', data),

  status: (checkoutSessionKey: string) =>
    apiClient.get<ApiResponse<CheckoutSession>>(`/checkout/${checkoutSessionKey}/status`),
};
