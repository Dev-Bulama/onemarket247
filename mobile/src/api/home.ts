import apiClient from './client';
import { ApiResponse, Brand, Product, Store } from '../types';

export interface HomePayload {
  featured_products: Product[];
  new_arrivals: Product[];
  best_sellers: Product[];
  trending: Product[];
  flash_sale: { products: Product[]; ends_at: string | null };
  recommended_near_you: Product[];
  brands: Brand[];
  stores: Store[];
}

export const homeApi = {
  get: (params: { city_id?: number; state_id?: number } = {}) =>
    apiClient.get<ApiResponse<HomePayload>>('/home', { params }),
};
