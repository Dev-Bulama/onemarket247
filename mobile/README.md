# OneMarket 24/7 — Mobile App

React Native + TypeScript client for the OneMarket247 multivendor marketplace, talking to the Laravel `/api/v1` REST API in the repo root.

## Stack

| Layer          | Technology                          |
|----------------|--------------------------------------|
| Framework      | React Native 0.76 + TypeScript       |
| Navigation     | React Navigation (native-stack + bottom-tabs) |
| State          | Zustand                              |
| HTTP           | Axios (Bearer-token auth via Sanctum)|
| Payments       | Paystack (in-app WebView checkout)   |

## Setup

```bash
cd mobile
npm install

# iOS only
cd ios && pod install && cd ..
```

### Prerequisites for a native build

The `android/` and `ios/` folders are already in this repo (generated via
`@react-native-community/cli init`, then re-branded — package
`com.onemarket247`, display name "OneMarket 24/7"), so there's no
`react-native init` step to run. You still need:

- **Node 18+** (already required for `npm install` above).
- **Java 17** — specifically 17, not a newer or older version. Gradle's
  toolchain resolution is strict about this; a mismatched JDK fails the
  build immediately with `Cannot find a Java installation on your machine
  matching this tasks requirements: {languageVersion=17, ...}`. Use
  [sdkman](https://sdkman.io) or your OS package manager, and if you have
  multiple JDKs installed, point `JAVA_HOME` at the 17 one before building.
- **Android Studio** (for the Android SDK, platform-tools, and an
  emulator) — installing Android Studio normally sets `ANDROID_HOME`
  for you and creates `android/local.properties` automatically the first
  time you open the `android/` folder in it or run a Gradle sync.
- **Xcode** (macOS only, for iOS) + CocoaPods (`sudo gem install cocoapods`
  or via the bundled `Gemfile`: `bundle install && cd ios && bundle exec
  pod install`).

## Point the app at your backend

**You usually don't need to touch any code for this.** The app calls a
`bootstrap` endpoint on every start, always against the production URL
below, and that response tells it which API to actually use for
everything else — controlled from **Admin → Settings → App Settings**
(App\Models\AppSetting), no rebuild required. See "Admin-controlled
backend URL & branding" further down for how that works and how to
switch an already-installed app to your local machine for testing.

The only thing in `src/config/api.ts` you should need to set, once, ever:

- **`PRODUCTION_API_URL`** — your live domain (already defaults to
  `https://onemarket247.com/api/v1`). This is the one URL the app always
  calls first, before it knows anything else, so it needs to always be
  correct and always be reachable.

`LOCAL_API_URL` there is a fallback used only if the bootstrap call fails
entirely (e.g. testing fully offline with no prior successful launch) —
not something you normally need to edit.

If you *are* running the Laravel backend locally, from the Laravel repo root:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Run (development)

```bash
npm run android
# or
npm run ios
```

This produces a debug build signed with the shared RN debug keystore
(`android/app/debug.keystore`) — fine for local testing, but every debug
APK on every developer's machine shares that same signature, so **never
distribute a debug APK**, and never use it as your Play Store upload key.

## Building a release APK

1. **Generate your own upload keystore** (once — keep it somewhere safe,
   outside the repo, and never commit it):
   ```bash
   keytool -genkeypair -v -storetype PKCS12 \
     -keystore onemarket247-upload.keystore \
     -alias onemarket247 -keyalg RSA -keysize 2048 -validity 10000
   ```
2. Move it into `android/app/`, then add to `android/gradle.properties`
   (also outside version control — put it in `android/gradle.properties`
   locally, or better, in `~/.gradle/gradle.properties` so it's never at
   risk of being committed):
   ```
   ONEMARKET_UPLOAD_STORE_FILE=onemarket247-upload.keystore
   ONEMARKET_UPLOAD_KEY_ALIAS=onemarket247
   ONEMARKET_UPLOAD_STORE_PASSWORD=<your password>
   ONEMARKET_UPLOAD_KEY_PASSWORD=<your password>
   ```
3. Point the release `signingConfig` in `android/app/build.gradle` at
   those properties instead of `signingConfigs.debug` (the scaffold ships
   pointed at the debug keystore purely so `npm run android` works out of
   the box — swap this before you build anything you intend to distribute).
4. Before building, double-check `src/config/api.ts`'s
   `PRODUCTION_API_URL` is what you want a release build to hit — release
   builds always use it, debug builds default to it too once `__DEV__` is
   false, which it always is for a release build.
5. Build:
   ```bash
   cd android && ./gradlew assembleRelease
   # output: android/app/build/outputs/apk/release/app-release.apk
   ```
   Or for a Play Store upload, `./gradlew bundleRelease` produces an
   `.aab` instead (`app/build/outputs/bundle/release/app-release.aab`).

Before publishing to the Play Store, also replace the placeholder launcher
icon (`android/app/src/main/res/mipmap-*/ic_launcher*.png`) with your own
— those are still the default React Native template icon.

## Setting up push notifications

Push uses [OneSignal](https://onesignal.com). Two pieces of configuration,
in two different places:

1. **In the OneSignal dashboard**: create an app, grab its **App ID** and
   **REST API Key** (Settings → Keys & IDs).
2. **App ID** (public, safe to ship in the app) goes in
   `src/config/api.ts` → `ONESIGNAL_APP_ID`. Until you set this to a real
   value, `pushStore.initialize()` is a no-op — the app runs fine, it
   just never registers for push.
3. **REST API Key** (secret — never put this in the mobile app) goes into
   the admin panel: **Admin → Settings → Push Notifications**, along with
   the same App ID, then flip "Send push notifications" on. Use "Send
   test push" there once a real device has registered (open the app,
   log in — the device then shows up in the `device_tokens` table) to
   confirm it actually works before relying on it.

Once both are set, `AdminBroadcastNotification` (the "Send Message" admin
feature) delivers as a native push automatically — no code changes
needed. To add push to another notification, add
`App\Notifications\Channels\OneSignalChannel::class` to its `via()` and
implement `toOneSignal($notifiable): OneSignalMessage`, same as any
`toMail()`/`toDatabase()` method.

## Admin-controlled backend URL & branding

**Admin → Settings → App Settings** controls three things every
already-installed app picks up the next time it opens — no app-store
update, no rebuild:

- **Which backend it talks to.** Useful for pointing your own test
  devices at a local/staging server without touching the app's code —
  set "Active Environment" to Local, fill in "Local/staging API URL"
  (e.g. `http://192.168.1.50:8000/api/v1`, your machine's LAN IP), and
  turn **off** "Force production" so that choice actually takes effect.
  Leave "Force production" **on** (the default) for real users — it's a
  safety net that keeps every app on production regardless of what the
  environment picker says, so a forgotten test setting can never strand
  real users on an unreachable server.
- **App name, logo, and splash screen image** — hosted image URLs, shown
  on the splash screen while the app loads (`SplashScreen.tsx`).
- **Minimum app version** — an installed app older than this shows a
  full-screen "Update Required" prompt instead of continuing
  (`ForceUpdateScreen.tsx`). Leave blank to disable.

How it works: the app always calls `GET {PRODUCTION_API_URL}/bootstrap`
first, on every cold start (`bootstrapStore.ts`) — the one call that
never gets redirected, since it's how the app finds out where everything
else lives. That response's `api_base_url` is then applied to every
other API call via `apiClient.setBaseUrl()`. If the bootstrap call fails
(no internet on first launch, etc.), the app falls back to its last
successfully-resolved settings (cached in AsyncStorage) rather than
failing outright.

## Project structure

```
mobile/
  src/
    api/            One file per backend resource — thin Axios wrappers, typed
    components/      Shared UI (ProductCard, GuestGate)
    config/          API base URL
    constants/       Colors, sizes, static label maps
    navigation/       Root/Auth/Main navigators
    screens/         One folder per feature area
    store/           Zustand stores (auth, cart, notifications)
    types/           TypeScript types — each one mirrors a Laravel
                     App\Http\Resources\Api\V1\*Resource exactly
```

## Notes on the API contract

- Every price field from the API is an object — `{ amount, currency, formatted }`
  (see `App\Support\Api\Money`) — never a bare number. Render `.formatted` for
  display; use `.amount` (minor units) only for client-side math.
- The cart is guest-accessible: a guest gets a `guest_token` back on the
  cart response, which the app persists (`cartStore`) and replays as
  `cart_token` on every subsequent cart/checkout call. On login/register,
  `cartStore.mergeIntoAccount()` folds the guest cart into the new account's cart.
- Checkout is a two-step flow: `checkoutApi.init()` locks in a session key,
  then `checkoutApi.complete()` creates the order. Shipping cost is computed
  server-side as part of `complete()`, so the pre-checkout summary shown in
  `CheckoutScreen` only totals subtotal/discount — the definitive total
  (including shipping and tax) is shown on the order confirmation/detail screens.
- Paystack payment is completed via an in-app WebView loading
  `authorization_url`; once the WebView navigates away from the Paystack
  domain, the app calls `paymentsApi.verify()` (server-authoritative — a
  client-reported "success" is never trusted on its own).
- Every request carries `X-Language`/`X-Currency` headers (see
  `localeStore` + `apiClient`'s interceptor) driving the same
  `SetApiLocale`/`SetApiDisplayCurrency` middleware the web session-based
  switcher uses — change them from **Account → Language & Currency**.
- Reviews accept up to 5 photos (multipart upload via `react-native-image-picker`),
  stored the same way product images are (Spatie Media Library).

## What's stubbed / next steps

- No automated tests yet (Jest/RNTL) — the API layer is thin enough that
  most of the real logic (`cartStore`, `authStore`) would benefit from unit
  tests using mocked Axios responses.
- Push notifications are wired end-to-end (see "Setting up push
  notifications" above) but only `AdminBroadcastNotification` sends one
  today — order-status changes, review approvals, etc. still only reach
  mail/database until their notification classes are given the same
  `OneSignalChannel::class` + `toOneSignal()` treatment.
- Launcher icon and splash screen are still the React Native template
  defaults — swap them for OneMarket247 branding before any real release.
