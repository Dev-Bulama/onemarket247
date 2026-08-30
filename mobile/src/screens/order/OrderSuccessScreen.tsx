import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { ordersApi } from '../../api/orders';
import { Order } from '../../types';

export default function OrderSuccessScreen({ route, navigation }: any) {
  const { orderId } = route.params as { orderId: string };
  const [order, setOrder] = useState<Order | null>(null);

  useEffect(() => {
    ordersApi.show(orderId).then(res => setOrder(res.data.data)).catch(() => {});
  }, [orderId]);

  return (
    <View style={styles.flex}>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.iconCircle}>
          <IonIcon name="checkmark" size={48} color={COLORS.white} />
        </View>
        <Text style={styles.title}>Order Placed!</Text>
        <Text style={styles.subtitle}>Thank you — your order {order ? `#${order.order_number}` : ''} has been received.</Text>

        {order?.bank_transfer && (
          <View style={styles.bankCard}>
            <Text style={styles.bankTitle}>Complete payment via bank transfer</Text>
            <BankRow label="Bank" value={order.bank_transfer.bank_name} />
            <BankRow label="Account Name" value={order.bank_transfer.account_name} />
            <BankRow label="Account Number" value={order.bank_transfer.account_number} />
            <BankRow label="Reference" value={order.bank_transfer.reference} />
            <Text style={styles.bankNote}>Use the reference above so we can match your payment. Your order ships once payment is confirmed.</Text>
          </View>
        )}

        {order && (
          <View style={styles.totalCard}>
            <Text style={styles.totalLabel}>Total</Text>
            <Text style={styles.totalValue}>{order.total.formatted}</Text>
          </View>
        )}

        {!order && <ActivityIndicator color={COLORS.primary} style={{ marginTop: 20 }} />}

        <TouchableOpacity
          style={styles.primaryBtn}
          onPress={() => order && navigation.replace('OrderDetail', { orderId: order.id })}
        >
          <Text style={styles.primaryBtnText}>View Order</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.secondaryBtn} onPress={() => navigation.getParent()?.getParent()?.navigate('HomeTab')}>
          <Text style={styles.secondaryBtnText}>Continue Shopping</Text>
        </TouchableOpacity>
      </ScrollView>
    </View>
  );
}

function BankRow({ label, value }: { label: string; value?: string | null }) {
  return (
    <View style={styles.bankRow}>
      <Text style={styles.bankLabel}>{label}</Text>
      <Text style={styles.bankValue}>{value ?? '—'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  content: { padding: SIZES.screenPadding, paddingTop: 64, alignItems: 'center' },
  iconCircle: { width: 88, height: 88, borderRadius: 44, backgroundColor: COLORS.accent, alignItems: 'center', justifyContent: 'center', marginBottom: 20 },
  title: { fontSize: 22, fontWeight: 'bold', color: COLORS.text, marginBottom: 8 },
  subtitle: { fontSize: 13, color: COLORS.textSecondary, textAlign: 'center', marginBottom: 20 },

  bankCard: { width: '100%', backgroundColor: COLORS.grayLight, borderRadius: SIZES.borderRadius, padding: 16, marginBottom: 16 },
  bankTitle: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 10 },
  bankRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  bankLabel: { fontSize: 12, color: COLORS.textSecondary },
  bankValue: { fontSize: 12, color: COLORS.text, fontWeight: '700' },
  bankNote: { fontSize: 11, color: COLORS.textMuted, marginTop: 8, lineHeight: 16 },

  totalCard: { width: '100%', flexDirection: 'row', justifyContent: 'space-between', backgroundColor: COLORS.white, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius, padding: 16, marginBottom: 20 },
  totalLabel: { fontSize: 14, fontWeight: '600', color: COLORS.text },
  totalValue: { fontSize: 16, fontWeight: 'bold', color: COLORS.primary },

  primaryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', width: '100%', marginBottom: 10 },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  secondaryBtn: { paddingVertical: 10 },
  secondaryBtnText: { color: COLORS.primary, fontWeight: '600', fontSize: 13 },
});
