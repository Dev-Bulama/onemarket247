import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, Modal, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES, WITHDRAWAL_STATUSES } from '../../constants';
import { vendorWithdrawalsApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorWithdrawal, VendorWithdrawalMethod } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';
import { useToastStore } from '../../store/toastStore';

const toMinorUnits = (text: string): number => Math.round((parseFloat(text) || 0) * 100);

export default function VendorWithdrawalsScreen({ navigation }: any) {
  const [withdrawals, setWithdrawals] = useState<VendorWithdrawal[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');

  const [methods, setMethods] = useState<VendorWithdrawalMethod[]>([]);
  const [addMethodVisible, setAddMethodVisible] = useState(false);
  const [requestVisible, setRequestVisible] = useState(false);

  const loadMethods = useCallback(() => {
    vendorWithdrawalsApi.methods().then(res => setMethods(res.data.data)).catch(() => {});
  }, []);

  const load = useCallback(async (targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await vendorWithdrawalsApi.list(targetPage);
      setWithdrawals(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your withdrawals.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { load(1); loadMethods(); }, [load, loadMethods]));

  const handleCancel = (withdrawal: VendorWithdrawal) => {
    vendorWithdrawalsApi.cancel(Number(withdrawal.id))
      .then(res => {
        setWithdrawals(prev => prev.map(w => (w.id === withdrawal.id ? res.data.data : w)));
        useToastStore.getState().show('Withdrawal cancelled');
      })
      .catch(e => useToastStore.getState().show(apiErrorMessage(e, 'Could not cancel this withdrawal.'), 'error'));
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Withdrawals</Text>
        <View style={{ width: 22 }} />
      </View>

      <View style={styles.actionsRow}>
        <TouchableOpacity style={styles.secondaryBtn} onPress={() => setAddMethodVisible(true)}>
          <IonIcon name="card-outline" size={15} color={COLORS.primary} />
          <Text style={styles.secondaryBtnText}>Add Bank Account</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.primaryBtn} onPress={() => setRequestVisible(true)}>
          <IonIcon name="cash-outline" size={15} color={COLORS.white} />
          <Text style={styles.primaryBtnText}>Request Withdrawal</Text>
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : withdrawals.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="cash-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No withdrawals yet.</Text>
        </View>
      ) : (
        <FlatList
          data={withdrawals}
          keyExtractor={item => item.id}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => {
            const statusInfo = WITHDRAWAL_STATUSES[item.status] ?? { label: item.status_label, color: COLORS.textSecondary };
            return (
              <View style={styles.card}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.amount}>{item.amount.formatted}</Text>
                  <Text style={styles.meta}>{item.bank_name ?? 'Bank account'}</Text>
                  <Text style={styles.meta}>{new Date(item.requested_at).toLocaleDateString()}</Text>
                  {item.rejection_reason ? <Text style={styles.rejectionReason}>{item.rejection_reason}</Text> : null}
                </View>
                <View style={{ alignItems: 'flex-end', gap: 8 }}>
                  <StatusBadge label={statusInfo.label} color={statusInfo.color} />
                  {item.status === 'pending' && (
                    <TouchableOpacity onPress={() => handleCancel(item)}>
                      <Text style={styles.cancelLink}>Cancel</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            );
          }}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
      )}

      <Modal visible={addMethodVisible} transparent animationType="slide" onRequestClose={() => setAddMethodVisible(false)}>
        <AddMethodSheet
          onClose={() => setAddMethodVisible(false)}
          onAdded={method => { setMethods(prev => [...prev, method]); setAddMethodVisible(false); }}
        />
      </Modal>

      <Modal visible={requestVisible} transparent animationType="slide" onRequestClose={() => setRequestVisible(false)}>
        <RequestWithdrawalSheet
          methods={methods}
          onClose={() => setRequestVisible(false)}
          onRequested={withdrawal => { setWithdrawals(prev => [withdrawal, ...prev]); setRequestVisible(false); }}
          onAddAccount={() => { setRequestVisible(false); setAddMethodVisible(true); }}
        />
      </Modal>
    </View>
  );
}

