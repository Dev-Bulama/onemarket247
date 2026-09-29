import React, { useEffect } from 'react';
import { StyleSheet, Switch, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useAuthStore } from '../../store/authStore';
import { useLocationStore } from '../../store/locationStore';

export default function LocationSharingScreen({ navigation }: any) {
  const { user } = useAuthStore();
  const { enabled, toggle, hydrate } = useLocationStore();

  useEffect(() => {
    hydrate(user?.location_sharing_enabled ?? false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Location Sharing</Text>
        <View style={styles.backSpacer} />
      </View>

      <View style={styles.content}>
        <View style={styles.switchRow}>
          <View style={{ flex: 1 }}>
            <Text style={styles.label}>Share Live Location</Text>
            <Text style={styles.hint}>
              Lets OneMarket247 admins see your live location while you have the app open — for example, to help
              coordinate a delivery. You can turn this off anytime.
            </Text>
          </View>
          <Switch value={enabled} onValueChange={toggle} trackColor={{ true: COLORS.primary }} />
        </View>

        <Text style={styles.note}>
          Location is only sent while OneMarket 24/7 is open on your device — it is never tracked in the background.
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  backSpacer: { width: 22 },
  content: { padding: SIZES.screenPadding },
  switchRow: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius,
    padding: 14, marginTop: 16,
  },
  label: { fontSize: 14, fontWeight: '600', color: COLORS.text, marginBottom: 4 },
  hint: { fontSize: 12, color: COLORS.textMuted, lineHeight: 17 },
  note: { fontSize: 11, color: COLORS.textMuted, marginTop: 16, textAlign: 'center', lineHeight: 16 },
});
