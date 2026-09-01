import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Image, Modal, ScrollView, StyleSheet, Switch, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { Asset, launchImageLibrary } from 'react-native-image-picker';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, SIZES, STOCK_STATUSES } from '../../constants';
import { vendorProductsApi } from '../../api/vendor';
import { productsApi } from '../../api/products';
import { apiErrorMessage } from '../../api/client';
import { useToastStore } from '../../store/toastStore';
import { Brand, Category } from '../../types';

const MAX_IMAGES = 8;
const STOCK_STATUS_OPTIONS: { value: 'in_stock' | 'out_of_stock' | 'on_backorder' }[] = [
  { value: 'in_stock' }, { value: 'out_of_stock' }, { value: 'on_backorder' },
];
const TYPE_OPTIONS: { value: 'simple' | 'digital'; label: string }[] = [
  { value: 'simple', label: 'Simple' },
  { value: 'digital', label: 'Digital' },
];

// Assumes 2 decimal places (100 minor units per major unit) — true for the
// currencies this marketplace currently supports; the price input shows
// and accepts a plain decimal amount and converts to/from the integer
// minor-unit value the API stores.
const toMinorUnits = (text: string): number | undefined => {
  const n = parseFloat(text);
  return Number.isFinite(n) ? Math.round(n * 100) : undefined;
};
const fromMinorUnits = (amount?: number | null): string => (amount != null ? (amount / 100).toFixed(2) : '');

function flattenCategories(categories: Category[], depth = 0): { id: number; name: string; depth: number }[] {
  return categories.flatMap(cat => [
    { id: cat.id, name: cat.name, depth },
    ...flattenCategories(cat.children ?? [], depth + 1),
  ]);
}

