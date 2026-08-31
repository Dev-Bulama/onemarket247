import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Modal, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { addressesApi } from '../../api/addresses';
import { referenceApi } from '../../api/config';
import { apiErrorMessage } from '../../api/client';
import { City, Country, State } from '../../types';
import { useToastStore } from '../../store/toastStore';

export default function AddAddressScreen({ navigation }: any) {
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [addressLine1, setAddressLine1] = useState('');
  const [addressLine2, setAddressLine2] = useState('');
  const [postalCode, setPostalCode] = useState('');
  const [isDefault, setIsDefault] = useState(true);

  const [countries, setCountries] = useState<Country[]>([]);
  const [states, setStates] = useState<State[]>([]);
  const [cities, setCities] = useState<City[]>([]);
  const [countryId, setCountryId] = useState<number | null>(null);
  const [stateId, setStateId] = useState<number | null>(null);
  const [cityId, setCityId] = useState<number | null>(null);
  const [pickerFor, setPickerFor] = useState<'country' | 'state' | 'city' | null>(null);

  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    referenceApi.countries().then(res => setCountries(res.data.data)).catch(() => {});
  }, []);

  useEffect(() => {
    if (countryId) referenceApi.states(countryId).then(res => setStates(res.data.data)).catch(() => {});
    else setStates([]);
    setStateId(null);
  }, [countryId]);

  useEffect(() => {
    if (stateId) referenceApi.cities(stateId).then(res => setCities(res.data.data)).catch(() => {});
    else setCities([]);
    setCityId(null);
  }, [stateId]);

  const handleSave = async () => {
    if (!fullName || !addressLine1 || !countryId) {
      setError('Please fill in your name, address, and country.');
      return;
    }
    setSaving(true);
    setError('');
    try {
      await addressesApi.store({
        full_name: fullName,
        phone: phone || undefined,
        address_line_1: addressLine1,
        address_line_2: addressLine2 || undefined,
        country_id: countryId,
        state_id: stateId ?? undefined,
        city_id: cityId ?? undefined,
        postal_code: postalCode || undefined,
        is_default_shipping: isDefault,
        is_default_billing: isDefault,
      });
      useToastStore.getState().show('Address saved');
      navigation.goBack();
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not save this address.'));
    } finally {
      setSaving(false);
    }
  };

  const selectedCountry = countries.find(c => c.id === countryId);
  const selectedState = states.find(s => s.id === stateId);
  const selectedCity = cities.find(c => c.id === cityId);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Add New Address</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Full Name</Text>
        <TextInput style={styles.input} value={fullName} onChangeText={setFullName} placeholder="Jane Doe" placeholderTextColor={COLORS.placeholder} />

        <Text style={styles.label}>Phone</Text>
        <TextInput style={styles.input} value={phone} onChangeText={setPhone} placeholder="08012345678" placeholderTextColor={COLORS.placeholder} keyboardType="phone-pad" />

        <Text style={styles.label}>Address Line 1</Text>
        <TextInput style={styles.input} value={addressLine1} onChangeText={setAddressLine1} placeholder="15, Adeola Odeku Street" placeholderTextColor={COLORS.placeholder} />

        <Text style={styles.label}>Address Line 2 (optional)</Text>
        <TextInput style={styles.input} value={addressLine2} onChangeText={setAddressLine2} placeholder="Apartment, suite, etc." placeholderTextColor={COLORS.placeholder} />

        <Text style={styles.label}>Country</Text>
        <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('country')}>
          <Text style={selectedCountry ? styles.selectValue : styles.selectPlaceholder}>{selectedCountry?.name ?? 'Select Country'}</Text>
          <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
        </TouchableOpacity>

        {countryId && (
          <>
            <Text style={styles.label}>State</Text>
            <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('state')}>
              <Text style={selectedState ? styles.selectValue : styles.selectPlaceholder}>{selectedState?.name ?? 'Select State (optional)'}</Text>
              <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
            </TouchableOpacity>
          </>
        )}

        {stateId && (
          <>
            <Text style={styles.label}>City</Text>
            <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('city')}>
              <Text style={selectedCity ? styles.selectValue : styles.selectPlaceholder}>{selectedCity?.name ?? 'Select City (optional)'}</Text>
              <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
            </TouchableOpacity>
          </>
        )}

        <Text style={styles.label}>Postal Code (optional)</Text>
        <TextInput style={styles.input} value={postalCode} onChangeText={setPostalCode} placeholder="100001" placeholderTextColor={COLORS.placeholder} />

        <View style={styles.defaultRow}>
          <Text style={styles.label}>Set as default address</Text>
          <Switch value={isDefault} onValueChange={setIsDefault} trackColor={{ true: COLORS.primary }} />
        </View>

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Save Address</Text>}
        </TouchableOpacity>
      </ScrollView>

      <Modal visible={!!pickerFor} transparent animationType="slide" onRequestClose={() => setPickerFor(null)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setPickerFor(null)}>
          <View style={styles.sheet}>
            <ScrollView style={{ maxHeight: 360 }}>
              {(pickerFor === 'country' ? countries : pickerFor === 'state' ? states : cities).map((opt: any) => (
                <TouchableOpacity
                  key={opt.id}
                  style={styles.pickerRow}
                  onPress={() => {
                    if (pickerFor === 'country') setCountryId(opt.id);
                    if (pickerFor === 'state') setStateId(opt.id);
                    if (pickerFor === 'city') setCityId(opt.id);
                    setPickerFor(null);
                  }}
                >
                  <Text style={styles.selectValue}>{opt.name}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  error: { color: COLORS.danger, marginBottom: 12, fontSize: 13 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.grayLight },
  selectInput: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 12, backgroundColor: COLORS.grayLight },
  selectValue: { fontSize: 13, color: COLORS.text },
  selectPlaceholder: { fontSize: 13, color: COLORS.placeholder },
  defaultRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 18 },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 24 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  pickerRow: { paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
});
