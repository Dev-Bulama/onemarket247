import React, { useCallback, useState } from 'react';
import { useFocusEffect } from '@react-navigation/native';
import { ActivityIndicator, Modal, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import IonIcon from 'react-native-vector-icons/Ionicons';
import { COLORS, ORDER_STATUSES, SIZES } from '../../constants';
import { vendorOrdersApi } from '../../api/vendor';
import { apiErrorMessage } from '../../api/client';
import { VendorOrder } from '../../types/vendor';
import StatusBadge from '../../components/StatusBadge';
import { useToastStore } from '../../store/toastStore';

// Every App\Enums\VendorOrderStatus case is offered as a possible next
// status — the backend's real allowed-transition map
// (UpdateVendorOrderStatusAction::ALLOWED_TRANSITIONS) isn't exposed via
// API, so this intentionally doesn't try to duplicate it client-side. An
// invalid choice comes back as a 422 that's surfaced the same way any
// other validation error is (see apiErrorMessage()).
const ALL_STATUSES = [
  'pending_payment', 'confirmed', 'processing', 'ready_for_pickup', 'shipped',
  'out_for_delivery', 'delivered', 'completed', 'on_hold', 'cancelled', 'returned', 'refunded', 'disputed',
];

export default function VendorOrderDetailScreen({ route, navigation }: any) {
  const { orderId } = route.params as { orderId: number };
  const [order, setOrder] = useState<VendorOrder | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [statusModalVisible, setStatusModalVisible] = useState(false);
  const [cancelModalVisible, setCancelModalVisible] = useState(false);

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    vendorOrdersApi.show(orderId)
      .then(res => setOrder(res.data.data))
      .catch(e => setError(apiErrorMessage(e, 'Could not load this order.')))
      .finally(() => setLoading(false));
  }, [orderId]);

  useFocusEffect(useCallback(() => { load(); }, [load]));

  if (loading) {
    return <View style={styles.centerFlex}><ActivityIndicator size="large" color={COLORS.primary} /></View>;
  }

  if (error || !order) {
    return (
      <View style={styles.centerFlex}>
        <Text style={styles.errorText}>{error || 'Order not found.'}</Text>
      </View>
    );
  }

  const statusInfo = ORDER_STATUSES[order.status] ?? { label: order.status_label, color: COLORS.textSecondary };
  const isFinal = ['completed', 'cancelled', 'returned', 'refunded'].includes(order.status);

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()}><IonIcon name="arrow-back" size={22} color={COLORS.text} /></TouchableOpacity>
        <Text style={styles.headerTitle}>Order #{order.vendor_order_number}</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.statusCard}>
          <StatusBadge label={statusInfo.label} color={statusInfo.color} />
          <Text style={styles.total}>{order.total.formatted}</Text>
        </View>

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Items</Text>
          {order.items?.map(item => (
            <View key={item.id} style={styles.itemRow}>
              <Text style={styles.itemName} numberOfLines={2}>{item.product_name}</Text>
              <Text style={styles.itemQty}>x{item.quantity}</Text>
              <Text style={styles.itemPrice}>{item.line_total.formatted}</Text>
            </View>
          )) ?? <Text style={styles.emptyText}>No item details.</Text>}
        </View>

        {order.shipment && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Shipment</Text>
            <Text style={styles.shipmentLine}>{order.shipment.carrier ?? 'Carrier'} — {order.shipment.status_label}</Text>
            {order.shipment.tracking_number ? <Text style={styles.shipmentLine}>Tracking: {order.shipment.tracking_number}</Text> : null}
            {order.shipment.shipped_at ? <Text style={styles.shipmentMeta}>Shipped {new Date(order.shipment.shipped_at).toLocaleDateString()}</Text> : null}
            {order.shipment.estimated_delivery_at ? <Text style={styles.shipmentMeta}>Est. delivery {new Date(order.shipment.estimated_delivery_at).toLocaleDateString()}</Text> : null}
            {order.shipment.delivered_at ? <Text style={styles.shipmentMeta}>Delivered {new Date(order.shipment.delivered_at).toLocaleDateString()}</Text> : null}
            {order.shipment.events?.map((event, idx) => (
              <View key={idx} style={styles.eventRow}>
                <View style={styles.eventDot} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.eventStatus}>{event.status}{event.location ? ` · ${event.location}` : ''}</Text>
                  {event.description ? <Text style={styles.eventDescription}>{event.description}</Text> : null}
                  <Text style={styles.eventTime}>{new Date(event.occurred_at).toLocaleString()}</Text>
                </View>
              </View>
            ))}
          </View>
        )}

        <View style={styles.card}>
          <Text style={styles.cardTitle}>Summary</Text>
          <SummaryRow label="Subtotal" value={order.subtotal.formatted} />
          <SummaryRow label="Shipping" value={order.shipping_amount.formatted} />
          <SummaryRow label="Total" value={order.total.formatted} bold />
        </View>

        {!isFinal && (
          <View style={styles.actionsRow}>
            <TouchableOpacity style={styles.primaryBtn} onPress={() => setStatusModalVisible(true)}>
              <Text style={styles.primaryBtnText}>Update Status</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.dangerBtn} onPress={() => setCancelModalVisible(true)}>
              <Text style={styles.dangerBtnText}>Cancel Order</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>

      <Modal visible={statusModalVisible} transparent animationType="slide" onRequestClose={() => setStatusModalVisible(false)}>
        <StatusUpdateSheet
          order={order}
          onClose={() => setStatusModalVisible(false)}
          onUpdated={updated => { setOrder(updated); setStatusModalVisible(false); }}
        />
      </Modal>

      <Modal visible={cancelModalVisible} transparent animationType="slide" onRequestClose={() => setCancelModalVisible(false)}>
        <CancelSheet
          order={order}
          onClose={() => setCancelModalVisible(false)}
          onCancelled={updated => { setOrder(updated); setCancelModalVisible(false); }}
        />
      </Modal>
    </View>
  );
}

