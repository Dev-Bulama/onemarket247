import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useAuthStore } from '../../store/authStore';
import { useCartStore } from '../../store/cartStore';
import { apiErrorMessage } from '../../api/client';

export default function RegisterScreen({ navigation }: any) {
  const { register, isLoading } = useAuthStore();
  const { mergeIntoAccount } = useCartStore();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');

  const handleRegister = async () => {
    setError('');
    if (!name || !email || !password) {
      setError('Please fill in your name, email, and password.');
      return;
    }
    if (password !== confirmPassword) {
      setError('Passwords do not match.');
      return;
    }
    try {
      await register({ name, email, phone: phone || undefined, password, password_confirmation: confirmPassword });
      await mergeIntoAccount();
      navigation.getParent()?.goBack();
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not create your account. Please try again.'));
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <TouchableOpacity onPress={() => navigation.getParent()?.goBack()} style={styles.closeBtn} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
          <IonIcon name="close" size={24} color={COLORS.text} />
        </TouchableOpacity>

        <Text style={styles.title}>Create your account</Text>
        <Text style={styles.subtitle}>Join OneMarket 24/7 to start shopping</Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Full Name</Text>
        <TextInput style={styles.input} placeholder="Jane Doe" placeholderTextColor={COLORS.placeholder} value={name} onChangeText={setName} />

        <Text style={styles.label}>Email</Text>
        <TextInput style={styles.input} placeholder="you@example.com" placeholderTextColor={COLORS.placeholder} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />

        <Text style={styles.label}>Phone (optional)</Text>
        <TextInput style={styles.input} placeholder="08012345678" placeholderTextColor={COLORS.placeholder} value={phone} onChangeText={setPhone} keyboardType="phone-pad" />

        <Text style={styles.label}>Password</Text>
        <TextInput style={styles.input} placeholder="••••••••" placeholderTextColor={COLORS.placeholder} value={password} onChangeText={setPassword} secureTextEntry />

        <Text style={styles.label}>Confirm Password</Text>
        <TextInput style={styles.input} placeholder="••••••••" placeholderTextColor={COLORS.placeholder} value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry />

        <TouchableOpacity style={styles.primaryBtn} onPress={handleRegister} disabled={isLoading} activeOpacity={0.85}>
          {isLoading ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.primaryBtnText}>Create Account</Text>}
        </TouchableOpacity>

        <TouchableOpacity style={styles.linkRow} onPress={() => navigation.navigate('Login')}>
          <Text style={styles.linkText}>Already have an account? <Text style={styles.linkAccent}>Log in</Text></Text>
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  container: { padding: SIZES.screenPadding, paddingTop: 56, flexGrow: 1 },
  closeBtn: { position: 'absolute', top: 16, right: 16, zIndex: 1 },
  title: { fontSize: 24, fontWeight: 'bold', color: COLORS.text, marginBottom: 6 },
  subtitle: { fontSize: 14, color: COLORS.textSecondary, marginBottom: SIZES.xl },
  error: { color: COLORS.danger, marginBottom: 12, fontSize: 13 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 14 },
  input: {
    borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius,
    paddingHorizontal: 14, paddingVertical: 12, fontSize: 14, color: COLORS.text, backgroundColor: COLORS.grayLight,
  },
  primaryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: SIZES.xl },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  linkRow: { alignItems: 'center', marginTop: SIZES.lg, marginBottom: SIZES.xl },
  linkText: { color: COLORS.textSecondary, fontSize: 13 },
  linkAccent: { color: COLORS.primary, fontWeight: '700' },
});
