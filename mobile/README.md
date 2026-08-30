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

Edit `src/config/api.ts`:

- **Emulator**: `LOCAL_API_URL` defaults to `http://10.0.2.2:8000/api/v1` (Android emulator's alias for the host machine's `localhost`).
- **Physical device**: put your phone and PC on the same network (or use a hotspot), find your PC's LAN IP, and set `LOCAL_API_URL` to `http://<that-ip>:8000/api/v1`.
- **Production**: set `PRODUCTION_API_URL` to `https://onemarket247.com/api/v1` (already the default).

Then, from the Laravel repo root:

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

## What's stubbed / next steps

- Push notifications (OneSignal or similar) are not wired up; the in-app
  notifications list/badge uses polling against `/notifications` only, so
  an admin's broadcast message only appears once the app is opened —
  not as a native push while the app is closed.
- No automated tests yet (Jest/RNTL) — the API layer is thin enough that
  most of the real logic (`cartStore`, `authStore`) would benefit from unit
  tests using mocked Axios responses.
- No blog, static pages (FAQ/Terms/Privacy/About/Contact), or
  language/currency switcher in the app — the web storefront has these,
  but there's no `/api/v1` endpoint for any of them yet, so building the
  mobile screens would mean adding the backend endpoints first.
- Launcher icon and splash screen are still the React Native template
  defaults — swap them for OneMarket247 branding before any real release.
