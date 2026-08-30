import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { CurrencyOption, LanguageOption, referenceApi } from '../../api/config';
import { useLocaleStore } from '../../store/localeStore';

export default function PreferencesScreen({ navigation }: any) {
  const { language, currency, setLanguage, setCurrency } = useLocaleStore();
  const [languages, setLanguages] = useState<LanguageOption[]>([]);
  const [currencies, setCurrencies] = useState<CurrencyOption[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([referenceApi.languages(), referenceApi.currencies()]).then(([lang, curr]) => {
      setLanguages(lang.data.data);
      setCurrencies(curr.data.data);
    }).finally(() => setLoading(false));
  }, []);

  const activeLanguage = language ?? languages.find(l => l.is_default)?.code;
  const activeCurrency = currency ?? currencies.find(c => c.is_default)?.code;

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Language & Currency</Text>
        <View style={styles.backSpacer} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.content}>
          <Text style={styles.sectionLabel}>Language</Text>
          <View style={styles.optionList}>
            {languages.map(lang => (
              <TouchableOpacity key={lang.code} style={styles.optionRow} onPress={() => setLanguage(lang.code)}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.optionLabel}>{lang.native_name}</Text>
                  <Text style={styles.optionSubLabel}>{lang.name}</Text>
                </View>
                {activeLanguage === lang.code && <IonIcon name="checkmark-circle" size={20} color={COLORS.primary} />}
              </TouchableOpacity>
            ))}
          </View>

          <Text style={styles.sectionLabel}>Currency</Text>
          <View style={styles.optionList}>
            {currencies.map(curr => (
              <TouchableOpacity key={curr.code} style={styles.optionRow} onPress={() => setCurrency(curr.code)}>
                <Text style={styles.optionLabel}>{curr.symbol} {curr.code}</Text>
                {activeCurrency === curr.code && <IonIcon name="checkmark-circle" size={20} color={COLORS.primary} />}
              </TouchableOpacity>
            ))}
          </View>

          <Text style={styles.note}>Changes apply the next time you load a page — pull to refresh if a screen you're already on doesn't update.</Text>
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
