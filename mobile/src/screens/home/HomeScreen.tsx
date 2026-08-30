import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator, FlatList, Image, Modal, RefreshControl, ScrollView,
  StyleSheet, Text, TouchableOpacity, View,
} from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { productsApi, ProductFilters } from '../../api/products';
import { homeApi } from '../../api/home';
import { Category, Product } from '../../types';
import { useAuthStore } from '../../store/authStore';
import { useCartStore } from '../../store/cartStore';
import { useNotificationStore } from '../../store/notificationStore';
import ProductCard from '../../components/ProductCard';
import { apiErrorMessage } from '../../api/client';

const SORT_OPTIONS: { label: string; value: ProductFilters['sort'] }[] = [
  { label: 'Latest', value: 'latest' },
  { label: 'Price: Low to High', value: 'price_asc' },
  { label: 'Price: High to Low', value: 'price_desc' },
  { label: 'Name A–Z', value: 'name' },
];

const CATEGORY_ICONS: Record<string, string> = {
  Electronics: 'phone-portrait-outline',
  Fashion: 'shirt-outline',
  'Home & Kitchen': 'basket-outline',
  'Home & Living': 'home-outline',
  Beauty: 'flower-outline',
  'Beauty & Health': 'flower-outline',
  Sports: 'football-outline',
  Groceries: 'cart-outline',
};

function categoryIcon(name: string): string {
  return CATEGORY_ICONS[name] ?? 'pricetag-outline';
}

