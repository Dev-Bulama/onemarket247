import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { storesApi } from '../../api/products';
import { apiErrorMessage } from '../../api/client';
import { Product, Store } from '../../types';
import { useCartStore } from '../../store/cartStore';
import ProductCard from '../../components/ProductCard';

export default function StoreScreen({ route, navigation }: any) {
  const { slug } = route.params as { slug: string };
  const { addItem } = useCartStore();

  const [store, setStore] = useState<Store | null>(null);
  const [products, setProducts] = useState<Product[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setLoading(true);
    storesApi.show(slug).then(res => setStore(res.data.data)).catch(e => setError(apiErrorMessage(e, 'Could not load this store.')));
    storesApi.products(slug).then(res => {
      setProducts(res.data.data);
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    }).finally(() => setLoading(false));
  }, [slug]);

  const loadMore = useCallback(() => {
    if (loadingMore || page >= lastPage) return;
    setLoadingMore(true);
    storesApi.products(slug, { page: page + 1 }).then(res => {
      setProducts(prev => [...prev, ...res.data.data]);
      setPage(res.data.meta.pagination.current_page);
    }).finally(() => setLoadingMore(false));
  }, [slug, page, lastPage, loadingMore]);

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  if (error || !store) {
    return (
      <View style={styles.centerFlex}>
        <Text style={styles.errorText}>{error || 'Store not found.'}</Text>
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{store.name}</Text>
        <View style={styles.backSpacer} />
      </View>

      <FlatList
        data={products}
        keyExtractor={item => String(item.id)}
        numColumns={2}
        columnWrapperStyle={styles.row}
        contentContainerStyle={styles.list}
        onEndReached={loadMore}
        onEndReachedThreshold={0.4}
        ListHeaderComponent={
          <View style={styles.storeCard}>
            <View style={styles.storeIconBox}>
              <IonIcon name="storefront" size={28} color={COLORS.primary} />
            </View>
            <View style={styles.storeInfo}>
              <View style={styles.storeNameRow}>
                <Text style={styles.storeName}>{store.name}</Text>
                {store.is_verified && <IonIcon name="checkmark-circle" size={16} color={COLORS.accent} />}
              </View>
              {store.description ? <Text style={styles.storeDescription} numberOfLines={3}>{store.description}</Text> : null}
              {[store.address, store.city, store.state, store.country].filter(Boolean).length > 0 && (
                <View style={styles.locationRow}>
                  <IonIcon name="location-outline" size={13} color={COLORS.textMuted} />
                  <Text style={styles.locationText}>{[store.city, store.state, store.country].filter(Boolean).join(', ')}</Text>
                </View>
              )}
              {store.vacation_message ? (
                <View style={styles.vacationBanner}>
                  <Text style={styles.vacationText}>{store.vacation_message}</Text>
                </View>
              ) : null}
            </View>
          </View>
        }
        renderItem={({ item }) => (
          <ProductCard product={item} onPress={() => navigation.navigate('ProductDetail', { slug: item.slug })} onAddToCart={id => addItem(id, 1)} />
        )}
        ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={styles.footerLoader} /> : null}
        ListEmptyComponent={<Text style={styles.emptyText}>This store has no products yet.</Text>}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  errorText: { color: COLORS.textSecondary },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { flex: 1, fontSize: 15, fontWeight: 'bold', color: COLORS.text, marginHorizontal: 10 },
  backSpacer: { width: 22 },
  row: { justifyContent: 'space-between' },
  list: { padding: SIZES.screenPadding },
  storeCard: { flexDirection: 'row', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 16, gap: 12 },
  storeIconBox: { width: 56, height: 56, borderRadius: 28, backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center' },
  storeInfo: { flex: 1 },
  storeNameRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  storeName: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  storeDescription: { fontSize: 12, color: COLORS.textSecondary, marginTop: 4, lineHeight: 17 },
  locationRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 6 },
  locationText: { fontSize: 11, color: COLORS.textMuted },
  vacationBanner: { backgroundColor: '#FFF3EB', borderRadius: SIZES.borderRadiusSm, padding: 8, marginTop: 8 },
  vacationText: { fontSize: 11, color: COLORS.primaryDark },
  footerLoader: { marginVertical: 16 },
  emptyText: { textAlign: 'center', color: COLORS.textSecondary, marginTop: 24 },
});
