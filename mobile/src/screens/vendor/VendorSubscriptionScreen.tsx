import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES, SUBSCRIPTION_STATUSES } from '../../constants';
import { vendorSubscriptionApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { VendorSubscriptionItem, VendorSubscriptionPlanItem } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';

export default function VendorSubscriptionScreen({ navigation }: any) {
  const { user } = useAuthStore();
  const isOwner = user?.user_type === 'vendor_owner';

  const [plans, setPlans] = useState<VendorSubscriptionPlanItem[]>([]);
  const [current, setCurrent] = useState<VendorSubscriptionItem | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [info, setInfo] = useState('');
  const [switchingId, setSwitchingId] = useState<number | null>(null);

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    vendorSubscriptionApi.index()
      .then(res => { setPlans(res.data.data.plans); setCurrent(res.data.data.current); })
      .catch(e => setError(apiErrorMessage(e, 'Could not load subscription plans.')))
      .finally(() => setLoading(false));
  }, []);

  useFocusEffect(useCallback(() => { if (isOwner) load(); else setLoading(false); }, [load, isOwner]));

  const handleSwitch = async (plan: VendorSubscriptionPlanItem) => {
    setSwitchingId(plan.id);
    setError('');
    setInfo('');
    try {
      const res = await vendorSubscriptionApi.switchTo(plan.id);
      if (res.data.data.requires_contact_support) {
        setInfo(res.data.message ?? 'Contact support to upgrade to a paid plan.');
      } else if (res.data.data.switched) {
        setInfo(res.data.message ?? 'Subscription plan updated.');
        load();
      }
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not switch your subscription plan.'));
    } finally {
      setSwitchingId(null);
    }
  };

  if (!isOwner) {
    return (
      <View style={styles.flex}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
          <Text style={styles.headerTitle}>Subscription</Text>
          <View style={{ width: 22 }} />
        </View>
        <View style={styles.empty}>
          <IonIcon name="lock-closed-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>Only the store owner can manage the subscription.</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Subscription</Text>
        <View style={{ width: 22 }} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={load}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : (
        <ScrollView contentContainerStyle={styles.content}>
          {info ? <Text style={styles.info}>{info}</Text> : null}

          {current && (
            <View style={styles.currentCard}>
              <Text style={styles.currentLabel}>Current Plan</Text>
              <Text style={styles.currentPlanName}>{current.plan.name}</Text>
              <StatusBadge label={SUBSCRIPTION_STATUSES[current.status]?.label ?? current.status_label} color={SUBSCRIPTION_STATUSES[current.status]?.color ?? COLORS.textSecondary} />
            </View>
          )}

          {plans.map(plan => {
            const isCurrent = current?.plan.id === plan.id;
            return (
              <View key={plan.id} style={[styles.planCard, isCurrent && styles.planCardActive]}>
                <View style={styles.planHeaderRow}>
                  <Text style={styles.planName}>{plan.name}</Text>
                  {plan.is_default ? <View style={styles.defaultBadge}><Text style={styles.defaultBadgeText}>Default</Text></View> : null}
                </View>
                <Text style={styles.planPrice}>{plan.is_free ? 'Free' : `${plan.price.formatted}${plan.billing_period ? ` / ${plan.billing_period}` : ''}`}</Text>
                {plan.description ? <Text style={styles.planDescription}>{plan.description}</Text> : null}
                {plan.max_products != null ? <Text style={styles.planMeta}>Up to {plan.max_products} products</Text> : null}

                {isCurrent ? (
                  <View style={styles.currentPill}><Text style={styles.currentPillText}>Current Plan</Text></View>
                ) : (
                  <TouchableOpacity style={styles.switchBtn} onPress={() => handleSwitch(plan)} disabled={switchingId === plan.id}>
                    {switchingId === plan.id ? <ActivityIndicator color={COLORS.white} size="small" /> : <Text style={styles.switchBtnText}>Switch to this plan</Text>}
                  </TouchableOpacity>
                )}
              </View>
            );
          })}
        </ScrollView>
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
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  info: { backgroundColor: `${COLORS.info}1A`, color: COLORS.info, fontSize: 12, padding: 12, borderRadius: SIZES.borderRadiusSm, marginBottom: 14 },

  currentCard: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 16, marginBottom: 16, alignItems: 'center', gap: 6 },
  currentLabel: { fontSize: 11, color: COLORS.textMuted, textTransform: 'uppercase', fontWeight: '700' },
  currentPlanName: { fontSize: 17, fontWeight: 'bold', color: COLORS.text },

  planCard: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: COLORS.border },
  planCardActive: { borderColor: COLORS.primary },
  planHeaderRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  planName: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  defaultBadge: { backgroundColor: COLORS.grayLight, borderRadius: 999, paddingHorizontal: 8, paddingVertical: 3 },
  defaultBadgeText: { fontSize: 10, color: COLORS.textSecondary, fontWeight: '700' },
  planPrice: { fontSize: 18, fontWeight: 'bold', color: COLORS.primary, marginTop: 6 },
  planDescription: { fontSize: 12, color: COLORS.textSecondary, marginTop: 6, lineHeight: 17 },
  planMeta: { fontSize: 11, color: COLORS.textMuted, marginTop: 6 },
  currentPill: { alignSelf: 'flex-start', backgroundColor: `${COLORS.accent}1A`, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 6, marginTop: 12 },
  currentPillText: { fontSize: 12, fontWeight: '700', color: COLORS.accent },
  switchBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, paddingVertical: 10, alignItems: 'center', marginTop: 12 },
  switchBtnText: { color: COLORS.white, fontWeight: '700', fontSize: 12 },
});
