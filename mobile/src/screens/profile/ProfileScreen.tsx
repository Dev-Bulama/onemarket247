import React from 'react';
import { Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useAuthStore } from '../../store/authStore';
import { useCartStore } from '../../store/cartStore';

export default function ProfileScreen({ navigation }: any) {
  const { user, logout } = useAuthStore();
  const { clearLocal } = useCartStore();

  const handleLogout = () => {
    Alert.alert('Log Out', 'Are you sure you want to log out?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Log Out', style: 'destructive', onPress: async () => { await logout(); clearLocal(); } },
    ]);
  };

  const menuItems = [
    { icon: 'receipt-outline', label: 'My Orders', onPress: () => navigation.navigate('Orders') },
    { icon: 'location-outline', label: 'My Addresses', onPress: () => navigation.navigate('Addresses') },
    { icon: 'heart-outline', label: 'My Wishlist', onPress: () => navigation.navigate('Wishlist') },
    { icon: 'git-compare-outline', label: 'Compare Products', onPress: () => navigation.navigate('Compare') },
  ];

  return (
    <ScrollView style={styles.flex} contentContainerStyle={{ paddingBottom: 40 }}>
      <View style={styles.header}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{(user?.name ?? '?').charAt(0).toUpperCase()}</Text>
        </View>
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <View style={styles.menu}>
        {menuItems.map(item => (
          <TouchableOpacity key={item.label} style={styles.menuRow} onPress={item.onPress}>
            <IonIcon name={item.icon} size={20} color={COLORS.text} />
            <Text style={styles.menuLabel}>{item.label}</Text>
            <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} />
          </TouchableOpacity>
        ))}
      </View>

      <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
        <IonIcon name="log-out-outline" size={18} color={COLORS.danger} />
        <Text style={styles.logoutText}>Log Out</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: { alignItems: 'center', backgroundColor: COLORS.white, paddingTop: 56, paddingBottom: 24 },
  avatar: { width: 72, height: 72, borderRadius: 36, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  avatarText: { color: COLORS.white, fontSize: 28, fontWeight: 'bold' },
  name: { fontSize: 17, fontWeight: 'bold', color: COLORS.text },
  email: { fontSize: 13, color: COLORS.textSecondary, marginTop: 2 },
  menu: { backgroundColor: COLORS.white, marginTop: 12 },
  menuRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingHorizontal: SIZES.screenPadding, paddingVertical: 16, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  menuLabel: { flex: 1, fontSize: 14, color: COLORS.text },
  logoutBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, marginTop: 24, marginHorizontal: SIZES.screenPadding, borderWidth: 1, borderColor: COLORS.danger, borderRadius: SIZES.borderRadius, paddingVertical: 14 },
  logoutText: { color: COLORS.danger, fontWeight: 'bold', fontSize: 14 },
});
