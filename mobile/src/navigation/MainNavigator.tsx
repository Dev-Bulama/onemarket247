import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StyleSheet, Text, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS } from '../constants';
import { useCartStore } from '../store/cartStore';
import { useAuthStore } from '../store/authStore';
import GuestGate from '../components/GuestGate';

import HomeScreen from '../screens/home/HomeScreen';
import SearchScreen from '../screens/search/SearchScreen';
import CategoriesScreen from '../screens/home/CategoriesScreen';
import ProductListScreen from '../screens/product/ProductListScreen';
import ProductDetailScreen from '../screens/product/ProductDetailScreen';
import StoreScreen from '../screens/product/StoreScreen';
import NotificationsScreen from '../screens/notifications/NotificationsScreen';
import CartScreen from '../screens/cart/CartScreen';
import CheckoutScreen from '../screens/checkout/CheckoutScreen';
import OrderSuccessScreen from '../screens/order/OrderSuccessScreen';
import OrderDetailScreen from '../screens/order/OrderDetailScreen';
import OrdersScreen from '../screens/order/OrdersScreen';
import ProfileScreen from '../screens/profile/ProfileScreen';
import AddressesScreen from '../screens/profile/AddressesScreen';
import AddAddressScreen from '../screens/profile/AddAddressScreen';
import WishlistScreen from '../screens/profile/WishlistScreen';
import CompareScreen from '../screens/profile/CompareScreen';

const Tab = createBottomTabNavigator();
const HomeStack = createNativeStackNavigator();
const CategoriesStack = createNativeStackNavigator();
const SearchStack = createNativeStackNavigator();
const CartStack = createNativeStackNavigator();
const ProfileStack = createNativeStackNavigator();

function HomeStackNav() {
  return (
    <HomeStack.Navigator screenOptions={{ headerShown: false }}>
      <HomeStack.Screen name="Home" component={HomeScreen} />
      <HomeStack.Screen name="ProductList" component={ProductListScreen} />
      <HomeStack.Screen name="ProductDetail" component={ProductDetailScreen} />
      <HomeStack.Screen name="Store" component={StoreScreen} />
      <HomeStack.Screen name="Notifications" component={NotificationsScreen} />
    </HomeStack.Navigator>
  );
}

function CategoriesStackNav() {
  return (
    <CategoriesStack.Navigator screenOptions={{ headerShown: false }}>
      <CategoriesStack.Screen name="Categories" component={CategoriesScreen} />
      <CategoriesStack.Screen name="ProductList" component={ProductListScreen} />
      <CategoriesStack.Screen name="ProductDetail" component={ProductDetailScreen} />
      <CategoriesStack.Screen name="Store" component={StoreScreen} />
    </CategoriesStack.Navigator>
  );
}

function SearchStackNav() {
  return (
    <SearchStack.Navigator screenOptions={{ headerShown: false }}>
      <SearchStack.Screen name="Search" component={SearchScreen} />
      <SearchStack.Screen name="ProductList" component={ProductListScreen} />
      <SearchStack.Screen name="ProductDetail" component={ProductDetailScreen} />
      <SearchStack.Screen name="Store" component={StoreScreen} />
    </SearchStack.Navigator>
  );
}

function CartStackNav() {
  // Cart and checkout are guest-accessible on the backend (see
  // routes/api.php's cart/checkout groups) — no auth gate here.
  return (
    <CartStack.Navigator screenOptions={{ headerShown: false }}>
      <CartStack.Screen name="Cart" component={CartScreen} />
      <CartStack.Screen name="Checkout" component={CheckoutScreen} />
      <CartStack.Screen name="AddAddress" component={AddAddressScreen} />
      <CartStack.Screen name="OrderSuccess" component={OrderSuccessScreen} />
      <CartStack.Screen name="OrderDetail" component={OrderDetailScreen} />
    </CartStack.Navigator>
  );
}

