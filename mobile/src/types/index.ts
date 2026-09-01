// Every shape here mirrors an actual App\Http\Resources\Api\V1\* class in
// the Laravel backend — see mobile/README.md for the mapping. Keep them in
// sync when a resource's toArray() changes.

export interface Money {
  amount: number; // minor units, always in the store's base/settlement currency — safe for client-side math
  currency: string; // ISO code of `amount` (the base currency, NOT the shopper's chosen display currency)
  formatted: string; // already converted + formatted in the shopper's chosen display currency (X-Currency header) — always use this for display
}

export interface ApiResponse<T> {
  data: T;
  meta?: { pagination?: Pagination } | null;
  message?: string | null;
}

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: { pagination: Pagination };
}

export interface User {
  id: number;
  name: string;
  email: string;
  user_type: string;
  email_verified: boolean;
}

export interface BrandSummary {
  id: number;
  name: string;
  slug: string;
}

export interface Brand extends BrandSummary {
  logo?: string | null;
}

export interface VendorSummary {
  store_name: string;
  store_slug: string;
  city?: string | null;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  icon?: string | null;
  image?: string | null;
  children?: Category[];
}

export interface PriceRange {
  min: Money;
  max: Money;
}

export interface Product {
  id: number;
  slug: string;
  name: string;
  sku?: string | null;
  thumbnail?: string | null;
  brand?: BrandSummary | null;
  price: Money | null;
  price_range: PriceRange | null;
  compare_at_price: Money | null;
  discount_percent: number | null;
  on_flash_sale: boolean;
  stock_status: string;
  in_stock: boolean;
  rating: number | null;
  review_count: number;
  vendor?: VendorSummary | null;
}

export interface ProductImage {
  url: string;
  thumbnail: string;
}

export interface ProductVariationAttribute {
  attribute: string;
  value: string;
}

export interface ProductVariation {
  id: number;
  price: Money;
  in_stock: boolean;
  attributes: ProductVariationAttribute[];
}

export interface ProductDetail {
  id: number;
  slug: string;
  name: string;
  short_description?: string | null;
  description?: string | null;
  sku?: string | null;
  images: ProductImage[];
  brand?: BrandSummary | null;
  categories: { id: number; name: string; slug: string }[];
  price: Money | null;
  price_range: PriceRange | null;
  compare_at_price: Money | null;
  discount_percent: number | null;
  on_flash_sale: boolean;
  flash_sale_ends_at?: string | null;
  stock_status: string;
  in_stock: boolean;
  manage_stock: boolean;
  stock_quantity?: number | null;
  rating: number | null;
  review_count: number;
  variations: ProductVariation[];
  vendor?: VendorSummary | null;
}

export interface Store {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  status: string;
  is_verified: boolean;
  is_featured: boolean;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  working_hours?: unknown;
  vacation_message?: string | null;
}

export interface CartItem {
  id: number;
  product: {
    id: number;
    slug: string;
    name: string;
    thumbnail?: string | null;
    vendor_store?: string | null;
  };
  variation?: { id: number; attributes: ProductVariationAttribute[] } | null;
  quantity: number;
  unit_price: Money;
  line_total: Money;
  in_stock: boolean;
  price_drifted: boolean;
  saved_for_later: boolean;
}

export interface CartVendorGroup {
  store_name?: string | null;
  items: CartItem[];
}

export interface Cart {
  guest_token: string | null;
  items: CartItem[];
  saved_items: CartItem[];
  vendor_groups: CartVendorGroup[];
  coupon: { code: string; discount: Money } | null;
  subtotal: Money;
  discount: Money;
  total: Money;
}

export interface CheckoutSession {
  checkout_session_key: string;
  subtotal: Money;
  discount_amount: Money;
  total: Money;
  expires_at: string;
  is_expired: boolean;
  is_resolved: boolean;
  order: Order | null;
}

export interface OrderItem {
  id: number;
  product_id: number;
  product_name: string;
  sku?: string | null;
  quantity: number;
  unit_price: Money;
  line_total: Money;
}

export interface ShipmentEvent {
  status: string;
  location?: string | null;
  description?: string | null;
  occurred_at: string;
}

export interface VendorOrder {
  id: number;
  vendor_order_number: string;
  store_name?: string | null;
  status: string;
  status_label: string;
  subtotal: Money;
  shipping_amount: Money;
  total: Money;
  items?: OrderItem[];
  shipment: {
    tracking_number?: string | null;
    carrier?: string | null;
    status: string;
    status_label: string;
    shipped_at?: string | null;
    estimated_delivery_at?: string | null;
    delivered_at?: string | null;
    events: ShipmentEvent[];
  } | null;
}

export interface Order {
  id: string; // public_id (UUID)
  order_number: string;
  status: string;
  status_label: string;
  placed_at: string;
  subtotal: Money;
  discount_amount: Money;
  shipping_amount: Money;
  tax_amount: Money;
  total: Money;
  coupon_code?: string | null;
  shipping_address: {
    full_name: string;
    phone?: string | null;
    address_line_1: string;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    country?: string | null;
    postal_code?: string | null;
  };
  payment: { status: string; gateway?: string | null; paid_at?: string | null } | null;
  bank_transfer: {
    bank_name?: string | null;
    account_name?: string | null;
    account_number?: string | null;
    reference: string;
  } | null;
  vendor_orders?: VendorOrder[];
}

export interface Address {
  id: number;
  label?: string | null;
  full_name: string;
  phone?: string | null;
  address_line_1: string;
  address_line_2?: string | null;
  country?: string | null;
  state?: string | null;
  city?: string | null;
  country_id: number;
  state_id?: number | null;
  city_id?: number | null;
  postal_code?: string | null;
  is_default_shipping: boolean;
  is_default_billing: boolean;
}

export interface Review {
  id: number;
  customer_name?: string | null;
  rating: number;
  title?: string | null;
  body?: string | null;
  is_verified_purchase: boolean;
  vendor_response?: string | null;
  images: string[];
  helpful_count: number;
  created_at: string;
}

export interface AppNotification {
  id: number;
  subject?: string | null;
  body?: string | null;
  read_at?: string | null;
  created_at: string;
}

export interface Country {
  id: number;
  name: string;
  iso2: string;
  phone_code: string;
}

export interface State {
  id: number;
  name: string;
}

export interface City {
  id: number;
  name: string;
}

export interface HeroSlide {
  id: number;
  image_url: string | null;
  sort_order: number;
}

export interface AppConfig {
  default_currency: string;
  default_language: string;
  payment_methods: string[];
}
