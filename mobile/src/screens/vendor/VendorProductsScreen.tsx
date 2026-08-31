import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, PRODUCT_STATUSES, SIZES } from '../../constants';
import { vendorProductsApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorProductItem } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';
import { useToastStore } from '../../store/toastStore';

const FILTERS: { label: string; value?: string }[] = [
  { label: 'All' },
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending_approval' },
  { label: 'Published', value: 'published' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Archived', value: 'archived' },
];

export default function VendorProductsScreen({ navigation }: any) {
  const [products, setProducts] = useState<VendorProductItem[]>([]);
  const [status, setStatus] = useState<string | undefined>(undefined);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async (targetPage: number, targetStatus: string | undefined) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await vendorProductsApi.list({ status: targetStatus, page: targetPage });
      setProducts(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your products.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(1, status); }, [load, status]));

  const handleDelete = (product: VendorProductItem) => {
    vendorProductsApi.destroy(product.id)
      .then(() => {
        setProducts(prev => prev.filter(p => p.id !== product.id));
        useToastStore.getState().show('Product deleted');
      })
      .catch(e => setError(apiErrorMessage(e, 'Could not delete this product.')));
  };

  const [submittingId, setSubmittingId] = useState<number | null>(null);

  const handleSubmitForReview = (product: VendorProductItem) => {
    setSubmittingId(product.id);
    vendorProductsApi.submit(product.id)
      .then(res => {
        setProducts(prev => prev.map(p => (p.id === product.id ? res.data.data : p)));
        useToastStore.getState().show('Product submitted for review');
      })
      .catch(e => setError(apiErrorMessage(e, 'Could not submit this product for review.')))
      .finally(() => setSubmittingId(null));
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Products</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => navigation.navigate('VendorProductForm')}>
          <IonIcon name="add" size={20} color={COLORS.white} />
        </TouchableOpacity>
      </View>

      <FlatList
        horizontal
        showsHorizontalScrollIndicator={false}
        data={FILTERS}
        keyExtractor={item => item.label}
        contentContainerStyle={styles.filterRow}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.filterChip, status === item.value && styles.filterChipActive]}
            onPress={() => setStatus(item.value)}
          >
            <Text style={[styles.filterChipText, status === item.value && styles.filterChipTextActive]}>{item.label}</Text>
          </TouchableOpacity>
        )}
      />

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1, status)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : products.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="cube-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No products found.</Text>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1, status)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => {
            const statusInfo = PRODUCT_STATUSES[item.status] ?? { label: item.status_label, color: COLORS.textSecondary };
            return (
              <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('VendorProductForm', { productId: item.id })}>
                <View style={styles.thumbBox}>
                  {item.thumbnail ? (
                    <Image source={{ uri: item.thumbnail }} style={styles.thumb} />
                  ) : (
                    <IonIcon name="image-outline" size={22} color={COLORS.border} />
                  )}
                </View>
                <View style={{ flex: 1, marginLeft: 10 }}>
                  <Text style={styles.name} numberOfLines={2}>{item.name}</Text>
                  <Text style={styles.price}>{item.price?.formatted ?? '—'}</Text>
                  {item.status === 'rejected' && item.rejection_reason ? (
                    <Text style={styles.rejectionReason} numberOfLines={2}>{item.rejection_reason}</Text>
                  ) : null}
                  <StatusBadge label={statusInfo.label} color={statusInfo.color} />
                  {(item.status === 'draft' || item.status === 'rejected') && (
                    <TouchableOpacity
                      style={styles.submitBtn}
                      onPress={() => handleSubmitForReview(item)}
                      disabled={submittingId === item.id}
                    >
                      {submittingId === item.id ? (
                        <ActivityIndicator size="small" color={COLORS.primary} />
                      ) : (
                        <>
                          <IonIcon name="paper-plane-outline" size={13} color={COLORS.primary} />
                          <Text style={styles.submitBtnText}>Submit for review</Text>
                        </>
                      )}
                    </TouchableOpacity>
                  )}
                </View>
                <TouchableOpacity style={styles.deleteBtn} onPress={() => handleDelete(item)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                  <IonIcon name="trash-outline" size={18} color={COLORS.danger} />
                </TouchableOpacity>
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
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
  addBtn: { width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center' },
  filterRow: { paddingHorizontal: SIZES.screenPadding, paddingVertical: 10, gap: 8, backgroundColor: COLORS.white },
  filterChip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 14, paddingVertical: 7, marginRight: 8 },
  filterChipActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  filterChipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  filterChipTextActive: { color: COLORS.white },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 12, marginBottom: 10 },
  thumbBox: { width: 56, height: 56, borderRadius: SIZES.borderRadiusSm, backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  thumb: { width: 56, height: 56 },
  name: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 4 },
  price: { fontSize: 13, fontWeight: 'bold', color: COLORS.primary, marginBottom: 6 },
  rejectionReason: { fontSize: 11, color: COLORS.danger, marginBottom: 6 },
  deleteBtn: { padding: 6 },
  submitBtn: {
    flexDirection: 'row', alignItems: 'center', alignSelf: 'flex-start', gap: 4,
    borderWidth: 1, borderColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm,
    paddingHorizontal: 8, paddingVertical: 4, marginTop: 6,
  },
  submitBtnText: { fontSize: 11, color: COLORS.primary, fontWeight: '700' },
});
