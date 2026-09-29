import { create } from 'zustand';
import { Platform, PermissionsAndroid } from 'react-native';
import Geolocation from '@react-native-community/geolocation';
import { locationApi } from '../api/location';

const PING_INTERVAL_MS = 5 * 60 * 1000; // 5 minutes

/**
 * Priority 8's live location sharing, from the mobile app — a real GPS
 * fix sent to the server, but only ever while this app is open and in the
 * foreground. There is deliberately no background service wired up here:
 * this codebase has no way to build or verify native Android/iOS
 * background-location code in its sandbox, so shipping one untested was
 * judged a worse risk than not having one. Enabling sharing here starts a
 * plain JS interval that takes a fresh fix and posts it every five
 * minutes for as long as the app stays open — closing or backgrounding
 * the app for long stops it, same as any other JS timer.
 */
interface LocationState {
  enabled: boolean;
  tracking: boolean;
  /** Call once, e.g. when a settings screen mounts, with whatever the
   * profile API last reported — starts the ping loop immediately if
   * already enabled from a previous session. */
  hydrate: (enabled: boolean) => void;
  toggle: (enabled: boolean) => Promise<void>;
  stop: () => void;
}

let intervalId: ReturnType<typeof setInterval> | null = null;

async function requestPermission(): Promise<boolean> {
  if (Platform.OS !== 'android') return true;

  try {
    const granted = await PermissionsAndroid.request(PermissionsAndroid.PERMISSIONS.ACCESS_FINE_LOCATION);
    return granted === PermissionsAndroid.RESULTS.GRANTED;
  } catch {
    return false;
  }
}

function sendCurrentPosition() {
  Geolocation.getCurrentPosition(
    (position: { coords: { latitude: number; longitude: number; accuracy: number } }) => {
      locationApi.ping(position.coords.latitude, position.coords.longitude, position.coords.accuracy).catch(() => {});
    },
    () => {},
    { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 },
  );
}

function startTracking() {
  if (intervalId) return;
  sendCurrentPosition();
  intervalId = setInterval(sendCurrentPosition, PING_INTERVAL_MS);
}

function stopTracking() {
  if (intervalId) {
    clearInterval(intervalId);
    intervalId = null;
  }
}

export const useLocationStore = create<LocationState>((set) => ({
  enabled: false,
  tracking: false,

  hydrate: (enabled: boolean) => {
    set({ enabled });
    if (enabled) {
      requestPermission().then(granted => {
        if (granted) {
          startTracking();
          set({ tracking: true });
        }
      });
    }
  },

  toggle: async (nextEnabled: boolean) => {
    if (nextEnabled) {
      const granted = await requestPermission();
      if (!granted) return;
    }

    await locationApi.updateConsent(nextEnabled);
    set({ enabled: nextEnabled });

    if (nextEnabled) {
      startTracking();
      set({ tracking: true });
    } else {
      stopTracking();
      set({ tracking: false });
    }
  },

  stop: () => {
    stopTracking();
    set({ tracking: false });
  },
}));
