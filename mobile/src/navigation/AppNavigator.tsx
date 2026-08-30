import React, { useEffect, useState } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { RootStackParamList } from './types';
import { useAuthStore } from '../store/authStore';
import { useCartStore } from '../store/cartStore';
import { useLocaleStore } from '../store/localeStore';
import { usePushStore } from '../store/pushStore';
import { useBootstrapStore } from '../store/bootstrapStore';
import AuthNavigator from './AuthNavigator';
import MainNavigator from './MainNavigator';
import VendorNavigator from './VendorNavigator';
import SplashScreen from '../screens/auth/SplashScreen';
import ForceUpdateScreen from '../screens/common/ForceUpdateScreen';

const Stack = createNativeStackNavigator<RootStackParamList>();

export default function AppNavigator() {
  const { loadUser } = useAuthStore();
  const { fetchCart } = useCartStore();
  const { load: loadLocale } = useLocaleStore();
  const { initialize: initializePush, registerCurrentDevice } = usePushStore();
  const { load: loadBootstrap, splashLogoUrl, updateRequired } = useBootstrapStore();
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    (async () => {
      // loadBootstrap() must finish first — it resolves the real API base
      // URL (see bootstrapStore.ts) that every call below actually needs
      // to hit. loadUser() then must finish before fetchCart(), since it
      // sets the auth token apiClient sends on every subsequent request —
      // otherwise fetchCart() could race out as an unauthenticated (guest)
      // request instead of the logged-in user's own cart.
      await loadBootstrap();

      if (useBootstrapStore.getState().updateRequired) {
        setIsLoading(false);
        return;
      }

      await loadUser();
      await Promise.all([fetchCart(), loadLocale()]);
      setIsLoading(false);

      initializePush();
      if (useAuthStore.getState().isAuthenticated) registerCurrentDevice();
    })();
  }, [loadBootstrap, loadUser, fetchCart, loadLocale, initializePush, registerCurrentDevice]);

  if (isLoading) return <SplashScreen logoUrl={splashLogoUrl} />;
  if (updateRequired) return <ForceUpdateScreen />;

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Main" component={MainNavigator} />
        <Stack.Screen name="Auth" component={AuthNavigator} options={{ animation: 'slide_from_bottom' }} />
        <Stack.Screen name="Vendor" component={VendorNavigator} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
