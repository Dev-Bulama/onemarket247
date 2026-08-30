import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { User } from '../types';
import { authApi } from '../api/auth';
import { setAuthToken } from '../api/client';
import { usePushStore } from './pushStore';

interface RegisterData {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
}

interface AuthState {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  loadUser: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  updateUser: (user: User) => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isLoading: false,
  isAuthenticated: false,

  loadUser: async () => {
    try {
      const token = await AsyncStorage.getItem('auth_token');
      const userStr = await AsyncStorage.getItem('user');
      if (token && userStr) {
        await setAuthToken(token);
        set({ user: JSON.parse(userStr), isAuthenticated: true });
        try {
          const res = await authApi.profile();
          await AsyncStorage.setItem('user', JSON.stringify(res.data.data));
          set({ user: res.data.data });
        } catch {
          // offline or token expired — keep the cached user, interceptor handles a real 401
        }
      }
    } catch {
      // ignore
    }
  },

  login: async (email, password) => {
    set({ isLoading: true });
    try {
      const res = await authApi.login(email, password);
      const { token, user } = res.data.data;
      await setAuthToken(token);
      await AsyncStorage.setItem('user', JSON.stringify(user));
      set({ user, isAuthenticated: true });
      usePushStore.getState().registerCurrentDevice();
    } finally {
      set({ isLoading: false });
    }
  },

  register: async (data) => {
    set({ isLoading: true });
    try {
      const res = await authApi.register(data);
      const { token, user } = res.data.data;
      await setAuthToken(token);
      await AsyncStorage.setItem('user', JSON.stringify(user));
      set({ user, isAuthenticated: true });
      usePushStore.getState().registerCurrentDevice();
    } finally {
      set({ isLoading: false });
    }
  },

  logout: async () => {
    // Unregister this device before clearing the token — the
    // device-tokens DELETE endpoint requires auth:sanctum.
    await usePushStore.getState().unregisterCurrentDevice();
    try {
      await authApi.logout();
    } catch {
      // best-effort — token clears locally either way
    }
    await setAuthToken(null);
    await AsyncStorage.removeItem('user');
    set({ user: null, isAuthenticated: false });
  },

  updateUser: (user) => {
    set({ user });
    AsyncStorage.setItem('user', JSON.stringify(user)).catch(() => {});
  },
}));
