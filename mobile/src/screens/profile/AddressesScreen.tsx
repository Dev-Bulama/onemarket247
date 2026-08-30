import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, FlatList, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { addressesApi } from '../../api/addresses';
import { Address } from '../../types';

export default function AddressesScreen({ navigation }: any) {
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(() => {
    setLoading(true);
    addressesApi.list().then(res => setAddresses(res.data.data)).finally(() => setLoading(false));
  }, []);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  const handleDelete = async (id: number) => {
    await addressesApi.destroy(id);
    load();
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>My Addresses</Text>
        <TouchableOpacity onPress={() => navigation.navigate('AddAddress')}><IonIcon name="add" size={24} color={COLORS.primary} /></TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : addresses.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="location-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No addresses yet</Text>
          <TouchableOpacity style={styles.addBtn} onPress={() => navigation.navigate('AddAddress')}>
            <Text style={styles.addBtnText}>Add Address</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={addresses}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          renderItem={({ item }) => (
            <View style={styles.card}>
              <View style={{ flex: 1 }}>
                <Text style={styles.name}>{item.full_name}</Text>
                {item.phone ? <Text style={styles.line}>{item.phone}</Text> : null}
                <Text style={styles.line}>{[item.address_line_1, item.city, item.state, item.country].filter(Boolean).join(', ')}</Text>
                {item.is_default_shipping && <View style={styles.pill}><Text style={styles.pillText}>Default</Text></View>}
              </View>
              <TouchableOpacity onPress={() => handleDelete(item.id)} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
                <IonIcon name="trash-outline" size={18} color={COLORS.danger} />
              </TouchableOpacity>
            </View>
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
  emptyText: { color: COLORS.textSecondary, marginTop: 12, marginBottom: 16 },
  addBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingHorizontal: 24, paddingVertical: 12 },
  addBtnText: { color: COLORS.white, fontWeight: 'bold' },
  card: { flexDirection: 'row', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  name: { fontSize: 14, fontWeight: '700', color: COLORS.text },
  line: { fontSize: 12, color: COLORS.textSecondary, marginTop: 2 },
  pill: { alignSelf: 'flex-start', backgroundColor: '#EAFBF0', borderRadius: 4, paddingHorizontal: 6, paddingVertical: 2, marginTop: 6 },
  pillText: { color: COLORS.accent, fontSize: 10, fontWeight: '700' },
});