function StatusUpdateSheet({ order, onClose, onUpdated }: { order: VendorOrder; onClose: () => void; onUpdated: (order: VendorOrder) => void }) {
  const [selected, setSelected] = useState<string | null>(null);
  const [note, setNote] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async () => {
    if (!selected) { setError('Please choose a status.'); return; }
    setSaving(true);
    setError('');
    try {
      const res = await vendorOrdersApi.updateStatus(order.id, selected, note.trim() || undefined);
      onUpdated(res.data.data);
      useToastStore.getState().show('Order status updated');
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not update the order status.'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Update Order Status</Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}

        <ScrollView style={{ maxHeight: 220 }}>
          <View style={styles.chipRow}>
            {ALL_STATUSES.map(value => {
              const info = ORDER_STATUSES[value] ?? { label: value, color: COLORS.textSecondary };
              const active = selected === value;
              return (
                <TouchableOpacity
                  key={value}
                  style={[styles.chip, active && { backgroundColor: info.color, borderColor: info.color }]}
                  onPress={() => setSelected(value)}
                >
                  <Text style={[styles.chipText, active && styles.chipTextActive]}>{info.label}</Text>
                </TouchableOpacity>
              );
            })}
          </View>
        </ScrollView>

        <Text style={styles.label}>Note (optional)</Text>
        <TextInput style={styles.input} value={note} onChangeText={setNote} placeholder="Add a note for this update" placeholderTextColor={COLORS.placeholder} />

        <TouchableOpacity style={styles.saveBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Update Status</Text>}
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

function CancelSheet({ order, onClose, onCancelled }: { order: VendorOrder; onClose: () => void; onCancelled: (order: VendorOrder) => void }) {
  const [reason, setReason] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async () => {
    if (!reason.trim()) { setError('Please give a reason for cancelling.'); return; }
    setSaving(true);
    setError('');
    try {
      const res = await vendorOrdersApi.cancel(order.id, reason.trim());
      onCancelled(res.data.data);
      useToastStore.getState().show('Order cancelled');
    } catch (e) {
      setError(apiErrorMessage(e, 'Could not cancel this order.'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={onClose}>
      <View style={styles.sheet} onStartShouldSetResponder={() => true}>
        <Text style={styles.sheetTitle}>Cancel Order</Text>
        {error ? <Text style={styles.error}>{error}</Text> : null}
        <Text style={styles.label}>Reason</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          value={reason}
          onChangeText={setReason}
          placeholder="Why is this order being cancelled?"
          placeholderTextColor={COLORS.placeholder}
          multiline
          numberOfLines={4}
        />
        <TouchableOpacity style={styles.dangerFillBtn} onPress={handleSubmit} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.dangerFillBtnText}>Cancel Order</Text>}
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

function SummaryRow({ label, value, bold }: { label: string; value: string; bold?: boolean }) {
  return (
    <View style={styles.summaryRow}>
      <Text style={[styles.summaryLabel, bold && styles.summaryLabelBold]}>{label}</Text>
      <Text style={[styles.summaryValue, bold && styles.summaryValueBold]}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: COLORS.background },
  centerFlex: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: COLORS.white },
  errorText: { color: COLORS.textSecondary },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SIZES.screenPadding, paddingTop: 48, paddingBottom: 12, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.divider,
  },
  headerTitle: { fontSize: 15, fontWeight: 'bold', color: COLORS.text },
  content: { padding: SIZES.screenPadding, paddingBottom: 40 },

  statusCard: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 16, marginBottom: 12, alignItems: 'center', gap: 8 },
  total: { fontSize: 18, fontWeight: 'bold', color: COLORS.text },

  card: { backgroundColor: COLORS.white, borderRadius: SIZES.borderRadius, padding: 14, marginBottom: 12 },
  cardTitle: { fontSize: 14, fontWeight: 'bold', color: COLORS.text, marginBottom: 8 },
  itemRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 6, borderBottomWidth: 1, borderBottomColor: COLORS.divider },
  itemName: { flex: 1, fontSize: 12, color: COLORS.text },
  itemQty: { fontSize: 12, color: COLORS.textMuted, marginHorizontal: 8 },
  itemPrice: { fontSize: 12, fontWeight: '700', color: COLORS.text },
  emptyText: { fontSize: 12, color: COLORS.textMuted },

  shipmentLine: { fontSize: 12, color: COLORS.text, marginBottom: 4 },
  shipmentMeta: { fontSize: 11, color: COLORS.textMuted, marginBottom: 2 },
  eventRow: { flexDirection: 'row', gap: 8, marginTop: 10 },
  eventDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: COLORS.primary, marginTop: 4 },
  eventStatus: { fontSize: 12, fontWeight: '700', color: COLORS.text, textTransform: 'capitalize' },
  eventDescription: { fontSize: 11, color: COLORS.textSecondary, marginTop: 1 },
  eventTime: { fontSize: 10, color: COLORS.textMuted, marginTop: 2 },

  summaryRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 6 },
  summaryLabel: { fontSize: 12, color: COLORS.textSecondary },
  summaryLabelBold: { fontSize: 14, fontWeight: 'bold', color: COLORS.text },
  summaryValue: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  summaryValueBold: { fontSize: 15, fontWeight: 'bold', color: COLORS.primary },

  actionsRow: { flexDirection: 'row', gap: 10, marginTop: 8 },
  primaryBtn: { flex: 1, backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center' },
  primaryBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 14 },
  dangerBtn: { flex: 1, borderWidth: 1, borderColor: COLORS.danger, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center' },
  dangerBtnText: { color: COLORS.danger, fontWeight: 'bold', fontSize: 14 },

  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { backgroundColor: COLORS.white, borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: SIZES.screenPadding, paddingBottom: 32 },
  sheetTitle: { fontSize: 16, fontWeight: 'bold', color: COLORS.text, marginBottom: 12 },
  error: { color: COLORS.danger, marginBottom: 8, fontSize: 12 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 6, marginTop: 12 },
  input: { borderWidth: 1, borderColor: COLORS.border, borderRadius: SIZES.borderRadiusSm, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, backgroundColor: COLORS.grayLight },
  textArea: { height: 90, textAlignVertical: 'top' },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { borderWidth: 1, borderColor: COLORS.border, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 7, marginBottom: 4 },
  chipText: { fontSize: 12, color: COLORS.text, fontWeight: '600' },
  chipTextActive: { color: COLORS.white },
  saveBtn: { backgroundColor: COLORS.primary, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  saveBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
  dangerFillBtn: { backgroundColor: COLORS.danger, borderRadius: SIZES.borderRadius, paddingVertical: 14, alignItems: 'center', marginTop: 20 },
  dangerFillBtnText: { color: COLORS.white, fontWeight: 'bold', fontSize: 15 },
});
