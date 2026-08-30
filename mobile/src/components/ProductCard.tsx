import React, { useState } from 'react';
import { ActivityIndicator, Dimensions, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../constants';
import { Product } from '../types';

// Grid math lives here (not duplicated per screen) so every product grid in
// the app — Home, Search, Category, Store, Wishlist — gets a card width that
// actually adds up to the screen width for a 2-column row. A hardcoded card
// width doesn't: on a 360dp-wide phone, two 168dp cards plus 16dp side
// padding is 368dp — 8dp wider than the screen, so the cards visibly crowd
// or clip each other. This derives the width instead, from whatever the
// device's screen actually is.
export const GRID_GAP = 12;
const { width: SCREEN_WIDTH } = Dimensions.get('window');
const DEFAULT_CARD_WIDTH = (SCREEN_WIDTH - SIZES.screenPadding * 2 - GRID_GAP) / 2;

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
  const cardWidth = width ?? DEFAULT_CARD_WIDTH;
  // HomeScreen's list-view toggle passes width="100%" (a string) for a
  // full-width row — the image can't be a "100%" height in that case (no
  // percentage-height parent), so it keeps the old fixed height instead.
  const imageHeight = typeof cardWidth === 'number' ? cardWidth : 150;

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
    <TouchableOpacity style={[styles.card, { width: cardWidth, marginBottom: GRID_GAP }]} onPress={onPress} activeOpacity={0.9}>
      <View style={[styles.imageBox, { height: imageHeight }]}>
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
            <IonIcon name={isWishlisted ? 'heart' : 'heart-outline'} size={15} color={isWishlisted ? COLORS.danger : COLORS.textSecondary} />
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
          <Text style={product.in_stock ? styles.stockText : styles.outOfStockText}>
            {product.in_stock ? 'In stock' : 'Out of stock'}
          </Text>
        </View>

        <View style={styles.priceRow}>
          <Text style={styles.price}>{price?.formatted ?? '—'}</Text>
          {product.compare_at_price ? (
            <Text style={styles.comparePrice}>{product.compare_at_price.formatted}</Text>
          ) : null}
        </View>

        {onAddToCart && (
          <TouchableOpacity
            style={[styles.addToCartBar, !product.in_stock && styles.addToCartBarDisabled]}
            onPress={handleAddToCart}
            activeOpacity={0.8}
            disabled={adding || !product.in_stock}
          >
            {adding ? (
              <ActivityIndicator size="small" color={COLORS.primary} />
            ) : (
              <>
                <IonIcon name="add" size={15} color={product.in_stock ? COLORS.primary : COLORS.textMuted} />
                <Text style={[styles.addToCartText, !product.in_stock && styles.addToCartTextDisabled]}>
                  Add to cart
                </Text>
              </>
            )}
          </TouchableOpacity>
        )}
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.white, borderRadius: 14,
    borderWidth: 1, borderColor: COLORS.border,
    shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.04, shadowRadius: 6,
    elevation: 1, overflow: 'hidden',
  },
  imageBox: { width: '100%', backgroundColor: COLORS.grayLight, position: 'relative' },
  image: { width: '100%', height: '100%' },
  imagePlaceholder: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  discountBadge: {
    position: 'absolute', top: 8, left: 8,
    backgroundColor: COLORS.accent, borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2,
  },
  discountText: { color: COLORS.white, fontSize: 11, fontWeight: 'bold' },
  wishlistBtn: {
    position: 'absolute', top: 8, right: 8,
    width: 28, height: 28, borderRadius: 14, backgroundColor: 'rgba(255,255,255,0.92)',
    alignItems: 'center', justifyContent: 'center',
  },
  info: { padding: 10 },
  brand: { fontSize: 10, color: COLORS.textMuted, marginBottom: 2, textTransform: 'uppercase' },
  name: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, lineHeight: 17, minHeight: 34 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  ratingText: { fontSize: 11, color: COLORS.text, marginLeft: 2, fontWeight: '600' },
  reviewCount: { fontSize: 11, color: COLORS.textMuted },
  stockText: { fontSize: 10, color: COLORS.accent, fontWeight: '600', marginLeft: 'auto' },
  outOfStockText: { fontSize: 10, color: COLORS.danger, fontWeight: '600', marginLeft: 'auto' },
  priceRow: { flexDirection: 'row', alignItems: 'baseline', flexWrap: 'wrap', marginBottom: 8 },
  price: { fontSize: 15, fontWeight: 'bold', color: COLORS.primary, marginRight: 6 },
  comparePrice: { fontSize: 11, color: COLORS.textMuted, textDecorationLine: 'line-through' },
  addToCartBar: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 4,
    borderWidth: 1.3, borderColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm,
    paddingVertical: 7,
  },
  addToCartBarDisabled: { borderColor: COLORS.border, backgroundColor: COLORS.grayLight },
  addToCartText: { color: COLORS.primary, fontSize: 11.5, fontWeight: '700' },
  addToCartTextDisabled: { color: COLORS.textMuted },
});
