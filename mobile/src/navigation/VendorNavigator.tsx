import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StyleSheet } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS } from '../constants';

import VendorDashboardScreen from '../screens/vendor/VendorDashboardScreen';
import VendorProductsScreen from '../screens/vendor/VendorProductsScreen';
import VendorProductFormScreen from '../screens/vendor/VendorProductFormScreen';
import VendorInventoryScreen from '../screens/vendor/VendorInventoryScreen';
import VendorOrdersScreen from '../screens/vendor/VendorOrdersScreen';
import VendorOrderDetailScreen from '../screens/vendor/VendorOrderDetailScreen';
import VendorMoreScreen from '../screens/vendor/VendorMoreScreen';
import VendorEarningsScreen from '../screens/vendor/VendorEarningsScreen';
import VendorWithdrawalsScreen from '../screens/vendor/VendorWithdrawalsScreen';
import VendorStaffScreen from '../screens/vendor/VendorStaffScreen';
import VendorSubscriptionScreen from '../screens/vendor/VendorSubscriptionScreen';
import VendorDocumentsScreen from '../screens/vendor/VendorDocumentsScreen';
import VendorStoreSettingsScreen from '../screens/vendor/VendorStoreSettingsScreen';

// Mirrors MainNavigator.tsx's structure exactly (same Tab.Navigator style
// constants, same TabIcon helper pattern) but for the vendor side of the
// app — a vendor owner/staff member managing their store instead of
// shopping. Reached via RootStackParamList's "Vendor" screen.
const Tab = createBottomTabNavigator();
const DashboardStack = createNativeStackNavigator();
const ProductsStack = createNativeStackNavigator();
const OrdersStack = createNativeStackNavigator();
const MoreStack = createNativeStackNavigator();

function DashboardStackNav() {
  return (
    <DashboardStack.Navigator screenOptions={{ headerShown: false }}>
      <DashboardStack.Screen name="VendorDashboard" component={VendorDashboardScreen} />
    </DashboardStack.Navigator>
  );
}

function ProductsStackNav() {
  return (
    <ProductsStack.Navigator screenOptions={{ headerShown: false }}>
      <ProductsStack.Screen name="VendorProducts" component={VendorProductsScreen} />
      <ProductsStack.Screen name="VendorProductForm" component={VendorProductFormScreen} />
      <ProductsStack.Screen name="VendorInventory" component={VendorInventoryScreen} />
    </ProductsStack.Navigator>
  );
}

function OrdersStackNav() {
  return (
    <OrdersStack.Navigator screenOptions={{ headerShown: false }}>
      <OrdersStack.Screen name="VendorOrders" component={VendorOrdersScreen} />
      <OrdersStack.Screen name="VendorOrderDetail" component={VendorOrderDetailScreen} />
    </OrdersStack.Navigator>
  );
}

function MoreStackNav() {
  return (
    <MoreStack.Navigator screenOptions={{ headerShown: false }}>
      <MoreStack.Screen name="VendorMore" component={VendorMoreScreen} />
      <MoreStack.Screen name="VendorEarnings" component={VendorEarningsScreen} />
      <MoreStack.Screen name="VendorWithdrawals" component={VendorWithdrawalsScreen} />
      <MoreStack.Screen name="VendorStaff" component={VendorStaffScreen} />
      <MoreStack.Screen name="VendorSubscription" component={VendorSubscriptionScreen} />
      <MoreStack.Screen name="VendorDocuments" component={VendorDocumentsScreen} />
      <MoreStack.Screen name="VendorStoreSettings" component={VendorStoreSettingsScreen} />
    </MoreStack.Navigator>
  );
}

function TabIcon({ name, focused }: { name: string; focused: boolean }) {
  const iconMap: Record<string, [string, string]> = {
    Dashboard: ['grid', 'grid-outline'],
    Products: ['cube', 'cube-outline'],
    Orders: ['receipt', 'receipt-outline'],
    More: ['ellipsis-horizontal', 'ellipsis-horizontal-outline'],
  };
  const [activeIcon, inactiveIcon] = iconMap[name] ?? ['ellipse', 'ellipse-outline'];
  return <IonIcon name={focused ? activeIcon : inactiveIcon} size={focused ? 24 : 22} color={focused ? COLORS.primary : COLORS.gray} />;
}

export default function VendorNavigator() {
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
      <Tab.Screen name="DashboardTab" component={DashboardStackNav} options={{ tabBarLabel: 'Dashboard', tabBarIcon: ({ focused }) => <TabIcon name="Dashboard" focused={focused} /> }} />
      <Tab.Screen name="ProductsTab" component={ProductsStackNav} options={{ tabBarLabel: 'Products', tabBarIcon: ({ focused }) => <TabIcon name="Products" focused={focused} /> }} />
      <Tab.Screen name="OrdersTab" component={OrdersStackNav} options={{ tabBarLabel: 'Orders', tabBarIcon: ({ focused }) => <TabIcon name="Orders" focused={focused} /> }} />
      <Tab.Screen name="MoreTab" component={MoreStackNav} options={{ tabBarLabel: 'More', tabBarIcon: ({ focused }) => <TabIcon name="More" focused={focused} /> }} />
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
});
