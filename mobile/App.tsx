import React from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'react-native';
import AppNavigator from './src/navigation/AppNavigator';
import Toast from './src/components/Toast';
import DisclaimerModal from './src/components/DisclaimerModal';

export default function App() {
  return (
    <SafeAreaProvider>
      <StatusBar barStyle="dark-content" backgroundColor="#FFFFFF" />
      <AppNavigator />
      <Toast />
      <DisclaimerModal />
    </SafeAreaProvider>
  );
}
