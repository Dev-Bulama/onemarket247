import React, { useCallback } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { useNotificationStore } from '../../store/notificationStore';
import { AppNotification } from '../../types';

export default function NotificationsScreen({ navigation }: any) {
  const { notifications, isLoading, fetchNotifications, markRead } = useNotificationStore();

  useFocusEffect(useCallback(() => { fetchNotifications(); }, [fetchNotifications]));

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Notifications</Text>
        <View style={{ width: 22 }} />
      </View>

      {!isLoading && notifications.length === 0 && (
        <View style={styles.empty}>
          <IonIcon name="notifications-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No notifications yet.</Text>
        </View>
      )}

      <FlatList
        data={notifications}
        keyExtractor={item => String(item.id)}
        contentContainerStyle={{ padding: SIZES.screenPadding }}
        renderItem={({ item }: { item: AppNotification }) => (
          <TouchableOpacity style={[styles.card, !item.read_at && styles.cardUnread]} onPress={() => !item.read_at && markRead(item.id)}>
            {!item.read_at && <View style={styles.dot} />}
            <View style={{ flex: 1 }}>
              <Text style={styles.subject}>{item.subject ?? 'Notification'}</Text>
              {item.body ? <Text style={styles.body} numberOfLines={3}>{item.body}</Text> : null}
              <Text style={styles.date}>{new Date(item.created_at).toLocaleString()}</Text>
            </View>
          </TouchableOpacity>
        )}
      />
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
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  card: { flexDirection: 'row', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10, gap: 8 },
  cardUnread: { backgroundColor: '#FFF3EB' },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: COLORS.primary, marginTop: 6 },
  subject: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 2 },
  body: { fontSize: 12, color: COLORS.textSecondary, marginBottom: 4, lineHeight: 17 },
  date: { fontSize: 10, color: COLORS.textMuted },
});
