import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../constants';

export default function GuestGate({
  navigation,
  title,
  message,
}: {
  navigation: any;
  title: string;
  message: string;
}) {
  return (
    <View style={styles.container}>
      <View style={styles.iconBox}>
        <IonIcon name="lock-closed-outline" size={40} color={COLORS.primary} />
      </View>
      <Text style={styles.title}>{title}</Text>
      <Text style={styles.message}>{message}</Text>
      <TouchableOpacity
        style={styles.primaryBtn}
        onPress={() => navigation.getParent()?.navigate('Auth', { screen: 'Login' })}
        activeOpacity={0.85}
      >
        <Text style={styles.primaryBtnText}>Login</Text>
      </TouchableOpacity>
      <TouchableOpacity
        style={styles.secondaryBtn}
        onPress={() => navigation.getParent()?.navigate('Auth', { screen: 'Register' })}
        activeOpacity={0.85}
      >
        <Text style={styles.secondaryBtnText}>Create an account</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl, backgroundColor: COLORS.background },
  iconBox: {
    width: 88, height: 88, borderRadius: 44, backgroundColor: COLORS.grayLight,
    alignItems: 'center', justifyContent: 'center', marginBottom: SIZES.lg,
  },
  title: { fontSize: 18, fontWeight: 'bold', color: COLORS.text, marginBottom: 8 },
  message: { fontSize: 14, color: COLORS.textSecondary, textAlign: 'center', marginBottom: SIZES.xl, lineHeight: 20 },
  primaryBtn: {
    backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius,
    paddingVertical: 14, paddingHorizontal: SIZES.xxl, width: '100%', alignItems: 'center', marginBottom: 12,
  },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  secondaryBtn: { paddingVertical: 10 },
  secondaryBtnText: { color: COLORS.primary, fontWeight: '600', fontSize: 14 },
});
