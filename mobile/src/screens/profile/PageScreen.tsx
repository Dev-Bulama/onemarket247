import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { pagesApi, StaticPage } from '../../api/content';

const LOADERS: Record<string, () => Promise<{ data: { data: StaticPage } }>> = {
  'about-us': pagesApi.aboutUs,
  partnership: pagesApi.partnership,
  privacy: pagesApi.privacy,
  terms: pagesApi.terms,
};

export default function PageScreen({ route, navigation }: any) {
  const { page } = route.params as { page: keyof typeof LOADERS };
  const [content, setContent] = useState<StaticPage | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    LOADERS[page]().then(res => setContent(res.data.data)).finally(() => setLoading(false));
  }, [page]);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{content?.title ?? ''}</Text>
        <View style={styles.backSpacer} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.content}>
          {content?.sections.map((section, idx) => (
            <View key={idx} style={styles.section}>
              {section.heading ? <Text style={styles.sectionHeading}>{section.heading}</Text> : null}
              <Text style={styles.sectionBody}>{section.body}</Text>
            </View>
          ))}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { flex: 1, fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginHorizontal: 10, textAlign: 'center' },
  backSpacer: { width: 22 },
  loader: { marginTop: 40 },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  section: { marginBottom: 18 },
  sectionHeading: { fontSize: 14, fontWeight: '700', color: COLORS.text, marginBottom: 6 },
  sectionBody: { fontSize: 13, color: COLORS.textSecondary, lineHeight: 20 },
});
