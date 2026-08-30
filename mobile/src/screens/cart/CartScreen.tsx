import React, { useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, Image, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useCartStore } from '../../store/cartStore';
import { useAuthStore } from '../../store/authStore';
import { CartItem } from '../../types';
import { apiErrorMessage } from '../../api/client';

export default function CartScreen({ navigation }: any) {
  const { cart, fetchCart, updateItem, removeItem, applyCoupon, removeCoupon, isLoading } = useCartStore();
  const { isAuthenticated } = useAuthStore();
  const [couponCode, setCouponCode] = useState('');
  const [couponError, setCouponError] = useState('');
  const [applyingCoupon, setApplyingCoupon] = useState(false);

  useEffect(() => {
    fetchCart();
  }, [fetchCart]);

  const handleApplyCoupon = async () => {
    if (!couponCode.trim()) return;
    setApplyingCoupon(true);
    setCouponError('');
    try {
      await applyCoupon(couponCode.trim());
      setCouponCode('');
    } catch (e) {
      setCouponError(apiErrorMessage(e, 'That coupon code is invalid.'));
    } finally {
      setApplyingCoupon(false);
    }
  };

  const items = cart?.items ?? [];

  if (isLoading && !cart) {
    return (
      <View style={styles.centerFlex}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  if (items.length === 0) {
    return (
      <View style={styles.centerFlex}>
        <IonIcon name="cart-outline" size={64} color={COLORS.border} />
        <Text style={styles.emptyTitle}>Your cart is empty</Text>
        <Text style={styles.emptySubtitle}>Browse products and add items to get started.</Text>
        <TouchableOpacity style={styles.shopBtn} onPress={() => navigation.getParent()?.navigate('HomeTab')}>
          <Text style={styles.shopBtnText}>Start Shopping</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>My Cart ({items.length})</Text>
      </View>

      <FlatList
        data={items}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={{ padding: SIZES.screenPadding, paddingBottom: 220 }}
        renderItem={({ item }) => (
          <CartLineItem
            item={item}
            onIncrement={() => updateItem(item.id, item.quantity + 1)}
            onDecrement={() => item.quantity > 1 && updateItem(item.id, item.quantity - 1)}
            onRemove={() => removeItem(item.id)}
          />
        )}
        ListFooterComponent={
          <View style={styles.couponBox}>
            {cart?.coupon ? (
              <View style={styles.couponApplied}>
                <Text style={styles.couponAppliedText}>Coupon "{cart.coupon.code}" applied — {cart.coupon.discount.formatted} off</Text>
                <TouchableOpacity onPress={() => removeCoupon()}><IonIcon name="close-circle" size={18} color={COLORS.textSecondary} /></TouchableOpacity>
              </View>
            ) : (
              <View style={styles.couponRow}>
                <TextInput
                  style={styles.couponInput}
                  placeholder="Enter coupon code"
                  placeholderTextColor={COLORS.placeholder}
                  value={couponCode}
                  onChangeText={setCouponCode}
                  autoCapitalize="characters"
                />
                <TouchableOpacity style={styles.couponBtn} onPress={handleApplyCoupon} disabled={applyingCoupon}>
                  {applyingCoupon ? <ActivityIndicator size="small" color={COLORS.white} /> : <Text style={styles.couponBtnText}>Apply</Text>}
                </TouchableOpacity>
              </View>
            )}
            {couponError ? <Text style={styles.couponError}>{couponError}</Text> : null}
          </View>
        }
      />

      <View style={styles.summaryBar}>
        <View style={styles.summaryRow}>
          <Text style={styles.summaryLabel}>Subtotal</Text>
          <Text style={styles.summaryValue}>{cart?.subtotal.formatted}</Text>
        </View>
        {cart && cart.discount.amount > 0 && (
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Discount</Text>
            <Text style={[styles.summaryValue, { color: COLORS.accent }]}>-{cart.discount.formatted}</Text>
          </View>
        )}
        <View style={styles.summaryRow}>
          <Text style={styles.totalLabel}>Total</Text>
          <Text style={styles.totalValue}>{cart?.total.formatted}</Text>
        </View>
        <TouchableOpacity
          style={styles.checkoutBtn}
          onPress={() => (isAuthenticated ? navigation.navigate('Checkout') : navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Login' }))}
        >
          <Text style={styles.checkoutBtnText}>Proceed to Checkout</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

function CartLineItem({ item, onIncrement, onDecrement, onRemove }: { item: CartItem; onIncrement: () => void; onDecrement: () => void; onRemove: () => void }) {
  return (
    <View style={styles.lineItem}>
      {item.product.thumbnail ? (
        <Image source={{ uri: item.product.thumbnail }} style={styles.lineImage} />
      ) : (
        <View style={[styles.lineImage, styles.linePlaceholder]}><IonIcon name="image-outline" size={24} color={COLORS.border} /></View>
      )}
      <View style={styles.lineInfo}>
        <Text style={styles.lineName} numberOfLines={2}>{item.product.name}</Text>
        {item.variation && item.variation.attributes.length > 0 && (
          <Text style={styles.lineVariant}>{item.variation.attributes.map(a => a.value).join(' / ')}</Text>
        )}
        {item.product.vendor_store ? <Text style={styles.lineVendor}>Sold by {item.product.vendor_store}</Text> : null}
        {!item.in_stock && <Text style={styles.lineOutOfStock}>Out of stock</Text>}
        {item.price_drifted && <Text style={styles.linePriceDrift}>Price has changed since you added this</Text>}

        <View style={styles.lineFooter}>
          <Text style={styles.linePrice}>{item.unit_price.formatted}</Text>
          <View style={styles.qtyRow}>
            <TouchableOpacity style={styles.qtyBtn} onPress={onDecrement}><IonIcon name="remove" size={14} color={COLORS.text} /></TouchableOpacity>
            <Text style={styles.qtyValue}>{item.quantity}</Text>
            <TouchableOpacity style={styles.qtyBtn} onPress={onIncrement}><IonIcon name="add" size={14} color={COLORS.text} /></TouchableOpacity>
          </View>
        </View>
      </View>
      <TouchableOpacity onPress={onRemove} style={styles.removeBtn} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
        <IonIcon name="trash-outline" size={18} color={COLORS.danger} />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.background, padding: SIZES.xxl },
  header: { paddingHorizontal: SIZES.screenPadding, paddingTop: 52, paddingBottom: 12, backgroundColor: COLORS.white },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: COLORS.text },

  emptyTitle: { fontSize: 17, fontWeight: 'bold', color: COLORS.text, marginTop: 16, marginBottom: 4 },
  emptySubtitle: { fontSize: 13, color: COLORS.textSecondary, textAlign: 'center', marginBottom: 20 },
  shopBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingHorizontal: 28, paddingVertical: 12 },
  shopBtnText: { color: COLORS.white, fontWeight: 'bold' },

  lineItem: { flexDirection: 'row', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 10, marginBottom: 10 },
  lineImage: { width: 72, height: 72, borderRadius: 8, marginRight: 10 },
  linePlaceholder: { alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.grayLight },
  lineInfo: { flex: 1 },
  lineName: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 2 },
  lineVariant: { fontSize: 11, color: COLORS.textSecondary, marginBottom: 2 },
  lineVendor: { fontSize: 11, color: COLORS.textMuted, marginBottom: 2 },
  lineOutOfStock: { fontSize: 11, color: COLORS.danger, fontWeight: '600' },
  linePriceDrift: { fontSize: 11, color: COLORS.warning, fontWeight: '600' },
  lineFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 6 },
  linePrice: { fontSize: 14, fontWeight: 'bold', color: COLORS.primary },
  qtyRow: { flexDirection: 'row', alignItems: 'center' },
  qtyBtn: { width: 26, height: 26, borderRadius: 6, borderWidth: 1, borderColor: COLORS.border, alignItems: 'center', justifyContent: 'center' },
  qtyValue: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginHorizontal: 10 },
  removeBtn: { padding: 4, marginLeft: 4 },

  couponBox: { marginTop: 4 },
  couponRow: { flexDirection: 'row', gap: 8 },
  couponInput: { flex: 1, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.white },
  couponBtn: { backgroundColor: COLORS.secondary, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 18, alignItems: 'center', justifyContent: 'center' },
  couponBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 13 },
  couponError: { color: COLORS.danger, fontSize: 12, marginTop: 6 },
  couponApplied: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#EAFBF0', borderRadius: SIZES.borderRadiusSm, padding: 12 },
  couponAppliedText: { color: COLORS.accent, fontSize: 12, fontWeight: '600', flex: 1 },

  summaryBar: {
    position: 'absolute', bottom: 0, left: 0, right: 0, backgroundColor: COLORS.white,
    borderTopWidth: 1, borderTopColor: COLORS.border, padding: SIZES.screenPadding,
  },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  summaryLabel: { fontSize: 13, color: COLORS.textSecondary },
  summaryValue: { fontSize: 13, color: COLORS.text, fontWeight: '600' },
  totalLabel: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  totalValue: { fontSize: 17, fontWeight: 'bold', color: COLORS.primary },
  checkoutBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 12 },
  checkoutBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
