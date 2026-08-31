import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useAuthStore } from '../../store/authStore';
import { useCartStore } from '../../store/cartStore';
import { useToastStore } from '../../store/toastStore';
import { apiErrorMessage } from '../../api/client';

export default function LoginScreen({ navigation }: any) {
  const { login, isLoading } = useAuthStore();
  const { mergeIntoAccount } = useCartStore();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async () => {
    setError('');
    if (!email || !password) {
      setError('Please enter your email and password.');
      return;
    }
    try {
      await login(email, password);
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not log in. Please check your credentials.'));
      return;
    }
    // Already logged in successfully at this point — a cart-merge failure
    // must not be reported as a login failure (it isn't one).
    try {
      await mergeIntoAccount();
    } catch {
      // best-effort — the guest cart simply won't have merged in
    }
    useToastStore.getState().show('Welcome back!');
    navigation.getParent()?.goBack();
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <TouchableOpacity onPress={() => navigation.getParent()?.goBack()} style={styles.closeBtn} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
          <IonIcon name="close" size={24} color={COLORS.text} />
        </TouchableOpacity>

        <Text style={styles.title}>Welcome back</Text>
        <Text style={styles.subtitle}>Log in to continue shopping on OneMarket 24/7</Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Email</Text>
        <TextInput
          style={styles.input}
          placeholder="you@example.com"
          placeholderTextColor={COLORS.placeholder}
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
        />

        <Text style={styles.label}>Password</Text>
        <View style={styles.passwordRow}>
          <TextInput
            style={styles.passwordInput}
            placeholder="••••••••"
            placeholderTextColor={COLORS.placeholder}
            value={password}
            onChangeText={setPassword}
            secureTextEntry={!showPassword}
          />
          <TouchableOpacity onPress={() => setShowPassword(v => !v)} style={styles.eyeBtn}>
            <IonIcon name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color={COLORS.textSecondary} />
          </TouchableOpacity>
        </View>

        <TouchableOpacity style={styles.primaryBtn} onPress={handleLogin} disabled={isLoading} activeOpacity={0.85}>
          {isLoading ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.primaryBtnText}>Log In</Text>}
        </TouchableOpacity>

        <TouchableOpacity style={styles.linkRow} onPress={() => navigation.navigate('Register')}>
          <Text style={styles.linkText}>Don't have an account? <Text style={styles.linkAccent}>Sign up</Text></Text>
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
  passwordRow: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius, backgroundColor: COLORS.grayLight },
  passwordInput: { flex: 1, paddingHorizontal: 14, paddingVertical: 12, fontSize: 14, color: COLORS.text },
  eyeBtn: { paddingHorizontal: 12 },
  primaryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: SIZES.xl },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  linkRow: { alignItems: 'center', marginTop: SIZES.lg },
  linkText: { color: COLORS.textSecondary, fontSize: 13 },
  linkAccent: { color: COLORS.primary, fontWeight: '700' },
});
