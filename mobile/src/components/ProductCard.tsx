import React, { useState } from 'react';
import { ActivityIndicator, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../constants';
import { Product } from '../types';

export default function ProductCard({
  product,
  onPress,
  onAddToCart,
  onToggleWishlist,
  isWishlisted,
  width,
}: {
  product: Product;
  onPress: () => void;
  onAddToCart?: (id: number) => void;
  onToggleWishlist?: (id: number) => void;
  isWishlisted?: boolean;
  width?: number;
}) {
  const [adding, setAdding] = useState(false);
  const price = product.price ?? product.price_range?.min ?? null;

  const handleAddToCart = async () => {
    if (adding || !onAddToCart) return;
    setAdding(true);
    try {
      await onAddToCart(product.id);
    } finally {
      setAdding(false);
    }
  };

  return (
    <TouchableOpacity style={[styles.card, width ? { width } : null]} onPress={onPress} activeOpacity={0.88}>
      <View style={styles.imageBox}>
        {product.thumbnail ? (
          <Image source={{ uri: product.thumbnail }} style={styles.image} resizeMode="cover" />
        ) : (
          <View style={styles.imagePlaceholder}>
            <IonIcon name="bag-handle-outline" size={32} color={COLORS.border} />
          </View>
        )}

        {product.discount_percent ? (
          <View style={styles.discountBadge}>
            <Text style={styles.discountText}>-{product.discount_percent}%</Text>
          </View>
        ) : null}

        {onToggleWishlist && (
          <TouchableOpacity style={styles.wishlistBtn} onPress={() => onToggleWishlist(product.id)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
            <IonIcon name={isWishlisted ? 'heart' : 'heart-outline'} size={16} color={isWishlisted ? COLORS.danger : COLORS.textSecondary} />
          </TouchableOpacity>
        )}
      </View>

      <View style={styles.info}>
        <Text style={styles.brand} numberOfLines={1}>{product.brand?.name ?? ' '}</Text>
        <Text style={styles.name} numberOfLines={2}>{product.name}</Text>

        <View style={styles.ratingRow}>
          <IonIcon name="star" size={11} color={COLORS.star} />
          <Text style={styles.ratingText}>{(Number(product.rating) || 0).toFixed(1)}</Text>
          <Text style={styles.reviewCount}> ({product.review_count})</Text>
        </View>

        <View style={styles.priceRow}>
          <Text style={styles.price}>{price?.formatted ?? '—'}</Text>
          {product.compare_at_price ? (
            <Text style={styles.comparePrice}>{product.compare_at_price.formatted}</Text>
          ) : null}
        </View>

        <View style={styles.footerRow}>
          {product.in_stock ? (
            <Text style={styles.stockText}>In Stock</Text>
          ) : (
            <Text style={styles.outOfStockText}>Out of Stock</Text>
          )}
          {onAddToCart && (
            <TouchableOpacity style={styles.cartFab} onPress={handleAddToCart} activeOpacity={0.85} disabled={adding || !product.in_stock}>
              {adding ? <ActivityIndicator size="small" color={COLORS.white} /> : <IonIcon name="cart-outline" size={14} color={COLORS.white} />}
            </TouchableOpacity>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    width: 168, backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius,
    marginBottom: 4, elevation: 2, shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 4,
    overflow: 'hidden',
  },
  imageBox: { width: '100%', height: 150, backgroundColor: COLORS.grayLight, position: 'relative' },
  image: { width: '100%', height: '100%' },
  imagePlaceholder: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  discountBadge: {
    position: 'absolute', top: 8, left: 8,
    backgroundColor: COLORS.accent, borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2,
  },
  discountText: { color: COLORS.white, fontSize: 11, fontWeight: 'bold' },
  wishlistBtn: {
    position: 'absolute', top: 8, right: 8,
    width: 28, height: 28, borderRadius: 14, backgroundColor: 'rgba(255,255,255,0.9)',
    alignItems: 'center', justifyContent: 'center',
  },
  info: { padding: 10 },
  brand: { fontSize: 10, color: COLORS.textMuted, marginBottom: 2, textTransform: 'uppercase' },
  name: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, lineHeight: 17, minHeight: 34 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  ratingText: { fontSize: 11, color: COLORS.text, marginLeft: 2, fontWeight: '600' },
  reviewCount: { fontSize: 11, color: COLORS.textMuted },
  priceRow: { flexDirection: 'row', alignItems: 'baseline', flexWrap: 'wrap', marginBottom: 4 },
  price: { fontSize: 15, fontWeight: 'bold', color: COLORS.primary, marginRight: 6 },
  comparePrice: { fontSize: 11, color: COLORS.textMuted, textDecorationLine: 'line-through' },
  stockText: { fontSize: 11, color: COLORS.accent, fontWeight: '600' },
  outOfStockText: { fontSize: 11, color: COLORS.danger, fontWeight: '600' },
  footerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 2 },
  cartFab: {
    backgroundColor: COLORS.primary, borderRadius: 14,
    width: 26, height: 26, alignItems: 'center', justifyContent: 'center',
  },
});
