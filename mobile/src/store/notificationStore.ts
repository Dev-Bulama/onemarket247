import { create } from 'zustand';
import { AppNotification } from '../types';
import { notificationsApi } from '../api/notifications';

interface NotificationState {
  notifications: AppNotification[];
  unreadCount: number;
  isLoading: boolean;
  fetchNotifications: () => Promise<void>;
  fetchUnreadCount: () => Promise<void>;
  markRead: (id: number) => Promise<void>;
}

export const useNotificationStore = create<NotificationState>((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,

  fetchNotifications: async () => {
    set({ isLoading: true });
    try {
      const res = await notificationsApi.list();
      const notifications = res.data.data;
      set({ notifications, unreadCount: notifications.filter(n => !n.read_at).length });
    } catch {
      // ignore — keep whatever was already loaded
    } finally {
      set({ isLoading: false });
    }
  },

  fetchUnreadCount: async () => {
    try {
      const res = await notificationsApi.list();
      set({ unreadCount: res.data.data.filter(n => !n.read_at).length });
    } catch {
      // ignore
    }
  },

  markRead: async (id) => {
    try {
      await notificationsApi.markRead(id);
      set({
        notifications: get().notifications.map(n => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n)),
        unreadCount: Math.max(0, get().unreadCount - 1),
      });
    } catch {
      // ignore
    }
  },
}));