export default function HomeScreen({ navigation }: any) {
  const { user } = useAuthStore();
  const { addItem } = useCartStore();
  const { unreadCount, fetchUnreadCount } = useNotificationStore();

  const [categories, setCategories] = useState<Category[]>([]);
  const [activeCategoryId, setActiveCategoryId] = useState<number | null>(null);
  const [flashSale, setFlashSale] = useState<Product[]>([]);

  const [products, setProducts] = useState<Product[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');

  const [sort, setSort] = useState<ProductFilters['sort']>('latest');
  const [numColumns, setNumColumns] = useState<1 | 2>(2);
  const [sortModalVisible, setSortModalVisible] = useState(false);
  const [filterModalVisible, setFilterModalVisible] = useState(false);
  const [inStockOnly, setInStockOnly] = useState(false);

  useEffect(() => {
    productsApi.categories().then(res => setCategories(res.data.data)).catch(() => {});
    homeApi.get().then(res => setFlashSale(res.data.data.flash_sale.products)).catch(() => {});
    fetchUnreadCount();
  }, [fetchUnreadCount]);

  const loadProducts = useCallback(async (targetPage: number, replace: boolean) => {
    if (targetPage === 1) setLoading(replace ? false : true);
    else setLoadingMore(true);
    setError('');
    try {
      const res = await productsApi.list({
        category_id: activeCategoryId ?? undefined,
        sort,
        in_stock: inStockOnly || undefined,
        page: targetPage,
      });
      setProducts(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e));
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  }, [activeCategoryId, sort, inStockOnly]);

  useEffect(() => {
    loadProducts(1, false);
  }, [loadProducts]);

  const onRefresh = () => {
    setRefreshing(true);
    loadProducts(1, true);
  };

  const onEndReached = () => {
    if (!loadingMore && page < lastPage) loadProducts(page + 1, false);
  };

  const handleAddToCart = async (productId: number) => {
    try {
      await addItem(productId, 1);
    } catch {
      // silently ignore — the cart badge simply won't update
    }
  };

  const firstName = user?.name?.trim().split(' ')[0] ?? '';
  const activeSortLabel = SORT_OPTIONS.find(o => o.value === sort)?.label ?? 'Popular';

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.logoRow}>
          <Text style={styles.logo}>One<Text style={styles.logoDark}>Market</Text></Text>
          <View style={styles.badge}><Text style={styles.badgeText}>24/7</Text></View>
        </View>
        <View style={styles.headerIcons}>
          <TouchableOpacity onPress={() => navigation.getParent()?.navigate('SearchTab')} style={styles.iconBtn}>
            <IonIcon name="search-outline" size={22} color={COLORS.text} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.getParent()?.navigate('CartTab')} style={styles.iconBtn}>
            <IonIcon name="cart-outline" size={22} color={COLORS.text} />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => navigation.navigate('Notifications')} style={styles.iconBtn}>
            <View>
              <IonIcon name="notifications-outline" size={22} color={COLORS.text} />
              {unreadCount > 0 && (
                <View style={styles.notifBadge}><Text style={styles.notifBadgeText}>{unreadCount > 9 ? '9+' : unreadCount}</Text></View>
              )}
            </View>
          </TouchableOpacity>
        </View>
      </View>

      {firstName ? <Text style={styles.greeting}>Hello, {firstName} 👋</Text> : null}

      {/* Search bar */}
      <View style={styles.searchRow}>
        <TouchableOpacity style={styles.searchBar} activeOpacity={0.8} onPress={() => navigation.getParent()?.navigate('SearchTab')}>
          <IonIcon name="search" size={16} color={COLORS.textMuted} style={{ marginRight: 8 }} />
          <Text style={styles.searchPlaceholder}>Search for products, brands and categories</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.searchBtn} onPress={() => navigation.getParent()?.navigate('SearchTab')}>
          <IonIcon name="search" size={18} color={COLORS.white} />
        </TouchableOpacity>
      </View>

      <FlatList
        key={numColumns}
        data={products}
        keyExtractor={item => String(item.id)}
        numColumns={numColumns}
        columnWrapperStyle={numColumns === 2 ? styles.gridRow : undefined}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[COLORS.primary]} />}
        onEndReached={onEndReached}
        onEndReachedThreshold={0.4}
        renderItem={({ item }) => (
          <ProductCard
            product={item}
            width={numColumns === 2 ? undefined : '100%' as any}
            onPress={() => navigation.navigate('ProductDetail', { slug: item.slug })}
            onAddToCart={handleAddToCart}
          />
        )}
        ListHeaderComponent={
          <View>
            {/* Category pills */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.categoryList}>
              <TouchableOpacity style={styles.categoryItem} onPress={() => setActiveCategoryId(null)}>
                <View style={[styles.categoryIconBox, activeCategoryId === null && styles.categoryIconBoxActive]}>
                  <IonIcon name="apps" size={20} color={activeCategoryId === null ? COLORS.white : COLORS.text} />
                </View>
                <Text style={[styles.categoryName, activeCategoryId === null && styles.categoryNameActive]}>All</Text>
              </TouchableOpacity>
              {categories.slice(0, 8).map(cat => (
                <TouchableOpacity key={cat.id} style={styles.categoryItem} onPress={() => setActiveCategoryId(cat.id)}>
                  <View style={[styles.categoryIconBox, activeCategoryId === cat.id && styles.categoryIconBoxActive]}>
                    {cat.image ? (
                      <Image source={{ uri: cat.image }} style={styles.categoryImage} />
                    ) : (
                      <IonIcon name={categoryIcon(cat.name)} size={20} color={activeCategoryId === cat.id ? COLORS.white : COLORS.text} />
                    )}
                  </View>
                  <Text style={[styles.categoryName, activeCategoryId === cat.id && styles.categoryNameActive]} numberOfLines={1}>{cat.name}</Text>
                </TouchableOpacity>
              ))}
              <TouchableOpacity style={styles.categoryItem} onPress={() => navigation.getParent()?.navigate('CategoriesTab')}>
                <View style={styles.categoryIconBox}>
                  <IonIcon name="ellipsis-horizontal" size={20} color={COLORS.text} />
                </View>
                <Text style={styles.categoryName}>More</Text>
              </TouchableOpacity>
            </ScrollView>

            {/* Flash sale */}
            {flashSale.length > 0 && (
              <View style={styles.flashSection}>
                <Text style={styles.flashTitle}>⚡ Flash Sale</Text>
                <FlatList
                  data={flashSale}
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  keyExtractor={item => 'flash-' + item.id}
                  contentContainerStyle={{ paddingHorizontal: SIZES.screenPadding }}
                  renderItem={({ item }) => (
                    <ProductCard product={item} onPress={() => navigation.navigate('ProductDetail', { slug: item.slug })} onAddToCart={handleAddToCart} />
                  )}
                />
              </View>
            )}

            {/* All products toolbar */}
            <View style={styles.sectionHeaderRow}>
              <Text style={styles.sectionTitle}>All Products</Text>
            </View>
            <View style={styles.toolbar}>
              <TouchableOpacity style={styles.toolbarBtn} onPress={() => setFilterModalVisible(true)}>
                <IonIcon name="filter-outline" size={16} color={COLORS.text} />
                <Text style={styles.toolbarBtnText}>Filter</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.toolbarBtn} onPress={() => setSortModalVisible(true)}>
                <Text style={styles.toolbarBtnText}>Sort by: {activeSortLabel}</Text>
                <IonIcon name="chevron-down" size={14} color={COLORS.text} />
              </TouchableOpacity>
              <View style={styles.viewToggle}>
                <TouchableOpacity onPress={() => setNumColumns(2)} style={styles.viewToggleBtn}>
                  <IonIcon name="grid" size={18} color={numColumns === 2 ? COLORS.primary : COLORS.textMuted} />
                </TouchableOpacity>
                <TouchableOpacity onPress={() => setNumColumns(1)} style={styles.viewToggleBtn}>
                  <IonIcon name="list" size={18} color={numColumns === 1 ? COLORS.primary : COLORS.textMuted} />
                </TouchableOpacity>
              </View>
            </View>

            {error !== '' && (
              <TouchableOpacity style={styles.errorBanner} onPress={() => loadProducts(1, false)}>
                <IonIcon name="warning-outline" size={16} color="#fff" style={{ marginRight: 8 }} />
                <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
              </TouchableOpacity>
            )}

            {loading && <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 32 }} />}
          </View>
        }
        ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 20 }} /> : <View style={{ height: 24 }} />}
      />

      {/* Sort modal */}
      <Modal visible={sortModalVisible} transparent animationType="fade" onRequestClose={() => setSortModalVisible(false)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setSortModalVisible(false)}>
          <View style={styles.sortSheet}>
            <Text style={styles.sheetTitle}>Sort by</Text>
            {SORT_OPTIONS.map(opt => (
              <TouchableOpacity key={opt.value} style={styles.sortOption} onPress={() => { setSort(opt.value); setSortModalVisible(false); }}>
                <Text style={[styles.sortOptionText, sort === opt.value && styles.sortOptionTextActive]}>{opt.label}</Text>
                {sort === opt.value && <IonIcon name="checkmark" size={18} color={COLORS.primary} />}
              </TouchableOpacity>
            ))}
          </View>
        </TouchableOpacity>
      </Modal>

      {/* Filter modal */}
      <Modal visible={filterModalVisible} transparent animationType="fade" onRequestClose={() => setFilterModalVisible(false)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setFilterModalVisible(false)}>
          <View style={styles.sortSheet}>
            <Text style={styles.sheetTitle}>Filter</Text>
            <TouchableOpacity style={styles.sortOption} onPress={() => setInStockOnly(v => !v)}>
              <Text style={styles.sortOptionText}>In stock only</Text>
              <IonIcon name={inStockOnly ? 'checkbox' : 'square-outline'} size={20} color={inStockOnly ? COLORS.primary : COLORS.textMuted} />
            </TouchableOpacity>
            <TouchableOpacity style={styles.applyBtn} onPress={() => setFilterModalVisible(false)}>
              <Text style={styles.applyBtnText}>Apply</Text>
            </TouchableOpacity>
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 52, paddingBottom: 4, backgroundColor: COLORS.white,
  },
  logoRow: { flexDirection: 'row', alignItems: 'center' },
  logo: { fontSize: 20, fontWeight: 'bold', color: COLORS.primary },
  logoDark: { color: COLORS.text },
  badge: { backgroundColor: COLORS.accent, borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2, marginLeft: 6 },
  badgeText: { color: COLORS.white, fontWeight: 'bold', fontSize: 11 },
  headerIcons: { flexDirection: 'row', alignItems: 'center' },
  iconBtn: { padding: 6, marginLeft: 6 },
  notifBadge: {
    position: 'absolute', top: -4, right: -6, backgroundColor: COLORS.danger, borderRadius: 9,
    minWidth: 16, height: 16, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 3,
  },
  notifBadgeText: { color: COLORS.white, fontSize: 9, fontWeight: 'bold' },
  greeting: { fontSize: 13, color: COLORS.textSecondary, paddingHorizontal: SIZES.screenPadding, backgroundColor: COLORS.white, paddingBottom: 8 },

  searchRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SIZES.screenPadding, paddingVertical: 10, backgroundColor: COLORS.white, gap: 8 },
  searchBar: {
    flex: 1, flexDirection: 'row', alignItems: 'center',
    backgroundColor: COLORS.grayLight, borderRadius: SIZES.borderRadius, padding: 12,
    borderWidth: 1, borderColor: COLORS.border,
  },
  searchPlaceholder: { color: COLORS.textMuted, fontSize: 13, flex: 1 },
  searchBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, width: 44, height: 44, alignItems: 'center', justifyContent: 'center' },

  listContent: { paddingHorizontal: SIZES.screenPadding, paddingBottom: 24 },
  gridRow: { justifyContent: 'space-between' },

  categoryList: { paddingVertical: 12, gap: 4 },
  categoryItem: { width: 68, marginRight: 10, alignItems: 'center' },
  categoryIconBox: {
    width: 52, height: 52, borderRadius: 26, backgroundColor: COLORS.grayLight,
    alignItems: 'center', justifyContent: 'center', marginBottom: 6, borderWidth: 1, borderColor: COLORS.border, overflow: 'hidden',
  },
  categoryIconBoxActive: { backgroundColor: COLORS.accent, borderColor: COLORS.accent },
  categoryImage: { width: 52, height: 52 },
  categoryName: { fontSize: 11, color: COLORS.text, textAlign: 'center' },
  categoryNameActive: { color: COLORS.accent, fontWeight: '700' },

  flashSection: { backgroundColor: COLORS.white, paddingVertical: 14, marginBottom: 12, borderRadius: SIZES.borderRadius },
  flashTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.danger, paddingHorizontal: SIZES.screenPadding, marginBottom: 10 },

  sectionHeaderRow: { marginTop: 4, marginBottom: 10 },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', color: COLORS.text },

  toolbar: { flexDirection: 'row', alignItems: 'center', marginBottom: 14, gap: 8 },
  toolbarBtn: {
    flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border,
    borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 10, paddingVertical: 8, gap: 4, backgroundColor: COLORS.white,
  },
  toolbarBtnText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  viewToggle: { flexDirection: 'row', marginLeft: 'auto', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadiusSm, borderWidth: 1, borderColor: COLORS.border },
  viewToggleBtn: { padding: 8 },

  errorBanner: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.danger, padding: 10, borderRadius: SIZES.borderRadiusSm, marginBottom: 12 },
  errorBannerText: { color: '#fff', fontSize: 12, flex: 1 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sortSheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  sortOption: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  sortOptionText: { fontSize: 14, color: COLORS.text },
  sortOptionTextActive: { color: COLORS.primary, fontWeight: '700' },
  applyBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 16 },
  applyBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 14 },
});
