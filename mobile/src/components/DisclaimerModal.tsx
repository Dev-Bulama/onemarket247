import React from 'react';
import { Modal, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { COLORS, SIZES } from '../constants';
import { useDisclaimerStore } from '../store/disclaimerStore';

/**
 * Mounted once at the app root (see App.tsx), same pattern as Toast —
 * whichever disclaimer is currently loaded into useDisclaimerStore (the
 * general site-wide one, or a screen's own checkpoint one) pops up over
 * everything until accepted/dismissed. See disclaimerStore.ts for when
 * each screen loads one.
 */
export default function DisclaimerModal() {
  const { current, accept, dismissWithoutAccepting } = useDisclaimerStore();

  if (!current) return null;

  return (
    <Modal visible transparent animationType="fade" statusBarTranslucent>
      <View style={styles.overlay}>
        <View style={styles.card}>
          <Text style={styles.title}>{current.title}</Text>
          <ScrollView style={styles.contentScroll}>
            <Text style={styles.content}>{current.content}</Text>
          </ScrollView>
          <TouchableOpacity
            style={styles.button}
            onPress={current.requires_acceptance ? accept : dismissWithoutAccepting}
          >
            <Text style={styles.buttonText}>{current.requires_acceptance ? 'Accept & Continue' : 'Got it'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: { flex: 1, backgroundColor: COLORS.overlay, alignItems: 'center', justifyContent: 'center', padding: SIZES.screenPadding },
  card: { width: '100%', maxWidth: 420, maxHeight: '80%', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: SIZES.lg },
  title: { fontSize: 17, fontWeight: 'bold', color: COLORS.text, marginBottom: SIZES.sm },
  contentScroll: { maxHeight: 260, marginBottom: SIZES.lg },
  content: { fontSize: 14, color: COLORS.textSecondary, lineHeight: 20 },
  button: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, paddingVertical: 14, alignItems: 'center' },
  buttonText: { color: COLORS.white, fontSize: 14, fontWeight: '600' },
});
