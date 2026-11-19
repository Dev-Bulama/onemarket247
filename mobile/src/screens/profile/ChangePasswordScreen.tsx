import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { authApi } from '../../api/auth';
import { apiErrorMessage } from '../../api/client';
import { useToastStore } from '../../store/toastStore';

function PasswordField({
  label, value, onChangeText, placeholder,
}: { label: string; value: string; onChangeText: (v: string) => void; placeholder?: string }) {
  const [show, setShow] = useState(false);
  return (
    <>
      <Text style={styles.label}>{label}</Text>
      <View style={styles.passwordRow}>
        <TextInput
          style={styles.passwordInput}
          value={value}
          onChangeText={onChangeText}
          placeholder={placeholder}
          placeholderTextColor={COLORS.placeholder}
          secureTextEntry={!show}
        />
        <TouchableOpacity onPress={() => setShow(v => !v)} style={styles.eyeBtn}>
          <IonIcon name={show ? 'eye-off-outline' : 'eye-outline'} size={20} color={COLORS.textSecondary} />
        </TouchableOpacity>
      </View>
    </>
  );
}

export default function ChangePasswordScreen({ navigation }: any) {
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [saving, setSaving] = useState(false);

  const handleSubmit = async () => {
    if (!currentPassword || !password || !passwordConfirmation) {
      useToastStore.getState().show('Please fill in all three fields.', 'error');
      return;
    }
    if (password !== passwordConfirmation) {
      useToastStore.getState().show('New password and confirmation don\'t match.', 'error');
      return;
    }
    setSaving(true);
    try {
      await authApi.updatePassword({ current_password: currentPassword, password, password_confirmation: passwordConfirmation });
      useToastStore.getState().show('Password changed.');
      navigation.goBack();
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not change your password.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Change Password</Text>
        <View style={styles.backSpacer} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <PasswordField label="Current Password" value={currentPassword} onChangeText={setCurrentPassword} placeholder="••••••••" />
        <PasswordField label="New Password" value={password} onChangeText={setPassword} placeholder="At least 8 characters" />
        <PasswordField label="Confirm New Password" value={passwordConfirmation} onChangeText={setPasswordConfirmation} placeholder="••••••••" />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving} activeOpacity={0.85}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Update Password</Text>}
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  backSpacer: { width: 22 },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 14 },
  passwordRow: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius, backgroundColor: COLORS.grayLight },
  passwordInput: { flex: 1, paddingHorizontal: 14, paddingVertical: 12, fontSize: 14, color: COLORS.text },
  eyeBtn: { paddingHorizontal: 12 },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: SIZES.xl },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
