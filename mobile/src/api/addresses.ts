import apiClient from './client';
import { Address, ApiResponse } from '../types';

export interface AddressPayload {
  label?: string;
  full_name: string;
  phone?: string;
  address_line_1: string;
  address_line_2?: string;
  country_id: number;
  state_id?: number;
  city_id?: number;
  postal_code?: string;
  is_default_shipping?: boolean;
  is_default_billing?: boolean;
}

export const addressesApi = {
  list: () => apiClient.get<ApiResponse<Address[]>>('/addresses'),
  store: (data: AddressPayload) => apiClient.post<ApiResponse<Address>>('/addresses', data),
  update: (id: number, data: AddressPayload) => apiClient.patch<ApiResponse<Address>>(`/addresses/${id}`, data),
  destroy: (id: number) => apiClient.delete(`/addresses/${id}`),
};
