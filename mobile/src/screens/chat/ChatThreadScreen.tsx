import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import {
  ActivityIndicator, FlatList, KeyboardAvoidingView, Platform, StyleSheet, Text, TextInput, TouchableOpacity, View,
} from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { chatApi } from '../../api/chat';
import { apiErrorMessage } from '../../api/client';
import { ChatMessage, Conversation } from '../../types';

export default function ChatThreadScreen({ route, navigation }: any) {
  const { conversationId, title } = route.params as { conversationId: number; title?: string };

  const [conversation, setConversation] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [loading, setLoading] = useState(true);
  const [body, setBody] = useState('');
  const [sending, setSending] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(() => {
    chatApi.show(conversationId).then(res => {
      setConversation(res.data.data.conversation);
      setMessages(res.data.data.messages);
    }).catch(e => setError(apiErrorMessage(e))).finally(() => setLoading(false));
  }, [conversationId]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const handleSend = async () => {
    const text = body.trim();
    if (!text || sending) return;
    setSending(true);
    try {
      const res = await chatApi.sendMessage(conversationId, text);
      setMessages(prev => [...prev, res.data.data]);
      setBody('');
    } catch (e) {
      setError(apiErrorMessage(e));
    } finally {
      setSending(false);
    }
  };

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{title ?? conversation?.vendor?.store_name ?? 'Chat'}</Text>
        <View style={{ width: 22 }} />
      </View>

      {error !== '' && <Text style={styles.errorText}>{error}</Text>}

      <FlatList
        data={messages}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={{ padding: SIZES.screenPadding }}
        renderItem={({ item }) => (
          <View style={[styles.bubbleRow, item.is_mine ? styles.bubbleRowMine : styles.bubbleRowTheirs]}>
            <View style={[styles.bubble, item.is_mine ? styles.bubbleMine : styles.bubbleTheirs]}>
              <Text style={[styles.bubbleText, item.is_mine && styles.bubbleTextMine]}>{item.body}</Text>
              <Text style={[styles.bubbleTime, item.is_mine && styles.bubbleTimeMine]}>{new Date(item.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
            </View>
          </View>
        )}
        ListEmptyComponent={<Text style={styles.emptyText}>No messages yet. Say hello!</Text>}
      />

      {conversation?.is_closed ? (
        <View style={styles.closedBanner}>
          <Text style={styles.closedText}>This conversation has been closed.</Text>
        </View>
      ) : (
        <View style={styles.composer}>
          <TextInput
            style={styles.input}
            placeholder="Type a message…"
            placeholderTextColor={COLORS.placeholder}
            value={body}
            onChangeText={setBody}
            multiline
          />
          <TouchableOpacity style={styles.sendBtn} onPress={handleSend} disabled={sending || !body.trim()}>
            {sending ? <ActivityIndicator size="small" color={COLORS.white} /> : <IonIcon name="send" size={18} color={COLORS.white} />}
          </TouchableOpacity>
        </View>
      )}
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { flex: 1, fontSize: 15, fontWeight: 'bold', color: COLORS.text, marginHorizontal: 10, textAlign: 'center' },
  errorText: { color: COLORS.danger, fontSize: 12, textAlign: 'center', paddingVertical: 6 },
  emptyText: { textAlign: 'center', color: COLORS.textSecondary, marginTop: 24 },
  bubbleRow: { marginBottom: 10, flexDirection: 'row' },
  bubbleRowMine: { justifyContent: 'flex-end' },
  bubbleRowTheirs: { justifyContent: 'flex-start' },
  bubble: { maxWidth: '78%', borderRadius: SIZES.borderRadius, paddingHorizontal: 12, paddingVertical: 8 },
  bubbleMine: { backgroundColor: COLORS.primary },
  bubbleTheirs: { backgroundColor: COLORS.white, borderWidth: 1, borderColor: COLORS.border },
  bubbleText: { fontSize: 13, color: COLORS.text },
  bubbleTextMine: { color: COLORS.white },
  bubbleTime: { fontSize: 9, color: COLORS.textMuted, marginTop: 4, textAlign: 'right' },
  bubbleTimeMine: { color: 'rgba(255,255,255,0.75)' },
  closedBanner: { padding: 14, backgroundColor: COLORS.white, borderTopWidth: 1, borderTopColor: COLORS.divider },
  closedText: { fontSize: 12, color: COLORS.textSecondary, textAlign: 'center' },
  composer: {
    flexDirection: 'row', alignItems: 'flex-end', gap: 8, padding: SIZES.screenPadding,
    backgroundColor: COLORS.white, borderTopWidth: 1, borderTopColor: COLORS.divider,
  },
  input: {
    flex: 1, maxHeight: 100, borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadius,
    paddingHorizontal: 12, paddingVertical: 8, fontSize: 13, color: COLORS.text,
  },
  sendBtn: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center',
  },
});
