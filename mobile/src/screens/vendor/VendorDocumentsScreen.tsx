import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, Modal, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, DOCUMENT_STATUSES, SIZES } from '../../constants';
import { vendorDocumentsApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { PickedFile } from '../../api/vendor';
import { VendorDocumentItem } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';
import { useToastStore } from '../../store/toastStore';
import { pickDocumentFile, takeDocumentPhoto } from '../../utils/documentPicker';

const DOCUMENT_TYPES: { value: string; label: string }[] = [
  { value: 'identity', label: 'Identity Document' },
  { value: 'business_registration', label: 'Business Registration' },
  { value: 'tax_certificate', label: 'Tax Certificate' },
  { value: 'other', label: 'Other' },
];

export default function VendorDocumentsScreen({ navigation }: any) {
  const [documents, setDocuments] = useState<VendorDocumentItem[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');
  const [uploadVisible, setUploadVisible] = useState(false);

  const load = useCallback(async (targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await vendorDocumentsApi.list(targetPage);
      setDocuments(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your documents.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(1); }, [load]));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Documents</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setUploadVisible(true)}>
          <IonIcon name="add" size={20} color={COLORS.white} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : documents.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="document-text-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No documents uploaded yet.</Text>
        </View>
      ) : (
        <FlatList
          data={documents}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => {
            const statusInfo = DOCUMENT_STATUSES[item.status] ?? { label: item.status_label, color: COLORS.textSecondary };
            return (
              <View style={styles.card}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.type}>{item.type_label}</Text>
                  <Text style={styles.date}>{new Date(item.created_at).toLocaleDateString()}</Text>
                  {item.status === 'rejected' && item.rejection_reason ? (
                    <Text style={styles.rejectionReason}>{item.rejection_reason}</Text>
                  ) : null}
                </View>
                <StatusBadge label={statusInfo.label} color={statusInfo.color} />
              </View>
            );
          }}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
      )}

      <Modal visible={uploadVisible} transparent animationType="slide" onRequestClose={() => setUploadVisible(false)}>
        <UploadSheet
          onClose={() => setUploadVisible(false)}
          onUploaded={document => { setDocuments(prev => [document, ...prev]); setUploadVisible(false); }}
        />
      </Modal>
    </View>
  );
}

function UploadSheet({ onClose, onUploaded }: { onClose: () => void; onUploaded: (document: VendorDocumentItem) => void }) {
  const [type, setType] = useState('identity');
  const [file, setFile] = useState<PickedFile | null>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  const handlePickFile = async () => {
    setError('');
    try {
      const picked = await pickDocumentFile();
      if (picked) setFile(picked);
    } catch {
      setError('Could not open the file picker. Please try again.');
    }
  };

  const handleTakePhoto = async () => {
    setError('');
    const picked = await takeDocumentPhoto();
    if (picked) setFile(picked);
  };

  const handleSubmit = async () => {
    if (!file) { setError('Please choose a file or take a photo of the document.'); return; }
    setUploading(true);
    setError('');
    try {
      const res = await vendorDocumentsApi.upload(type, file);
      onUploaded(res.data.data);
      useToastStore.getState().show('Document uploaded');
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not upload this document.'));
    } finally {
      setUploading(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Upload Document</Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Document Type</Text>
        <View style={styles.chipRow}>
          {DOCUMENT_TYPES.map(opt => (
            <TouchableOpacity key={opt.value} style={[styles.chip, type === opt.value && styles.chipActive]} onPress={() => setType(opt.value)}>
              <Text style={[styles.chipText, type === opt.value && styles.chipTextActive]}>{opt.label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        <Text style={styles.label}>File</Text>
        {file ? <Text style={styles.fileName} numberOfLines={1}>{file.name}</Text> : null}
        <View style={styles.pickRow}>
          <TouchableOpacity style={styles.pickBtn} onPress={handlePickFile}>
            <IonIcon name="document-outline" size={20} color={COLORS.textSecondary} />
            <Text style={styles.pickBtnText}>Choose PDF or Image</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.pickBtn} onPress={handleTakePhoto}>
            <IonIcon name="camera-outline" size={20} color={COLORS.textSecondary} />
            <Text style={styles.pickBtnText}>Take Photo</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={uploading}>
          {uploading ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Upload</Text>}
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  addBtn: { width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center' },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  card: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  type: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  date: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  rejectionReason: { fontSize: 11, color: COLORS.danger, marginTop: 4 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  error: { color: COLORS.danger, marginBottom: 8, fontSize: 12 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 14, paddingVertical: 7 },
  chipActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  chipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  chipTextActive: { color: COLORS.white },
  fileName: { fontSize: 12, color: COLORS.text, marginBottom: 8 },
  pickRow: { flexDirection: 'row', gap: 8 },
  pickBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, borderWidth: 1, borderColor: COLORS.border, borderStyle: 'dashed', borderRadius: SIZES.borderRadiusSm, padding: 12, backgroundColor: COLORS.grayLight },
  pickBtnText: { fontSize: 12, color: COLORS.textSecondary, textAlign: 'center' },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