export default function VendorProductFormScreen({ route, navigation }: any) {
  const { productId } = (route.params ?? {}) as { productId?: number };
  const isEdit = productId != null;

  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  // Create-only fields
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [sku, setSku] = useState('');
  const [type, setType] = useState<'simple' | 'digital'>('simple');
  const [brandId, setBrandId] = useState<number | null>(null);
  const [categoryIds, setCategoryIds] = useState<number[]>([]);
  const [weight, setWeight] = useState('');
  const [length, setLength] = useState('');
  const [width, setWidth] = useState('');
  const [height, setHeight] = useState('');
  const [seoTitle, setSeoTitle] = useState('');
  const [seoDescription, setSeoDescription] = useState('');
  const [images, setImages] = useState<Asset[]>([]);

  // Shared fields (editable on both create and edit)
  const [shortDescription, setShortDescription] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState('');
  const [compareAtPrice, setCompareAtPrice] = useState('');
  const [manageStock, setManageStock] = useState(true);
  const [stockQuantity, setStockQuantity] = useState('');
  const [stockStatus, setStockStatus] = useState<'in_stock' | 'out_of_stock' | 'on_backorder'>('in_stock');
  const [lowStockThreshold, setLowStockThreshold] = useState('');

  const [existingThumbnail, setExistingThumbnail] = useState<string | null>(null);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [pickerFor, setPickerFor] = useState<'brand' | 'categories' | null>(null);

  useEffect(() => {
    if (!isEdit) {
      productsApi.brands().then(res => setBrands(res.data.data)).catch(() => {});
      productsApi.categories().then(res => setCategories(res.data.data)).catch(() => {});
    }
  }, [isEdit]);

  useEffect(() => {
    if (!isEdit) return;
    vendorProductsApi.show(productId!)
      .then(res => {
        const product = res.data.data;
        setName(product.name);
        setExistingThumbnail(product.thumbnail ?? null);
        setShortDescription(product.short_description ?? '');
        setDescription(product.description ?? '');
        setPrice(fromMinorUnits(product.price?.amount));
        setCompareAtPrice(fromMinorUnits(product.compare_at_price?.amount));
        setManageStock(product.manage_stock);
        setStockQuantity(product.stock_quantity != null ? String(product.stock_quantity) : '');
        setStockStatus((product.stock_status as any) ?? 'in_stock');
        setLowStockThreshold(product.low_stock_threshold != null ? String(product.low_stock_threshold) : '');
      })
      .catch(e => setError(apiErrorMessage(e, 'Could not load this product.')))
      .finally(() => setLoading(false));
  }, [isEdit, productId]);

  const pickImages = () => {
    launchImageLibrary({ mediaType: 'photo', selectionLimit: MAX_IMAGES - images.length }, response => {
      if (response.assets) setImages(prev => [...prev, ...response.assets!].slice(0, MAX_IMAGES));
    });
  };
  const removeImage = (uri?: string) => setImages(prev => prev.filter(img => img.uri !== uri));

  const toggleCategory = (id: number) => {
    setCategoryIds(prev => (prev.includes(id) ? prev.filter(c => c !== id) : [...prev, id]));
  };

  const flatCategories = flattenCategories(categories);
  const selectedBrand = brands.find(b => b.id === brandId);

  const handleSubmit = async () => {
    if (!isEdit && !name.trim()) { setError('Please enter a product name.'); return; }
    if (!price.trim()) { setError('Please enter a price.'); return; }
    setSaving(true);
    setError('');
    try {
      if (isEdit) {
        await vendorProductsApi.update(productId!, {
          short_description: shortDescription.trim() || undefined,
          description: description.trim() || undefined,
          price: toMinorUnits(price) ?? 0,
          compare_at_price: toMinorUnits(compareAtPrice),
          manage_stock: manageStock,
          stock_quantity: stockQuantity.trim() ? parseInt(stockQuantity, 10) : undefined,
          stock_status: stockStatus,
          low_stock_threshold: lowStockThreshold.trim() ? parseInt(lowStockThreshold, 10) : undefined,
        });
      } else {
        await vendorProductsApi.create({
          name: name.trim(),
          slug: slug.trim() || undefined,
          sku: sku.trim() || undefined,
          type,
          brand_id: brandId ?? undefined,
          categories: categoryIds.length ? categoryIds : undefined,
          short_description: shortDescription.trim() || undefined,
          description: description.trim() || undefined,
          price: toMinorUnits(price),
          compare_at_price: toMinorUnits(compareAtPrice),
          manage_stock: manageStock,
          stock_quantity: stockQuantity.trim() ? parseInt(stockQuantity, 10) : undefined,
          stock_status: stockStatus,
          low_stock_threshold: lowStockThreshold.trim() ? parseInt(lowStockThreshold, 10) : undefined,
          weight: weight.trim() ? parseFloat(weight) : undefined,
          length: length.trim() ? parseFloat(length) : undefined,
          width: width.trim() ? parseFloat(width) : undefined,
          height: height.trim() ? parseFloat(height) : undefined,
          seo_title: seoTitle.trim() || undefined,
          seo_description: seoDescription.trim() || undefined,
          images: images.map((img, idx) => ({ uri: img.uri!, name: img.fileName ?? `product-${idx}.jpg`, type: img.type ?? 'image/jpeg' })),
        });
      }
      useToastStore.getState().show(isEdit ? 'Product updated' : 'Product created');
      navigation.goBack();
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not save this product.'));
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>{isEdit ? 'Edit Product' : 'New Product'}</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        {error ? <Text style={styles.error}>{error}</Text> : null}

        {isEdit ? (
          <View style={styles.readonlyHeader}>
            {existingThumbnail ? <Image source={{ uri: existingThumbnail }} style={styles.readonlyThumb} /> : null}
            <Text style={styles.readonlyName} numberOfLines={2}>{name}</Text>
            <Text style={styles.readonlyNote}>Name, images, and categories can't be changed after creation.</Text>
          </View>
        ) : (
          <>
            <Field label="Product Name" value={name} onChangeText={setName} placeholder="Wireless Headphones" />
            <Field label="Slug (optional)" value={slug} onChangeText={setSlug} placeholder="wireless-headphones" autoCapitalize="none" />
            <Field label="SKU (optional)" value={sku} onChangeText={setSku} placeholder="WH-001" autoCapitalize="none" />

            <Text style={styles.label}>Type</Text>
            <View style={styles.chipRow}>
              {TYPE_OPTIONS.map(opt => (
                <TouchableOpacity key={opt.value} style={[styles.chip, type === opt.value && styles.chipActive]} onPress={() => setType(opt.value)}>
                  <Text style={[styles.chipText, type === opt.value && styles.chipTextActive]}>{opt.label}</Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Brand (optional)</Text>
            <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('brand')}>
              <Text style={selectedBrand ? styles.selectValue : styles.selectPlaceholder}>{selectedBrand?.name ?? 'Select Brand'}</Text>
              <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
            </TouchableOpacity>

            <Text style={styles.label}>Categories (optional)</Text>
            <TouchableOpacity style={styles.selectInput} onPress={() => setPickerFor('categories')}>
              <Text style={categoryIds.length ? styles.selectValue : styles.selectPlaceholder}>
                {categoryIds.length ? `${categoryIds.length} selected` : 'Select Categories'}
              </Text>
              <IonIcon name="chevron-down" size={16} color={COLORS.textMuted} />
            </TouchableOpacity>
          </>
        )}

        <Text style={styles.label}>Short Description (optional)</Text>
        <TextInput style={styles.input} value={shortDescription} onChangeText={setShortDescription} placeholder="A brief summary" placeholderTextColor={COLORS.placeholder} />

        <Text style={styles.label}>Description (optional)</Text>
        <TextInput style={[styles.input, styles.textArea]} value={description} onChangeText={setDescription} placeholder="Full product description" placeholderTextColor={COLORS.placeholder} multiline numberOfLines={5} />

        <View style={styles.row}>
          <View style={{ flex: 1, marginRight: 8 }}>
            <Field label="Price" value={price} onChangeText={setPrice} placeholder="0.00" keyboardType="decimal-pad" />
          </View>
          <View style={{ flex: 1 }}>
            <Field label="Compare-at Price" value={compareAtPrice} onChangeText={setCompareAtPrice} placeholder="0.00" keyboardType="decimal-pad" />
          </View>
        </View>

        <View style={styles.switchRow}>
          <Text style={styles.label}>Manage Stock</Text>
          <Switch value={manageStock} onValueChange={setManageStock} trackColor={{ true: COLORS.primary }} />
        </View>

        {manageStock && (
          <Field label="Stock Quantity" value={stockQuantity} onChangeText={setStockQuantity} placeholder="0" keyboardType="number-pad" />
        )}

        <Text style={styles.label}>Stock Status</Text>
        <View style={styles.chipRow}>
          {STOCK_STATUS_OPTIONS.map(opt => {
            const info = STOCK_STATUSES[opt.value];
            return (
              <TouchableOpacity key={opt.value} style={[styles.chip, stockStatus === opt.value && styles.chipActive]} onPress={() => setStockStatus(opt.value)}>
                <Text style={[styles.chipText, stockStatus === opt.value && styles.chipTextActive]}>{info.label}</Text>
              </TouchableOpacity>
            );
          })}
        </View>

        <Field label="Low Stock Threshold (optional)" value={lowStockThreshold} onChangeText={setLowStockThreshold} placeholder="5" keyboardType="number-pad" />

        {!isEdit && (
          <>
            <View style={styles.row}>
              <View style={{ flex: 1, marginRight: 8 }}><Field label="Weight (optional)" value={weight} onChangeText={setWeight} placeholder="kg" keyboardType="decimal-pad" /></View>
              <View style={{ flex: 1 }}><Field label="Length (optional)" value={length} onChangeText={setLength} placeholder="cm" keyboardType="decimal-pad" /></View>
            </View>
            <View style={styles.row}>
              <View style={{ flex: 1, marginRight: 8 }}><Field label="Width (optional)" value={width} onChangeText={setWidth} placeholder="cm" keyboardType="decimal-pad" /></View>
              <View style={{ flex: 1 }}><Field label="Height (optional)" value={height} onChangeText={setHeight} placeholder="cm" keyboardType="decimal-pad" /></View>
            </View>

            <Field label="SEO Title (optional)" value={seoTitle} onChangeText={setSeoTitle} placeholder="SEO title" />
            <Field label="SEO Description (optional)" value={seoDescription} onChangeText={setSeoDescription} placeholder="SEO description" />

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
          </>
        )}

        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>{isEdit ? 'Save Changes' : 'Create Product'}</Text>}
        </TouchableOpacity>
        {!isEdit && <Text style={styles.moderationNote}>New products start as a draft and need approval before they're visible to shoppers.</Text>}
      </ScrollView>

      <Modal visible={!!pickerFor} transparent animationType="slide" onRequestClose={() => setPickerFor(null)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setPickerFor(null)}>
          <View style={styles.sheet}>
            {pickerFor === 'brand' ? (
              <ScrollView style={{ maxHeight: 360 }}>
                {brands.map(brand => (
                  <TouchableOpacity key={brand.id} style={styles.pickerRow} onPress={() => { setBrandId(brand.id); setPickerFor(null); }}>
                    <Text style={styles.selectValue}>{brand.name}</Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            ) : (
              <>
                <ScrollView style={{ maxHeight: 320 }}>
                  {flatCategories.map(cat => (
                    <TouchableOpacity key={cat.id} style={[styles.pickerRow, { paddingLeft: 16 + cat.depth * 16 }]} onPress={() => toggleCategory(cat.id)}>
                      <View style={styles.checkboxRow}>
                        <IonIcon name={categoryIds.includes(cat.id) ? 'checkbox' : 'square-outline'} size={18} color={categoryIds.includes(cat.id) ? COLORS.primary : COLORS.textMuted} />
                        <Text style={styles.selectValue}>{cat.name}</Text>
                      </View>
                    </TouchableOpacity>
                  ))}
                </ScrollView>
                <TouchableOpacity style={styles.doneBtn} onPress={() => setPickerFor(null)}>
                  <Text style={styles.doneBtnText}>Done</Text>
                </TouchableOpacity>
              </>
            )}
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

function Field(props: {
  label: string;
  value: string;
  onChangeText: (t: string) => void;
  placeholder?: string;
  keyboardType?: 'default' | 'decimal-pad' | 'number-pad';
  autoCapitalize?: 'none' | 'sentences' | 'words';
}) {
  return (
    <>
      <Text style={styles.label}>{props.label}</Text>
      <TextInput
        style={styles.input}
        value={props.value}
        onChangeText={props.onChangeText}
        placeholder={props.placeholder}
        placeholderTextColor={COLORS.placeholder}
        keyboardType={props.keyboardType}
        autoCapitalize={props.autoCapitalize}
      />
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.white },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },
  error: { color: COLORS.danger, marginBottom: 12, fontSize: 13 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: COLORS.text, backgroundColor: COLORS.grayLight },
  textArea: { height: 100, textAlignVertical: 'top' },
  row: { flexDirection: 'row' },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 4 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 14, paddingVertical: 7 },
  chipActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  chipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  chipTextActive: { color: COLORS.white },
  selectInput: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 12, backgroundColor: COLORS.grayLight },
  selectValue: { fontSize: 13, color: COLORS.text },
  selectPlaceholder: { fontSize: 13, color: COLORS.placeholder },
  imagesRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  imageThumb: { width: 72, height: 72, borderRadius: 8, position: 'relative' },
  imageThumbImg: { width: 72, height: 72, borderRadius: 8 },
  removeImageBtn: { position: 'absolute', top: -6, right: -6, backgroundColor: COLORS.white, borderRadius: 10 },
  addImageBtn: { width: 72, height: 72, borderRadius: 8, borderWidth: 1, borderColor: COLORS.border, borderStyle: 'dashed', alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.grayLight },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 28 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  moderationNote: { fontSize: 11, color: COLORS.textMuted, textAlign: 'center', marginTop: 10 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  pickerRow: { paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  checkboxRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  doneBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 12, alignItems: 'center', marginTop: 12 },
  doneBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 14 },
  readonlyHeader: { alignItems: 'center', marginBottom: 8 },
  readonlyThumb: { width: 80, height: 80, borderRadius: SIZES.borderRadiusSm, marginBottom: 8, backgroundColor: COLORS.grayLight },
  readonlyName: { fontSize: 15, fontWeight: 'bold', color: COLORS.text, textAlign: 'center' },
  readonlyNote: { fontSize: 11, color: COLORS.textMuted, marginTop: 4, textAlign: 'center' },
});
