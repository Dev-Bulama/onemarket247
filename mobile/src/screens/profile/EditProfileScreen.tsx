import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { authApi } from '../../api/auth';
import { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { useToastStore } from '../../store/toastStore';

export default function EditProfileScreen({ navigation }: any) {
  const { user, updateUser } = useAuthStore();
  const [name, setName] = useState(user?.name ?? '');
  const [phone, setPhone] = useState(user?.phone ?? '');
  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    if (!name.trim()) {
      useToastStore.getState().show('Please enter your name.', 'error');
      return;
    }
    setSaving(true);
    try {
      const res = await authApi.updateProfile({ name: name.trim(), phone: phone.trim() || undefined });
      updateUser({ ...user, ...res.data.data });
      useToastStore.getState().show('Profile updated.');
      navigation.goBack();
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not update your profile.'), 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Edit Profile</Text>
        <View style={styles.backSpacer} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Text style={styles.label}>Name</Text>
        <TextInput
          style={styles.input}
          value={name}
          onChangeText={setName}
          placeholder="Your full name"
          placeholderTextColor={COLORS.placeholder}
        />

        <Text style={styles.label}>Phone</Text>
        <TextInput
          style={styles.input}
          value={phone}
          onChangeText={setPhone}
          placeholder="e.g. +234 800 000 0000"
          placeholderTextColor={COLORS.placeholder}
          keyboardType="phone-pad"
        />

        <Text style={styles.label}>Email</Text>
        <View style={[styles.input, styles.inputDisabled]}>
          <Text style={styles.disabledText}>{user?.email}</Text>
        </View>
        <Text style={styles.hint}>Your email can't be changed here — contact support if you need it updated.</Text>

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving} activeOpacity={0.85}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Save Changes</Text>}
        </TouchableOpacity>

        <TouchableOpacity style={styles.changePasswordRow} onPress={() => navigation.navigate('ChangePassword')}>
          <IonIcon name="lock-closed-outline" size={18} color={COLORS.text} />
          <Text style={styles.changePasswordText}>Change Password</Text>
          <IonIcon name="chevron-forward" size={18} color={COLORS.textMuted} />
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
  input: {
    borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius,
    paddingHorizontal: 14, paddingVertical: 12, fontSize: 14, color: COLORS.text, backgroundColor: COLORS.grayLight,
  },
  inputDisabled: { justifyContent: 'center' },
  disabledText: { fontSize: 14, color: COLORS.textMuted },
  hint: { fontSize: 11, color: COLORS.textMuted, marginTop: 6 },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: SIZES.xl },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  changePasswordRow: { flexDirection: 'row', alignItems: 'center', gap: 10, marginTop: 24, paddingVertical: 14, paddingHorizontal: 4 },
  changePasswordText: { flex: 1, fontSize: 14, color: COLORS.text, fontWeight: '600' },
});
