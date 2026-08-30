import React from 'react';
import { ActivityIndicator, Image, StyleSheet, Text, View } from 'react-native';
import { COLORS } from '../../constants';

export default function SplashScreen({ logoUrl }: { logoUrl?: string | null }) {
  return (
    <View style={styles.container}>
      {logoUrl ? (
        <Image source={{ uri: logoUrl }} style={styles.logoImage} resizeMode="contain" />
      ) : (
        <View style={styles.logoRow}>
          <Text style={styles.logo}>
            One<Text style={styles.logoAccent}>Market</Text>
          </Text>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>24/7</Text>
          </View>
        </View>
      )}
      <ActivityIndicator size="small" color={COLORS.primary} style={styles.loader} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  logoRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'center' },
  logoImage: { width: 160, height: 80 },
  logo: { fontSize: 30, fontWeight: 'bold', color: COLORS.primary },
  logoAccent: { color: COLORS.text },
  badge: { backgroundColor: COLORS.accent, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3, marginLeft: 8, alignSelf: 'center' },
  badgeText: { color: COLORS.white, fontWeight: 'bold', fontSize: 13 },
  loader: { marginTop: 24 },
});
