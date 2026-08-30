import apiClient from './client';
import { ApiResponse, Brand, Category, PaginatedResponse, Product, ProductDetail, Review, Store } from '../types';

export interface ProductFilters {
  category_id?: number;
  brand_id?: number;
  min_price?: number;
  max_price?: number;
  in_stock?: boolean;
  flash_sale?: boolean;
  search?: string;
  sort?: 'price_asc' | 'price_desc' | 'name' | 'latest';
  page?: number;
}

export const productsApi = {
  list: (filters: ProductFilters = {}) => apiClient.get<PaginatedResponse<Product>>('/products', { params: filters }),

  show: (slug: string) => apiClient.get<ApiResponse<ProductDetail>>(`/products/${slug}`),

  reviews: (slug: string, page = 1) =>
    apiClient.get<PaginatedResponse<Review>>(`/products/${slug}/reviews`, { params: { page } }),

  storeReview: (slug: string, data: { rating: number; title?: string; body: string }) =>
    apiClient.post(`/products/${slug}/reviews`, data),

  categories: () => apiClient.get<ApiResponse<Category[]>>('/categories'),

  category: (slug: string) => apiClient.get<ApiResponse<Category>>(`/categories/${slug}`),

  brands: () => apiClient.get<ApiResponse<Brand[]>>('/brands'),

  brand: (slug: string) => apiClient.get<ApiResponse<Brand>>(`/brands/${slug}`),
};

export const storesApi = {
  list: () => apiClient.get<PaginatedResponse<Store>>('/stores'),

  show: (slug: string) => apiClient.get<ApiResponse<Store>>(`/stores/${slug}`),

  products: (slug: string, filters: ProductFilters = {}) =>
    apiClient.get<PaginatedResponse<Product>>(`/stores/${slug}/products`, { params: filters }),
};

export const searchApi = {
  search: (query: string, page = 1) => apiClient.get<PaginatedResponse<Product>>('/search', { params: { q: query, page } }),
};
