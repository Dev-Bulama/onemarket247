import React, { useState } from 'react';
import { ActivityIndicator, Image, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { launchImageLibrary, Asset } from 'react-native-image-picker';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES } from '../../constants';
import { productsApi } from '../../api/products';
import { apiErrorMessage } from '../../api/client';
import { useToastStore } from '../../store/toastStore';

const MAX_IMAGES = 5;

export default function WriteReviewScreen({ route, navigation }: any) {
  const { slug, productName } = route.params as { slug: string; productName: string };

  const [rating, setRating] = useState(0);
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [images, setImages] = useState<Asset[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  const pickImages = () => {
    launchImageLibrary({ mediaType: 'photo', selectionLimit: MAX_IMAGES - images.length }, response => {
      if (response.assets) setImages(prev => [...prev, ...response.assets!].slice(0, MAX_IMAGES));
    });
  };

  const removeImage = (uri?: string) => setImages(prev => prev.filter(img => img.uri !== uri));

  const handleSubmit = async () => {
    if (rating === 0) {
      setError('Please select a star rating.');
      return;
    }
    if (!body.trim()) {
      setError('Please write a review.');
      return;
    }
    setSubmitting(true);
    setError('');
    try {
      await productsApi.storeReview(slug, {
        rating,
        title: title.trim() || undefined,
        body: body.trim(),
        images: images.map((img, idx) => ({
          uri: img.uri!,
          name: img.fileName ?? `review-${idx}.jpg`,
          type: img.type ?? 'image/jpeg',
        })),
      });
      useToastStore.getState().show('Review submitted — thanks for your feedback!');
      navigation.goBack();
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not submit your review. Please try again.'));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="close" size={24} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Write a Review</Text>
        <View style={styles.backSpacer} />
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.productName} numberOfLines={2}>{productName}</Text>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Text style={styles.label}>Your Rating</Text>
        <View style={styles.starsRow}>
          {[1, 2, 3, 4, 5].map(n => (
            <TouchableOpacity key={n} onPress={() => setRating(n)} hitSlop={{ top: 8, bottom: 8, left: 4, right: 4 }}>
              <IonIcon name={n <= rating ? 'star' : 'star-outline'} size={32} color={COLORS.star} style={{ marginRight: 4 }} />
            </TouchableOpacity>
          ))}
        </View>

        <Text style={styles.label}>Title (optional)</Text>
        <TextInput style={styles.input} placeholder="Sum it up in a few words" placeholderTextColor={COLORS.placeholder} value={title} onChangeText={setTitle} maxLength={150} />

        <Text style={styles.label}>Your Review</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          placeholder="What did you like or dislike? How did you use this product?"
          placeholderTextColor={COLORS.placeholder}
          value={body}
          onChangeText={setBody}
          multiline
          numberOfLines={6}
          maxLength={5000}
        />

        <Text style={styles.label}>Photos (optional, up to {MAX_IMAGES})</Text>
        <View style={styles.imagesRow}>
          {images.map(img => (
            <View key={img.uri} style={styles.imageThumb}>
              <Image source={{ uri: img.uri }} style={styles.imageThumbImg} />
              <TouchableOpacity style={styles.removeImageBtn} onPress={() => removeImage(img.uri)}>
                <IonIcon name="close-circle" size={18} color={COLORS.danger} />
              </TouchableOpacity>
            </View>
          ))}
          {images.length < MAX_IMAGES && (
            <TouchableOpacity style={styles.addImageBtn} onPress={pickImages}>
              <IonIcon name="camera-outline" size={24} color={COLORS.textSecondary} />
            </TouchableOpacity>
          )}
        </View>

        <TouchableOpacity style={styles.submitBtn} onPress={handleSubmit} disabled={submitting}>
          {submitting ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.submitBtnText}>Submit Review</Text>}
        </TouchableOpacity>
        <Text style={styles.moderationNote}>Your review will be visible after it's moderated.</Text>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  backSpacer: { width: 24 },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  productName: { fontSize: 14, fontWeight: '600', color: COLORS.textSecondary, marginBottom: 16 },
  error: { color: COLORS.danger, marginBottom: 12, fontSize: 13 },
  label: { fontSize: 13, fontWeight: '700', color: COLORS.text, marginBottom: 8, marginTop: 16 },
  starsRow: { flexDirection: 'row' },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.grayLight },
  textArea: { height: 120, textAlignVertical: 'top' },
  imagesRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  imageThumb: { width: 72, height: 72, borderRadius: 8, position: 'relative' },
  imageThumbImg: { width: 72, height: 72, borderRadius: 8 },
  removeImageBtn: { position: 'absolute', top: -6, right: -6, backgroundColor: COLORS.white, borderRadius: 10 },
  addImageBtn: { width: 72, height: 72, borderRadius: 8, borderWidth: 1, borderColor: COLORS.border, borderStyle: 'dashed', alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.grayLight },
  submitBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 28 },
  submitBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  moderationNote: { fontSize: 11, color: COLORS.textMuted, textAlign: 'center', marginTop: 10 },
});
