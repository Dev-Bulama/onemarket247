import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { CurrencyOption, referenceApi } from '../../api/config';
import { useLocaleStore } from '../../store/localeStore';

// The language picker is hidden for now — there's no translated UI content
// anywhere yet (web or mobile), so picking a non-English language changed
// nothing visible. Currency switching is real (server-side conversion) and
// stays. Re-add the language section once real translations exist — see
// useLocaleStore's setLanguage()/referenceApi.languages(), both still here
// and functional, just not surfaced in this screen.
export default function PreferencesScreen({ navigation }: any) {
  const { currency, setCurrency } = useLocaleStore();
  const [currencies, setCurrencies] = useState<CurrencyOption[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    referenceApi.currencies().then(res => setCurrencies(res.data.data)).finally(() => setLoading(false));
  }, []);

  const activeCurrency = currency ?? currencies.find(c => c.is_default)?.code;

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Currency</Text>
        <View style={styles.backSpacer} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.content}>
          <Text style={styles.sectionLabel}>Currency</Text>
          <View style={styles.optionList}>
            {currencies.map(curr => (
              <TouchableOpacity key={curr.code} style={styles.optionRow} onPress={() => setCurrency(curr.code)}>
                <Text style={styles.optionLabel}>{curr.symbol} {curr.code}</Text>
                {activeCurrency === curr.code && <IonIcon name="checkmark-circle" size={20} color={COLORS.primary} />}
              </TouchableOpacity>
            ))}
          </View>
        </ScrollView>
      )}
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
  loader: { marginTop: 40 },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  sectionLabel: { fontSize: 12, fontWeight: '700', color: COLORS.textMuted, textTransform: 'uppercase', marginBottom: 8, marginTop: 16 },
  optionList: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, overflow: 'hidden' },
  optionRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 14, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  optionLabel: { fontSize: 14, color: COLORS.text, fontWeight: '600' },
  optionSubLabel: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  note: { fontSize: 11, color: COLORS.textMuted, marginTop: 20, textAlign: 'center', lineHeight: 16 },
});
