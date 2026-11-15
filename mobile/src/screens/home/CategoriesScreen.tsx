import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { productsApi } from '../../api/products';
import { apiErrorMessage } from '../../api/client';
import { Category } from '../../types';

export default function CategoriesScreen({ navigation }: any) {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    productsApi.categories()
      .then(res => setCategories(res.data.data))
      .catch(e => setError(apiErrorMessage(e, 'Could not load categories.')))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { load(); }, [load]);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Categories</Text>
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />
      ) : error ? (
        <TouchableOpacity style={styles.errorBanner} onPress={load}>
          <Text style={styles.errorBannerText}>{error} — Tap to retry.</Text>
        </TouchableOpacity>
      ) : (
        <FlatList
          data={categories}
          keyExtractor={item => String(item.id)}
          numColumns={3}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          columnWrapperStyle={{ justifyContent: 'space-between' }}
          renderItem={({ item }) => (
            <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('ProductList', { categoryId: item.id, title: item.name })}>
              <View style={styles.iconBox}>
                {item.image ? <Image source={{ uri: item.image }} style={styles.image} /> : <IonIcon name="pricetag-outline" size={26} color={COLORS.primary} />}
              </View>
              <Text style={styles.name} numberOfLines={2}>{item.name}</Text>
            </TouchableOpacity>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: { paddingHorizontal: SIZES.screenPadding, paddingTop: 52, paddingBottom: 12, backgroundColor: COLORS.white },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: COLORS.text },
  card: { width: '31%', alignItems: 'center', backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, paddingVertical: 16, marginBottom: 12 },
  iconBox: { width: 56, height: 56, borderRadius: 28, backgroundColor: COLORS.grayLight, alignItems: 'center', justifyContent: 'center', marginBottom: 8, overflow: 'hidden' },
  image: { width: 56, height: 56 },
  name: { fontSize: 11, color: COLORS.text, textAlign: 'center', paddingHorizontal: 4 },
  errorBanner: { backgroundColor: COLORS.danger, padding: 14, margin: SIZES.screenPadding, borderRadius: SIZES.borderRadiusSm },
  errorBannerText: { color: COLORS.white, fontSize: 12, textAlign: 'center' },
});
