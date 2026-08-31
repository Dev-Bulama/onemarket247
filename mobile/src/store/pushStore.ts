import { create } from 'zustand';
import { Platform } from 'react-native';
import { OneSignal } from 'react-native-onesignal';
import { ONESIGNAL_APP_ID } from '../config/api';
import { deviceTokensApi } from '../api/deviceTokens';

interface PushState {
  subscriptionId: string | null;
  initialized: boolean;
  /** Call once on app start, regardless of auth state — sets up the SDK
   * and requests permission, but only registers the device with the
   * backend once a user is logged in (see registerCurrentDevice()). */
  initialize: () => void;
  /** Call after login/register, and whenever the OneSignal subscription
   * id changes while a user is logged in — ties this physical device to
   * the current account so admin broadcasts can reach it. */
  registerCurrentDevice: () => Promise<void>;
  /** Call on logout — stops this device from being addressable as the
   * now-logged-out user. */
  unregisterCurrentDevice: () => Promise<void>;
}

export const usePushStore = create<PushState>((set, get) => ({
  subscriptionId: null,
  initialized: false,

  initialize: () => {
    if (get().initialized || ONESIGNAL_APP_ID === 'YOUR-ONESIGNAL-APP-ID') return;
    set({ initialized: true });

    OneSignal.initialize(ONESIGNAL_APP_ID);
    OneSignal.Notifications.requestPermission(false).catch(() => {});

    OneSignal.User.pushSubscription.addEventListener('change', event => {
      set({ subscriptionId: event.current.id ?? null });
      get().registerCurrentDevice();
    });

    OneSignal.User.pushSubscription.getIdAsync()
      .then(id => set({ subscriptionId: id }))
      .catch(() => {});
  },

  registerCurrentDevice: async () => {
    // Calling into the native OneSignal bridge before OneSignal.initialize()
    // has run (e.g. ONESIGNAL_APP_ID is still unset) crashes natively — a
    // JS try/catch can't trap that, unlike a normal rejected promise. This
    // must stay the first line here, not just inside initialize().
    if (!get().initialized) return;
    const id = get().subscriptionId ?? (await OneSignal.User.pushSubscription.getIdAsync().catch(() => null));
    if (!id) return;
    try {
      await deviceTokensApi.register(id, Platform.OS);
    } catch {
      // best-effort — the device simply won't receive push until the next successful registration attempt
    }
  },

  unregisterCurrentDevice: async () => {
    const id = get().subscriptionId;
    if (!id) return;
    try {
      await deviceTokensApi.unregister(id);
    } catch {
      // best-effort — harmless if it still lingers server-side under the old user
    }
  },
}));
