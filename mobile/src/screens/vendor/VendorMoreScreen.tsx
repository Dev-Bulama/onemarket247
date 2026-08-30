import React from 'react';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useAuthStore } from '../../store/authStore';

export default function VendorMoreScreen({ navigation }: any) {
  const { user } = useAuthStore();
  const isOwner = user?.user_type === 'vendor_owner';

  const storeItems = [
    { icon: 'cash-outline', label: 'Earnings', onPress: () => navigation.navigate('VendorEarnings') },
    { icon: 'wallet-outline', label: 'Withdrawals', onPress: () => navigation.navigate('VendorWithdrawals') },
    ...(isOwner ? [{ icon: 'people-outline', label: 'Staff', onPress: () => navigation.navigate('VendorStaff') }] : []),
    ...(isOwner ? [{ icon: 'ribbon-outline', label: 'Subscription', onPress: () => navigation.navigate('VendorSubscription') }] : []),
    { icon: 'document-text-outline', label: 'Documents', onPress: () => navigation.navigate('VendorDocuments') },
    { icon: 'storefront-outline', label: 'Store Settings', onPress: () => navigation.navigate('VendorStoreSettings') },
  ];

  return (
    <ScrollView style={styles.flex} contentContainerStyle={styles.scrollContent}>
      <View style={styles.header}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{(user?.name ?? '?').charAt(0).toUpperCase()}</Text>
        </View>
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.role}>{isOwner ? 'Store Owner' : 'Store Staff'}</Text>
      </View>

      <View style={styles.menu}>
        {storeItems.map(item => (
          <TouchableOpacity key={item.label} style={styles.menuRow} onPress={item.onPress}>
            <IonIcon name={item.icon} size={20} color={COLORS.text} />
            <Text style={styles.menuLabel}>{item.label}</Text>
            <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} />
          </TouchableOpacity>
        ))}
      </View>

      <TouchableOpacity style={styles.switchBtn} onPress={() => navigation.getParent()?.getParent()?.navigate('Main')}>
        <IonIcon name="storefront-outline" size={18} color={COLORS.primary} />
        <Text style={styles.switchBtnText}>Switch to Shopping</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  scrollContent: { paddingBottom: 40 },
  header: { alignItems: 'center', backgroundColor: COLORS.white, paddingTop: 56, paddingBottom: 24 },
  avatar: { width: 72, height: 72, borderRadius: 36, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  avatarText: { color: COLORS.white, fontSize: 28, fontWeight: 'bold' },
  name: { fontSize: 17, fontWeight: 'bold', color: COLORS.text, textAlign: 'center' },
  role: { fontSize: 13, color: COLORS.textSecondary, marginTop: 2 },
  menu: { backgroundColor: COLORS.white, marginTop: 20 },
  menuRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingHorizontal: SIZES.screenPadding, paddingVertical: 16, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  menuLabel: { flex: 1, fontSize: 14, color: COLORS.text },
  switchBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, marginTop: 24, marginHorizontal: SIZES.screenPadding, borderWidth: 1, borderColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14 },
  switchBtnText: { color: COLORS.primary, fontWeight: 'bold', fontSize: 14 },
});
