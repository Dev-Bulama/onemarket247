import apiClient from './client';
import { ApiResponse, Cart } from '../types';

/**
 * The API identifies a guest's cart by a `cart_token` the client persists
 * itself (see App\Http\Controllers\Api\V1\Concerns\ResolvesApiCart) — every
 * function here takes it explicitly rather than reading storage itself, so
 * cartStore stays the single source of truth for "which guest token".
 */
export const cartApi = {
  get: (guestToken: string | null) => apiClient.get<ApiResponse<Cart>>('/cart', { params: withToken(guestToken) }),

  addItem: (productId: number, quantity: number, guestToken: string | null, variationId?: number) =>
    apiClient.post<ApiResponse<Cart>>('/cart/items', {
      product_id: productId,
      product_variation_id: variationId,
      quantity,
      ...withToken(guestToken),
    }),

  updateItem: (itemId: number, quantity: number, guestToken: string | null) =>
    apiClient.patch<ApiResponse<Cart>>(`/cart/items/${itemId}`, { quantity, ...withToken(guestToken) }),

  removeItem: (itemId: number, guestToken: string | null) =>
    apiClient.delete<ApiResponse<Cart>>(`/cart/items/${itemId}`, { params: withToken(guestToken) }),

  applyCoupon: (code: string, guestToken: string | null) =>
    apiClient.post<ApiResponse<Cart>>('/cart/coupons', { code, ...withToken(guestToken) }),

  removeCoupon: (guestToken: string | null) =>
    apiClient.delete<ApiResponse<Cart>>('/cart/coupons', { params: withToken(guestToken) }),

  merge: (guestToken: string) => apiClient.post<ApiResponse<Cart>>('/cart/merge', { guest_token: guestToken }),
};

function withToken(guestToken: string | null): { cart_token?: string } {
  return guestToken ? { cart_token: guestToken } : {};
}
