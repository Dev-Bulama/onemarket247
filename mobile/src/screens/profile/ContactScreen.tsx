import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { pagesApi } from '../../api/content';
import { apiErrorMessage } from '../../api/client';
import { useAuthStore } from '../../store/authStore';

export default function ContactScreen({ navigation }: any) {
  const { user } = useAuthStore();
  const [name, setName] = useState(user?.name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [sent, setSent] = useState(false);

  const handleSubmit = async () => {
    if (!name || !email || !subject || !message) {
      setError('Please fill in every field.');
      return;
    }
    setSubmitting(true);
    setError('');
    try {
      await pagesApi.contact({ name, email, subject, message });
      setSent(true);
      setSubject('');
      setMessage('');
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not send your message. Please try again.'));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Contact Us</Text>
        <View style={styles.backSpacer} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        {sent ? (
          <View style={styles.sentBox}>
            <IonIcon name="checkmark-circle" size={48} color={COLORS.accent} />
            <Text style={styles.sentTitle}>Message sent!</Text>
            <Text style={styles.sentText}>Thanks for reaching out — we'll get back to you soon.</Text>
            <TouchableOpacity style={styles.sentBtn} onPress={() => setSent(false)}>
              <Text style={styles.sentBtnText}>Send another message</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            {error ? <Text style={styles.error}>{error}</Text> : null}

            <Text style={styles.label}>Your Name</Text>
            <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Jane Doe" placeholderTextColor={COLORS.placeholder} />

            <Text style={styles.label}>Email</Text>
            <TextInput style={styles.input} value={email} onChangeText={setEmail} placeholder="you@example.com" placeholderTextColor={COLORS.placeholder} autoCapitalize="none" keyboardType="email-address" />

            <Text style={styles.label}>Subject</Text>
            <TextInput style={styles.input} value={subject} onChangeText={setSubject} placeholder="How can we help?" placeholderTextColor={COLORS.placeholder} />

            <Text style={styles.label}>Message</Text>
            <TextInput
              style={[styles.input, styles.textArea]}
              value={message}
              onChangeText={setMessage}
              placeholder="Tell us more..."
              placeholderTextColor={COLORS.placeholder}
              multiline
              numberOfLines={6}
            />

            <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={submitting}>
              {submitting ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.submitBtnText}>Send Message</Text>}
            </TouchableOpacity>
          </>
        )}
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
  error: { color: COLORS.danger, marginBottom: 12, fontSize: 13 },
  label: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 6, marginTop: 14 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.grayLight },
  textArea: { height: 120, textAlignVertical: 'top' },
  submitBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 24 },
  submitBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  sentBox: { alignItems: 'center', paddingVertical: 60 },
  sentTitle: { fontSize: 18, fontWeight: 'bold', color: COLORS.text, marginTop: 16, marginBottom: 6 },
  sentText: { fontSize: 13, color: COLORS.textSecondary, textAlign: 'center', marginBottom: 20 },
  sentBtn: { paddingVertical: 10 },
  sentBtnText: { color: COLORS.primary, fontWeight: '600' },
});
