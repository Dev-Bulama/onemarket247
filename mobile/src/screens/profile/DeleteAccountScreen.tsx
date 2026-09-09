import React, { useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { authApi } from '../../api/auth';
import { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { useCartStore } from '../../store/cartStore';
import { useToastStore } from '../../store/toastStore';

export default function DeleteAccountScreen({ navigation }: any) {
  const { logout } = useAuthStore();
  const { clearLocal } = useCartStore();
  const [password, setPassword] = useState('');
  const [deleting, setDeleting] = useState(false);

  const handleDelete = () => {
    Alert.alert(
      'Delete Account',
      "This permanently deletes your account. This can't be undone. Are you sure?",
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Delete My Account', style: 'destructive', onPress: submit },
      ],
    );
  };

  const submit = async () => {
    setDeleting(true);
    try {
      await authApi.deleteAccount(password || undefined);
      useToastStore.getState().show('Your account has been deleted.');
      await logout();
      clearLocal();
      navigation.navigate('Profile');
    } catch (e) {
      useToastStore.getState().show(apiErrorMessage(e, 'Could not delete your account.'), 'error');
    } finally {
      setDeleting(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Delete Account</Text>
        <View style={styles.backSpacer} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <View style={styles.warningBox}>
          <IonIcon name="warning-outline" size={28} color={COLORS.danger} />
          <Text style={styles.warningTitle}>This is permanent</Text>
          <Text style={styles.warningText}>
            Deleting your account removes your personal information, saved addresses, wishlist, and cart.
            You'll be signed out everywhere immediately. Some order records may be kept as required by law
            or for accounting purposes, but they'll no longer be linked to your personal details.
          </Text>
        </View>

        <Text style={styles.label}>Confirm Your Password</Text>
        <TextInput
          style={styles.input}
          value={password}
          onChangeText={setPassword}
          placeholder="Leave blank if you signed in with Google/Facebook"
          placeholderTextColor={COLORS.placeholder}
          secureTextEntry
        />

        <TouchableOpacity style={styles.deleteBtn} onPress={handleDelete} disabled={deleting}>
          {deleting ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.deleteBtnText}>Delete My Account</Text>}
        </TouchableOpacity>

        <TouchableOpacity style={styles.cancelBtn} onPress={() => navigation.goBack()}>
          <Text style={styles.cancelBtnText}>Cancel</Text>
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
  warningBox: {
    alignItems: 'center', backgroundColor: `${COLORS.danger}0D`, borderRadius: SIZES.borderRadius,
    padding: 20, marginBottom: 24,
  },
  warningTitle: { fontSize: 15, fontWeight: 'bold', color: COLORS.text, marginTop: 10, marginBottom: 8 },
  warningText: { fontSize: 12, color: COLORS.textSecondary, textAlign: 'center', lineHeight: 18 },
  label: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 6 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  deleteBtn: { backgroundColor: COLORS.danger, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 24 },
  deleteBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  cancelBtn: { paddingVertical: 14, alignItems: 'center', marginTop: 8 },
  cancelBtnText: { color: COLORS.textSecondary, fontWeight: '600', fontSize: 14 },
});
