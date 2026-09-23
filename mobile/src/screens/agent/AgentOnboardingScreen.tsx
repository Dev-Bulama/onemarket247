import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Image, Modal, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { agentApplicationApi, AgentApplicationReceipt, PickedFile } from '../../api/agent';
import { referenceApi } from '../../api/config';
import { apiErrorMessage } from '../../api/client';
import { pickDocumentFile, takeDocumentPhoto } from '../../utils/documentPicker';
import { City, Country, State } from '../../types';
import { useToastStore } from '../../store/toastStore';

interface FormState {
  fullName: string;
  email: string;
  phone: string;
  countryId: number | null;
  stateId: number | null;
  cityId: number | null;
  postalCode: string;
  address: string;
  identityType: string;
  identityNumber: string;
  notes: string;
  terms: boolean;
}

const INITIAL: FormState = {
  fullName: '', email: '', phone: '', countryId: null, stateId: null, cityId: null,
  postalCode: '', address: '', identityType: '', identityNumber: '', notes: '', terms: false,
};

export default function AgentOnboardingScreen({ navigation }: any) {
  const [form, setForm] = useState<FormState>(INITIAL);
  const set = <K extends keyof FormState>(key: K, value: FormState[K]) => setForm(prev => ({ ...prev, [key]: value }));

  const [countries, setCountries] = useState<Country[]>([]);
  const [states, setStates] = useState<State[]>([]);
  const [cities, setCities] = useState<City[]>([]);
  const [pickerFor, setPickerFor] = useState<'country' | 'state' | 'city' | null>(null);

  const [identityDoc, setIdentityDoc] = useState<PickedFile | null>(null);
  const [addressDoc, setAddressDoc] = useState<PickedFile | null>(null);

  const [submitting, setSubmitting] = useState(false);
  const [receipt, setReceipt] = useState<AgentApplicationReceipt | null>(null);

  useEffect(() => {
    referenceApi.countries().then(res => setCountries(res.data.data)).catch(() => {});
  }, []);

  useEffect(() => {
    if (form.countryId) referenceApi.states(form.countryId).then(res => setStates(res.data.data)).catch(() => {});
    else setStates([]);
  }, [form.countryId]);

  useEffect(() => {
    if (form.stateId) referenceApi.cities(form.stateId).then(res => setCities(res.data.data)).catch(() => {});
    else setCities([]);
  }, [form.stateId]);

  const pickFile = async (onPicked: (file: PickedFile) => void) => {
    try {
      const file = await pickDocumentFile();
      if (file) onPicked(file);
    } catch {
      useToastStore.getState().show('Could not open the file picker. Please try again.', 'error');
    }
  };

  const takePhoto = async (onPicked: (file: PickedFile) => void) => {
    const file = await takeDocumentPhoto();
    if (file) onPicked(file);
  };

  const validate = (): string => {
    if (!form.fullName.trim() || !form.email.trim()) return 'Please fill in your name and email.';
    if (!identityDoc) return 'Please add a photo of your identity document.';
    if (!form.terms) return 'You must accept the agent terms to continue.';
    return '';
  };

  const handleSubmit = async () => {
    const validationError = validate();
    if (validationError) { useToastStore.getState().show(validationError, 'error'); return; }
    setSubmitting(true);
    try {
      const res = await agentApplicationApi.apply({
        full_name: form.fullName.trim(),
        email: form.email.trim(),
        phone: form.phone.trim() || undefined,
        country_id: form.countryId ?? undefined,
        state_id: form.stateId ?? undefined,
        city_id: form.cityId ?? undefined,
        postal_code: form.postalCode.trim() || undefined,
        address: form.address.trim() || undefined,
        identity_type: form.identityType.trim() || undefined,
        identity_number: form.identityNumber.trim() || undefined,
        notes: form.notes.trim() || undefined,
        identity_document: identityDoc!,
        proof_of_address_document: addressDoc ?? undefined,
        terms: form.terms,
      });
      setReceipt(res.data.data);
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not submit your application. Please try again.'), 'error');
    } finally {
      setSubmitting(false);
    }
  };

  if (receipt) {
    return (
      <View style={styles.flex}>
        <View style={styles.confirmContainer}>
          <View style={styles.confirmIconBox}>
            <IonIcon name="time-outline" size={48} color={COLORS.warning} />
          </View>
          <Text style={styles.confirmTitle}>Application Submitted</Text>
          <Text style={styles.confirmMessage}>We'll review your application and email you once a decision is made.</Text>
          <View style={styles.confirmRefPill}>
            <Text style={styles.confirmRefLabel}>Reference</Text>
            <Text style={styles.confirmRefValue}>{receipt.reference_number}</Text>
          </View>
          <TouchableOpacity style={styles.primaryBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.primaryBtnText}>Done</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const selectedCountry = countries.find(c => c.id === form.countryId);
  const selectedState = states.find(s => s.id === form.stateId);
  const selectedCity = cities.find(c => c.id === form.cityId);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Become an Agent</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Text style={styles.intro}>
          OneMarket247 field agents help customers apply to become vendors. Once approved, you'll
          appear in the "Registered Agent" list vendors can pick from when they apply.
        </Text>

        <Field label="Full Name" value={form.fullName} onChangeText={t => set('fullName', t)} placeholder="Jane Agent" />
        <Field label="Email" value={form.email} onChangeText={t => set('email', t)} placeholder="jane@example.com" keyboardType="email-address" autoCapitalize="none" />
        <Field label="Phone (optional)" value={form.phone} onChangeText={t => set('phone', t)} placeholder="08012345678" keyboardType="phone-pad" />

        <Text style={styles.label}>Country (optional)</Text>
        <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('country')}>
          <Text style={selectedCountry ? styles.selectValue : styles.selectPlaceholder}>{selectedCountry?.name ?? 'Select Country'}</Text>
          <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
        </TouchableOpacity>

        {form.countryId && (
          <>
            <Text style={styles.label}>State (optional)</Text>
            <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('state')}>
              <Text style={selectedState ? styles.selectValue : styles.selectPlaceholder}>{selectedState?.name ?? 'Select State'}</Text>
              <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
            </TouchableOpacity>
          </>
        )}

        {form.stateId && (
          <>
            <Text style={styles.label}>City (optional)</Text>
            <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('city')}>
              <Text style={selectedCity ? styles.selectValue : styles.selectPlaceholder}>{selectedCity?.name ?? 'Select City'}</Text>
              <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
            </TouchableOpacity>
          </>
        )}

        <Field label="Address (optional)" value={form.address} onChangeText={t => set('address', t)} placeholder="15, Adeola Odeku Street" />
        <Field label="Postal Code (optional)" value={form.postalCode} onChangeText={t => set('postalCode', t)} placeholder="100001" />
        <Field label="ID Type (optional)" value={form.identityType} onChangeText={t => set('identityType', t)} placeholder="National ID, Passport…" />
        <Field label="ID Number (optional)" value={form.identityNumber} onChangeText={t => set('identityNumber', t)} placeholder="A1234567" />

        <Text style={styles.label}>Notes (optional — areas you cover, experience, etc.)</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          placeholderTextColor={COLORS.placeholder}
          value={form.notes}
          onChangeText={t => set('notes', t)}
          multiline
          numberOfLines={3}
        />

        <DocumentPicker label="Identity Document" required file={identityDoc} onPickFile={() => pickFile(setIdentityDoc)} onTakePhoto={() => takePhoto(setIdentityDoc)} onRemove={() => setIdentityDoc(null)} />
        <DocumentPicker label="Proof of Address (optional)" file={addressDoc} onPickFile={() => pickFile(setAddressDoc)} onTakePhoto={() => takePhoto(setAddressDoc)} onRemove={() => setAddressDoc(null)} />

        <View style={styles.termsRow}>
          <Switch value={form.terms} onValueChange={v => set('terms', v)} trackColor={{ true: COLORS.primary }} />
          <Text style={styles.termsText}>I accept the agent terms and conditions</Text>
        </View>

        <TouchableOpacity style={styles.primaryBtn} onPress={handleSubmit} disabled={submitting}>
          {submitting ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.primaryBtnText}>Submit Application</Text>}
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
                    if (pickerFor === 'country') { set('countryId', opt.id); set('stateId', null); set('cityId', null); }
                    if (pickerFor === 'state') { set('stateId', opt.id); set('cityId', null); }
                    if (pickerFor === 'city') set('cityId', opt.id);
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

