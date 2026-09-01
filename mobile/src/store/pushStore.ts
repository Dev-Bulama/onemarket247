import { create } from 'zustand';
import { Platform } from 'react-native';
import { OneSignal } from 'react-native-onesignal';
import { deviceTokensApi } from '../api/deviceTokens';

interface PushState {
  subscriptionId: string | null;
  initialized: boolean;
  /** Call once on app start, regardless of auth state — sets up the SDK
   * and requests permission, but only registers the device with the
   * backend once a user is logged in (see registerCurrentDevice()).
   * `appId` comes from bootstrapStore's oneSignalAppId (App\Models\PushSetting,
   * entered via Admin → Settings → Push Notifications). Pass null/undefined
   * whenever push is off or unconfigured — this must be the ONLY gate that
   * decides whether the native OneSignal SDK is touched at all, since any
   * OneSignal method called before a real initialize() crashes natively in
   * a way JS try/catch cannot trap (see registerCurrentDevice below). */
  initialize: (appId: string | null | undefined) => void;
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

  initialize: (appId) => {
    if (get().initialized || !appId) return;
    set({ initialized: true });

    OneSignal.initialize(appId);
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
    // has run (e.g. no admin-configured App ID yet) crashes natively — a
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
