import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { compareApi } from '../../api/wishlist';
import { Product } from '../../types';

const ROWS: { label: string; render: (p: Product) => string }[] = [
  { label: 'Price', render: p => p.price?.formatted ?? p.price_range?.min.formatted ?? '—' },
  { label: 'Brand', render: p => p.brand?.name ?? '—' },
  { label: 'Rating', render: p => `${p.rating.toFixed(1)} (${p.review_count})` },
  { label: 'Stock', render: p => (p.in_stock ? 'In Stock' : 'Out of Stock') },
  { label: 'Sold by', render: p => p.vendor?.store_name ?? '—' },
];

export default function CompareScreen({ navigation }: any) {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    compareApi.list().then(res => setProducts(res.data.data)).finally(() => setLoading(false));
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const handleRemove = async (id: number) => {
    await compareApi.remove(id);
    load();
  };

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Compare Products</Text>
        <View style={styles.backSpacer} />
      </View>

      {products.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="git-compare-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>Add products to compare from their product page.</Text>
        </View>
      ) : (
        <ScrollView horizontal>
          <View>
            <View style={styles.headerRow}>
              <View style={styles.labelCell} />
              {products.map(p => (
                <View key={p.id} style={styles.productCell}>
                  <TouchableOpacity onPress={() => handleRemove(p.id)} style={styles.removeBtn}>
                    <IonIcon name="close-circle" size={18} color={COLORS.danger} />
                  </TouchableOpacity>
                  <TouchableOpacity onPress={() => navigation.navigate('ProductDetail', { slug: p.slug })}>
                    <Text style={styles.productName} numberOfLines={2}>{p.name}</Text>
                  </TouchableOpacity>
                </View>
              ))}
            </View>
            {ROWS.map(row => (
              <View key={row.label} style={styles.dataRow}>
                <View style={styles.labelCell}><Text style={styles.labelText}>{row.label}</Text></View>
                {products.map(p => (
                  <View key={p.id} style={styles.productCell}><Text style={styles.valueText}>{row.render(p)}</Text></View>
                ))}
              </View>
            ))}
          </View>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  backSpacer: { width: 22 },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
  headerRow: { flexDirection: 'row', backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  dataRow: { flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  labelCell: { width: 100, padding: 12, justifyContent: 'center', backgroundColor: COLORS.grayLight },
  labelText: { fontSize: 12, fontWeight: '700', color: COLORS.textSecondary },
  productCell: { width: 140, padding: 12, justifyContent: 'center' },
  productName: { fontSize: 12, fontWeight: '600', color: COLORS.text },
  valueText: { fontSize: 12, color: COLORS.text },
  removeBtn: { alignSelf: 'flex-end', marginBottom: 4 },
});
