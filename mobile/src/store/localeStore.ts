import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { setPreferredCurrency, setPreferredLanguage } from '../api/client';

interface LocaleState {
  language: string | null;
  currency: string | null;
  load: () => Promise<void>;
  setLanguage: (code: string | null) => Promise<void>;
  setCurrency: (code: string | null) => Promise<void>;
}

export const useLocaleStore = create<LocaleState>(set => ({
  language: null,
  currency: null,

  load: async () => {
    const [language, currency] = await Promise.all([
      AsyncStorage.getItem('preferred_language'),
      AsyncStorage.getItem('preferred_currency'),
    ]);
    set({ language, currency });
  },

  setLanguage: async (code) => {
    await setPreferredLanguage(code);
    set({ language: code });
  },

  setCurrency: async (code) => {
    await setPreferredCurrency(code);
    set({ currency: code });
  },
}));
