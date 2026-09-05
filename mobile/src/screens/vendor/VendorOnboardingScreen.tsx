import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Image, Modal, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { vendorApplicationApi, PickedFile } from '../../api/vendor';
import { referenceApi } from '../../api/config';
import { apiErrorMessage } from '../../api/client';
import { pickDocumentFile, takeDocumentPhoto } from '../../utils/documentPicker';
import { City, Country, State } from '../../types';
import { VendorApplicationReceipt } from '../../types/vendor';
import { useToastStore } from '../../store/toastStore';

const STEPS = ['Business', 'Store', 'Banking', 'Documents'] as const;

interface FormState {
  fullName: string;
  email: string;
  phone: string;
  businessName: string;
  registrationNumber: string;
  taxIdentificationNumber: string;
  agentIdNumber: string;
  agentFullName: string;
  agentPhone: string;
  storeName: string;
  storeCategory: string;
  storeDescription: string;
  countryId: number | null;
  stateId: number | null;
  cityId: number | null;
  postalCode: string;
  address: string;
  website: string;
  bankName: string;
  bankAccountName: string;
  bankAccountNumber: string;
  terms: boolean;
}

const INITIAL: FormState = {
  fullName: '', email: '', phone: '', businessName: '', registrationNumber: '', taxIdentificationNumber: '',
  agentIdNumber: '', agentFullName: '', agentPhone: '',
  storeName: '', storeCategory: '', storeDescription: '', countryId: null, stateId: null, cityId: null,
  postalCode: '', address: '', website: '',
  bankName: '', bankAccountName: '', bankAccountNumber: '',
  terms: false,
};

