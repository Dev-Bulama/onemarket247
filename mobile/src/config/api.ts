/**
 * BOOTSTRAP URL — change these two lines for your environment.
 *
 * LOCAL DEVELOPMENT — EMULATOR
 *   LOCAL_API_URL = 'http://10.0.2.2:8000/api/v1'
 *
 * LOCAL DEVELOPMENT — PHYSICAL DEVICE (via phone hotspot)
 *   1. Turn on your phone's Personal Hotspot, connect your PC to it
 *   2. Find your PC's IPv4 on that network (ipconfig / ifconfig)
 *   3. Set LOCAL_API_URL = 'http://192.168.x.x:8000/api/v1'
 *   4. php artisan serve --host=0.0.0.0 --port=8000
 *
 * PRODUCTION
 *   Set PRODUCTION_API_URL to the live domain below and rebuild.
 */

const LOCAL_API_URL = 'http://10.0.2.2:8000/api/v1';
const PRODUCTION_API_URL = 'https://onemarket247.com/api/v1';

export const API_BASE_URL: string = __DEV__ ? LOCAL_API_URL : PRODUCTION_API_URL;

export const API_TIMEOUT = 20000;
export const APP_VERSION = '1.0.0';

/**
 * The App ID from your OneSignal dashboard (Settings → Keys & IDs) — this
 * is a public identifier, safe to ship inside the app (unlike the REST
 * API key, which stays server-side only — see App\Filament\Pages\PushSettings
 * in the Laravel repo). Must match the App ID entered there for push to work.
 */
export const ONESIGNAL_APP_ID = 'YOUR-ONESIGNAL-APP-ID';
