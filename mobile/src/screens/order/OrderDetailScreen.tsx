import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, ORDER_STATUSES, SIZES } from '../../constants';
import { ordersApi } from '../../api/orders';
import { apiErrorMessage } from '../../api/client';
import { Order, VendorOrder } from '../../types';

export default function OrderDetailScreen({ route, navigation }: any) {
  const { orderId } = route.params as { orderId: string };
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    ordersApi.show(orderId)
      .then(res => setOrder(res.data.data))
      .catch(e => setError(apiErrorMessage(e, 'Could not load this order.')))
      .finally(() => setLoading(false));
  }, [orderId]);

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  if (error || !order) {
    return (
      <View style={styles.centerFlex}>
        <Text style={styles.errorText}>{error || 'Order not found.'}</Text>
      </View>
    );
  }

  const statusInfo = ORDER_STATUSES[order.status] ?? { label: order.status_label, color: COLORS.textSecondary };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Order #{order.order_number}</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.statusCard}>
          <View style={[styles.statusDot, { backgroundColor: statusInfo.color }]} />
          <Text style={[styles.statusText, { color: statusInfo.color }]}>{order.status_label}</Text>
          <Text style={styles.placedAt}>Placed {new Date(order.placed_at).toLocaleDateString()}</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Delivery Address</Text>
          <Text style={styles.addressName}>{order.shipping_address.full_name}</Text>
          {order.shipping_address.phone ? <Text style={styles.addressLine}>{order.shipping_address.phone}</Text> : null}
          <Text style={styles.addressLine}>
            {[order.shipping_address.address_line_1, order.shipping_address.address_line_2, order.shipping_address.city, order.shipping_address.state, order.shipping_address.country].filter(Boolean).join(', ')}
          </Text>
        </View>

        {order.vendor_orders?.map((vo: VendorOrder) => (
          <View key={vo.id} style={styles.card}>
            <View style={styles.cardHeaderRow}>
              <Text style={styles.cardTitle}>{vo.store_name ?? 'Store'}</Text>
              <Text style={styles.voStatus}>{vo.status_label}</Text>
            </View>
            {vo.items?.map(item => (
              <View key={item.id} style={styles.itemRow}>
                <Text style={styles.itemName} numberOfLines={2}>{item.product_name}</Text>
                <Text style={styles.itemQty}>x{item.quantity}</Text>
                <Text style={styles.itemPrice}>{item.line_total.formatted}</Text>
              </View>
            ))}
            {vo.shipment?.tracking_number && (
              <View style={styles.trackingBox}>
                <IonIcon name="cube-outline" size={16} color={COLORS.textSecondary} />
                <Text style={styles.trackingText}>
                  {vo.shipment.carrier ?? 'Carrier'}: {vo.shipment.tracking_number} — {vo.shipment.status_label}
                </Text>
              </View>
            )}
          </View>
        ))}

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Payment Summary</Text>
          <SummaryRow label="Subtotal" value={order.subtotal.formatted} />
          {order.discount_amount.amount > 0 && <SummaryRow label="Discount" value={'-' + order.discount_amount.formatted} valueColor={COLORS.accent} />}
          <SummaryRow label="Shipping" value={order.shipping_amount.formatted} />
          {order.tax_amount.amount > 0 && <SummaryRow label="Tax" value={order.tax_amount.formatted} />}
          <SummaryRow label="Total" value={order.total.formatted} bold />
          {order.payment && (
            <Text style={styles.paymentStatus}>Payment: {order.payment.status} {order.payment.gateway ? `via ${order.payment.gateway}` : ''}</Text>
          )}
        </View>

        {order.bank_transfer && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Bank Transfer Details</Text>
            <SummaryRow label="Bank" value={order.bank_transfer.bank_name ?? '—'} />
            <SummaryRow label="Account Name" value={order.bank_transfer.account_name ?? '—'} />
            <SummaryRow label="Account Number" value={order.bank_transfer.account_number ?? '—'} />
            <SummaryRow label="Reference" value={order.bank_transfer.reference} />
          </View>
        )}
      </ScrollView>
    </View>
  );
}

function SummaryRow({ label, value, bold, valueColor }: { label: string; value: string; bold?: boolean; valueColor?: string }) {
  return (
    <View style={styles.summaryRow}>
      <Text style={[styles.summaryLabel, bold && styles.summaryLabelBold]}>{label}</Text>
      <Text style={[styles.summaryValue, bold && styles.summaryValueBold, valueColor ? { color: valueColor } : null]}>{value}</Text>
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
  headerTitle: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },

  statusCard: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 16, marginBottom: 12, alignItems: 'center' },
  statusDot: { width: 10, height: 10, borderRadius: 5, marginBottom: 6 },
  statusText: { fontSize: 15, fontWeight: 'bold' },
  placedAt: { fontSize: 12, color: COLORS.textMuted, marginTop: 4 },

  card: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 12 },
  cardHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  cardTitle: { fontSize: 14, fontWeight: 'bold', color: COLORS.text, marginBottom: 8 },
  voStatus: { fontSize: 12, color: COLORS.accent, fontWeight: '700' },
  addressName: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  addressLine: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },

  itemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 6, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  itemName: { flex: 1, fontSize: 12, color: COLORS.text },
  itemQty: { fontSize: 12, color: COLORS.textMuted, marginHorizontal: 8 },
  itemPrice: { fontSize: 12, fontWeight: '700', color: COLORS.text },
  trackingBox: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 10, backgroundColor: COLORS.grayLight, padding: 10, borderRadius: SIZES.borderRadiusSm },
  trackingText: { fontSize: 11, color: COLORS.textSecondary, flex: 1 },

  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  summaryLabel: { fontSize: 12, color: COLORS.textSecondary },
  summaryLabelBold: { fontSize: 14, fontWeight: 'bold', color: COLORS.text },
  summaryValue: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  summaryValueBold: { fontSize: 15, fontWeight: 'bold', color: COLORS.primary },
  paymentStatus: { fontSize: 11, color: COLORS.textMuted, marginTop: 8, textTransform: 'capitalize' },
});
