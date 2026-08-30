import React, { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Image, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { blogApi, BlogPostSummary } from '../../api/content';

export default function BlogListScreen({ navigation }: any) {
  const [posts, setPosts] = useState<BlogPostSummary[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);

  const load = useCallback((targetPage: number) => {
    if (targetPage === 1) setLoading(true); else setLoadingMore(true);
    blogApi.list(targetPage).then(res => {
      setPosts(prev => (targetPage === 1 ? res.data.data : [...prev, ...res.data.data]));
      setPage(res.data.meta.pagination.current_page);
      setLastPage(res.data.meta.pagination.last_page);
    }).finally(() => {
      setLoading(false);
      setLoadingMore(false);
    });
  }, []);

  React.useEffect(() => { load(1); }, [load]);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Blog</Text>
        <View style={styles.backSpacer} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loader} />
      ) : posts.length === 0 ? (
        <View style={styles.empty}>
          <IonIcon name="newspaper-outline" size={48} color={COLORS.border} />
          <Text style={styles.emptyText}>No posts yet.</Text>
        </View>
      ) : (
        <FlatList
          data={posts}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={styles.list}
          onEndReached={() => !loadingMore && page < lastPage && load(page + 1)}
          onEndReachedThreshold={0.4}
          renderItem={({ item }) => (
            <TouchableOpacity style={styles.card} onPress={() => navigation.navigate('BlogPost', { slug: item.slug })}>
              {item.cover_image ? (
                <Image source={{ uri: item.cover_image }} style={styles.cover} />
              ) : (
                <View style={[styles.cover, styles.coverPlaceholder]}><IonIcon name="image-outline" size={28} color={COLORS.border} /></View>
              )}
              <View style={styles.cardInfo}>
                <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
                <Text style={styles.excerpt} numberOfLines={2}>{item.excerpt}</Text>
                <Text style={styles.meta}>{item.author_name ?? 'OneMarket247'} · {new Date(item.published_at).toLocaleDateString()}</Text>
              </View>
            </TouchableOpacity>
          )}
          ListFooterComponent={loadingMore ? <ActivityIndicator size="small" color={COLORS.primary} style={styles.footerLoader} /> : null}
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
  backSpacer: { width: 22 },
  loader: { marginTop: 40 },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SIZES.xxl },
  emptyText: { color: COLORS.textSecondary, marginTop: 12 },
  list: { padding: SIZES.screenPadding },
  card: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, marginBottom: 14, overflow: 'hidden' },
  cover: { width: '100%', height: 160 },
  coverPlaceholder: { alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.grayLight },
  cardInfo: { padding: 14 },
  title: { fontSize: 15, fontWeight: 'bold', color: COLORS.text, marginBottom: 6 },
  excerpt: { fontSize: 12, color: COLORS.textSecondary, lineHeight: 17, marginBottom: 8 },
  meta: { fontSize: 11, color: COLORS.textMuted },
  footerLoader: { marginVertical: 16 },
});
