import { create } from 'zustand';

export type ToastType = 'success' | 'error' | 'info';

interface ToastState {
  visible: boolean;
  message: string;
  type: ToastType;
  /** Bumped on every show() call so the Toast component can restart its
   * auto-dismiss timer even if the same message is shown twice in a row. */
  key: number;
  show: (message: string, type?: ToastType) => void;
  hide: () => void;
}

export const useToastStore = create<ToastState>((set, get) => ({
  visible: false,
  message: '',
  type: 'success',
  key: 0,

  show: (message, type = 'success') => {
    set({ visible: true, message, type, key: get().key + 1 });
  },

  hide: () => set({ visible: false }),
}));
