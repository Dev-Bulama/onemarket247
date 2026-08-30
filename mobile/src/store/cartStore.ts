import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Cart } from '../types';
import { cartApi } from '../api/cart';

const GUEST_TOKEN_KEY = 'cart_guest_token';

interface CartState {
  cart: Cart | null;
  guestToken: string | null;
  isLoading: boolean;
  fetchCart: () => Promise<void>;
  addItem: (productId: number, quantity: number, variationId?: number) => Promise<void>;
  updateItem: (itemId: number, quantity: number) => Promise<void>;
  removeItem: (itemId: number) => Promise<void>;
  applyCoupon: (code: string) => Promise<void>;
  removeCoupon: () => Promise<void>;
  /** Called right after login/register so a guest's cart isn't lost. */
  mergeIntoAccount: () => Promise<void>;
  clearLocal: () => void;
}

async function persistGuestToken(token: string | null) {
  if (token) {
    await AsyncStorage.setItem(GUEST_TOKEN_KEY, token);
  } else {
    await AsyncStorage.removeItem(GUEST_TOKEN_KEY);
  }
}

export const useCartStore = create<CartState>((set, get) => ({
  cart: null,
  guestToken: null,
  isLoading: false,

  fetchCart: async () => {
    set({ isLoading: true });
    try {
      const guestToken = get().guestToken ?? (await AsyncStorage.getItem(GUEST_TOKEN_KEY));
      const res = await cartApi.get(guestToken);
      const cart = res.data.data;
      if (cart.guest_token) await persistGuestToken(cart.guest_token);
      set({ cart, guestToken: cart.guest_token ?? guestToken });
    } catch {
      set({ cart: null });
    } finally {
      set({ isLoading: false });
    }
  },

  addItem: async (productId, quantity, variationId) => {
    const res = await cartApi.addItem(productId, quantity, get().guestToken, variationId);
    const cart = res.data.data;
    if (cart.guest_token) await persistGuestToken(cart.guest_token);
    set({ cart, guestToken: cart.guest_token ?? get().guestToken });
  },

  updateItem: async (itemId, quantity) => {
    const res = await cartApi.updateItem(itemId, quantity, get().guestToken);
    set({ cart: res.data.data });
  },

  removeItem: async (itemId) => {
    const res = await cartApi.removeItem(itemId, get().guestToken);
    set({ cart: res.data.data });
  },

  applyCoupon: async (code) => {
    const res = await cartApi.applyCoupon(code, get().guestToken);
    set({ cart: res.data.data });
  },

  removeCoupon: async () => {
    const res = await cartApi.removeCoupon(get().guestToken);
    set({ cart: res.data.data });
  },

  mergeIntoAccount: async () => {
    const guestToken = get().guestToken;
    if (!guestToken) return;
    try {
      const res = await cartApi.merge(guestToken);
      set({ cart: res.data.data, guestToken: null });
      await persistGuestToken(null);
    } catch {
      // non-fatal — worst case the guest cart items are simply not merged
    }
  },

  clearLocal: () => set({ cart: null }),
}));
