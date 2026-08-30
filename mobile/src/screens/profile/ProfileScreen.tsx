import React from 'react';
import { Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useAuthStore } from '../../store/authStore';
import { useCartStore } from '../../store/cartStore';

export default function ProfileScreen({ navigation }: any) {
  const { user, isAuthenticated, logout } = useAuthStore();
  const { clearLocal } = useCartStore();

  const handleLogout = () => {
    Alert.alert('Log Out', 'Are you sure you want to log out?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Log Out', style: 'destructive', onPress: async () => { await logout(); clearLocal(); } },
    ]);
  };

  const accountItems = [
    { icon: 'receipt-outline', label: 'My Orders', onPress: () => navigation.navigate('Orders') },
    { icon: 'location-outline', label: 'My Addresses', onPress: () => navigation.navigate('Addresses') },
    { icon: 'heart-outline', label: 'My Wishlist', onPress: () => navigation.navigate('Wishlist') },
    { icon: 'git-compare-outline', label: 'Compare Products', onPress: () => navigation.navigate('Compare') },
  ];

  const infoItems = [
    { icon: 'language-outline', label: 'Language & Currency', onPress: () => navigation.navigate('Preferences') },
    { icon: 'newspaper-outline', label: 'Blog', onPress: () => navigation.navigate('Blog') },
    { icon: 'help-circle-outline', label: 'FAQ', onPress: () => navigation.navigate('Faq') },
    { icon: 'mail-outline', label: 'Contact Us', onPress: () => navigation.navigate('Contact') },
    { icon: 'information-circle-outline', label: 'About Us', onPress: () => navigation.navigate('Page', { page: 'about-us' }) },
    { icon: 'people-outline', label: 'Partnership', onPress: () => navigation.navigate('Page', { page: 'partnership' }) },
    { icon: 'document-text-outline', label: 'Terms of Service', onPress: () => navigation.navigate('Page', { page: 'terms' }) },
    { icon: 'shield-outline', label: 'Privacy Policy', onPress: () => navigation.navigate('Page', { page: 'privacy' }) },
  ];

  return (
    <ScrollView style={styles.flex} contentContainerStyle={styles.scrollContent}>
      {isAuthenticated ? (
        <View style={styles.header}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(user?.name ?? '?').charAt(0).toUpperCase()}</Text>
          </View>
          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.email}>{user?.email}</Text>
        </View>
      ) : (
        <View style={styles.guestHeader}>
          <View style={styles.avatar}>
            <IonIcon name="person" size={32} color={COLORS.white} />
          </View>
          <Text style={styles.name}>Welcome to OneMarket 24/7</Text>
          <Text style={styles.email}>Log in to see your orders, addresses, and wishlist.</Text>
          <View style={styles.authBtnRow}>
            <TouchableOpacity style={styles.loginBtn} onPress={() => navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Login' })}>
              <Text style={styles.loginBtnText}>Log In</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.registerBtn} onPress={() => navigation.getParent()?.getParent()?.navigate('Auth', { screen: 'Register' })}>
              <Text style={styles.registerBtnText}>Sign Up</Text>
            </TouchableOpacity>
          </View>
        </View>
      )}

      {isAuthenticated && (
        <View style={styles.menu}>
          {accountItems.map(item => (
            <TouchableOpacity key={item.label} style={styles.menuRow} onPress={item.onPress}>
              <IonIcon name={item.icon} size={20} color={COLORS.text} />
              <Text style={styles.menuLabel}>{item.label}</Text>
              <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} />
            </TouchableOpacity>
          ))}
        </View>
      )}

      <Text style={styles.sectionLabel}>Info & Help</Text>
      <View style={styles.menu}>
        {infoItems.map(item => (
          <TouchableOpacity key={item.label} style={styles.menuRow} onPress={item.onPress}>
            <IonIcon name={item.icon} size={20} color={COLORS.text} />
            <Text style={styles.menuLabel}>{item.label}</Text>
            <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} />
          </TouchableOpacity>
        ))}
      </View>

      {isAuthenticated && (
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
          <IonIcon name="log-out-outline" size={18} color={COLORS.danger} />
          <Text style={styles.logoutText}>Log Out</Text>
        </TouchableOpacity>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  scrollContent: { paddingBottom: 40 },
  header: { alignItems: 'center', backgroundColor: COLORS.white, paddingTop: 56, paddingBottom: 24 },
  guestHeader: { alignItems: 'center', backgroundColor: COLORS.white, paddingTop: 56, paddingBottom: 24, paddingHorizontal: SIZES.xxl },
  avatar: { width: 72, height: 72, borderRadius: 36, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  avatarText: { color: COLORS.white, fontSize: 28, fontWeight: 'bold' },
  name: { fontSize: 17, fontWeight: 'bold', color: COLORS.text, textAlign: 'center' },
  email: { fontSize: 13, color: COLORS.textSecondary, marginTop: 2, textAlign: 'center' },
  authBtnRow: { flexDirection: 'row', gap: 10, marginTop: 16 },
  loginBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingHorizontal: 24, paddingVertical: 10 },
  loginBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 13 },
  registerBtn: { borderWidth: 1, borderColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingHorizontal: 24, paddingVertical: 10 },
  registerBtnText: { color: COLORS.primary, fontWeight: 'bold', fontSize: 13 },
  sectionLabel: { fontSize: 12, fontWeight: '700', color: COLORS.textMuted, textTransform: 'uppercase', marginTop: 20, marginBottom: 6, marginLeft: SIZES.screenPadding },
  menu: { backgroundColor: COLORS.white },
  menuRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingHorizontal: SIZES.screenPadding, paddingVertical: 16, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  menuLabel: { flex: 1, fontSize: 14, color: COLORS.text },
  logoutBtn: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, marginTop: 24, marginHorizontal: SIZES.screenPadding, borderWidth: 1, borderColor: COLORS.danger, borderRadius: SIZES.borderRadius, paddingVertical: 14 },
  logoutText: { color: COLORS.danger, fontWeight: 'bold', fontSize: 14 },
});
