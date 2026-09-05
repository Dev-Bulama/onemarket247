import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, Alert, FlatList, Modal, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES, STAFF_PERMISSIONS, STAFF_STATUSES } from '../../constants';
import { vendorStaffApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { VendorStaffMember } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';
import { useToastStore } from '../../store/toastStore';

export default function VendorStaffScreen({ navigation }: any) {
  const { user } = useAuthStore();
  const isOwner = user?.user_type === 'vendor_owner';

  const [staff, setStaff] = useState<VendorStaffMember[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState('');
  const [inviteVisible, setInviteVisible] = useState(false);
  const [editing, setEditing] = useState<VendorStaffMember | null>(null);

  const load = useCallback(async (targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    setError('');
    try {
      const res = await vendorStaffApi.list(targetPage);
      setStaff(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not load your staff.'));
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, []);

  useFocusEffect(useCallback(() => { if (isOwner) load(1); else setLoading(false); }, [load, isOwner]));

  const handleRemove = (member: VendorStaffMember) => {
    Alert.alert('Remove Staff', `Remove ${member.name ?? 'this staff member'} from your store?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove', style: 'destructive', onPress: () => {
          vendorStaffApi.destroy(member.id)
            .then(() => {
              setStaff(prev => prev.filter(s => s.id !== member.id));
              useToastStore.getState().show('Staff member removed');
            })
            .catch(e => useToastStore.getState().show(apiErrorMessage(e, 'Could not remove this staff member.'), 'error'));
        },
      },
    ]);
  };

  if (!isOwner) {
    return (
      <View style={styles.flex}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
          <Text style={styles.headerTitle}>Staff</Text>
          <View style={{ width: 22 }} />
        </View>
        <View style={styles.empty}>
          <IonIcon name="lock-closed-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>Only the store owner can manage staff.</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Staff</Text>
        <TouchableOpacity style={styles.addBtn} onPress={() => setInviteVisible(true)}>
          <IonIcon name="add" size={20} color={COLORS.white} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={() => load(1)}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : staff.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="people-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No staff invited yet.</Text>
        </View>
      ) : (
        <FlatList
          data={staff}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => {
            const statusInfo = STAFF_STATUSES[item.status] ?? { label: item.status_label, color: COLORS.textSecondary };
            return (
              <View style={styles.card}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.name}>{item.name ?? 'Staff member'}</Text>
                  <Text style={styles.email}>{item.email}</Text>
                  <Text style={styles.permissions} numberOfLines={2}>
                    {item.permissions.length ? item.permissions.map(p => p.split(/[._]/).slice(1).join(' ')).join(', ') : 'No permissions granted'}
                  </Text>
                  <View style={{ marginTop: 6 }}>
                    <StatusBadge label={statusInfo.label} color={statusInfo.color} />
                  </View>
                </View>
                <View style={styles.rowActions}>
                  <TouchableOpacity style={styles.iconBtn} onPress={() => setEditing(item)}>
                    <IonIcon name="create-outline" size={18} color={COLORS.primary} />
                  </TouchableOpacity>
                  <TouchableOpacity style={styles.iconBtn} onPress={() => handleRemove(item)}>
                    <IonIcon name="trash-outline" size={18} color={COLORS.danger} />
                  </TouchableOpacity>
                </View>
              </View>
            );
          }}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={{ marginVertical: 16 }} /> : null}
        />
      )}

      <Modal visible={inviteVisible} transparent animationType="slide" onRequestClose={() => setInviteVisible(false)}>
        <InviteSheet
          onClose={() => setInviteVisible(false)}
          onInvited={member => { setStaff(prev => [member, ...prev]); setInviteVisible(false); }}
        />
      </Modal>

      <Modal visible={!!editing} transparent animationType="slide" onRequestClose={() => setEditing(null)}>
        {editing && (
          <EditSheet
            member={editing}
            onClose={() => setEditing(null)}
            onUpdated={updated => { setStaff(prev => prev.map(s => (s.id === updated.id ? updated : s))); setEditing(null); }}
          />
        )}
      </Modal>
    </View>
  );
}

function PermissionsChecklist({ selected, onToggle }: { selected: string[]; onToggle: (name: string) => void }) {
  return (
    <ScrollView style={{ maxHeight: 220 }}>
      {STAFF_PERMISSIONS.map(perm => (
        <TouchableOpacity key={perm.name} style={styles.permRow} onPress={() => onToggle(perm.name)}>
          <IonIcon name={selected.includes(perm.name) ? 'checkbox' : 'square-outline'} size={18} color={selected.includes(perm.name) ? COLORS.primary : COLORS.textMuted} />
          <Text style={styles.permLabel}>{perm.label}</Text>
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
}

function InviteSheet({ onClose, onInvited }: { onClose: () => void; onInvited: (member: VendorStaffMember) => void }) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [permissions, setPermissions] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);

  const togglePermission = (perm: string) => setPermissions(prev => (prev.includes(perm) ? prev.filter(p => p !== perm) : [...prev, perm]));

  const handleSubmit = async () => {
    if (!name.trim() || !email.trim()) { useToastStore.getState().show('Please fill in name and email.', 'error'); return; }
    setSaving(true);
    try {
      const res = await vendorStaffApi.invite({ name: name.trim(), email: email.trim(), permissions });
      onInvited(res.data.data);
      useToastStore.getState().show('Invite sent');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not invite this staff member.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Invite Staff</Text>
        <Text style={styles.label}>Name</Text>
        <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Full name" placeholderTextColor={COLORS.placeholder} />
        <Text style={styles.label}>Email</Text>
        <TextInput style={styles.input} value={email} onChangeText={setEmail} placeholder="staff@example.com" placeholderTextColor={COLORS.placeholder} keyboardType="email-address" autoCapitalize="none" />
        <Text style={styles.label}>Permissions</Text>
        <PermissionsChecklist selected={permissions} onToggle={togglePermission} />
        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Send Invite</Text>}
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

function EditSheet({ member, onClose, onUpdated }: { member: VendorStaffMember; onClose: () => void; onUpdated: (member: VendorStaffMember) => void }) {
  const [status, setStatus] = useState<'active' | 'suspended'>(member.status === 'suspended' ? 'suspended' : 'active');
  const [permissions, setPermissions] = useState<string[]>(member.permissions);
  const [saving, setSaving] = useState(false);

  const togglePermission = (perm: string) => setPermissions(prev => (prev.includes(perm) ? prev.filter(p => p !== perm) : [...prev, perm]));

  const handleSubmit = async () => {
    setSaving(true);
    try {
      const res = await vendorStaffApi.update(member.id, { status, permissions });
      onUpdated(res.data.data);
      useToastStore.getState().show('Staff member updated');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not update this staff member.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>{member.name ?? 'Staff member'}</Text>

        <Text style={styles.label}>Status</Text>
        <View style={styles.statusToggleRow}>
          <TouchableOpacity style={[styles.chip, status === 'active' && styles.chipActive]} onPress={() => setStatus('active')}>
            <Text style={[styles.chipText, status === 'active' && styles.chipTextActive]}>Active</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[styles.chip, status === 'suspended' && styles.chipActiveDanger]} onPress={() => setStatus('suspended')}>
            <Text style={[styles.chipText, status === 'suspended' && styles.chipTextActive]}>Suspended</Text>
          </TouchableOpacity>
        </View>

        <Text style={styles.label}>Permissions</Text>
        <PermissionsChecklist selected={permissions} onToggle={togglePermission} />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Save Changes</Text>}
        </TouchableOpacity>
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
  addBtn: { width: 32, height: 32, borderRadius: 16, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center' },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: '#fff', fontSize: 12, textAlign: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'flex-start', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  name: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  email: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  permissions: { fontSize: 11, color: COLORS.textSecondary, marginTop: 6, textTransform: 'capitalize' },
  rowActions: { gap: 8 },
  iconBtn: { padding: 6 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  permRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  permLabel: { fontSize: 13, color: COLORS.text },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  statusToggleRow: { flexDirection: 'row', gap: 8 },
  chip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 16, paddingVertical: 8 },
  chipActive: { backgroundColor: COLORS.accent, borderColor: COLORS.accent },
  chipActiveDanger: { backgroundColor: COLORS.danger, borderColor: COLORS.danger },
  chipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  chipTextActive: { color: COLORS.white },
});