function ProfileStackNav({ navigation }: any) {
  const { isAuthenticated } = useAuthStore();
  if (!isAuthenticated) {
    return (
      <GuestGate navigation={navigation} title="Your Account" message="Log in to view your profile, addresses, and orders." />
    );
  }
  return (
    <ProfileStack.Navigator screenOptions={{ headerShown: false }}>
      <ProfileStack.Screen name="Profile" component={ProfileScreen} />
      <ProfileStack.Screen name="Addresses" component={AddressesScreen} />
      <ProfileStack.Screen name="AddAddress" component={AddAddressScreen} />
      <ProfileStack.Screen name="Orders" component={OrdersScreen} />
      <ProfileStack.Screen name="OrderDetail" component={OrderDetailScreen} />
      <ProfileStack.Screen name="Wishlist" component={WishlistScreen} />
      <ProfileStack.Screen name="Compare" component={CompareScreen} />
      <ProfileStack.Screen name="ProductDetail" component={ProductDetailScreen} />
      <ProfileStack.Screen name="Store" component={StoreScreen} />
    </ProfileStack.Navigator>
  );
}

function TabIcon({ name, focused }: { name: string; focused: boolean }) {
  const iconMap: Record<string, [string, string]> = {
    Home: ['home', 'home-outline'],
    Categories: ['grid', 'grid-outline'],
    Search: ['search', 'search-outline'],
    Cart: ['cart', 'cart-outline'],
    Profile: ['person', 'person-outline'],
  };
  const [activeIcon, inactiveIcon] = iconMap[name] ?? ['ellipse', 'ellipse-outline'];
  return <IonIcon name={focused ? activeIcon : inactiveIcon} size={focused ? 24 : 22} color={focused ? COLORS.primary : COLORS.gray} />;
}

function CartTabIcon({ focused }: { focused: boolean }) {
  const { cart } = useCartStore();
  const count = cart?.items?.length ?? 0;
  return (
    <View>
      <TabIcon name="Cart" focused={focused} />
      {count > 0 && (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{count > 9 ? '9+' : count}</Text>
        </View>
      )}
    </View>
  );
}

export default function MainNavigator() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarStyle: styles.tabBar,
        tabBarActiveTintColor: COLORS.primary,
        tabBarInactiveTintColor: COLORS.gray,
        tabBarLabelStyle: styles.tabLabel,
      }}
    >
      <Tab.Screen name="HomeTab" component={HomeStackNav} options={{ tabBarLabel: 'Home', tabBarIcon: ({ focused }) => <TabIcon name="Home" focused={focused} /> }} />
      <Tab.Screen name="CategoriesTab" component={CategoriesStackNav} options={{ tabBarLabel: 'Categories', tabBarIcon: ({ focused }) => <TabIcon name="Categories" focused={focused} /> }} />
      <Tab.Screen name="SearchTab" component={SearchStackNav} options={{ tabBarLabel: 'Search', tabBarIcon: ({ focused }) => <TabIcon name="Search" focused={focused} /> }} />
      <Tab.Screen name="CartTab" component={CartStackNav} options={{ tabBarLabel: 'Cart', tabBarIcon: ({ focused }) => <CartTabIcon focused={focused} /> }} />
      <Tab.Screen name="ProfileTab" component={ProfileStackNav} options={{ tabBarLabel: 'Account', tabBarIcon: ({ focused }) => <TabIcon name="Profile" focused={focused} /> }} />
    </Tab.Navigator>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    height: 60, paddingBottom: 8, paddingTop: 4, backgroundColor: COLORS.white,
    borderTopWidth: 1, borderTopColor: COLORS.border,
    elevation: 8, shadowColor: '#000', shadowOffset: { width: 0, height: -2 }, shadowOpacity: 0.08, shadowRadius: 8,
  },
  tabLabel: { fontSize: 11, fontWeight: '500' },
  badge: {
    position: 'absolute', top: -4, right: -8, backgroundColor: COLORS.primary, borderRadius: 10,
    minWidth: 18, height: 18, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4,
  },
  badgeText: { color: COLORS.white, fontSize: 10, fontWeight: 'bold' },
});
