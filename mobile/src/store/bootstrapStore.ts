import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, APP_VERSION } from '../config/api';
import { setBaseUrl } from '../api/client';
import { bootstrapApi } from '../api/bootstrap';

const CACHE_KEY = 'bootstrap_cache_v1';

interface BootstrapState {
  appName: string | null;
  logoUrl: string | null;
  splashLogoUrl: string | null;
  updateRequired: boolean;
  isLoading: boolean;
  /** Admin-configured product grid column count (App\Models\AppSetting's
   * product_grid_columns) — defaults to 4 before bootstrap resolves, same
   * as the backend column default. */
  productGridColumns: number;
  /** Admin-configured OneSignal App ID (App\Models\PushSetting, entered via
   * Admin → Settings → Push Notifications) — null whenever push is turned
   * off or unconfigured. Sourced from the backend, never hardcoded, so an
   * admin can turn push on/off or rotate the App ID without a mobile
   * rebuild — see pushStore.ts, which must never touch the native OneSignal
   * SDK while this is null. */
  oneSignalAppId: string | null;
  /** Call once, before anything else on app start (see AppNavigator).
   * Resolves the real API base URL and applies it to apiClient, then
   * loads branding — falling back to a cached last-known-good result
   * (or the hardcoded API_BASE_URL default) if the network call fails,
   * so a cold start never hard-fails just because bootstrap didn't answer. */
  load: () => Promise<void>;
}

function isVersionAtLeast(current: string, minimum: string): boolean {
  const c = current.split('.').map(Number);
  const m = minimum.split('.').map(Number);
  for (let i = 0; i < Math.max(c.length, m.length); i++) {
    const diff = (c[i] ?? 0) - (m[i] ?? 0);
    if (diff !== 0) return diff > 0;
  }
  return true;
}

export const useBootstrapStore = create<BootstrapState>((set) => ({
  appName: null,
  logoUrl: null,
  splashLogoUrl: null,
  updateRequired: false,
  isLoading: true,
  productGridColumns: 4,
  oneSignalAppId: null,

  load: async () => {
    let payload = null;
    try {
      const res = await bootstrapApi.fetch();
      payload = res.data.data;
      await AsyncStorage.setItem(CACHE_KEY, JSON.stringify(payload));
    } catch {
      try {
        const cached = await AsyncStorage.getItem(CACHE_KEY);
        if (cached) payload = JSON.parse(cached);
      } catch {
        // no cache either — fall through to the hardcoded default below
      }
    }

    setBaseUrl(payload?.api_base_url || API_BASE_URL);

    set({
      appName: payload?.app_name ?? null,
      logoUrl: payload?.logo_url ?? null,
      splashLogoUrl: payload?.splash_logo_url ?? null,
      updateRequired: !!payload?.min_app_version && !isVersionAtLeast(APP_VERSION, payload.min_app_version),
      productGridColumns: payload?.product_grid_columns && payload.product_grid_columns >= 2 ? payload.product_grid_columns : 4,
      oneSignalAppId: payload?.onesignal_app_id || null,
      isLoading: false,
    });
  },
}));
