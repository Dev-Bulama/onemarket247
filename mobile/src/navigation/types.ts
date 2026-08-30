export type RootStackParamList = {
  Main: undefined;
  Auth: { screen?: 'Login' | 'Register' } | undefined;
  Vendor: undefined;
};

export type AuthStackParamList = {
  Login: undefined;
  Register: undefined;
};

export type HomeStackParamList = {
  Home: undefined;
  Search: undefined;
  ProductList: { categoryId?: number; brandId?: number; title?: string; search?: string } | undefined;
  ProductDetail: { slug: string };
  Store: { slug: string };
  Notifications: undefined;
};

export type CartStackParamList = {
  Cart: undefined;
  Checkout: undefined;
  OrderSuccess: { orderId: string };
  OrderDetail: { orderId: string };
};

export type OrdersStackParamList = {
  Orders: undefined;
  OrderDetail: { orderId: string };
};

export type ProfileStackParamList = {
  Profile: undefined;
  Addresses: undefined;
  AddAddress: { addressId?: number } | undefined;
  Orders: undefined;
  OrderDetail: { orderId: string };
  Wishlist: undefined;
  Compare: undefined;
  ProductDetail: { slug: string };
  Store: { slug: string };
  VendorOnboarding: undefined;
};

// Vendor dashboard (createBottomTabNavigator, see VendorNavigator.tsx) —
// each tab wraps its own native-stack, listed here per-stack the same
// loose way the customer-facing stacks above are (not every screen in
// every stack, just the params that matter).
export type VendorProductsStackParamList = {
  VendorProducts: undefined;
  VendorProductForm: { productId?: number } | undefined;
  VendorInventory: undefined;
};

export type VendorOrdersStackParamList = {
  VendorOrders: undefined;
  VendorOrderDetail: { orderId: number };
};

export type VendorMoreStackParamList = {
  VendorMore: undefined;
  VendorEarnings: undefined;
  VendorWithdrawals: undefined;
  VendorStaff: undefined;
  VendorSubscription: undefined;
  VendorDocuments: undefined;
  VendorStoreSettings: undefined;
};
