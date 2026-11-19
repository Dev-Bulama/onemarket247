import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { authApi } from '../../api/auth';
import { apiErrorMessage } from '../../api/client';
import { useToastStore } from '../../store/toastStore';

export default function ForgotPasswordScreen({ navigation }: any) {
  const [email, setEmail] = useState('');
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSend = async () => {
    if (!email.trim()) {
      useToastStore.getState().show('Please enter your email address.', 'error');
      return;
    }
    setSending(true);
    try {
      await authApi.forgotPassword(email.trim());
      setSent(true);
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not send the reset link.'), 'error');
    } finally {
      setSending(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.closeBtn} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
          <IonIcon name="close" size={24} color={COLORS.text} />
        </TouchableOpacity>

        {sent ? (
          <View style={styles.sentBox}>
            <IonIcon name="mail-open-outline" size={48} color={COLORS.primary} />
            <Text style={styles.title}>Check your email</Text>
            <Text style={styles.subtitle}>
              If an account exists for {email.trim()}, we've sent a link to reset your password.
              Open it from your email app on this device to finish resetting your password in the browser.
            </Text>
            <TouchableOpacity style={styles.primaryBtn} onPress={() => navigation.goBack()} activeOpacity={0.85}>
              <Text style={styles.primaryBtnText}>Back to Log In</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            <Text style={styles.title}>Forgot your password?</Text>
            <Text style={styles.subtitle}>
              Enter the email address on your account and we'll send you a link to reset your password.
            </Text>

            <Text style={styles.label}>Email</Text>
            <TextInput
              style={styles.input}
              placeholder="you@example.com"
              placeholderTextColor={COLORS.placeholder}
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
              autoFocus
            />

            <TouchableOpacity style={styles.primaryBtn} onPress={handleSend} disabled={sending} activeOpacity={0.85}>
              {sending ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.primaryBtnText}>Send Reset Link</Text>}
            </TouchableOpacity>

            <TouchableOpacity style={styles.linkRow} onPress={() => navigation.goBack()}>
              <Text style={styles.linkText}>Remembered it? <Text style={styles.linkAccent}>Log in</Text></Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  container: { padding: SIZES.screenPadding, paddingTop: 56, flexGrow: 1 },
  closeBtn: { position: 'absolute', top: 16, right: 16, zIndex: 1 },
  title: { fontSize: 24, fontWeight: 'bold', color: COLORS.text, marginBottom: 6 },
  subtitle: { fontSize: 14, color: COLORS.textSecondary, marginBottom: SIZES.xl, lineHeight: 20 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 14 },
  input: {
    borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius,
    paddingHorizontal: 14, paddingVertical: 12, fontSize: 14, color: COLORS.text, backgroundColor: COLORS.grayLight,
  },
  primaryBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: SIZES.xl },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  linkRow: { alignItems: 'center', marginTop: SIZES.lg },
  linkText: { color: COLORS.textSecondary, fontSize: 13 },
  linkAccent: { color: COLORS.primary, fontWeight: '700' },
  sentBox: { alignItems: 'center', paddingTop: 40 },
});