function AddMethodSheet({ onClose, onAdded }: { onClose: () => void; onAdded: (method: VendorWithdrawalMethod) => void }) {
  const [bankName, setBankName] = useState('');
  const [accountName, setAccountName] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [saving, setSaving] = useState(false);

  const handleSubmit = async () => {
    if (!bankName.trim() || !accountName.trim() || !accountNumber.trim()) {
      useToastStore.getState().show('Please fill in all fields.', 'error');
      return;
    }
    setSaving(true);
    try {
      const res = await vendorWithdrawalsApi.addMethod({
        bank_name: bankName.trim(),
        account_name: accountName.trim(),
        account_number: accountNumber.trim(),
      });
      onAdded(res.data.data);
      useToastStore.getState().show('Bank account added');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not add this bank account.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Add Bank Account</Text>
        <Text style={styles.label}>Bank Name</Text>
        <TextInput style={styles.input} value={bankName} onChangeText={setBankName} placeholder="First Bank" placeholderTextColor={COLORS.placeholder} />
        <Text style={styles.label}>Account Name</Text>
        <TextInput style={styles.input} value={accountName} onChangeText={setAccountName} placeholder="Jane Doe" placeholderTextColor={COLORS.placeholder} />
        <Text style={styles.label}>Account Number</Text>
        <TextInput style={styles.input} value={accountNumber} onChangeText={setAccountNumber} placeholder="0123456789" placeholderTextColor={COLORS.placeholder} keyboardType="number-pad" />
        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Add Account</Text>}
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

function RequestWithdrawalSheet({ methods, onClose, onRequested, onAddAccount }: {
  methods: VendorWithdrawalMethod[];
  onClose: () => void;
  onRequested: (withdrawal: VendorWithdrawal) => void;
  onAddAccount: () => void;
}) {
  const [methodId, setMethodId] = useState<number | null>(methods[0]?.id ?? null);
  const [amount, setAmount] = useState('');
  const [saving, setSaving] = useState(false);

  const handleSubmit = async () => {
    if (!methodId) { useToastStore.getState().show('Please add and select a bank account first.', 'error'); return; }
    const minorAmount = toMinorUnits(amount);
    if (minorAmount <= 0) { useToastStore.getState().show('Please enter a valid amount.', 'error'); return; }
    setSaving(true);
    try {
      const res = await vendorWithdrawalsApi.request(methodId, minorAmount);
      onRequested(res.data.data);
      useToastStore.getState().show('Withdrawal requested');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not request this withdrawal.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Request Withdrawal</Text>

        {methods.length === 0 ? (
          <View style={styles.noMethodBox}>
            <Text style={styles.hint}>Add a bank account before requesting a withdrawal.</Text>
            <TouchableOpacity style={styles.saveBtn} onPress={onAddAccount}>
              <Text style={styles.saveBtnText}>Add Bank Account</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            <Text style={styles.label}>Bank Account</Text>
            <View style={styles.methodList}>
              {methods.map(method => (
                <TouchableOpacity
                  key={method.id}
                  style={[styles.methodRow, methodId === method.id && styles.methodRowActive]}
                  onPress={() => setMethodId(method.id)}
                >
                  <IonIcon name={methodId === method.id ? 'radio-button-on' : 'radio-button-off'} size={18} color={methodId === method.id ? COLORS.primary : COLORS.textMuted} />
                  <View style={{ marginLeft: 8 }}>
                    <Text style={styles.methodBank}>{method.bank_name}</Text>
                    <Text style={styles.methodAccount}>{method.account_name} · {method.account_number}</Text>
                  </View>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Amount</Text>
            <TextInput style={styles.input} value={amount} onChangeText={setAmount} placeholder="0.00" placeholderTextColor={COLORS.placeholder} keyboardType="decimal-pad" />

            <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
              {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Request Withdrawal</Text>}
            </TouchableOpacity>
          </>
        )}
      </View>
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
  actionsRow: { flexDirection: 'row', gap: 10, padding: SIZES.screenPadding, backgroundColor: COLORS.white },
  primaryBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, paddingVertical: 11 },
  primaryBtnText: { color: COLORS.white, fontWeight: '700', fontSize: 12 },
  secondaryBtn: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6, borderWidth: 1, borderColor: COLORS.primary, borderRadius: SIZES.borderRadiusSm, paddingVertical: 11 },
  secondaryBtnText: { color: COLORS.primary, fontWeight: '700', fontSize: 12 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  card: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  amount: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  meta: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  rejectionReason: { fontSize: 11, color: COLORS.danger, marginTop: 4 },
  cancelLink: { fontSize: 11, color: COLORS.danger, fontWeight: '700' },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  hint: { fontSize: 12, color: COLORS.textSecondary, marginBottom: 12, textAlign: 'center' },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  noMethodBox: { alignItems: 'center', paddingVertical: 12 },
  methodList: { gap: 8 },
  methodRow: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, padding: 12 },
  methodRowActive: { borderColor: COLORS.primary, backgroundColor: `${COLORS.primary}0D` },
  methodBank: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  methodAccount: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
});
