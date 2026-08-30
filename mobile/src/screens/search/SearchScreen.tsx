import React, { useState } from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { searchApi } from '../../api/products';
import { Product } from '../../types';
import { useCartStore } from '../../store/cartStore';
import ProductCard from '../../components/ProductCard';

const RECENT_SEARCHES = ['headphones', 'smartphone', 'laptop', 'smart watch'];

export default function SearchScreen({ navigation }: any) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[] | null>(null);
  const [loading, setLoading] = useState(false);
  const { addItem } = useCartStore();

  const runSearch = async (q: string) => {
    if (!q.trim()) return;
    setLoading(true);
    try {
      const res = await searchApi.search(q.trim());
      setResults(res.data.data);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <View style={styles.searchBar}>
          <IonIcon name="search" size={16} color={COLORS.textMuted} style={{ marginRight: 8 }} />
          <TextInput
            style={styles.input}
            placeholder="Search for products, brands and categories"
            placeholderTextColor={COLORS.placeholder}
            value={query}
            onChangeText={setQuery}
            onSubmitEditing={() => runSearch(query)}
            returnKeyType="search"
            autoFocus
          />
          {query.length > 0 && (
            <TouchableOpacity onPress={() => { setQuery(''); setResults(null); }}>
              <IonIcon name="close-circle" size={18} color={COLORS.textMuted} />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {loading && <ActivityIndicator size="large" color={COLORS.primary} style={{ marginTop: 40 }} />}

      {!loading && results === null && (
        <View style={styles.recentBox}>
          <Text style={styles.recentTitle}>Popular Searches</Text>
          <View style={styles.chipsRow}>
            {RECENT_SEARCHES.map(term => (
              <TouchableOpacity key={term} style={styles.chip} onPress={() => { setQuery(term); runSearch(term); }}>
                <Text style={styles.chipText}>{term}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      )}

      {!loading && results !== null && results.length === 0 && (
        <View style={styles.empty}>
          <IonIcon name="search-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No results for "{query}"</Text>
        </View>
      )}

      {!loading && results && results.length > 0 && (
        <FlatList
          data={results}
          keyExtractor={item => String(item.id)}
          numColumns={2}
          columnWrapperStyle={{ justifyContent: 'space-between' }}
          contentContainerStyle={{ padding: SIZES.screenPadding }}
          renderItem={({ item }) => (
            <ProductCard product={item} onPress={() => navigation.navigate('ProductDetail', { slug: item.slug })} onAddToCart={id => addItem(id, 1)} />
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  header: { paddingHorizontal: SIZES.screenPadding, paddingTop: 52, paddingBottom: 12, backgroundColor: COLORS.white },
  searchBar: { flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.grayLight, borderRadius: SIZES.borderRadius, paddingHorizontal: 12, borderWidth: 1, borderColor: COLORS.border },
  input: { flex: 1, paddingVertical: 12, fontSize: 13, color: COLORS.text },
  recentBox: { padding: SIZES.screenPadding },
  recentTitle: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 10 },
  chipsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { backgroundColor: COLORS.white, borderWidth: 1, borderColor: COLORS.border, borderRadius: 20, paddingHorizontal: 14, paddingVertical: 8 },
  chipText: { fontSize: 12, color: COLORS.text },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12, textAlign: 'center' },
});
