import { create } from 'zustand';
import { disclaimersApi, DisclaimerData } from '../api/disclaimers';
import { getGuestId } from '../utils/guestId';
import { useAuthStore } from './authStore';

interface DisclaimerState {
  current: DisclaimerData | null;
  /** Call once on app start (after bootstrap/auth load) and again after
   * login/logout, since which general disclaimer (if any) applies can
   * differ between a guest and a logged-in user. */
  loadGeneral: () => Promise<void>;
  /** Call from a specific screen (checkout, vendor/agent onboarding) to
   * check its own checkpoint trigger — replaces whatever general
   * disclaimer might be showing, so only one modal is ever up at once,
   * mirroring the web's `$pageDisclaimer ?? $activeDisclaimer` partial. */
  loadForTrigger: (trigger: string) => Promise<DisclaimerData | null>;
  accept: () => Promise<void>;
  dismissWithoutAccepting: () => void;
}

export const useDisclaimerStore = create<DisclaimerState>((set, get) => ({
  current: null,

  loadGeneral: async () => {
    const isAuthenticated = useAuthStore.getState().isAuthenticated;
    const guestId = isAuthenticated ? null : await getGuestId();

    try {
      const res = await disclaimersApi.general(guestId);
      set({ current: res.data.data });
    } catch {
      // A failed fetch shouldn't block browsing — just skip the pop-up this time.
    }
  },

  loadForTrigger: async (trigger: string) => {
    const isAuthenticated = useAuthStore.getState().isAuthenticated;
    const guestId = isAuthenticated ? null : await getGuestId();

    try {
      const res = await disclaimersApi.active(trigger, guestId);
      set({ current: res.data.data });
      return res.data.data;
    } catch {
      return null;
    }
  },

  accept: async () => {
    const disclaimer = get().current;
    if (!disclaimer) return;

    const isAuthenticated = useAuthStore.getState().isAuthenticated;
    const guestId = isAuthenticated ? null : await getGuestId();

    try {
      await disclaimersApi.accept(disclaimer.id, guestId);
    } finally {
      set({ current: null });
    }
  },

  dismissWithoutAccepting: () => set({ current: null }),
}));
