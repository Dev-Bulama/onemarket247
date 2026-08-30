import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { COLORS } from '../constants';

// Small colored pill used by every vendor screen's status column (order,
// product, document, withdrawal, staff, subscription status) — takes the
// {label, color} pair straight out of the maps in src/constants/index.ts.
export default function StatusBadge({ label, color }: { label: string; color: string }) {
  return (
    <View style={[styles.badge, { backgroundColor: `${color}1A`, borderColor: color }]}>
      <Text style={[styles.text, { color }]} numberOfLines={1}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    alignSelf: 'flex-start',
    borderWidth: 1,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  text: { fontSize: 10, fontWeight: '700' },
});

export const FALLBACK_STATUS_COLOR = COLORS.textSecondary;
