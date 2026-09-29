import AsyncStorage from '@react-native-async-storage/async-storage';

const GUEST_ID_KEY = 'guest_id';

let cachedId: string | null;

function generateId(): string {
  // Not a cryptographic UUID — just a stable-enough random identifier for
  // this device install, the same role App\Http\Middleware\TrackSiteVisit's
  // `visitor_id` cookie plays on the web. No new dependency pulled in for
  // something this simple.
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;
}

/**
 * A stable per-install identifier for a guest (not logged in) — sent as
 * `guest_id` to the disclaimer and delivery-request endpoints so a guest's
 * acceptance/consent can be recorded and recognized on their next visit,
 * mirroring the web's `visitor_id` cookie. Persists across app restarts
 * via AsyncStorage; a fresh install gets a new one, same as a browser with
 * cookies cleared getting a new `visitor_id`.
 */
export async function getGuestId(): Promise<string> {
  if (cachedId) return cachedId;

  const stored = await AsyncStorage.getItem(GUEST_ID_KEY);
  if (stored) {
    cachedId = stored;
    return stored;
  }

  const fresh = generateId();
  cachedId = fresh;
  await AsyncStorage.setItem(GUEST_ID_KEY, fresh);
  return fresh;
}
