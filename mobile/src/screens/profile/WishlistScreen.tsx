import React, { useCallback } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useWishlistStore } from '../../store/wishlistStore';
import { useCartStore } from '../../store/cartStore';
import ProductCard from '../../components/ProductCard';

export default function WishlistScreen({ navigation }: any) {
  const { items, isLoading, fetchWishlist, toggle } = useWishlistStore();
  const { addItem } = useCartStore();

  useFocusEffect(useCallback(() => { fetchWishlist(); }, [fetchWishlist]));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>My Wishlist</Text>
        <View style={styles.backSpacer} />
      </View>

      {isLoading && items.length === 0 ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loader} />
      ) : items.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="heart-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>Your wishlist is empty.</Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={item => String(item.id)}
          numColumns={2}
          columnWrapperStyle={styles.row}
          contentContainerStyle={styles.list}
          renderItem={({ item }) => (
            <ProductCard
              product={item}
              onPress={() => navigation.navigate('ProductDetail', { slug: item.slug })}
              onAddToCart={id => addItem(id, 1)}
              onToggleWishlist={toggle}
              isWishlisted
            />
          )}
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
  backSpacer: { width: 22 },
  loader: { marginTop: 40 },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  row: { justifyContent: 'space-between' },
  list: { padding: SIZES.screenPadding },
});
