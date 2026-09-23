import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { chatApi } from '../../api/chat';
import { Conversation } from '../../types';

export default function ChatListScreen({ navigation }: any) {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(() => {
    chatApi.list().then(res => setConversations(res.data.data)).finally(() => {
      setLoading(false);
      setRefreshing(false);
    });
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const onRefresh = () => {
    setRefreshing(true);
    load();
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Chats</Text>
        <View style={{ width: 22 }} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : conversations.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="chatbubbles-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No chats yet. Start one from a product or store page.</Text>
        </View>
      ) : (
        <FlatList
          data={conversations}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[COLORS.primary]} />}
          renderItem={({ item }) => (
            <TouchableOpacity
              style={[styles.card, item.unread_count > 0 && styles.cardUnread]}
              onPress={() => navigation.navigate('ChatThread', { conversationId: item.id, title: item.vendor?.store_name })}
            >
              <View style={{ flex: 1 }}>
                <Text style={styles.name}>{item.vendor?.store_name ?? 'Vendor'}</Text>
                {item.subject ? <Text style={styles.subject}>{item.subject}</Text> : null}
                {item.last_message ? <Text style={styles.preview} numberOfLines={1}>{item.last_message.body}</Text> : null}
              </View>
              {item.unread_count > 0 && (
                <View style={styles.badge}><Text style={styles.badgeText}>{item.unread_count > 9 ? '9+' : item.unread_count}</Text></View>
              )}
            </TouchableOpacity>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
  card: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10, gap: 8 },
  cardUnread: { backgroundColor: '#FFF3EB' },
  name: { fontSize: 13, fontWeight: '700', color: COLORS.text },
  subject: { fontSize: 11, color: COLORS.textMuted, marginTop: 2 },
  preview: { fontSize: 12, color: COLORS.textSecondary, marginTop: 4 },
  badge: { backgroundColor: COLORS.primary, borderRadius: 10, minWidth: 20, height: 20, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 5 },
  badgeText: { color: COLORS.white, fontSize: 11, fontWeight: 'bold' },
});
