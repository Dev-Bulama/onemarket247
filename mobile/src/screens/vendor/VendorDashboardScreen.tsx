import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, ORDER_STATUSES, SIZES } from '../../constants';
import { vendorEarningsApi, vendorOrdersApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorEarningsSummary, VendorOrder } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';

export default function VendorDashboardScreen({ navigation }: any) {
  const [summary, setSummary] = useState<VendorEarningsSummary | null>(null);
  const [recentOrders, setRecentOrders] = useState<VendorOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true); else setLoading(true);
    setError('');
    try {
      const [earningsRes, ordersRes] = await Promise.all([
        vendorEarningsApi.summary(),
        vendorOrdersApi.list(undefined, 1),
      ]);
      setSummary(earningsRes.data.data);
      setRecentOrders(ordersRes.data.data.slice(0, 5));
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your dashboard.'));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Vendor Dashboard</Text>
        <TouchableOpacity style={styles.switchBtn} onPress={() => navigation.getParent()?.getParent()?.navigate('Main')}>
          <IonIcon name="storefront-outline" size={14} color={COLORS.primary} />
          <Text style={styles.switchBtnText}>Switch to Shopping</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load()}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : (
        <ScrollView
          contentContainerStyle={styles.content}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => load(true)} colors={[COLORS.primary]} />}
        >
          <View style={styles.statsGrid}>
            <StatCard label="Available Balance" value={summary?.available_balance.formatted ?? '—'} icon="wallet-outline" color={COLORS.accent} />
            <StatCard label="Pending Balance" value={summary?.pending_balance.formatted ?? '—'} icon="hourglass-outline" color={COLORS.warning} />
            <StatCard label="Reserved" value={summary?.reserved_balance.formatted ?? '—'} icon="lock-closed-outline" color={COLORS.info} />
            <StatCard label="Withdrawn" value={summary?.withdrawn_balance.formatted ?? '—'} icon="checkmark-done-outline" color={COLORS.textSecondary} />
          </View>

          <View style={styles.quickActionsRow}>
            <QuickAction icon="cube-outline" label="Products" onPress={() => navigation.navigate('ProductsTab')} />
            <QuickAction icon="receipt-outline" label="Orders" onPress={() => navigation.navigate('OrdersTab')} />
            <QuickAction icon="cash-outline" label="Withdraw" onPress={() => navigation.navigate('MoreTab', { screen: 'VendorWithdrawals' })} />
            <QuickAction icon="ellipsis-horizontal-outline" label="More" onPress={() => navigation.navigate('MoreTab')} />
          </View>

          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionTitle}>Recent Orders</Text>
            <TouchableOpacity onPress={() => navigation.navigate('OrdersTab')}>
              <Text style={styles.seeAll}>See all</Text>
            </TouchableOpacity>
          </View>

          {recentOrders.length === 0 ? (
            <View style={styles.empty}>
              <IonIcon name="receipt-outline" size={40} color={COLORS.border} />
              <Text style={styles.emptyText}>No orders yet.</Text>
            </View>
          ) : (
            recentOrders.map(order => {
              const statusInfo = ORDER_STATUSES[order.status] ?? { label: order.status_label, color: COLORS.textSecondary };
              return (
                <TouchableOpacity
                  key={order.id}
                  style={styles.orderCard}
                  onPress={() => navigation.navigate('OrdersTab', { screen: 'VendorOrderDetail', params: { orderId: order.id } })}
                >
                  <View style={{ flex: 1 }}>
                    <Text style={styles.orderNumber}>#{order.vendor_order_number}</Text>
                    <Text style={styles.orderTotal}>{order.total.formatted}</Text>
                  </View>
                  <StatusBadge label={statusInfo.label} color={statusInfo.color} />
                </TouchableOpacity>
              );
            })
          )}
        </ScrollView>
      )}
    </View>
  );
}

function StatCard({ label, value, icon, color }: { label: string; value: string; icon: string; color: string }) {
  return (
    <View style={styles.statCard}>
      <View style={[styles.statIconBox, { backgroundColor: `${color}1A` }]}>
        <IonIcon name={icon} size={18} color={color} />
      </View>
      <Text style={styles.statValue} numberOfLines={1}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

function QuickAction({ icon, label, onPress }: { icon: string; label: string; onPress: () => void }) {
  return (
    <TouchableOpacity style={styles.quickAction} onPress={onPress}>
      <View style={styles.quickActionIconBox}>
        <IonIcon name={icon} size={20} color={COLORS.primary} />
      </View>
      <Text style={styles.quickActionLabel}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  switchBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1, borderColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 10, paddingVertical: 6 },
  switchBtnText: { fontSize: 11, fontWeight: '700', color: COLORS.primary },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },

  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 20 },
  statCard: { width: '47%', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14 },
  statIconBox: { width: 34, height: 34, borderRadius: 17, alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
  statValue: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  statLabel: { fontSize: 11, color: COLORS.textSecondary, marginTop: 2 },

  quickActionsRow: { flexDirection: 'row', justifyContent: 'space-between', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 20 },
  quickAction: { alignItems: 'center', gap: 6 },
  quickActionIconBox: { width: 40, height: 40, borderRadius: 20, backgroundColor: `${COLORS.primary}1A`, alignItems: 'center', justifyContent: 'center' },
  quickActionLabel: { fontSize: 11, color: COLORS.text, fontWeight: '600' },

  sectionHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  sectionTitle: { fontSize: 14, fontWeight: 'bold', color: COLORS.text },
  seeAll: { fontSize: 12, color: COLORS.primary, fontWeight: '600' },

  empty: { alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl, backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius },
  emptyText: { color: COLORS.textSecondary, marginTop: 8, fontSize: 12 },

  orderCard: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  orderNumber: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  orderTotal: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },
});
