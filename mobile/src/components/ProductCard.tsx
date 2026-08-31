import React, { useState } from 'react';
import { ActivityIndicator, Dimensions, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../constants';
import { Product } from '../types';

// Grid math lives here (not duplicated per screen) so every product grid in
// the app — Home, Search, Category, Store, Wishlist — gets a card width
// that actually adds up to the screen width for however many columns the
// admin has configured (App\Models\AppSetting's product_grid_columns, read
// via useBootstrapStore — see bootstrapStore.ts). A hardcoded card width
// doesn't: two fixed-width cards plus side padding is rarely exactly the
// screen width, so cards visibly crowd or clip.
export const GRID_GAP = 12;
const { width: SCREEN_WIDTH } = Dimensions.get('window');

export function computeGridCardWidth(columns: number): number {
  return (SCREEN_WIDTH - SIZES.screenPadding * 2 - GRID_GAP * (columns - 1)) / columns;
}

const DEFAULT_CARD_WIDTH = computeGridCardWidth(4);

// The card's text/spacing was originally designed around a 2-column
// (~170dp-wide) card. At 4+ columns a card is much narrower, so this scales
// font sizes and padding down proportionally rather than letting a 4-column
// card cram 2-column-sized text into a much smaller box. Clamped so a
// wide (e.g. 2-column) card never scales up enough to look oversized.
const REFERENCE_CARD_WIDTH = 170;

function scale(cardWidth: number): number {
  return Math.min(1, Math.max(0.62, cardWidth / REFERENCE_CARD_WIDTH));
}

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
  const isNumericWidth = typeof cardWidth === 'number';
  const imageHeight = isNumericWidth ? cardWidth : 150;
  const s = isNumericWidth ? scale(cardWidth) : 1;

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
            <IonIcon name="bag-handle-outline" size={32 * s} color={COLORS.border} />
          </View>
        )}

        {product.discount_percent ? (
          <View style={[styles.discountBadge, { paddingHorizontal: 6 * s, paddingVertical: 2 * s }]}>
            <Text style={[styles.discountText, { fontSize: 11 * s }]}>-{product.discount_percent}%</Text>
          </View>
        ) : null}

        {onToggleWishlist && (
          <TouchableOpacity
            style={[styles.wishlistBtn, { width: 28 * s, height: 28 * s, borderRadius: 14 * s }]}
            onPress={() => onToggleWishlist(product.id)}
            hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
          >
            <IonIcon name={isWishlisted ? 'heart' : 'heart-outline'} size={15 * s} color={isWishlisted ? COLORS.danger : COLORS.textSecondary} />
          </TouchableOpacity>
        )}
      </View>

      <View style={[styles.info, { padding: 10 * s }]}>
        <Text style={[styles.brand, { fontSize: 10 * s }]} numberOfLines={1}>{product.brand?.name ?? ' '}</Text>
        <Text style={[styles.name, { fontSize: 13 * s, lineHeight: 17 * s, minHeight: 34 * s }]} numberOfLines={2}>{product.name}</Text>

        <View style={styles.ratingRow}>
          <IonIcon name="star" size={11 * s} color={COLORS.star} />
          <Text style={[styles.ratingText, { fontSize: 11 * s }]}>{(Number(product.rating) || 0).toFixed(1)}</Text>
          <Text style={[styles.reviewCount, { fontSize: 11 * s }]}> ({product.review_count})</Text>
          <Text style={[product.in_stock ? styles.stockText : styles.outOfStockText, { fontSize: 10 * s }]}>
            {product.in_stock ? 'In stock' : 'Out of stock'}
          </Text>
        </View>

        <View style={styles.priceRow}>
          <Text style={[styles.price, { fontSize: 15 * s }]}>{price?.formatted ?? '—'}</Text>
          {product.compare_at_price ? (
            <Text style={[styles.comparePrice, { fontSize: 11 * s }]}>{product.compare_at_price.formatted}</Text>
          ) : null}
        </View>

        {onAddToCart && (
          <TouchableOpacity
            style={[styles.addToCartBar, { paddingVertical: 7 * s }, !product.in_stock && styles.addToCartBarDisabled]}
            onPress={handleAddToCart}
            activeOpacity={0.8}
            disabled={adding || !product.in_stock}
          >
            {adding ? (
              <ActivityIndicator size="small" color={COLORS.primary} />
            ) : (
              <>
                <IonIcon name="add" size={15 * s} color={product.in_stock ? COLORS.primary : COLORS.textMuted} />
                <Text style={[styles.addToCartText, { fontSize: 11.5 * s }, !product.in_stock && styles.addToCartTextDisabled]}>
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
    backgroundColor: COLORS.accent, borderRadius: 4,
  },
  discountText: { color: COLORS.white, fontWeight: 'bold' },
  wishlistBtn: {
    position: 'absolute', top: 8, right: 8,
    backgroundColor: 'rgba(255,255,255,0.92)',
    alignItems: 'center', justifyContent: 'center',
  },
  info: {},
  brand: { color: COLORS.textMuted, marginBottom: 2, textTransform: 'uppercase' },
  name: { fontWeight: '600', color: COLORS.text, marginBottom: 6 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  ratingText: { color: COLORS.text, marginLeft: 2, fontWeight: '600' },
  reviewCount: { color: COLORS.textMuted },
  stockText: { color: COLORS.accent, fontWeight: '600', marginLeft: 'auto' },
  outOfStockText: { color: COLORS.danger, fontWeight: '600', marginLeft: 'auto' },
  priceRow: { flexDirection: 'row', alignItems: 'baseline', flexWrap: 'wrap', marginBottom: 8 },
  price: { fontWeight: 'bold', color: COLORS.primary, marginRight: 6 },
  comparePrice: { color: COLORS.textMuted, textDecorationLine: 'line-through' },
  addToCartBar: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 4,
    borderWidth: 1.3, borderColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm,
  },
  addToCartBarDisabled: { borderColor: COLORS.border, backgroundColor: COLORS.grayLight },
  addToCartText: { color: COLORS.primary, fontWeight: '700' },
  addToCartTextDisabled: { color: COLORS.textMuted },
});
