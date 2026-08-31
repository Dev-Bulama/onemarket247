import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { productsApi, ProductFilters } from '../../api/products';
import { Product } from '../../types';
import { useCartStore } from '../../store/cartStore';
import { useAuthStore } from '../../store/authStore';
import { useWishlistStore } from '../../store/wishlistStore';
import { useBootstrapStore } from '../../store/bootstrapStore';
import { useLocaleStore } from '../../store/localeStore';
import ProductCard, { computeGridCardWidth } from '../../components/ProductCard';
import { apiErrorMessage } from '../../api/client';

const SORT_OPTIONS: { label: string; value: ProductFilters['sort'] }[] = [
  { label: 'Latest', value: 'latest' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' },
  { label: 'Name A–Z', value: 'name' },
];

export default function ProductListScreen({ route, navigation }: any) {
  const { categoryId, brandId, title } = (route.params ?? {}) as { categoryId?: number; brandId?: number; title?: string };
  const { addItem } = useCartStore();
  const { isAuthenticated } = useAuthStore();
  const { ids: wishlistIds, toggle: toggleWishlist, fetchWishlist } = useWishlistStore();
  const gridColumns = useBootstrapStore(s => s.productGridColumns);
  const { language, currency } = useLocaleStore();

  useEffect(() => {
    if (isAuthenticated) fetchWishlist();
  }, [isAuthenticated, fetchWishlist]);

  const handleToggleWishlist = (productId: number) => {
    if (!isAuthenticated) {
      navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Login' });
      return;
    }
    toggleWishlist(productId);
  };

  const [products, setProducts] = useState<Product[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');
  const [sort, setSort] = useState<ProductFilters['sort']>('latest');
  const [sortModalVisible, setSortModalVisible] = useState(false);

  const load = useCallback(async (targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await productsApi.list({ category_id: categoryId, brand_id: brandId, sort, page: targetPage });
      setProducts(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, [categoryId, brandId, sort]);

  useEffect(() => { load(1); }, [load, language, currency]);

  const activeSortLabel = SORT_OPTIONS.find(o => o.value === sort)?.label ?? 'Latest';

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{title ?? 'Products'}</Text>
        <TouchableOpacity onPress={() => setSortModalVisible(true)} style={styles.sortBtn}>
          <Text style={styles.sortBtnText}>{activeSortLabel}</Text>
          <IonIcon name="chevron-down" size={14} color={COLORS.text} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : products.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="cube-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No products found.</Text>
        </View>
      ) : (
        <FlatList
          key={gridColumns}
          data={products}
          keyExtractor={item => String(item.id)}
          numColumns={gridColumns}
          columnWrapperStyle={{ justifyContent: 'space-between' }}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => (
            <ProductCard
              product={item}
              width={computeGridCardWidth(gridColumns)}
              onPress={() => navigation.navigate('ProductDetail', { slug: item.slug })}
              onAddToCart={id => addItem(id, 1)}
              onToggleWishlist={handleToggleWishlist}
              isWishlisted={wishlistIds.has(item.id)}
            />
          )}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
      )}

      {sortModalVisible && (
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setSortModalVisible(false)}>
          <View style={styles.sheet}>
            <Text style={styles.sheetTitle}>Sort by</Text>
            {SORT_OPTIONS.map(opt => (
              <TouchableOpacity key={opt.value} style={styles.sortOption} onPress={() => { setSort(opt.value); setSortModalVisible(false); }}>
                <Text style={[styles.sortOptionText, sort === opt.value && styles.sortOptionTextActive]}>{opt.label}</Text>
                {sort === opt.value && <IonIcon name="checkmark" size={18} color={COLORS.primary} />}
              </TouchableOpacity>
            ))}
          </View>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8,
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { flex: 1, fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  sortBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 10, paddingVertical: 6 },
  sortBtnText: { fontSize: 11, color: COLORS.text, fontWeight: '600' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  modalOverlay: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  sortOption: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  sortOptionText: { fontSize: 14, color: COLORS.text },
  sortOptionTextActive: { color: COLORS.primary, fontWeight: '700' },
});
