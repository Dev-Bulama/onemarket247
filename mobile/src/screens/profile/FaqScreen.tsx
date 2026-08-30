import React, { useEffect, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { FaqPage, pagesApi } from '../../api/content';

export default function FaqScreen({ navigation }: any) {
  const [faq, setFaq] = useState<FaqPage | null>(null);
  const [loading, setLoading] = useState(true);
  const [openIndex, setOpenIndex] = useState<number | null>(0);

  useEffect(() => {
    pagesApi.faq().then(res => setFaq(res.data.data)).finally(() => setLoading(false));
  }, []);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>{faq?.title ?? 'FAQ'}</Text>
        <View style={styles.backSpacer} />
      </View>

      {loading ? (
        <ActivityIndicator size="large" color={COLORS.primary} style={styles.loader} />
      ) : (
        <ScrollView contentContainerStyle={styles.content}>
          {faq?.questions.map((qa, idx) => {
            const open = openIndex === idx;
            return (
              <TouchableOpacity key={idx} style={styles.item} onPress={() => setOpenIndex(open ? null : idx)} activeOpacity={0.8}>
                <View style={styles.questionRow}>
                  <Text style={styles.question}>{qa.question}</Text>
                  <IonIcon name={open ? 'chevron-up' : 'chevron-down'} size={16} color={COLORS.textMuted} />
                </View>
                {open && <Text style={styles.answer}>{qa.answer}</Text>}
              </TouchableOpacity>
            );
          })}
        </ScrollView>
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
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  item: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 10 },
  questionRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  question: { flex: 1, fontSize: 13, fontWeight: '700', color: COLORS.text },
  answer: { fontSize: 12, color: COLORS.textSecondary, marginTop: 10, lineHeight: 18 },
});
