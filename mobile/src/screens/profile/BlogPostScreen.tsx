import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Dimensions, Image, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import RenderHTML from 'react-native-render-html';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { blogApi, BlogPostDetail } from '../../api/content';
import { apiErrorMessage } from '../../api/client';

const { width } = Dimensions.get('window');

export default function BlogPostScreen({ route, navigation }: any) {
  const { slug } = route.params as { slug: string };
  const [post, setPost] = useState<BlogPostDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    setLoading(true);
    blogApi.show(slug)
      .then(res => setPost(res.data.data))
      .catch(e => setError(apiErrorMessage(e, 'Could not load this post.')))
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  if (error || !post) {
    return <View style={styles.centerFlex}><Text style={styles.errorText}>{error || 'Post not found.'}</Text></View>;
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <View style={styles.backSpacer} />
      </View>
      <ScrollView contentContainerStyle={styles.content}>
        {post.cover_image ? <Image source={{ uri: post.cover_image }} style={styles.cover} /> : null}
        <Text style={styles.title}>{post.title}</Text>
        <Text style={styles.meta}>{post.author_name ?? 'OneMarket247'} · {new Date(post.published_at).toLocaleDateString()}</Text>
        <RenderHTML
          contentWidth={width - SIZES.screenPadding * 2}
          source={{ html: post.body }}
          baseStyle={styles.bodyBase}
        />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  errorText: { color: COLORS.textSecondary },
  header: { paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 8 },
  backSpacer: { height: 1 },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  cover: { width: '100%', height: 200, borderRadius: SIZES.borderRadius, marginBottom: 16 },
  title: { fontSize: 20, fontWeight: 'bold', color: COLORS.text, marginBottom: 6, lineHeight: 27 },
  meta: { fontSize: 12, color: COLORS.textMuted, marginBottom: 16 },
  bodyBase: { fontSize: 14, color: COLORS.textSecondary, lineHeight: 22 },
});
