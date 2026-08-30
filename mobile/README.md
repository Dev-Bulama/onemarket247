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

## Point the app at your backend

Edit `src/config/api.ts`:

- **Emulator**: `LOCAL_API_URL` defaults to `http://10.0.2.2:8000/api/v1` (Android emulator's alias for the host machine's `localhost`).
- **Physical device**: put your phone and PC on the same network (or use a hotspot), find your PC's LAN IP, and set `LOCAL_API_URL` to `http://<that-ip>:8000/api/v1`.
- **Production**: set `PRODUCTION_API_URL` to `https://onemarket247.com/api/v1` (already the default).

Then, from the Laravel repo root:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Run

```bash
npm run android
# or
npm run ios
```

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

- Wishlist and product-compare screens are not built yet (the API endpoints
  exist — `wishlist`, `compare` — but no UI consumes them).
- Push notifications (OneSignal or similar) are not wired up; the in-app
  notifications list/badge uses polling against `/notifications` only.
- No automated tests yet (Jest/RNTL) — the API layer is thin enough that
  most of the real logic (`cartStore`, `authStore`) would benefit from unit
  tests using mocked Axios responses.
