/**
 * PRE-BOOTSTRAP DEFAULT — used only to build the very first request (the
 * bootstrap call itself, see bootstrapStore.ts) before the server has had
 * a chance to say anything. After that, the real API base URL the app
 * uses for everything else is server-controlled — see "How the app finds
 * its backend" below. You should rarely need to touch this file at all;
 * change the backend via Admin → Settings → App Settings instead.
 *
 * LOCAL DEVELOPMENT — EMULATOR
 *   LOCAL_API_URL = 'http://10.0.2.2:8000/api/v1'
 *
 * LOCAL DEVELOPMENT — PHYSICAL DEVICE (same WiFi or a phone hotspot)
 *   1. Find your PC's IPv4 on that network (ipconfig / ifconfig)
 *   2. Set LOCAL_API_URL = 'http://192.168.x.x:8000/api/v1'
 *   3. php artisan serve --host=0.0.0.0 --port=8000
 *
 * PRODUCTION
 *   Set PRODUCTION_API_URL to your live domain once, on first setup.
 *   This is also the ONE url the app ALWAYS calls for bootstrap itself
 *   (see BOOTSTRAP_URL below) — it must always be reachable, since
 *   nothing else works until that first call resolves.
 */

const LOCAL_API_URL = 'http://10.0.2.2:8000/api/v1';
const PRODUCTION_API_URL = 'https://onemarket247.com/api/v1';

export const API_BASE_URL: string = __DEV__ ? LOCAL_API_URL : PRODUCTION_API_URL;

/**
 * How the app finds its backend:
 *
 * 1. On every cold start, the app calls GET {BOOTSTRAP_URL}/bootstrap —
 *    always against PRODUCTION_API_URL, regardless of __DEV__, because
 *    that's the one URL guaranteed reachable without any prior setup.
 * 2. That response's `api_base_url` (resolved server-side from
 *    Admin → Settings → App Settings — see App\Models\AppSetting) becomes
 *    the URL every other API call in the app actually uses
 *    (apiClient.setBaseUrl()).
 * 3. If bootstrap fails (offline first launch, etc.), the app falls back
 *    to the last value it successfully resolved (cached in AsyncStorage),
 *    or API_BASE_URL above if there's no cache yet.
 *
 * This is what lets an admin point every already-installed app at a
 * different backend (e.g. their own machine for testing, or a staging
 * server) without publishing a new build — see mobile/README.md.
 */
export const BOOTSTRAP_URL = PRODUCTION_API_URL;

export const API_TIMEOUT = 20000;
export const APP_VERSION = '1.0.0';

/**
 * The App ID from your OneSignal dashboard (Settings → Keys & IDs) — this
 * is a public identifier, safe to ship inside the app (unlike the REST
 * API key, which stays server-side only — see App\Filament\Pages\PushSettings
 * in the Laravel repo). Must match the App ID entered there for push to work.
 */
export const ONESIGNAL_APP_ID = 'YOUR-ONESIGNAL-APP-ID';
