export type RootStackParamList = {
  Main: undefined;
  Auth: { screen?: 'Login' | 'Register' } | undefined;
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
};
