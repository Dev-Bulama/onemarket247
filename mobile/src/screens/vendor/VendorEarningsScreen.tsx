import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { vendorEarningsApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorEarningsSummary, VendorWalletTransaction } from '../../types/vendor';

export default function VendorEarningsScreen({ navigation }: any) {
  const [summary, setSummary] = useState<VendorEarningsSummary | null>(null);
  const [transactions, setTransactions] = useState<VendorWalletTransaction[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async (targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const [summaryRes, txnRes] = await Promise.all([
        targetPage === 1 ? vendorEarningsApi.summary() : Promise.resolve(null),
        vendorEarningsApi.transactions(targetPage),
      ]);
      if (summaryRes) setSummary(summaryRes.data.data);
      setTransactions(prev => (targetPage === 1 ? txnRes.data.data : [...prev, ...txnRes.data.data]));
      setPage(txnRes.data.meta.pagination.current_page);
      setLastPage(txnRes.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your earnings.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(1); }, [load]));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Earnings</Text>
        <View style={{ width: 22 }} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : (
        <FlatList
          data={transactions}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          ListHeaderComponent={
            <>
              <View style={styles.statsGrid}>
                <StatCard label="Available" value={summary?.available_balance.formatted ?? '—'} color={COLORS.accent} />
                <StatCard label="Pending" value={summary?.pending_balance.formatted ?? '—'} color={COLORS.warning} />
                <StatCard label="Reserved" value={summary?.reserved_balance.formatted ?? '—'} color={COLORS.info} />
                <StatCard label="Withdrawn" value={summary?.withdrawn_balance.formatted ?? '—'} color={COLORS.textSecondary} />
              </View>
              <TouchableOpacity style={styles.withdrawBtn} onPress={() => navigation.navigate('VendorWithdrawals')}>
                <IonIcon name="cash-outline" size={16} color={COLORS.white} />
                <Text style={styles.withdrawBtnText}>Go to Withdrawals</Text>
              </TouchableOpacity>
              <Text style={styles.sectionTitle}>Transactions</Text>
            </>
          }
          ListEmptyComponent={
            <View style={styles.empty}>
              <IonIcon name="swap-vertical-outline" size={40} color={COLORS.border} />
              <Text style={styles.emptyText}>No transactions yet.</Text>
            </View>
          }
          renderItem={({ item }) => {
            const positive = item.amount.amount >= 0;
            return (
              <View style={styles.txnRow}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.txnType}>{item.type.replace(/_/g, ' ')}</Text>
                  <Text style={styles.txnMeta}>
                    {item.order_number ? `#${item.order_number} · ` : ''}{item.balance_bucket.replace(/_/g, ' ')}
                  </Text>
                  {item.reason ? <Text style={styles.txnMeta}>{item.reason}</Text> : null}
                  <Text style={styles.txnDate}>{new Date(item.created_at).toLocaleDateString()}</Text>
                </View>
                <Text style={[styles.txnAmount, { color: positive ? COLORS.accent : COLORS.danger }]}>
                  {positive ? '+' : ''}{item.amount.formatted}
                </Text>
              </View>
            );
          }}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
      )}
    </View>
  );
}

function StatCard({ label, value, color }: { label: string; value: string; color: string }) {
  return (
    <View style={styles.statCard}>
      <Text style={[styles.statValue, { color }]} numberOfLines={1}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
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
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },

  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 14 },
  statCard: { width: '47%', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14 },
  statValue: { fontSize: 16, fontWeight: 'bold' },
  statLabel: { fontSize: 11, color: COLORS.textSecondary, marginTop: 2 },

  withdrawBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 12, marginBottom: 20 },
  withdrawBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 13 },

  sectionTitle: { fontSize: 14, fontWeight: 'bold', color: COLORS.text, marginBottom: 10 },
  empty: { alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 8, fontSize: 12 },

  txnRow: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  txnType: { fontSize: 13, fontWeight: '700', color: COLORS.text, textTransform: 'capitalize' },
  txnMeta: { fontSize: 11, color: COLORS.textMuted, marginTop: 2, textTransform: 'capitalize' },
  txnDate: { fontSize: 10, color: COLORS.textMuted, marginTop: 2 },
  txnAmount: { fontSize: 13, fontWeight: 'bold', marginLeft: 8 },
});
