import React, { useEffect, useRef } from 'react';
import { Animated, StyleSheet, Text } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../constants';
import { useToastStore } from '../store/toastStore';

const AUTO_DISMISS_MS = 2800;

const ICON: Record<string, string> = {
  success: 'checkmark-circle',
  error: 'close-circle',
  info: 'information-circle',
};

const COLOR: Record<string, string> = {
  success: COLORS.accent,
  error: COLORS.danger,
  info: COLORS.info,
};

/**
 * Mounted once at the app root (see App.tsx) so it can pop up over any
 * screen — triggered from anywhere via useToastStore.getState().show(),
 * not tied to any one screen's component tree. Plain Animated (no new
 * dependency) to avoid another native rebuild for something this small.
 */
export default function Toast() {
  const { visible, message, type, key, hide } = useToastStore();
  const translateY = useRef(new Animated.Value(-100)).current;
  const insets = useSafeAreaInsets();

  useEffect(() => {
    if (!visible) return;

    Animated.spring(translateY, { toValue: 0, useNativeDriver: true, bounciness: 6 }).start();

    const timer = setTimeout(() => {
      Animated.timing(translateY, { toValue: -100, duration: 200, useNativeDriver: true }).start(() => hide());
    }, AUTO_DISMISS_MS);

    return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key, visible]);

  if (!visible) return null;

  return (
    <Animated.View style={[styles.container, { top: insets.top + 8, transform: [{ translateY }] }]}>
      <IonIcon name={ICON[type]} size={20} color={COLOR[type]} />
      <Text style={styles.text} numberOfLines={2}>{message}</Text>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    position: 'absolute', left: SIZES.screenPadding, right: SIZES.screenPadding, zIndex: 999,
    flexDirection: 'row', alignItems: 'center', gap: 10,
    backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14,
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.15, shadowRadius: 10, elevation: 8,
  },
  text: { flex: 1, fontSize: 13, fontWeight: '600', color: COLORS.text },
});
