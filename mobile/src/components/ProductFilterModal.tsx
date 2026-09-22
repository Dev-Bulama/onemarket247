import React, { useEffect, useState } from 'react';
import { Modal, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../constants';
import { productsApi, ProductFilterOptions } from '../api/products';

export interface AppliedFilters {
  vendor_id?: number[];
  city_id?: number[];
  min_price?: number;
  max_price?: number;
  in_stock?: boolean;
}

interface Props {
  visible: boolean;
  onClose: () => void;
  filters: AppliedFilters;
  onApply: (filters: AppliedFilters) => void;
}

function toggleId(ids: number[] | undefined, id: number): number[] {
  const current = ids ?? [];
  return current.includes(id) ? current.filter(existing => existing !== id) : [...current, id];
}

/** Vendor/location checkbox filtering (see FiltersProducts on the backend) shared by product listing and search screens. */
export default function ProductFilterModal({ visible, onClose, filters, onApply }: Props) {
  const [options, setOptions] = useState<ProductFilterOptions | null>(null);
  const [draft, setDraft] = useState<AppliedFilters>(filters);

  useEffect(() => {
    if (visible) setDraft(filters);
  }, [visible, filters]);

  useEffect(() => {
    if (visible && !options) {
      productsApi.filters().then(res => setOptions(res.data.data)).catch(() => {});
    }
  }, [visible, options]);

  const handleApply = () => {
    onApply(draft);
    onClose();
  };

  const handleReset = () => {
    const cleared: AppliedFilters = {};
    setDraft(cleared);
    onApply(cleared);
    onClose();
  };

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <TouchableOpacity style={styles.overlay} activeOpacity={1} onPress={onClose}>
        <TouchableOpacity activeOpacity={1} style={styles.sheet} onPress={() => {}}>
          <View style={styles.headerRow}>
            <Text style={styles.title}>Filter</Text>
            <TouchableOpacity onPress={onClose}>
              <IonIcon name="close" size={22} color={COLORS.text} />
            </TouchableOpacity>
          </View>

          <ScrollView style={{ maxHeight: 420 }}>
            <Text style={styles.sectionLabel}>Store / Vendor</Text>
            {(options?.vendors ?? []).map(vendor => (
              <TouchableOpacity
                key={vendor.id}
                style={styles.checkboxRow}
                onPress={() => setDraft(d => ({ ...d, vendor_id: toggleId(d.vendor_id, vendor.id) }))}
              >
                <IonIcon
                  name={draft.vendor_id?.includes(vendor.id) ? 'checkbox' : 'square-outline'}
                  size={20}
                  color={draft.vendor_id?.includes(vendor.id) ? COLORS.primary : COLORS.textMuted}
                />
                <Text style={styles.checkboxLabel}>{vendor.name}</Text>
              </TouchableOpacity>
            ))}

            <Text style={styles.sectionLabel}>Location</Text>
            {(options?.cities ?? []).map(city => (
              <TouchableOpacity
                key={city.id}
                style={styles.checkboxRow}
                onPress={() => setDraft(d => ({ ...d, city_id: toggleId(d.city_id, city.id) }))}
              >
                <IonIcon
                  name={draft.city_id?.includes(city.id) ? 'checkbox' : 'square-outline'}
                  size={20}
                  color={draft.city_id?.includes(city.id) ? COLORS.primary : COLORS.textMuted}
                />
                <Text style={styles.checkboxLabel}>{city.name}</Text>
              </TouchableOpacity>
            ))}

            <Text style={styles.sectionLabel}>Price range</Text>
            <View style={styles.priceRow}>
              <TextInput
                style={styles.priceInput}
                placeholder="Min"
                placeholderTextColor={COLORS.placeholder}
                keyboardType="numeric"
                value={draft.min_price != null ? String(draft.min_price) : ''}
                onChangeText={text => setDraft(d => ({ ...d, min_price: text ? Number(text) : undefined }))}
              />
              <Text style={{ color: COLORS.textMuted }}>–</Text>
              <TextInput
                style={styles.priceInput}
                placeholder="Max"
                placeholderTextColor={COLORS.placeholder}
                keyboardType="numeric"
                value={draft.max_price != null ? String(draft.max_price) : ''}
                onChangeText={text => setDraft(d => ({ ...d, max_price: text ? Number(text) : undefined }))}
              />
            </View>

            <TouchableOpacity style={styles.checkboxRow} onPress={() => setDraft(d => ({ ...d, in_stock: !d.in_stock }))}>
              <IonIcon name={draft.in_stock ? 'checkbox' : 'square-outline'} size={20} color={draft.in_stock ? COLORS.primary : COLORS.textMuted} />
              <Text style={styles.checkboxLabel}>In stock only</Text>
            </TouchableOpacity>
          </ScrollView>

          <View style={styles.actionRow}>
            <TouchableOpacity style={styles.resetBtn} onPress={handleReset}>
              <Text style={styles.resetBtnText}>Clear all</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.applyBtn} onPress={handleApply}>
              <Text style={styles.applyBtnText}>Apply filters</Text>
            </TouchableOpacity>
          </View>
        </TouchableOpacity>
      </TouchableOpacity>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 },
  title: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  sectionLabel: { fontSize: 12, fontWeight: '700', color: COLORS.textSecondary, marginTop: 14, marginBottom: 6, textTransform: 'uppercase' },
  checkboxRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 8 },
  checkboxLabel: { fontSize: 14, color: COLORS.text },
  priceRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  priceInput: { flex: 1, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 10, paddingVertical: 8, fontSize: 13, color: COLORS.text },
  actionRow: { flexDirection: 'row', gap: 10, marginTop: 16 },
  resetBtn: { flex: 1, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center' },
  resetBtnText: { color: COLORS.text, fontWeight: '600', fontSize: 14 },
  applyBtn: { flex: 2, backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center' },
  applyBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 14 },
});