export default function VendorOnboardingScreen({ navigation }: any) {
  const [step, setStep] = useState(0);
  const [form, setForm] = useState<FormState>(INITIAL);
  const set = <K extends keyof FormState>(key: K, value: FormState[K]) => setForm(prev => ({ ...prev, [key]: value }));

  const [countries, setCountries] = useState<Country[]>([]);
  const [states, setStates] = useState<State[]>([]);
  const [cities, setCities] = useState<City[]>([]);
  const [pickerFor, setPickerFor] = useState<'country' | 'state' | 'city' | null>(null);

  const [identityDoc, setIdentityDoc] = useState<PickedFile | null>(null);
  const [businessDoc, setBusinessDoc] = useState<PickedFile | null>(null);
  const [taxDoc, setTaxDoc] = useState<PickedFile | null>(null);

  const [submitting, setSubmitting] = useState(false);
  const [receipt, setReceipt] = useState<VendorApplicationReceipt | null>(null);
  const [receiptMessage, setReceiptMessage] = useState('');

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

  const validateStep = (): string => {
    if (step === 0) {
      if (!form.fullName.trim() || !form.email.trim() || !form.businessName.trim()) {
        return 'Please fill in your name, email, and business name.';
      }
    }
    if (step === 1 && !form.storeName.trim()) {
      return 'Please enter your store name.';
    }
    if (step === 2) {
      if (!form.bankName.trim() || !form.bankAccountName.trim() || !form.bankAccountNumber.trim()) {
        return 'Please fill in all bank details.';
      }
    }
    if (step === 3) {
      if (!identityDoc || !businessDoc) return 'Please add a photo of your ID and business registration document.';
      if (!form.terms) return 'You must accept the vendor terms to continue.';
    }
    return '';
  };

  const goNext = () => {
    const validationError = validateStep();
    if (validationError) { useToastStore.getState().show(validationError, 'error'); return; }
    setStep(prev => Math.min(prev + 1, STEPS.length - 1));
  };

  const goBack = () => {
    if (step === 0) { navigation.goBack(); return; }
    setStep(prev => prev - 1);
  };

  const handleSubmit = async () => {
    const validationError = validateStep();
    if (validationError) { useToastStore.getState().show(validationError, 'error'); return; }
    setSubmitting(true);
    try {
      const res = await vendorApplicationApi.apply({
        full_name: form.fullName.trim(),
        email: form.email.trim(),
        phone: form.phone.trim() || undefined,
        business_name: form.businessName.trim(),
        registration_number: form.registrationNumber.trim() || undefined,
        tax_identification_number: form.taxIdentificationNumber.trim() || undefined,
        agent_id_number: form.agentIdNumber.trim() || undefined,
        agent_full_name: form.agentFullName.trim() || undefined,
        agent_phone: form.agentPhone.trim() || undefined,
        store_name: form.storeName.trim(),
        store_category: form.storeCategory.trim() || undefined,
        store_description: form.storeDescription.trim() || undefined,
        country_id: form.countryId ?? undefined,
        state_id: form.stateId ?? undefined,
        city_id: form.cityId ?? undefined,
        postal_code: form.postalCode.trim() || undefined,
        address: form.address.trim() || undefined,
        website: form.website.trim() || undefined,
        bank_name: form.bankName.trim(),
        bank_account_name: form.bankAccountName.trim(),
        bank_account_number: form.bankAccountNumber.trim(),
        identity_document: identityDoc!,
        business_registration_document: businessDoc!,
        tax_certificate_document: taxDoc ?? undefined,
        terms: form.terms,
      });
      setReceipt(res.data.data);
      setReceiptMessage(res.data.message ?? 'Your application has been submitted.');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not submit your application. Please try again.'), 'error');
    } finally {
      setSubmitting(false);
    }
  };

  if (receipt) {
    const approved = receipt.status === 'approved';
    return (
      <View style={styles.flex}>
        <View style={styles.confirmContainer}>
          <View style={[styles.confirmIconBox, { backgroundColor: approved ? `${COLORS.accent}1A` : `${COLORS.warning}1A` }]}>
            <IonIcon name={approved ? 'checkmark-circle' : 'time-outline'} size={48} color={approved ? COLORS.accent : COLORS.warning} />
          </View>
          <Text style={styles.confirmTitle}>{approved ? 'Application Approved' : 'Application Submitted'}</Text>
          <Text style={styles.confirmMessage}>{receiptMessage}</Text>
          <View style={styles.confirmRefPill}>
            <Text style={styles.confirmRefLabel}>Reference</Text>
            <Text style={styles.confirmRefValue}>#{receipt.id}</Text>
          </View>

          {!approved && (
            <View style={styles.nextStepsBox}>
              <Text style={styles.nextStepsHeading}>What happens next</Text>
              <NextStep icon="document-text-outline" text="Our team reviews your application and documents." />
              <NextStep icon="mail-outline" text="You'll get an email as soon as a decision is made." />
              <NextStep icon="storefront-outline" text="Once approved, you can set up your store and start listing products." />
            </View>
          )}

          <TouchableOpacity style={styles.primaryBtn} onPress={() => navigation.goBack()}>
            <Text style={styles.primaryBtnText}>{approved ? 'Go to Login' : 'Done'}</Text>
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
        <TouchableOpacity onPress={goBack}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Become a Seller</Text>
        <View style={{ width: 22 }} />
      </View>

      <View style={styles.progressRow}>
        {STEPS.map((label, index) => (
          <View key={label} style={styles.progressItem}>
            <View style={[styles.progressDot, index <= step && styles.progressDotActive]}>
              {index < step ? <IonIcon name="checkmark" size={12} color={COLORS.white} /> : <Text style={[styles.progressDotText, index <= step && styles.progressDotTextActive]}>{index + 1}</Text>}
            </View>
            <Text style={[styles.progressLabel, index === step && styles.progressLabelActive]}>{label}</Text>
          </View>
        ))}
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        {step === 0 && (
          <>
            <Field label="Full Name" value={form.fullName} onChangeText={t => set('fullName', t)} placeholder="Jane Doe" />
            <Field label="Email" value={form.email} onChangeText={t => set('email', t)} placeholder="jane@example.com" keyboardType="email-address" autoCapitalize="none" />
            <Field label="Phone (optional)" value={form.phone} onChangeText={t => set('phone', t)} placeholder="08012345678" keyboardType="phone-pad" />
            <Field label="Business Name" value={form.businessName} onChangeText={t => set('businessName', t)} placeholder="Jane's Fashion Ltd" />
            <Field label="Registration Number (optional)" value={form.registrationNumber} onChangeText={t => set('registrationNumber', t)} placeholder="RC123456" />
            <Field label="Tax ID Number (optional)" value={form.taxIdentificationNumber} onChangeText={t => set('taxIdentificationNumber', t)} placeholder="TIN-000000" />
            <Text style={styles.sectionLabel}>Agent Info (optional — if a field agent assisted you)</Text>
            <Field label="Agent ID Number" value={form.agentIdNumber} onChangeText={t => set('agentIdNumber', t)} placeholder="AGT-000" />
            <Field label="Agent Full Name" value={form.agentFullName} onChangeText={t => set('agentFullName', t)} placeholder="Agent name" />
            <Field label="Agent Phone" value={form.agentPhone} onChangeText={t => set('agentPhone', t)} placeholder="08012345678" keyboardType="phone-pad" />
          </>
        )}

        {step === 1 && (
          <>
            <Field label="Store Name" value={form.storeName} onChangeText={t => set('storeName', t)} placeholder="Jane's Fashion Store" />
            <Field label="Store Category (optional)" value={form.storeCategory} onChangeText={t => set('storeCategory', t)} placeholder="Fashion & Apparel" />
            <Text style={styles.label}>Store Description (optional)</Text>
            <TextInput
              style={[styles.input, styles.textArea]}
              placeholder="Tell shoppers about your store"
              placeholderTextColor={COLORS.placeholder}
              value={form.storeDescription}
              onChangeText={t => set('storeDescription', t)}
              multiline
              numberOfLines={4}
            />

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

            <Field label="Postal Code (optional)" value={form.postalCode} onChangeText={t => set('postalCode', t)} placeholder="100001" />
            <Field label="Address (optional)" value={form.address} onChangeText={t => set('address', t)} placeholder="15, Adeola Odeku Street" />
            <Field label="Website (optional)" value={form.website} onChangeText={t => set('website', t)} placeholder="https://example.com" autoCapitalize="none" keyboardType="url" />
          </>
        )}

        {step === 2 && (
          <>
            <Field label="Bank Name" value={form.bankName} onChangeText={t => set('bankName', t)} placeholder="First Bank" />
            <Field label="Bank Account Name" value={form.bankAccountName} onChangeText={t => set('bankAccountName', t)} placeholder="Jane Doe" />
            <Field label="Bank Account Number" value={form.bankAccountNumber} onChangeText={t => set('bankAccountNumber', t)} placeholder="0123456789" keyboardType="number-pad" />
          </>
        )}

        {step === 3 && (
          <>
            <DocumentPicker label="Identity Document" required file={identityDoc} onPickFile={() => pickFile(setIdentityDoc)} onTakePhoto={() => takePhoto(setIdentityDoc)} onRemove={() => setIdentityDoc(null)} />
            <DocumentPicker label="Business Registration Document" required file={businessDoc} onPickFile={() => pickFile(setBusinessDoc)} onTakePhoto={() => takePhoto(setBusinessDoc)} onRemove={() => setBusinessDoc(null)} />
            <DocumentPicker label="Tax Certificate (optional)" file={taxDoc} onPickFile={() => pickFile(setTaxDoc)} onTakePhoto={() => takePhoto(setTaxDoc)} onRemove={() => setTaxDoc(null)} />

            <View style={styles.termsRow}>
              <Switch value={form.terms} onValueChange={v => set('terms', v)} trackColor={{ true: COLORS.primary }} />
              <Text style={styles.termsText}>I accept the vendor terms and conditions</Text>
            </View>
          </>
        )}

        <TouchableOpacity
          style={styles.primaryBtn}
          onPress={step === STEPS.length - 1 ? handleSubmit : goNext}
          disabled={submitting}
        >
          {submitting ? <ActivityIndicator color={COLORS.white} /> : (
            <Text style={styles.primaryBtnText}>{step === STEPS.length - 1 ? 'Submit Application' : 'Next'}</Text>
          )}
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

function NextStep({ icon, text }: { icon: string; text: string }) {
  return (
    <View style={styles.nextStepRow}>
      <View style={styles.nextStepIconBox}>
        <IonIcon name={icon} size={16} color={COLORS.primary} />
      </View>
      <Text style={styles.nextStepText}>{text}</Text>
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
  progressRow: { flexDirection: 'row', justifyContent: 'space-between', paddingHorizontal: SIZES.screenPadding, paddingVertical: 14 },
  progressItem: { alignItems: 'center', flex: 1 },
  progressDot: { width: 24, height: 24, borderRadius: 12, backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center' },
  progressDotActive: { backgroundColor: COLORS.primary },
  progressDotText: { fontSize: 11, fontWeight: '700', color: COLORS.textMuted },
  progressDotTextActive: { color: COLORS.white },
  progressLabel: { fontSize: 10, color: COLORS.textMuted, marginTop: 4 },
  progressLabelActive: { color: COLORS.primary, fontWeight: '700' },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  sectionLabel: { fontSize: 12, fontWeight: '700', color: COLORS.textMuted, textTransform: 'uppercase', marginTop: 16, marginBottom: 4 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  textArea: { height: 90, textAlignVertical: 'top' },
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
  confirmIconBox: { width: 96, height: 96, borderRadius: 48, alignItems: 'center', justifyContent: 'center', marginBottom: SIZES.lg },
  confirmTitle: { fontSize: 18, fontWeight: 'bold', color: COLORS.text, marginBottom: 8, textAlign: 'center' },
  confirmMessage: { fontSize: 14, color: COLORS.textSecondary, textAlign: 'center', lineHeight: 20 },
  confirmRefPill: {
    flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: COLORS.grayLight,
    borderRadius: 999, paddingHorizontal: 14, paddingVertical: 6, marginTop: 14,
  },
  confirmRefLabel: { fontSize: 11, color: COLORS.textMuted, fontWeight: '600' },
  confirmRefValue: { fontSize: 12, color: COLORS.text, fontWeight: '700' },
  nextStepsBox: { width: '100%', backgroundColor: COLORS.grayLight, borderRadius: SIZES.borderRadius, padding: 16, marginTop: 24 },
  nextStepsHeading: { fontSize: 12, fontWeight: '700', color: COLORS.text, marginBottom: 12, textTransform: 'uppercase' },
  nextStepRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 10, marginBottom: 12 },
  nextStepIconBox: { width: 28, height: 28, borderRadius: 14, backgroundColor: COLORS.white, alignItems: 'center', justifyContent: 'center' },
  nextStepText: { flex: 1, fontSize: 12, color: COLORS.textSecondary, lineHeight: 17, marginTop: 5 },
});
