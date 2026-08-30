import apiClient from './client';
import { ApiResponse, Product } from '../types';

export const wishlistApi = {
  list: () => apiClient.get<ApiResponse<Product[]>>('/wishlist'),
  add: (productId: number) => apiClient.post<ApiResponse<null>>(`/wishlist/${productId}`),
  remove: (productId: number) => apiClient.delete<ApiResponse<null>>(`/wishlist/${productId}`),
};

export const compareApi = {
  list: () => apiClient.get<ApiResponse<Product[]>>('/compare'),
  add: (productId: number) => apiClient.post<ApiResponse<null>>(`/compare/${productId}`),
  remove: (productId: number) => apiClient.delete<ApiResponse<null>>(`/compare/${productId}`),
};

export interface QuestionAnswer {
  id: number;
  answer: string;
  answered_by?: string | null;
  created_at: string;
}

export interface ProductQuestion {
  id: number;
  customer_name?: string | null;
  question: string;
  is_answered: boolean;
  answers?: QuestionAnswer[];
  created_at: string;
}

export const questionsApi = {
  list: (slug: string, page = 1) =>
    apiClient.get<{ data: ProductQuestion[]; meta: { pagination: { current_page: number; last_page: number } } }>(`/products/${slug}/questions`, { params: { page } }),
  ask: (slug: string, question: string) =>
    apiClient.post<ApiResponse<ProductQuestion>>(`/products/${slug}/questions`, { question }),
};
