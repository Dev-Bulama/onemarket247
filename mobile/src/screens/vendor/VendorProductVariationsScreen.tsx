import React, { useCallback, useEffect, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import {
  ActivityIndicator, Alert, FlatList, Image, Modal, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View,
} from 'react-native';
import { Asset, launchCamera, launchImageLibrary } from 'react-native-image-picker';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { vendorAttributesApi, vendorProductVariationsApi, VendorProductVariationPayload } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorAttribute, VendorProductVariation } from '../../types/vendor';
import { useToastStore } from '../../store/toastStore';

const toMinorUnits = (text: string): number | undefined => {
  const n = parseFloat(text);
  return Number.isFinite(n) ? Math.round(n * 100) : undefined;
};
const fromMinorUnits = (amount?: number | null): string => (amount != null ? (amount / 100).toFixed(2) : '');

export default function VendorProductVariationsScreen({ route, navigation }: any) {
  const { productId, productName } = route.params as { productId: number; productName?: string };

  const [variations, setVariations] = useState<VendorProductVariation[]>([]);
  const [attributes, setAttributes] = useState<VendorAttribute[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [editing, setEditing] = useState<VendorProductVariation | 'new' | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await vendorProductVariationsApi.list(productId);
      setVariations(res.data.data);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load variations.'));
    } finally {
      setLoading(false);
    }
  }, [productId]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  useEffect(() => {
    vendorAttributesApi.list().then(res => setAttributes(res.data.data)).catch(() => {});
  }, []);

  const handleDelete = (variation: VendorProductVariation) => {
    Alert.alert('Delete Variation', `Remove "${variation.sku}"? This can't be undone.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete', style: 'destructive', onPress: async () => {
          try {
            await vendorProductVariationsApi.destroy(productId, variation.id);
            setVariations(prev => prev.filter(v => v.id !== variation.id));
          } catch (e) {
            useToastStore.getState().show(apiErrorMessage(e, 'Could not delete this variation.'), 'error');
          }
        },
      },
    ]);
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{productName ?? 'Variations'}</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setEditing('new')}>
          <IonIcon name="add" size={20} color={COLORS.white} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loadingIndicator} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={load}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : variations.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="albums-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No variations yet.</Text>
          <Text style={styles.emptySubtext}>Add one for each colour, size, or other option customers can choose.</Text>
        </View>
      ) : (
        <FlatList
          data={variations}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          renderItem={({ item }) => (
            <TouchableOpacity style={styles.card} onPress={() => setEditing(item)}>
              {item.image ? (
                <Image source={{ uri: item.image }} style={styles.cardImage} />
              ) : (
                <View style={[styles.cardImage, styles.cardImagePlaceholder]}>
                  <IonIcon name="image-outline" size={20} color={COLORS.border} />
                </View>
              )}
              <View style={{ flex: 1 }}>
                <Text style={styles.cardSku}>{item.sku}</Text>
                <Text style={styles.cardAttributes} numberOfLines={1}>
                  {item.attributes.map(a => `${a.attribute}: ${a.value}`).join(', ') || 'No attributes set'}
                </Text>
                <Text style={styles.cardPrice}>{item.price.formatted} · {item.stock_quantity} in stock</Text>
              </View>
              <TouchableOpacity style={styles.deleteBtn} onPress={() => handleDelete(item)}>
                <IonIcon name="trash-outline" size={18} color={COLORS.danger} />
              </TouchableOpacity>
            </TouchableOpacity>
          )}
        />
      )}

      <Modal visible={editing !== null} transparent animationType="slide" onRequestClose={() => setEditing(null)}>
        {editing !== null && (
          <VariationSheet
            productId={productId}
            variation={editing === 'new' ? null : editing}
            attributes={attributes}
            onClose={() => setEditing(null)}
            onSaved={saved => {
              setVariations(prev => (editing === 'new' ? [saved, ...prev] : prev.map(v => (v.id === saved.id ? saved : v))));
              setEditing(null);
            }}
          />
        )}
      </Modal>
    </View>
  );
}

function VariationSheet({ productId, variation, attributes, onClose, onSaved }: {
  productId: number;
  variation: VendorProductVariation | null;
  attributes: VendorAttribute[];
  onClose: () => void;
  onSaved: (variation: VendorProductVariation) => void;
}) {
  const isEdit = variation != null;
  const [sku, setSku] = useState(variation?.sku ?? '');
  const [price, setPrice] = useState(fromMinorUnits(variation?.price.amount));
  const [compareAtPrice, setCompareAtPrice] = useState(fromMinorUnits(variation?.compare_at_price?.amount));
  const [stockQuantity, setStockQuantity] = useState(variation ? String(variation.stock_quantity) : '0');
  const [selectedValueIds, setSelectedValueIds] = useState<number[]>(variation?.attributes.map(a => a.value_id) ?? []);
  const [image, setImage] = useState<Asset | null>(null);
  const [saving, setSaving] = useState(false);

  const toggleValue = (id: number) => {
    setSelectedValueIds(prev => (prev.includes(id) ? prev.filter(v => v !== id) : [...prev, id]));
  };

  const pickImage = () => {
    Alert.alert('Add Photo', undefined, [
      { text: 'Take Photo', onPress: () => launchCamera({ mediaType: 'photo' }, res => { if (res.assets?.[0]) setImage(res.assets[0]); }) },
      { text: 'Choose from Gallery', onPress: () => launchImageLibrary({ mediaType: 'photo' }, res => { if (res.assets?.[0]) setImage(res.assets[0]); }) },
      { text: 'Cancel', style: 'cancel' },
    ]);
  };

  const handleSubmit = async () => {
    if (!sku.trim()) { useToastStore.getState().show('Please enter a SKU.', 'error'); return; }
    if (!price.trim()) { useToastStore.getState().show('Please enter a price.', 'error'); return; }
    setSaving(true);
    try {
      const payload: VendorProductVariationPayload = {
        sku: sku.trim(),
        price: toMinorUnits(price) ?? 0,
        compare_at_price: toMinorUnits(compareAtPrice),
        stock_quantity: stockQuantity.trim() ? parseInt(stockQuantity, 10) : undefined,
        is_active: true,
        attribute_value_ids: selectedValueIds,
        image: image ? { uri: image.uri!, name: image.fileName ?? 'variation.jpg', type: image.type ?? 'image/jpeg' } : undefined,
      };
      const res = isEdit
        ? await vendorProductVariationsApi.update(productId, variation!.id, payload)
        : await vendorProductVariationsApi.create(productId, payload);
      onSaved(res.data.data);
      useToastStore.getState().show(isEdit ? 'Variation updated' : 'Variation added');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not save this variation.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <ScrollView keyboardShouldPersistTaps="handled">
          <Text style={styles.sheetTitle}>{isEdit ? 'Edit Variation' : 'New Variation'}</Text>

          <Text style={styles.label}>SKU</Text>
          <TextInput style={styles.input} value={sku} onChangeText={setSku} placeholder="e.g. TSHIRT-BLUE-M" placeholderTextColor={COLORS.placeholder} />

          <Text style={styles.label}>Price</Text>
          <TextInput style={styles.input} value={price} onChangeText={setPrice} placeholder="0.00" keyboardType="decimal-pad" placeholderTextColor={COLORS.placeholder} />

          <Text style={styles.label}>Compare-at Price (optional)</Text>
          <TextInput style={styles.input} value={compareAtPrice} onChangeText={setCompareAtPrice} placeholder="0.00" keyboardType="decimal-pad" placeholderTextColor={COLORS.placeholder} />

          <Text style={styles.label}>Stock Quantity</Text>
          <TextInput style={styles.input} value={stockQuantity} onChangeText={setStockQuantity} placeholder="0" keyboardType="number-pad" placeholderTextColor={COLORS.placeholder} />

          {attributes.map(attribute => (
            <View key={attribute.id}>
              <Text style={styles.label}>{attribute.name}</Text>
              <View style={styles.chipRow}>
                {attribute.values.map(value => (
                  <TouchableOpacity
                    key={value.id}
                    style={[styles.chip, selectedValueIds.includes(value.id) && styles.chipActive]}
                    onPress={() => toggleValue(value.id)}
                  >
                    <Text style={[styles.chipText, selectedValueIds.includes(value.id) && styles.chipTextActive]}>{value.value}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          ))}

          <Text style={styles.label}>Photo (optional)</Text>
          {image ? (
            <View style={styles.previewBox}>
              <Image source={{ uri: image.uri }} style={styles.previewImage} />
              <TouchableOpacity style={styles.retakeBtn} onPress={pickImage}>
                <IonIcon name="refresh-outline" size={16} color={COLORS.white} />
                <Text style={styles.retakeBtnText}>Change</Text>
              </TouchableOpacity>
            </View>
          ) : variation?.image ? (
            <View style={styles.previewBox}>
              <Image source={{ uri: variation.image }} style={styles.previewImage} />
              <TouchableOpacity style={styles.retakeBtn} onPress={pickImage}>
                <IonIcon name="refresh-outline" size={16} color={COLORS.white} />
                <Text style={styles.retakeBtnText}>Change</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <TouchableOpacity style={styles.pickBtn} onPress={pickImage}>
              <IonIcon name="camera-outline" size={20} color={COLORS.textSecondary} />
              <Text style={styles.pickBtnText}>Add a photo</Text>
            </TouchableOpacity>
          )}

          <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
            {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>{isEdit ? 'Save Changes' : 'Add Variation'}</Text>}
          </TouchableOpacity>
        </ScrollView>
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
  headerTitle: { flex: 1, fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginHorizontal: 10, textAlign: 'center' },
  addBtn: { width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center' },
  loadingIndicator: { marginTop: 40 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, fontWeight: '600' },
  emptySubtext: { color: COLORS.textMuted, marginTop: 4, fontSize: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 12, marginBottom: 10 },
  cardImage: { width: 48, height: 48, borderRadius: 8 },
  cardImagePlaceholder: { backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center' },
  cardSku: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  cardAttributes: { fontSize: 11, color: COLORS.textSecondary, marginTop: 2 },
  cardPrice: { fontSize: 12, color: COLORS.textMuted, marginTop: 2 },
  deleteBtn: { padding: 6 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32, maxHeight: '85%' },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 14, paddingVertical: 7 },
  chipActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  chipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  chipTextActive: { color: COLORS.white },
  previewBox: { marginBottom: 8 },
  previewImage: { width: '100%', height: 140, borderRadius: SIZES.borderRadiusSm, backgroundColor: COLORS.grayLight },
  retakeBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, alignSelf: 'flex-start', marginTop: 8, backgroundColor: COLORS.text, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 6 },
  retakeBtnText: { color: COLORS.white, fontSize: 12, fontWeight: '600' },
  pickBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, borderWidth: 1, borderColor: COLORS.border, borderStyle: 'dashed', borderRadius: SIZES.borderRadiusSm, padding: 12, backgroundColor: COLORS.grayLight },
  pickBtnText: { fontSize: 13, color: COLORS.textSecondary },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
