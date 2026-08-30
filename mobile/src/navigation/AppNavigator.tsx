import React, { useEffect, useState } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { RootStackParamList } from './types';
import { useAuthStore } from '../store/authStore';
import { useCartStore } from '../store/cartStore';
import { useLocaleStore } from '../store/localeStore';
import { usePushStore } from '../store/pushStore';
import AuthNavigator from './AuthNavigator';
import MainNavigator from './MainNavigator';
import SplashScreen from '../screens/auth/SplashScreen';

const Stack = createNativeStackNavigator<RootStackParamList>();

export default function AppNavigator() {
  const { loadUser } = useAuthStore();
  const { fetchCart } = useCartStore();
  const { load: loadLocale } = useLocaleStore();
  const { initialize: initializePush, registerCurrentDevice } = usePushStore();
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    (async () => {
      // loadUser() must finish first — it sets the auth token apiClient
      // sends on every subsequent request, so fetchCart() fires as the
      // logged-in user's own cart request rather than racing out as an
      // unauthenticated (guest) one. loadLocale() is independent of both.
      await loadUser();
      await Promise.all([fetchCart(), loadLocale()]);
      setIsLoading(false);

      initializePush();
      if (useAuthStore.getState().isAuthenticated) registerCurrentDevice();
    })();
  }, [loadUser, fetchCart, loadLocale, initializePush, registerCurrentDevice]);

  if (isLoading) return <SplashScreen />;

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Main" component={MainNavigator} />
        <Stack.Screen name="Auth" component={AuthNavigator} options={{ animation: 'slide_from_bottom' }} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