function Field(props: {
  label: string;
  value: string;
  onChangeText: (t: string) => void;
  placeholder?: string;
  keyboardType?: 'default' | 'email-address' | 'phone-pad' | 'number-pad' | 'url';
  autoCapitalize?: 'none' | 'sentences' | 'words';
}) {
  return (
    <>
      <Text style={styles.label}>{props.label}</Text>
      <TextInput
        style={styles.input}
        value={props.value}
        onChangeText={props.onChangeText}
        placeholder={props.placeholder}
        placeholderTextColor={COLORS.placeholder}
        keyboardType={props.keyboardType}
        autoCapitalize={props.autoCapitalize}
      />
    </>
  );
}

function DocumentPicker({ label, required, file, onPickFile, onTakePhoto, onRemove }: {
  label: string;
  required?: boolean;
  file: PickedFile | null;
  onPickFile: () => void;
  onTakePhoto: () => void;
  onRemove: () => void;
}) {
  const isImage = file?.type?.startsWith('image/');
  return (
    <View style={{ marginBottom: 16 }}>
      <Text style={styles.label}>{label}{required ? ' *' : ''}</Text>
      {file ? (
        <View style={styles.docPreviewBox}>
          {isImage ? (
            <Image source={{ uri: file.uri }} style={styles.docPreviewImage} />
          ) : (
            <View style={[styles.docPreviewImage, styles.docPreviewFile]}>
              <IonIcon name="document-text-outline" size={28} color={COLORS.textSecondary} />
              <Text style={styles.docFileName} numberOfLines={2}>{file.name}</Text>
            </View>
          )}
          <TouchableOpacity style={styles.docRemoveBtn} onPress={onRemove}>
            <IonIcon name="close-circle" size={20} color={COLORS.danger} />
          </TouchableOpacity>
        </View>
      ) : (
        <View style={styles.docPickRow}>
          <TouchableOpacity style={styles.docPickBtn} onPress={onPickFile}>
            <IonIcon name="document-outline" size={20} color={COLORS.textSecondary} />
            <Text style={styles.docPickText}>Choose PDF or Image</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.docPickBtn} onPress={onTakePhoto}>
            <IonIcon name="camera-outline" size={20} color={COLORS.textSecondary} />
            <Text style={styles.docPickText}>Take Photo</Text>
          </TouchableOpacity>
        </View>
      )}
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
  intro: { fontSize: 12, color: COLORS.textSecondary, lineHeight: 18, marginBottom: 16 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  textArea: { height: 80, textAlignVertical: 'top' },
  selectInput: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 12, backgroundColor: COLORS.grayLight },
  selectValue: { fontSize: 13, color: COLORS.text },
  selectPlaceholder: { fontSize: 13, color: COLORS.placeholder },
  docPickRow: { flexDirection: 'row', gap: 8 },
  docPickBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, borderWidth: 1, borderColor: COLORS.border, borderStyle: 'dashed', borderRadius: SIZES.borderRadiusSm, padding: 12, backgroundColor: COLORS.grayLight },
  docPickText: { fontSize: 12, color: COLORS.textSecondary, textAlign: 'center' },
  docPreviewBox: { position: 'relative', width: 100, height: 100 },
  docPreviewImage: { width: 100, height: 100, borderRadius: SIZES.borderRadiusSm },
  docPreviewFile: { alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.grayLight, padding: 6 },
  docFileName: { fontSize: 9, color: COLORS.textSecondary, textAlign: 'center', marginTop: 4 },
  docRemoveBtn: { position: 'absolute', top: -8, right: -8, backgroundColor: COLORS.white, borderRadius: 12 },
  termsRow: { flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: 20 },
  termsText: { flex: 1, fontSize: 13, color: COLORS.text },
  primaryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 28 },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  pickerRow: { paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  confirmContainer: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  confirmIconBox: { width: 96, height: 96, borderRadius: 48, alignItems: 'center', justifyContent: 'center', marginBottom: SIZES.lg, backgroundColor: `${COLORS.warning}1A` },
  confirmTitle: { fontSize: 18, fontWeight: 'bold', color: COLORS.text, marginBottom: 8, textAlign: 'center' },
  confirmMessage: { fontSize: 14, color: COLORS.textSecondary, textAlign: 'center', lineHeight: 20 },
  confirmRefPill: {
    flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: COLORS.grayLight,
    borderRadius: 999, paddingHorizontal: 14, paddingVertical: 6, marginTop: 14,
  },
  confirmRefLabel: { fontSize: 11, color: COLORS.textMuted, fontWeight: '600' },
  confirmRefValue: { fontSize: 12, color: COLORS.text, fontWeight: '700' },
});
