import React from 'react';
import { Linking, Platform, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';

/**
 * Shown instead of the whole app when App\Models\AppSetting's
 * min_app_version (Admin → Settings → App Settings) is newer than this
 * build's APP_VERSION (see bootstrapStore.ts) — no way to dismiss it,
 * matching how most marketplace apps force a hard update rather than
 * risk an old client hitting a backend that's moved on.
 */
export default function ForceUpdateScreen() {
  const storeUrl = Platform.OS === 'ios'
    ? 'itms-apps://apps.apple.com/'
    : 'market://details?id=com.onemarket247';

  return (
    <View style={styles.container}>
      <View style={styles.iconBox}>
        <IonIcon name="cloud-download-outline" size={48} color={COLORS.primary} />
      </View>
      <Text style={styles.title}>Update Required</Text>
      <Text style={styles.message}>A new version of OneMarket 24/7 is available with important updates. Please update to continue.</Text>
      <TouchableOpacity style={styles.button} onPress={() => Linking.openURL(storeUrl).catch(() => {})}>
        <Text style={styles.buttonText}>Update Now</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white, padding: SIZES.xxl },
  iconBox: { width: 88, height: 88, borderRadius: 44, backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center', marginBottom: SIZES.lg },
  title: { fontSize: 20, fontWeight: 'bold', color: COLORS.text, marginBottom: 10 },
  message: { fontSize: 14, color: COLORS.textSecondary, textAlign: 'center', marginBottom: SIZES.xl, lineHeight: 20 },
  button: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, paddingHorizontal: 40 },
  buttonText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
