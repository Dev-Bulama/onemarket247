# 06 — Customer-Facing Web Marketplace Page List

Server-rendered Blade + Alpine.js, SEO-friendly, mobile-first, RTL-compatible.
Base locale-prefixed routing: `/{locale?}/...` (default locale unprefixed).

## 1. Public / Discovery

| Route | Page |
|---|---|
| `/` | Homepage (builder-driven sections) |
| `/shop` | Full catalog / shop page |
| `/categories` | Category index |
| `/categories/{slug}` | Category page (with subcategory nav) |
| `/categories/{slug}/{subcategory}` | Subcategory page |
| `/brands` | Brand index |
| `/brands/{slug}` | Brand page |
| `/collections/{slug}` | Collection page |
| `/flash-sales` | Flash sales page |
| `/search` | Search results |
| `/products/{slug}` | Product details |
| `/stores` | Vendor listing |
| `/stores/{slug}` | Vendor store page |
| `/blog` | Blog index |
| `/blog/{slug}` | Blog post details |
| `/pages/{slug}` | CMS static page |
| `/contact` | Contact page |
| `/faq` | FAQ |
| `/terms` | Terms of service |
| `/privacy-policy` | Privacy policy |

## 2. Cart / Checkout

| Route | Page |
|---|---|
| `/cart` | Cart |
| `/checkout` | Single-page checkout |
| `/checkout/confirmation/{order}` | Order confirmation |

## 3. Auth

| Route | Page |
|---|---|
| `/login` | Customer login |
| `/register` | Customer registration |
| `/forgot-password` | Forgot password |
| `/reset-password/{token}` | Reset password |
| `/email/verify` | Email verification notice/handler |
| `/vendor/register` | Vendor registration (application form) |
| `/vendor/login` | Vendor login (redirects into vendor dashboard) |

## 4. Customer Account (`/account/*`, auth required)

| Route | Page |
|---|---|
| `/account` | Dashboard / overview |
| `/account/orders` | Order history |
| `/account/orders/{order}` | Order details |
| `/account/orders/{order}/track` | Order tracking |
| `/account/orders/{order}/invoice` | Invoice (PDF) |
| `/account/returns` | Return requests |
| `/account/returns/{return}` | Return details |
| `/account/wallet` | Wallet & transaction history |
| `/account/rewards` | Reward points |
| `/account/addresses` | Address book |
| `/account/profile` | Profile settings |
| `/account/security` | Password/2FA/sessions |
| `/account/wishlist` | Wishlist |
| `/account/compare` | Product comparison |
| `/account/notifications` | Notification center |
| `/account/support` | Support tickets |
| `/account/support/{ticket}` | Ticket thread |
| `/account/gift-cards` | Gift card balance/redeem |
| `/account/referrals` | Referral program |

## 5. Cross-cutting Requirements

- Every page above ships fully functional (no placeholder) before its owning
  phase's completion gate — see the page-to-phase map:
  - Phase 8: homepage, shop, categories, brands, collections, search,
    product details, store pages, blog, static pages
  - Phase 9: account profile/addresses/wishlist/compare/reviews/questions
  - Phase 10: `/cart`
  - Phase 11: `/checkout`, confirmation
  - Phase 12: `/account/orders*`
  - Phase 17: `/flash-sales`, coupon UI in cart/checkout
  - Phase 18: `/account/returns*`
  - Phase 19: `/blog*`, `/pages/*`, homepage builder sections
  - Phase 21: `/account/notifications`
  - Phase 22: `/account/wallet`, `/account/rewards`, `/account/gift-cards`,
    `/account/referrals`, `/account/support*`
- Responsive breakpoints tested per page: 320/360/375/390/414/768/1024/1280/
  1440/1920px (see [11-testing-roadmap.md](11-testing-roadmap.md)).
- Every route is covered by a feature test asserting a 200 response and key
  DOM landmarks, plus an authorization test where access should be restricted
  (e.g. `/account/*` redirects guests to `/login`).
