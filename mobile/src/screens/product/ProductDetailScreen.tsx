import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator, Dimensions, Image, ScrollView, StyleSheet, Text,
  TextInput, TouchableOpacity, View,
} from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { productsApi } from '../../api/products';
import { apiErrorMessage } from '../../api/client';
import { ProductDetail, ProductVariation, Review } from '../../types';
import { useCartStore } from '../../store/cartStore';
import { useAuthStore } from '../../store/authStore';
import { useWishlistStore } from '../../store/wishlistStore';
import { compareApi, ProductQuestion, questionsApi } from '../../api/wishlist';
import { useLocaleStore } from '../../store/localeStore';
import { useToastStore } from '../../store/toastStore';

const { width } = Dimensions.get('window');

export default function ProductDetailScreen({ route, navigation }: any) {
  const { slug } = route.params as { slug: string };
  const { addItem, cart } = useCartStore();
  const { isAuthenticated } = useAuthStore();
  const { ids: wishlistIds, toggle: toggleWishlist, fetchWishlist } = useWishlistStore();
  const { language, currency } = useLocaleStore();

  const [product, setProduct] = useState<ProductDetail | null>(null);
  const [reviews, setReviews] = useState<Review[]>([]);
  const [questions, setQuestions] = useState<ProductQuestion[]>([]);
  const [newQuestion, setNewQuestion] = useState('');
  const [askingQuestion, setAskingQuestion] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeImage, setActiveImage] = useState(0);
  const [selectedAttributes, setSelectedAttributes] = useState<Record<string, string>>({});
  const [quantity, setQuantity] = useState(1);
  const [adding, setAdding] = useState(false);
  const [addedMessage, setAddedMessage] = useState('');
  const [addedToCompare, setAddedToCompare] = useState(false);

  useEffect(() => {
    setLoading(true);
    setError('');
    productsApi.show(slug)
      .then(res => setProduct(res.data.data))
      .catch(e => setError(apiErrorMessage(e, 'Could not load this product.')))
      .finally(() => setLoading(false));
    productsApi.reviews(slug).then(res => setReviews(res.data.data)).catch(() => {});
    questionsApi.list(slug).then(res => setQuestions(res.data.data)).catch(() => {});
    if (isAuthenticated) fetchWishlist();
  }, [slug, isAuthenticated, fetchWishlist, language, currency]);

  const handleToggleWishlist = () => {
    if (!product) return;
    if (!isAuthenticated) {
      navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Login' });
      return;
    }
    const wasWishlisted = wishlistIds.has(product.id);
    toggleWishlist(product.id);
    useToastStore.getState().show(wasWishlisted ? 'Removed from wishlist' : 'Added to wishlist');
  };

  const handleAddToCompare = async () => {
    if (!product) return;
    try {
      await compareApi.add(product.id);
      setAddedToCompare(true);
      useToastStore.getState().show('Added to compare list');
    } catch {
      // silently ignore — compare list simply won't include this item
    }
  };

  const handleAskQuestion = async () => {
    if (!product || !newQuestion.trim()) return;
    if (!isAuthenticated) {
      navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Login' });
      return;
    }
    setAskingQuestion(true);
    try {
      await questionsApi.ask(slug, newQuestion.trim());
      setNewQuestion('');
      const res = await questionsApi.list(slug);
      setQuestions(res.data.data);
    } catch {
      // the input simply stays filled so the shopper can retry
    } finally {
      setAskingQuestion(false);
    }
  };

  const attributeGroups = useMemo(() => {
    if (!product) return {} as Record<string, string[]>;
    const groups: Record<string, string[]> = {};
    product.variations.forEach(v => {
      v.attributes.forEach(a => {
        groups[a.attribute] = groups[a.attribute] ?? [];
        if (!groups[a.attribute].includes(a.value)) groups[a.attribute].push(a.value);
      });
    });
    return groups;
  }, [product]);

  const matchedVariation: ProductVariation | undefined = useMemo(() => {
    if (!product || product.variations.length === 0) return undefined;
    return product.variations.find(v =>
      v.attributes.every(a => selectedAttributes[a.attribute] === a.value) &&
      v.attributes.length === Object.keys(selectedAttributes).length,
    );
  }, [product, selectedAttributes]);

  if (loading) {
    return (
      <View style={styles.centerFlex}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  if (error || !product) {
    return (
      <View style={styles.centerFlex}>
        <IonIcon name="alert-circle-outline" size={40} color={COLORS.danger} />
        <Text style={styles.errorText}>{error || 'Product not found.'}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.retryBtnText}>Go Back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const displayPrice = matchedVariation?.price ?? product.price ?? product.price_range?.min ?? null;
  const savings = product.compare_at_price && displayPrice
    ? product.compare_at_price.amount - displayPrice.amount
    : 0;

  const handleAddToCart = async (thenBuy = false) => {
    if (!product.in_stock) return;
    if (Object.keys(attributeGroups).length > 0 && !matchedVariation) {
      setAddedMessage('Please select ' + Object.keys(attributeGroups).join(', ').toLowerCase() + '.');
      return;
    }
    setAdding(true);
    setAddedMessage('');
    try {
      await addItem(product.id, quantity, matchedVariation?.id);
      setAddedMessage(thenBuy ? '' : 'Added to cart!');
      useToastStore.getState().show('Added to cart!');
      if (thenBuy) navigation.getParent()?.navigate('CartTab');
    } catch (e) {
      setAddedMessage(apiErrorMessage(e, 'Could not add to cart.'));
    } finally {
      setAdding(false);
    }
  };

  const images = product.images.length > 0 ? product.images : [{ url: '', thumbnail: '' }];

  return (
    <View style={styles.flex}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 100 }}>
        {/* Header */}
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.headerIconBtn}>
            <IonIcon name="arrow-back" size={22} color={COLORS.text} />
          </TouchableOpacity>
          <View style={styles.headerRightIcons}>
            <TouchableOpacity style={styles.headerIconBtn} onPress={handleAddToCompare}>
              <IonIcon name={addedToCompare ? 'git-compare' : 'git-compare-outline'} size={20} color={addedToCompare ? COLORS.primary : COLORS.text} />
            </TouchableOpacity>
            <TouchableOpacity style={styles.headerIconBtn} onPress={handleToggleWishlist}>
              <IonIcon name={wishlistIds.has(product.id) ? 'heart' : 'heart-outline'} size={20} color={wishlistIds.has(product.id) ? COLORS.danger : COLORS.text} />
            </TouchableOpacity>
            <TouchableOpacity style={styles.headerIconBtn} onPress={() => navigation.getParent()?.navigate('CartTab')}>
              <View>
                <IonIcon name="cart-outline" size={20} color={COLORS.text} />
                {cart && cart.items.length > 0 && (
                  <View style={styles.cartBadge}><Text style={styles.cartBadgeText}>{cart.items.length}</Text></View>
                )}
              </View>
            </TouchableOpacity>
          </View>
        </View>

        {/* Image */}
        <View style={styles.imageWrap}>
          {images[activeImage]?.url ? (
            <Image source={{ uri: images[activeImage].url }} style={styles.mainImage} resizeMode="contain" />
          ) : (
            <View style={[styles.mainImage, styles.imagePlaceholder]}>
              <IonIcon name="image-outline" size={64} color={COLORS.border} />
            </View>
          )}
          {product.discount_percent ? (
            <View style={styles.discountBadge}><Text style={styles.discountText}>-{product.discount_percent}%</Text></View>
          ) : null}
        </View>

        {images.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.thumbRow} contentContainerStyle={{ paddingHorizontal: SIZES.screenPadding }}>
            {images.map((img, idx) => (
              <TouchableOpacity key={idx} onPress={() => setActiveImage(idx)} style={[styles.thumbBox, activeImage === idx && styles.thumbBoxActive]}>
                {img.thumbnail ? <Image source={{ uri: img.thumbnail }} style={styles.thumbImage} /> : null}
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        <View style={styles.contentBox}>
          {product.brand ? <Text style={styles.brand}>{product.brand.name}</Text> : null}
          <View style={styles.titleRow}>
            <Text style={styles.name}>{product.name}</Text>
            {product.in_stock ? (
              <View style={styles.stockBadge}><Text style={styles.stockBadgeText}>In Stock</Text></View>
            ) : (
              <View style={[styles.stockBadge, styles.outOfStockBadge]}><Text style={styles.stockBadgeText}>Out of Stock</Text></View>
            )}
          </View>

          <View style={styles.ratingRow}>
            <IonIcon name="star" size={14} color={COLORS.star} />
            <Text style={styles.ratingText}>{product.rating.toFixed(1)}</Text>
            <Text style={styles.reviewCount}>({product.review_count} reviews)</Text>
          </View>

          <View style={styles.priceRow}>
            <Text style={styles.price}>{displayPrice?.formatted ?? '—'}</Text>
            {product.compare_at_price ? <Text style={styles.comparePrice}>{product.compare_at_price.formatted}</Text> : null}
          </View>
          {savings > 0 && (
            <Text style={styles.savings}>You save {(savings / 100).toLocaleString('en-NG', { style: 'currency', currency: displayPrice?.currency ?? 'NGN' })}</Text>
          )}

          {/* Trust badges */}
          <View style={styles.trustRow}>
            {[
              { icon: 'shield-checkmark-outline', label: '100% Original' },
              { icon: 'bicycle-outline', label: 'Fast Delivery' },
              { icon: 'refresh-outline', label: '7 Days Return' },
              { icon: 'ribbon-outline', label: 'Warranty' },
            ].map(b => (
              <View key={b.label} style={styles.trustItem}>
                <IonIcon name={b.icon} size={18} color={COLORS.primary} />
                <Text style={styles.trustLabel}>{b.label}</Text>
              </View>
            ))}
          </View>

          {/* Variation pickers */}
          {Object.entries(attributeGroups).map(([attribute, values]) => (
            <View key={attribute} style={styles.variationSection}>
              <Text style={styles.variationLabel}>Select {attribute}: {selectedAttributes[attribute] ?? ''}</Text>
              <View style={styles.variationRow}>
                {values.map(value => (
                  <TouchableOpacity
                    key={value}
                    style={[styles.variationChip, selectedAttributes[attribute] === value && styles.variationChipActive]}
                    onPress={() => setSelectedAttributes(prev => ({ ...prev, [attribute]: value }))}
                  >
                    <Text style={[styles.variationChipText, selectedAttributes[attribute] === value && styles.variationChipTextActive]}>{value}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          ))}

          {/* Quantity */}
          <Text style={styles.variationLabel}>Quantity</Text>
          <View style={styles.qtyRow}>
            <TouchableOpacity style={styles.qtyBtn} onPress={() => setQuantity(q => Math.max(1, q - 1))}>
              <IonIcon name="remove" size={16} color={COLORS.text} />
            </TouchableOpacity>
            <Text style={styles.qtyValue}>{quantity}</Text>
            <TouchableOpacity style={styles.qtyBtn} onPress={() => setQuantity(q => q + 1)}>
              <IonIcon name="add" size={16} color={COLORS.text} />
            </TouchableOpacity>
          </View>

          {product.vendor && (
            <TouchableOpacity style={styles.vendorRow} onPress={() => navigation.navigate('Store', { slug: product.vendor!.store_slug })}>
              <IonIcon name="storefront-outline" size={16} color={COLORS.textSecondary} />
              <Text style={styles.vendorText}>Sold by {product.vendor.store_name}</Text>
              <IonIcon name="chevron-forward" size={14} color={COLORS.textMuted} />
            </TouchableOpacity>
          )}

          {addedMessage ? <Text style={styles.addedMessage}>{addedMessage}</Text> : null}

          {product.short_description ? (
            <>
              <Text style={styles.sectionTitle}>About this item</Text>
              <Text style={styles.description}>{product.short_description}</Text>
            </>
          ) : null}
          {product.description ? <Text style={styles.description}>{product.description}</Text> : null}

          {/* Reviews */}
          <View style={styles.reviewsHeaderRow}>
            <Text style={styles.sectionTitle}>Reviews ({product.review_count})</Text>
            <TouchableOpacity
              style={styles.writeReviewBtn}
              onPress={() => {
                if (!isAuthenticated) {
                  navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Login' });
                  return;
                }
                navigation.navigate('WriteReview', { slug: product.slug, productName: product.name });
              }}
            >
              <IonIcon name="create-outline" size={14} color={COLORS.primary} />
              <Text style={styles.writeReviewText}>Write a Review</Text>
            </TouchableOpacity>
          </View>
          {reviews.length === 0 ? (
            <Text style={styles.noReviews}>No reviews yet.</Text>
          ) : (
            reviews.slice(0, 5).map(review => (
              <View key={review.id} style={styles.reviewCard}>
                <View style={styles.reviewHeader}>
                  <Text style={styles.reviewAuthor}>{review.customer_name ?? 'Anonymous'}</Text>
                  <View style={styles.reviewStars}>
                    {Array.from({ length: 5 }).map((_, i) => (
                      <IonIcon key={i} name="star" size={11} color={i < review.rating ? COLORS.star : COLORS.border} />
                    ))}
                  </View>
                </View>
                {review.title ? <Text style={styles.reviewTitle}>{review.title}</Text> : null}
                {review.body ? <Text style={styles.reviewBody}>{review.body}</Text> : null}
                {review.images && review.images.length > 0 && (
                  <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.reviewImagesRow}>
                    {review.images.map((url, idx) => (
                      <Image key={idx} source={{ uri: url }} style={styles.reviewImage} />
                    ))}
                  </ScrollView>
                )}
              </View>
            ))
          )}

          {/* Questions & Answers */}
          <Text style={styles.sectionTitle}>Questions & Answers</Text>
          <View style={styles.askRow}>
            <TextInput
              style={styles.askInput}
              placeholder="Ask a question about this product"
              placeholderTextColor={COLORS.placeholder}
              value={newQuestion}
              onChangeText={setNewQuestion}
            />
            <TouchableOpacity style={styles.askBtn} onPress={handleAskQuestion} disabled={askingQuestion || !newQuestion.trim()}>
              {askingQuestion ? <ActivityIndicator size="small" color={COLORS.white} /> : <IonIcon name="send" size={16} color={COLORS.white} />}
            </TouchableOpacity>
          </View>
          {questions.length === 0 ? (
            <Text style={styles.noReviews}>No questions yet — be the first to ask.</Text>
          ) : (
            questions.slice(0, 5).map(q => (
              <View key={q.id} style={styles.reviewCard}>
                <Text style={styles.questionText}>Q: {q.question}</Text>
                {q.answers?.map(a => (
                  <Text key={a.id} style={styles.answerText}>A: {a.answer}{a.answered_by ? ` — ${a.answered_by}` : ''}</Text>
                ))}
              </View>
            ))
          )}
        </View>
      </ScrollView>

      {/* Sticky bottom bar */}
      <View style={styles.bottomBar}>
        <TouchableOpacity
          style={[styles.addToCartBtn, !product.in_stock && styles.disabledBtn]}
          onPress={() => handleAddToCart(false)}
          disabled={adding || !product.in_stock}
        >
          {adding ? <ActivityIndicator size="small" color={COLORS.primary} /> : (
            <>
              <IonIcon name="cart-outline" size={18} color={COLORS.primary} />
              <Text style={styles.addToCartText}>Add to Cart</Text>
            </>
          )}
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.buyNowBtn, !product.in_stock && styles.disabledBtn]}
          onPress={() => handleAddToCart(true)}
          disabled={adding || !product.in_stock}
        >
          <Text style={styles.buyNowText}>Buy Now</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white, padding: SIZES.xxl },
  errorText: { color: COLORS.textSecondary, marginTop: 12, marginBottom: 20, textAlign: 'center' },
  retryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingHorizontal: 24, paddingVertical: 12 },
  retryBtnText: { color: COLORS.white, fontWeight: 'bold' },

  header: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 8,
  },
  headerIconBtn: { padding: 6, marginLeft: 4 },
  headerRightIcons: { flexDirection: 'row' },
  cartBadge: {
    position: 'absolute', top: -4, right: -6, backgroundColor: COLORS.primary, borderRadius: 8,
    minWidth: 15, height: 15, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 2,
  },
  cartBadgeText: { color: COLORS.white, fontSize: 9, fontWeight: 'bold' },

  imageWrap: { width, height: width * 0.85, backgroundColor: COLORS.grayLight, position: 'relative' },
  mainImage: { width: '100%', height: '100%' },
  imagePlaceholder: { alignItems: 'center', justifyContent: 'center' },
  discountBadge: { position: 'absolute', top: 12, left: SIZES.screenPadding, backgroundColor: COLORS.accent, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 4 },
  discountText: { color: COLORS.white, fontWeight: 'bold', fontSize: 12 },

  thumbRow: { marginTop: 10 },
  thumbBox: { width: 56, height: 56, borderRadius: 8, borderWidth: 1, borderColor: COLORS.border, marginRight: 8, overflow: 'hidden' },
  thumbBoxActive: { borderColor: COLORS.primary, borderWidth: 2 },
  thumbImage: { width: '100%', height: '100%' },

  contentBox: { padding: SIZES.screenPadding },
  brand: { fontSize: 12, color: COLORS.textMuted, textTransform: 'uppercase', marginBottom: 4 },
  titleRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 8 },
  name: { fontSize: 18, fontWeight: 'bold', color: COLORS.text, flex: 1, lineHeight: 24 },
  stockBadge: { backgroundColor: COLORS.accent, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 4 },
  outOfStockBadge: { backgroundColor: COLORS.danger },
  stockBadgeText: { color: COLORS.white, fontSize: 11, fontWeight: 'bold' },

  ratingRow: { flexDirection: 'row', alignItems: 'center', marginTop: 8 },
  ratingText: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginLeft: 4 },
  reviewCount: { fontSize: 12, color: COLORS.textMuted, marginLeft: 4 },

  priceRow: { flexDirection: 'row', alignItems: 'baseline', marginTop: 12, gap: 10 },
  price: { fontSize: 24, fontWeight: 'bold', color: COLORS.primary },
  comparePrice: { fontSize: 14, color: COLORS.textMuted, textDecorationLine: 'line-through' },
  savings: { fontSize: 12, color: COLORS.accent, fontWeight: '600', marginTop: 4 },

  trustRow: { flexDirection: 'row', justifyContent: 'space-between', backgroundColor: COLORS.grayLight, borderRadius: SIZES.borderRadius, padding: 12, marginTop: 16 },
  trustItem: { alignItems: 'center', flex: 1 },
  trustLabel: { fontSize: 9, color: COLORS.textSecondary, textAlign: 'center', marginTop: 4 },

  variationSection: { marginTop: 18 },
  variationLabel: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 8, marginTop: 14 },
  variationRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  variationChip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 14, paddingVertical: 8 },
  variationChipActive: { borderColor: COLORS.primary, backgroundColor: '#FFF3EB' },
  variationChipText: { fontSize: 13, color: COLORS.text },
  variationChipTextActive: { color: COLORS.primary, fontWeight: '700' },

  qtyRow: { flexDirection: 'row', alignItems: 'center' },
  qtyBtn: { width: 34, height: 34, borderRadius: 8, borderWidth: 1, borderColor: COLORS.border, alignItems: 'center', justifyContent: 'center' },
  qtyValue: { fontSize: 15, fontWeight: '700', color: COLORS.text, marginHorizontal: 18 },

  vendorRow: { flexDirection: 'row', alignItems: 'center', marginTop: 18, gap: 6 },
  vendorText: { fontSize: 13, color: COLORS.textSecondary },

  addedMessage: { marginTop: 12, color: COLORS.accent, fontWeight: '600', fontSize: 13 },

  sectionTitle: { fontSize: 15, fontWeight: 'bold', color: COLORS.text, marginTop: 24, marginBottom: 8 },
  description: { fontSize: 13, color: COLORS.textSecondary, lineHeight: 20, marginBottom: 8 },

  noReviews: { fontSize: 13, color: COLORS.textMuted },
  askRow: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  askInput: { flex: 1, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  askBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, width: 40, alignItems: 'center', justifyContent: 'center' },
  questionText: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 4 },
  answerText: { fontSize: 12, color: COLORS.textSecondary, marginLeft: 8, marginTop: 2, lineHeight: 17 },
  reviewsHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  writeReviewBtn: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  writeReviewText: { fontSize: 12, color: COLORS.primary, fontWeight: '700' },
  reviewCard: { borderBottomWidth: 1, borderBottomColor: COLORS.divider, paddingVertical: 12 },
  reviewImagesRow: { marginTop: 8 },
  reviewImage: { width: 64, height: 64, borderRadius: 8, marginRight: 8 },
  reviewHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  reviewAuthor: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  reviewStars: { flexDirection: 'row' },
  reviewTitle: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 2 },
  reviewBody: { fontSize: 12, color: COLORS.textSecondary, lineHeight: 18 },

  bottomBar: {
    position: 'absolute', bottom: 0, left: 0, right: 0, flexDirection: 'row',
    padding: SIZES.screenPadding, backgroundColor: COLORS.white, borderTopWidth: 1, borderTopColor: COLORS.border, gap: 10,
  },
  addToCartBtn: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6,
    borderWidth: 1.5, borderColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14,
  },
  addToCartText: { color: COLORS.primary, fontWeight: 'bold', fontSize: 14 },
  buyNowBtn: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14 },
  buyNowText: { color: COLORS.white, fontWeight: 'bold', fontSize: 14 },
  disabledBtn: { opacity: 0.5 },
});
