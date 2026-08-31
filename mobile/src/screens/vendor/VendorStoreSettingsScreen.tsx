import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { vendorStoreApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { useToastStore } from '../../store/toastStore';

export default function VendorStoreSettingsScreen({ navigation }: any) {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [saved, setSaved] = useState(false);

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [onVacation, setOnVacation] = useState(false);
  const [vacationMessage, setVacationMessage] = useState('');
  const [seoTitle, setSeoTitle] = useState('');
  const [seoDescription, setSeoDescription] = useState('');

  // city/state/country come back as plain display strings, not ids, so
  // there's no country/state/city picker here, just a free-text address
  // field (per the build spec).
  useEffect(() => {
    vendorStoreApi.show()
      .then(res => {
        const store = res.data.data;
        setName(store.name);
        setDescription(store.description ?? '');
        setEmail(store.email ?? '');
        setPhone(store.phone ?? '');
        setAddress(store.address ?? '');
        setOnVacation(store.status === 'vacation');
        setVacationMessage(store.vacation_message ?? '');
      })
      .catch(e => setError(apiErrorMessage(e, 'Could not load your store settings.')))
      .finally(() => setLoading(false));
  }, []);

  const handleSave = async () => {
    if (!name.trim()) { setError('Please enter your store name.'); return; }
    setSaving(true);
    setError('');
    setSaved(false);
    try {
      await vendorStoreApi.update({
        name: name.trim(),
        description: description.trim() || undefined,
        email: email.trim() || undefined,
        phone: phone.trim() || undefined,
        address: address.trim() || undefined,
        status: onVacation ? 'vacation' : 'active',
        vacation_message: onVacation ? (vacationMessage.trim() || undefined) : undefined,
        seo_title: seoTitle.trim() || undefined,
        seo_description: seoDescription.trim() || undefined,
      });
      setSaved(true);
      useToastStore.getState().show('Store settings saved');
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not save your store settings.'));
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Store Settings</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        {error ? <Text style={styles.error}>{error}</Text> : null}
        {saved ? <Text style={styles.saved}>Store settings saved.</Text> : null}

        <Text style={styles.label}>Store Name</Text>
        <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Store name" placeholderTextColor={COLORS.placeholder} />

        <Text style={styles.label}>Description (optional)</Text>
        <TextInput style={[styles.input, styles.textArea]} value={description} onChangeText={setDescription} placeholder="Tell shoppers about your store" placeholderTextColor={COLORS.placeholder} multiline numberOfLines={4} />

        <Text style={styles.label}>Contact Email (optional)</Text>
        <TextInput style={styles.input} value={email} onChangeText={setEmail} placeholder="store@example.com" placeholderTextColor={COLORS.placeholder} keyboardType="email-address" autoCapitalize="none" />

        <Text style={styles.label}>Contact Phone (optional)</Text>
        <TextInput style={styles.input} value={phone} onChangeText={setPhone} placeholder="08012345678" placeholderTextColor={COLORS.placeholder} keyboardType="phone-pad" />

        <Text style={styles.label}>Address (optional)</Text>
        <TextInput style={styles.input} value={address} onChangeText={setAddress} placeholder="Store address" placeholderTextColor={COLORS.placeholder} />

        <View style={styles.switchRow}>
          <View style={{ flex: 1 }}>
            <Text style={styles.label}>On Vacation</Text>
            <Text style={styles.hint}>Hides your store from shoppers temporarily.</Text>
          </View>
          <Switch value={onVacation} onValueChange={setOnVacation} trackColor={{ true: COLORS.warning }} />
        </View>

        {onVacation && (
          <>
            <Text style={styles.label}>Vacation Message (optional)</Text>
            <TextInput style={styles.input} value={vacationMessage} onChangeText={setVacationMessage} placeholder="We'll be back soon!" placeholderTextColor={COLORS.placeholder} />
          </>
        )}

        <Text style={styles.label}>SEO Title (optional)</Text>
        <TextInput style={styles.input} value={seoTitle} onChangeText={setSeoTitle} placeholder="SEO title" placeholderTextColor={COLORS.placeholder} />

        <Text style={styles.label}>SEO Description (optional)</Text>
        <TextInput style={styles.input} value={seoDescription} onChangeText={setSeoDescription} placeholder="SEO description" placeholderTextColor={COLORS.placeholder} />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Save Changes</Text>}
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  error: { color: COLORS.danger, marginBottom: 12, fontSize: 13 },
  saved: { color: COLORS.accent, marginBottom: 12, fontSize: 13 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  hint: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.grayLight },
  textArea: { height: 90, textAlignVertical: 'top' },
  switchRow: { flexDirection: 'row', alignItems: 'center', marginTop: 18 },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 28 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
