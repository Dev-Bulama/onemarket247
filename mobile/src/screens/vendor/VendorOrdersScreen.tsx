import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, ORDER_STATUSES, SIZES } from '../../constants';
import { vendorOrdersApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorOrder } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';

const FILTERS: { label: string; value?: string }[] = [
  { label: 'All' },
  { label: 'Pending Payment', value: 'pending_payment' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Processing', value: 'processing' },
  { label: 'Ready for Pickup', value: 'ready_for_pickup' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Out for Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Completed', value: 'completed' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Returned', value: 'returned' },
  { label: 'Refunded', value: 'refunded' },
  { label: 'Disputed', value: 'disputed' },
];

export default function VendorOrdersScreen({ navigation }: any) {
  const [orders, setOrders] = useState<VendorOrder[]>([]);
  const [status, setStatus] = useState<string | undefined>(undefined);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async (targetPage: number, targetStatus: string | undefined) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await vendorOrdersApi.list(targetStatus, targetPage);
      setOrders(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your orders.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(1, status); }, [load, status]));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Orders</Text>
        <View style={{ width: 22 }} />
      </View>

      <FlatList
        horizontal
        showsHorizontalScrollIndicator={false}
        data={FILTERS}
        keyExtractor={item => item.label}
        contentContainerStyle={styles.filterRow}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.filterChip, status === item.value && styles.filterChipActive]}
            onPress={() => setStatus(item.value)}
          >
            <Text style={[styles.filterChipText, status === item.value && styles.filterChipTextActive]}>{item.label}</Text>
          </TouchableOpacity>
        )}
      />

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1, status)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : orders.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="receipt-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No orders found.</Text>
        </View>
      ) : (
        <FlatList
          data={orders}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1, status)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => {
            const statusInfo = ORDER_STATUSES[item.status] ?? { label: item.status_label, color: COLORS.textSecondary };
            return (
              <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('VendorOrderDetail', { orderId: item.id })}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.orderNumber}>#{item.vendor_order_number}</Text>
                  <Text style={styles.total}>{item.total.formatted}</Text>
                </View>
                <StatusBadge label={statusInfo.label} color={statusInfo.color} />
                <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} style={{ marginLeft: 8 }} />
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
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
  filterRow: { paddingHorizontal: SIZES.screenPadding, paddingVertical: 10, backgroundColor: COLORS.white },
  filterChip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 14, paddingVertical: 7, marginRight: 8 },
  filterChipActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  filterChipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  filterChipTextActive: { color: COLORS.white },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  orderNumber: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  total: { fontSize: 13, fontWeight: 'bold', color: COLORS.primary, marginTop: 2 },
});
