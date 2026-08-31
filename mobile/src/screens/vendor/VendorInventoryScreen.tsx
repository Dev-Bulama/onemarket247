import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, Modal, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { vendorInventoryApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorInventoryItem } from '../../types/vendor';
import { useToastStore } from '../../store/toastStore';

export default function VendorInventoryScreen({ navigation }: any) {
  const [items, setItems] = useState<VendorInventoryItem[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');
  const [adjusting, setAdjusting] = useState<VendorInventoryItem | null>(null);

  const load = useCallback(async (targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await vendorInventoryApi.list(targetPage);
      setItems(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your inventory.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(1); }, [load]));

  const handleAdjusted = (updated: VendorInventoryItem) => {
    setItems(prev => prev.map(item => (item.id === updated.id ? updated : item)));
    setAdjusting(null);
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Inventory</Text>
        <View style={{ width: 22 }} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : items.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="file-tray-stacked-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No stock records found.</Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={{ flex: 1 }}>
                <Text style={styles.productName} numberOfLines={2}>{item.product?.name ?? `Variation #${item.variation_id}`}</Text>
                <Text style={styles.warehouse}>{item.warehouse ?? 'Warehouse'}</Text>
                <View style={styles.statsRow}>
                  <InventoryStat label="On Hand" value={item.on_hand} />
                  <InventoryStat label="Reserved" value={item.reserved} />
                  <InventoryStat label="Available" value={item.available} highlight />
                </View>
              </View>
              <TouchableOpacity style={styles.adjustBtn} onPress={() => setAdjusting(item)}>
                <IonIcon name="create-outline" size={16} color={COLORS.primary} />
                <Text style={styles.adjustBtnText}>Adjust</Text>
              </TouchableOpacity>
            </View>
          )}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
      )}

      <Modal visible={!!adjusting} transparent animationType="slide" onRequestClose={() => setAdjusting(null)}>
        {adjusting && <AdjustSheet item={adjusting} onClose={() => setAdjusting(null)} onAdjusted={handleAdjusted} />}
      </Modal>
    </View>
  );
}

function InventoryStat({ label, value, highlight }: { label: string; value: number; highlight?: boolean }) {
  return (
    <View style={styles.stat}>
      <Text style={[styles.statValue, highlight && styles.statValueHighlight]}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function AdjustSheet({ item, onClose, onAdjusted }: { item: VendorInventoryItem; onClose: () => void; onAdjusted: (updated: VendorInventoryItem) => void }) {
  const [delta, setDelta] = useState('');
  const [reason, setReason] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const applyDelta = (sign: 1 | -1) => {
    const n = parseInt(delta, 10);
    setDelta(String((Number.isFinite(n) ? Math.abs(n) : 0) * sign || sign));
  };

  const handleSubmit = async () => {
    const parsedDelta = parseInt(delta, 10);
    if (!Number.isFinite(parsedDelta) || parsedDelta === 0) { setError('Enter a non-zero adjustment.'); return; }
    if (!reason.trim()) { setError('Please give a reason for this adjustment.'); return; }
    setSaving(true);
    setError('');
    try {
      const res = await vendorInventoryApi.adjust(item.id, parsedDelta, reason.trim());
      onAdjusted(res.data.data);
      useToastStore.getState().show('Stock adjusted');
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not adjust stock.'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Adjust Stock</Text>
        <Text style={styles.sheetSubtitle}>{item.product?.name ?? `Variation #${item.variation_id}`} · Currently {item.on_hand} on hand</Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Adjustment</Text>
        <Text style={styles.hint}>Enter a positive number to add stock, negative to remove.</Text>
        <View style={styles.deltaRow}>
          <TouchableOpacity style={styles.deltaBtn} onPress={() => applyDelta(-1)}>
            <IonIcon name="remove" size={18} color={COLORS.text} />
          </TouchableOpacity>
          <TextInput
            style={styles.deltaInput}
            value={delta}
            onChangeText={setDelta}
            placeholder="0"
            placeholderTextColor={COLORS.placeholder}
            keyboardType="numbers-and-punctuation"
          />
          <TouchableOpacity style={styles.deltaBtn} onPress={() => applyDelta(1)}>
            <IonIcon name="add" size={18} color={COLORS.text} />
          </TouchableOpacity>
        </View>

        <Text style={styles.label}>Reason</Text>
        <TextInput
          style={styles.input}
          value={reason}
          onChangeText={setReason}
          placeholder="e.g. Restocked from supplier"
          placeholderTextColor={COLORS.placeholder}
        />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Save Adjustment</Text>}
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
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  productName: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  warehouse: { fontSize: 11, color: COLORS.textMuted, marginTop: 2, marginBottom: 8 },
  statsRow: { flexDirection: 'row', gap: 16 },
  stat: { alignItems: 'flex-start' },
  statValue: { fontSize: 14, fontWeight: 'bold', color: COLORS.text },
  statValueHighlight: { color: COLORS.accent },
  statLabel: { fontSize: 10, color: COLORS.textMuted },
  adjustBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 10, paddingVertical: 8 },
  adjustBtnText: { fontSize: 11, fontWeight: '700', color: COLORS.primary },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  sheetSubtitle: { fontSize: 12, color: COLORS.textSecondary, marginTop: 4, marginBottom: 12 },
  error: { color: COLORS.danger, marginBottom: 8, fontSize: 12 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 4, marginTop: 8 },
  hint: { fontSize: 11, color: COLORS.textMuted, marginBottom: 8 },
  deltaRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  deltaBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center' },
  deltaInput: { flex: 1, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 15, textAlign: 'center', backgroundColor: COLORS.grayLight },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.grayLight },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
