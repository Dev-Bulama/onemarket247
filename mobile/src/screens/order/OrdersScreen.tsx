import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, ORDER_STATUSES, SIZES } from '../../constants';
import { ordersApi } from '../../api/orders';
import { Order } from '../../types';

export default function OrdersScreen({ navigation }: any) {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);

  useFocusEffect(useCallback(() => {
    setLoading(true);
    ordersApi.list().then(res => setOrders(res.data.data)).finally(() => setLoading(false));
  }, []));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>My Orders</Text>
        <View style={{ width: 22 }} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : orders.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="receipt-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>You haven't placed any orders yet.</Text>
        </View>
      ) : (
        <FlatList
          data={orders}
          keyExtractor={item => item.id}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          renderItem={({ item }) => {
            const statusInfo = ORDER_STATUSES[item.status] ?? { label: item.status_label, color: COLORS.textSecondary };
            return (
              <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('OrderDetail', { orderId: item.id })}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.orderNumber}>#{item.order_number}</Text>
                  <Text style={styles.date}>{new Date(item.placed_at).toLocaleDateString()}</Text>
                </View>
                <View style={{ alignItems: 'flex-end' }}>
                  <Text style={styles.total}>{item.total.formatted}</Text>
                  <Text style={[styles.status, { color: statusInfo.color }]}>{item.status_label}</Text>
                </View>
                <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} style={{ marginLeft: 8 }} />
              </TouchableOpacity>
            );
          }}
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
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  orderNumber: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  date: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  total: { fontSize: 14, fontWeight: 'bold', color: COLORS.primary },
  status: { fontSize: 11, fontWeight: '700', marginTop: 2 },
});
