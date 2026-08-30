import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { COLORS } from '../../constants';

export default function SplashScreen() {
  return (
    <View style={styles.container}>
      <Text style={styles.logo}>
        One<Text style={styles.logoAccent}>Market</Text>
      </Text>
      <View style={styles.badge}>
        <Text style={styles.badgeText}>24/7</Text>
      </View>
      <ActivityIndicator size="small" color={COLORS.primary} style={{ marginTop: 24 }} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white, flexDirection: 'row', flexWrap: 'wrap' },
  logo: { fontSize: 30, fontWeight: 'bold', color: COLORS.primary },
  logoAccent: { color: COLORS.text },
  badge: { backgroundColor: COLORS.accent, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3, marginLeft: 8, alignSelf: 'center' },
  badgeText: { color: COLORS.white, fontWeight: 'bold', fontSize: 13 },
});
