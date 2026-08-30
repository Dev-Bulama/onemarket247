import { create } from 'zustand';
import { Product } from '../types';
import { wishlistApi } from '../api/wishlist';

interface WishlistState {
  items: Product[];
  ids: Set<number>;
  isLoading: boolean;
  fetchWishlist: () => Promise<void>;
  toggle: (productId: number) => Promise<void>;
}

export const useWishlistStore = create<WishlistState>((set, get) => ({
  items: [],
  ids: new Set(),
  isLoading: false,

  fetchWishlist: async () => {
    set({ isLoading: true });
    try {
      const res = await wishlistApi.list();
      set({ items: res.data.data, ids: new Set(res.data.data.map(p => p.id)) });
    } catch {
      // guest or offline — leave the list empty rather than crash the screen
    } finally {
      set({ isLoading: false });
    }
  },

  toggle: async (productId) => {
    const isWishlisted = get().ids.has(productId);
    // optimistic update
    set(state => {
      const ids = new Set(state.ids);
      isWishlisted ? ids.delete(productId) : ids.add(productId);
      return { ids };
    });
    try {
      if (isWishlisted) {
        await wishlistApi.remove(productId);
      } else {
        await wishlistApi.add(productId);
      }
      await get().fetchWishlist();
    } catch {
      // revert the optimistic change on failure
      set(state => {
        const ids = new Set(state.ids);
        isWishlisted ? ids.add(productId) : ids.delete(productId);
        return { ids };
      });
    }
  },
}));
