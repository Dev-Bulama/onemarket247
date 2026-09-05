import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator, Image, Modal, ScrollView, StyleSheet, Text,
  TextInput, TouchableOpacity, View,
} from 'react-native';
import { WebView, WebViewNavigation } from 'react-native-webview';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, PAYMENT_METHOD_LABELS, SIZES } from '../../constants';
import { useCartStore } from '../../store/cartStore';
import { useAuthStore } from '../../store/authStore';
import { checkoutApi } from '../../api/checkout';
import { paymentsApi } from '../../api/orders';
import { addressesApi } from '../../api/addresses';
import { configApi, referenceApi } from '../../api/config';
import { apiErrorMessage } from '../../api/client';
import { Address, City, Country, State } from '../../types';
import { useToastStore } from '../../store/toastStore';

const PAYSTACK_HOST = 'paystack.com';

export default function CheckoutScreen({ navigation }: any) {
  const { cart, guestToken, fetchCart } = useCartStore();
  const { isAuthenticated } = useAuthStore();

  const [sessionKey, setSessionKey] = useState<string | null>(null);
  const [initializing, setInitializing] = useState(true);
  const [initError, setInitError] = useState('');

  const [addresses, setAddresses] = useState<Address[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState<number | null>(null);
  const [addressPickerVisible, setAddressPickerVisible] = useState(false);

  const [guestName, setGuestName] = useState('');
  const [guestEmail, setGuestEmail] = useState('');
  const [guestPhone, setGuestPhone] = useState('');
  const [guestAddressLine1, setGuestAddressLine1] = useState('');
  const [countries, setCountries] = useState<Country[]>([]);
  const [states, setStates] = useState<State[]>([]);
  const [cities, setCities] = useState<City[]>([]);
  const [guestCountryId, setGuestCountryId] = useState<number | null>(null);
  const [guestStateId, setGuestStateId] = useState<number | null>(null);
  const [guestCityId, setGuestCityId] = useState<number | null>(null);

  const [paymentMethods, setPaymentMethods] = useState<string[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<'paystack' | 'bank_transfer'>('bank_transfer');

  const [placing, setPlacing] = useState(false);

  const [webviewUrl, setWebviewUrl] = useState<string | null>(null);
  const [pendingOrderId, setPendingOrderId] = useState<string | null>(null);
  const reachedPaystack = useRef(false);

  // Deliberately run-once: re-running init() on every guestToken/auth change
  // would mint a new checkout session mid-flow.
  useEffect(() => {
    checkoutApi.init(guestToken)
      .then(res => setSessionKey(res.data.data.checkout_session_key))
      .catch(e => setInitError(apiErrorMessage(e, 'Could not start checkout.')))
      .finally(() => setInitializing(false));

    configApi.get().then(res => {
      setPaymentMethods(res.data.data.payment_methods);
      if (res.data.data.payment_methods.length > 0) setPaymentMethod(res.data.data.payment_methods[0] as any);
    }).catch(() => {});

    if (isAuthenticated) {
      addressesApi.list().then(res => {
        setAddresses(res.data.data);
        const def = res.data.data.find(a => a.is_default_shipping) ?? res.data.data[0];
        if (def) setSelectedAddressId(def.id);
      }).catch(() => {});
    } else {
      referenceApi.countries().then(res => setCountries(res.data.data)).catch(() => {});
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (guestCountryId) referenceApi.states(guestCountryId).then(res => setStates(res.data.data)).catch(() => {});
    else setStates([]);
    setGuestStateId(null);
  }, [guestCountryId]);

  useEffect(() => {
    if (guestStateId) referenceApi.cities(guestStateId).then(res => setCities(res.data.data)).catch(() => {});
    else setCities([]);
    setGuestCityId(null);
  }, [guestStateId]);

  const selectedAddress = useMemo(() => addresses.find(a => a.id === selectedAddressId) ?? null, [addresses, selectedAddressId]);
  const items = cart?.items ?? [];

  const canPlaceOrder = isAuthenticated
    ? !!selectedAddress
    : !!(guestName && guestEmail && guestAddressLine1 && guestCountryId);

  const handlePlaceOrder = async () => {
    if (!sessionKey || !canPlaceOrder) return;
    setPlacing(true);
    try {
      const payload = isAuthenticated && selectedAddress
        ? {
            checkout_session_key: sessionKey,
            full_name: selectedAddress.full_name,
            phone: selectedAddress.phone ?? undefined,
            address_line_1: selectedAddress.address_line_1,
            address_line_2: selectedAddress.address_line_2 ?? undefined,
            country_id: selectedAddress.country_id,
            state_id: selectedAddress.state_id ?? undefined,
            city_id: selectedAddress.city_id ?? undefined,
            postal_code: selectedAddress.postal_code ?? undefined,
            payment_method: paymentMethod,
          }
        : {
            checkout_session_key: sessionKey,
            email: guestEmail,
            full_name: guestName,
            phone: guestPhone || undefined,
            address_line_1: guestAddressLine1,
            country_id: guestCountryId as number,
            state_id: guestStateId ?? undefined,
            city_id: guestCityId ?? undefined,
            payment_method: paymentMethod,
            cart_token: guestToken ?? undefined,
          };

      const res = await checkoutApi.complete(payload);
      const order = res.data.data;
      await fetchCart();

      if (paymentMethod === 'paystack') {
        const initRes = await paymentsApi.initialize(order.id);
        setPendingOrderId(order.id);
        reachedPaystack.current = false;
        setWebviewUrl(initRes.data.data.authorization_url);
      } else {
        navigation.replace('OrderSuccess', { orderId: order.id });
      }
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not place your order. Please try again.'), 'error');
    } finally {
      setPlacing(false);
    }
  };

  const handleWebviewNav = (navState: WebViewNavigation) => {
    if (navState.url.includes(PAYSTACK_HOST)) {
      reachedPaystack.current = true;
      return;
    }
    if (reachedPaystack.current && pendingOrderId) {
      const orderId = pendingOrderId;
      setWebviewUrl(null);
      setPendingOrderId(null);
      paymentsApi.verify(orderId).finally(() => {
        navigation.replace('OrderSuccess', { orderId });
      });
    }
  };

  if (initializing) {
    return (
      <View style={styles.centerFlex}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  if (initError || items.length === 0) {
    return (
      <View style={styles.centerFlex}>
        <IonIcon name="alert-circle-outline" size={40} color={COLORS.danger} />
        <Text style={styles.errorText}>{initError || 'Your cart is empty.'}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.retryBtnText}>Go Back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Checkout</Text>
        <View style={styles.secureRow}>
          <IonIcon name="shield-checkmark" size={13} color={COLORS.accent} />
          <Text style={styles.secureText}>Secure</Text>
        </View>
      </View>

      <StepIndicator step={2} />

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Delivery Address */}
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardTitle}>Delivery Address</Text>
            {isAuthenticated && addresses.length > 0 && (
              <TouchableOpacity onPress={() => setAddressPickerVisible(true)}><Text style={styles.linkText}>Change</Text></TouchableOpacity>
            )}
          </View>

          {isAuthenticated ? (
            selectedAddress ? (
              <View style={styles.addressRow}>
                <IonIcon name="location" size={20} color={COLORS.primary} style={{ marginTop: 2 }} />
                <View style={styles.addressInfo}>
                  <Text style={styles.addressName}>{selectedAddress.full_name}</Text>
                  {selectedAddress.phone ? <Text style={styles.addressLine}>{selectedAddress.phone}</Text> : null}
                  <Text style={styles.addressLine}>
                    {[selectedAddress.address_line_1, selectedAddress.address_line_2, selectedAddress.city, selectedAddress.state, selectedAddress.country].filter(Boolean).join(', ')}
                  </Text>
                  {selectedAddress.is_default_shipping && (
                    <View style={styles.defaultPill}><Text style={styles.defaultPillText}>Default</Text></View>
                  )}
                </View>
              </View>
            ) : (
              <TouchableOpacity style={styles.addAddressRow} onPress={() => navigation.navigate('AddAddress')}>
                <IonIcon name="add-circle-outline" size={20} color={COLORS.primary} />
                <Text style={styles.addAddressText}>Add a delivery address</Text>
              </TouchableOpacity>
            )
          ) : (
            <GuestAddressForm
              guestName={guestName} setGuestName={setGuestName}
              guestEmail={guestEmail} setGuestEmail={setGuestEmail}
              guestPhone={guestPhone} setGuestPhone={setGuestPhone}
              guestAddressLine1={guestAddressLine1} setGuestAddressLine1={setGuestAddressLine1}
              countries={countries} states={states} cities={cities}
              guestCountryId={guestCountryId} setGuestCountryId={setGuestCountryId}
              guestStateId={guestStateId} setGuestStateId={setGuestStateId}
              guestCityId={guestCityId} setGuestCityId={setGuestCityId}
            />
          )}
        </View>

        {/* Order Items */}
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardTitle}>Order Items ({items.length})</Text>
            <TouchableOpacity onPress={() => navigation.goBack()}><Text style={styles.linkText}>Edit Cart</Text></TouchableOpacity>
          </View>
          {items.map(item => (
            <View key={item.id} style={styles.orderItemRow}>
              {item.product.thumbnail ? (
                <Image source={{ uri: item.product.thumbnail }} style={styles.orderItemImage} />
              ) : (
                <View style={[styles.orderItemImage, styles.orderItemPlaceholder]}><IonIcon name="image-outline" size={18} color={COLORS.border} /></View>
              )}
              <View style={styles.orderItemInfo}>
                <Text style={styles.orderItemName} numberOfLines={2}>{item.product.name}</Text>
                {item.variation && item.variation.attributes.length > 0 && (
                  <Text style={styles.orderItemVariant}>{item.variation.attributes.map(a => a.value).join(' / ')}</Text>
                )}
                <Text style={styles.orderItemQty}>Qty: {item.quantity}</Text>
              </View>
              <Text style={styles.orderItemPrice}>{item.line_total.formatted}</Text>
            </View>
          ))}
        </View>

        {/* Order Summary */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Order Summary</Text>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Subtotal ({items.length} items)</Text>
            <Text style={styles.summaryValue}>{cart?.subtotal.formatted}</Text>
          </View>
          {cart && cart.discount.amount > 0 && (
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Discount</Text>
              <Text style={[styles.summaryValue, { color: COLORS.accent }]}>-{cart.discount.formatted}</Text>
            </View>
          )}
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Shipping</Text>
            <Text style={styles.summaryMuted}>Calculated after order is placed</Text>
          </View>
          <View style={[styles.summaryRow, styles.summaryTotalRow]}>
            <Text style={styles.totalLabel}>Total (excl. shipping)</Text>
            <Text style={styles.totalValue}>{cart?.total.formatted}</Text>
          </View>
        </View>

        {/* Payment Method */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Payment Method</Text>
          {paymentMethods.map(method => (
            <TouchableOpacity key={method} style={styles.paymentRow} onPress={() => setPaymentMethod(method as any)}>
              <IonIcon name={method === 'paystack' ? 'card-outline' : 'business-outline'} size={20} color={COLORS.text} />
              <Text style={styles.paymentLabel}>{PAYMENT_METHOD_LABELS[method] ?? method}</Text>
              <IonIcon name={paymentMethod === method ? 'radio-button-on' : 'radio-button-off'} size={20} color={paymentMethod === method ? COLORS.accent : COLORS.textMuted} />
            </TouchableOpacity>
          ))}
        </View>
      </ScrollView>

      <View style={styles.bottomBar}>
        <View style={styles.bottomBarLeft}>
          <Text style={styles.bottomTotalLabel}>Total Payable</Text>
          <Text style={styles.bottomTotalValue}>{cart?.total.formatted}</Text>
          <View style={styles.secureRow}>
            <IonIcon name="lock-closed" size={11} color={COLORS.accent} />
            <Text style={styles.secureNote}>Secure and encrypted payment</Text>
          </View>
        </View>
        <TouchableOpacity style={[styles.placeOrderBtn, (!canPlaceOrder || placing) && styles.disabledBtn]} onPress={handlePlaceOrder} disabled={!canPlaceOrder || placing}>
          {placing ? <ActivityIndicator size="small" color={COLORS.white} /> : <Text style={styles.placeOrderText}>Place Order</Text>}
        </TouchableOpacity>
      </View>

      {/* Address picker */}
      <Modal visible={addressPickerVisible} transparent animationType="slide" onRequestClose={() => setAddressPickerVisible(false)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setAddressPickerVisible(false)}>
          <View style={styles.sheet}>
            <Text style={styles.sheetTitle}>Choose delivery address</Text>
            {addresses.map(addr => (
              <TouchableOpacity key={addr.id} style={styles.pickerRow} onPress={() => { setSelectedAddressId(addr.id); setAddressPickerVisible(false); }}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.addressName}>{addr.full_name}</Text>
                  <Text style={styles.addressLine} numberOfLines={2}>{[addr.address_line_1, addr.city, addr.state].filter(Boolean).join(', ')}</Text>
                </View>
                {selectedAddressId === addr.id && <IonIcon name="checkmark-circle" size={20} color={COLORS.primary} />}
              </TouchableOpacity>
            ))}
            <TouchableOpacity style={styles.addAddressRow} onPress={() => { setAddressPickerVisible(false); navigation.navigate('AddAddress'); }}>
              <IonIcon name="add-circle-outline" size={20} color={COLORS.primary} />
              <Text style={styles.addAddressText}>Add new address</Text>
            </TouchableOpacity>
          </View>
        </TouchableOpacity>
      </Modal>

      {/* Paystack webview */}
      <Modal visible={!!webviewUrl} animationType="slide" onRequestClose={() => setWebviewUrl(null)}>
        <View style={styles.webviewHeader}>
          <TouchableOpacity onPress={() => setWebviewUrl(null)} style={{ padding: 8 }}>
            <IonIcon name="close" size={24} color={COLORS.text} />
          </TouchableOpacity>
          <Text style={styles.webviewTitle}>Complete Payment</Text>
          <View style={{ width: 40 }} />
        </View>
        {webviewUrl && (
          <WebView source={{ uri: webviewUrl }} onNavigationStateChange={handleWebviewNav} startInLoadingState renderLoading={() => (
            <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
          )} />
        )}
      </Modal>
    </View>
  );
}

function StepIndicator({ step }: { step: number }) {
  const steps = ['Delivery', 'Payment', 'Review', 'Complete'];
  return (
    <View style={styles.stepRow}>
      {steps.map((label, idx) => {
        const num = idx + 1;
        const active = num <= step;
        return (
          <React.Fragment key={label}>
            <View style={styles.stepItem}>
              <View style={[styles.stepCircle, active && styles.stepCircleActive]}>
                <Text style={[styles.stepNumber, active && styles.stepNumberActive]}>{num}</Text>
              </View>
              <Text style={[styles.stepLabel, active && styles.stepLabelActive]}>{label}</Text>
            </View>
            {idx < steps.length - 1 && <View style={[styles.stepLine, num < step && styles.stepLineActive]} />}
          </React.Fragment>
        );
      })}
    </View>
  );
}

function GuestAddressForm(props: {
  guestName: string; setGuestName: (v: string) => void;
  guestEmail: string; setGuestEmail: (v: string) => void;
  guestPhone: string; setGuestPhone: (v: string) => void;
  guestAddressLine1: string; setGuestAddressLine1: (v: string) => void;
  countries: Country[]; states: State[]; cities: City[];
  guestCountryId: number | null; setGuestCountryId: (v: number | null) => void;
  guestStateId: number | null; setGuestStateId: (v: number | null) => void;
  guestCityId: number | null; setGuestCityId: (v: number | null) => void;
}) {
  const [pickerFor, setPickerFor] = useState<'country' | 'state' | 'city' | null>(null);
  const selectedCountry = props.countries.find(c => c.id === props.guestCountryId);
  const selectedState = props.states.find(s => s.id === props.guestStateId);
  const selectedCity = props.cities.find(c => c.id === props.guestCityId);

  return (
    <View>
      <TextInput style={styles.input} placeholder="Full Name" placeholderTextColor={COLORS.placeholder} value={props.guestName} onChangeText={props.setGuestName} />
      <TextInput style={styles.input} placeholder="Email" placeholderTextColor={COLORS.placeholder} value={props.guestEmail} onChangeText={props.setGuestEmail} autoCapitalize="none" keyboardType="email-address" />
      <TextInput style={styles.input} placeholder="Phone" placeholderTextColor={COLORS.placeholder} value={props.guestPhone} onChangeText={props.setGuestPhone} keyboardType="phone-pad" />
      <TextInput style={styles.input} placeholder="Address" placeholderTextColor={COLORS.placeholder} value={props.guestAddressLine1} onChangeText={props.setGuestAddressLine1} />

      <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('country')}>
        <Text style={selectedCountry ? styles.selectValue : styles.selectPlaceholder}>{selectedCountry?.name ?? 'Select Country'}</Text>
        <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
      </TouchableOpacity>
      {props.guestCountryId && (
        <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('state')}>
          <Text style={selectedState ? styles.selectValue : styles.selectPlaceholder}>{selectedState?.name ?? 'Select State (optional)'}</Text>
          <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
        </TouchableOpacity>
      )}
      {props.guestStateId && (
        <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('city')}>
          <Text style={selectedCity ? styles.selectValue : styles.selectPlaceholder}>{selectedCity?.name ?? 'Select City (optional)'}</Text>
          <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
        </TouchableOpacity>
      )}

      <Modal visible={!!pickerFor} transparent animationType="slide" onRequestClose={() => setPickerFor(null)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setPickerFor(null)}>
          <View style={styles.sheet}>
            <ScrollView style={{ maxHeight: 360 }}>
              {(pickerFor === 'country' ? props.countries : pickerFor === 'state' ? props.states : props.cities).map((opt: any) => (
                <TouchableOpacity
                  key={opt.id}
                  style={styles.pickerRow}
                  onPress={() => {
                    if (pickerFor === 'country') props.setGuestCountryId(opt.id);
                    if (pickerFor === 'state') props.setGuestStateId(opt.id);
                    if (pickerFor === 'city') props.setGuestCityId(opt.id);
                    setPickerFor(null);
                  }}
                >
                  <Text style={styles.addressName}>{opt.name}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white, padding: SIZES.xxl },
  errorText: { color: COLORS.textSecondary, marginVertical: 12, textAlign: 'center' },
  retryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingHorizontal: 24, paddingVertical: 12 },
  retryBtnText: { color: COLORS.white, fontWeight: 'bold' },

  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  secureRow: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  secureText: { fontSize: 11, color: COLORS.accent, fontWeight: '700' },

  stepRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'center', paddingVertical: 16, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  stepItem: { alignItems: 'center', width: 60 },
  stepCircle: { width: 26, height: 26, borderRadius: 13, backgroundColor: COLORS.grayMedium, alignItems: 'center', justifyContent: 'center' },
  stepCircleActive: { backgroundColor: COLORS.accent },
  stepNumber: { color: COLORS.textSecondary, fontWeight: 'bold', fontSize: 12 },
  stepNumberActive: { color: COLORS.white },
  stepLabel: { fontSize: 10, color: COLORS.textMuted, marginTop: 4 },
  stepLabelActive: { color: COLORS.accent, fontWeight: '600' },
  stepLine: { width: 24, height: 2, backgroundColor: COLORS.grayMedium, marginTop: 13 },
  stepLineActive: { backgroundColor: COLORS.accent },

  scrollContent: { padding: SIZES.screenPadding, paddingBottom: 24 },
  card: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 12 },
  cardHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  cardTitle: { fontSize: 14, fontWeight: 'bold', color: COLORS.text, marginBottom: 4 },
  linkText: { fontSize: 12, color: COLORS.accent, fontWeight: '700' },

  addressRow: { flexDirection: 'row', gap: 10 },
  addressInfo: { flex: 1 },
  addressName: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  addressLine: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },
  defaultPill: { alignSelf: 'flex-start', backgroundColor: '#EAFBF0', borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2, marginTop: 6 },
  defaultPillText: { color: COLORS.accent, fontSize: 10, fontWeight: '700' },
  addAddressRow: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 8 },
  addAddressText: { color: COLORS.primary, fontWeight: '600', fontSize: 13 },

  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, marginBottom: 10, color: COLORS.text, backgroundColor: COLORS.grayLight },
  selectInput: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 12, marginBottom: 10, backgroundColor: COLORS.grayLight },
  selectValue: { fontSize: 13, color: COLORS.text },
  selectPlaceholder: { fontSize: 13, color: COLORS.placeholder },

  orderItemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  orderItemImage: { width: 44, height: 44, borderRadius: 6, marginRight: 10 },
  orderItemPlaceholder: { alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.grayLight },
  orderItemInfo: { flex: 1 },
  orderItemName: { fontSize: 12, fontWeight: '600', color: COLORS.text },
  orderItemVariant: { fontSize: 11, color: COLORS.textSecondary },
  orderItemQty: { fontSize: 11, color: COLORS.textMuted },
  orderItemPrice: { fontSize: 12, fontWeight: '700', color: COLORS.text },

  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  summaryTotalRow: { borderTopWidth: 1, borderTopColor: COLORS.divider, paddingTop: 8, marginTop: 4 },
  summaryLabel: { fontSize: 13, color: COLORS.textSecondary },
  summaryValue: { fontSize: 13, color: COLORS.text, fontWeight: '600' },
  summaryMuted: { fontSize: 11, color: COLORS.textMuted, fontStyle: 'italic' },
  totalLabel: { fontSize: 14, fontWeight: 'bold', color: COLORS.text },
  totalValue: { fontSize: 16, fontWeight: 'bold', color: COLORS.primary },

  paymentRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  paymentLabel: { flex: 1, fontSize: 13, color: COLORS.text },

  bottomBar: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    backgroundColor: COLORS.white, borderTopWidth: 1, borderTopColor: COLORS.border, padding: SIZES.screenPadding,
  },
  bottomBarLeft: { flex: 1 },
  bottomTotalLabel: { fontSize: 11, color: COLORS.textMuted },
  bottomTotalValue: { fontSize: 18, fontWeight: 'bold', color: COLORS.primary },
  secureNote: { fontSize: 10, color: COLORS.accent },
  placeOrderBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, paddingHorizontal: 28 },
  placeOrderText: { color: COLORS.white, fontWeight: 'bold', fontSize: 14 },
  disabledBtn: { opacity: 0.5 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32, maxHeight: '80%' },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  pickerRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider },

  webviewHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.border,
  },
  webviewTitle: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
});
