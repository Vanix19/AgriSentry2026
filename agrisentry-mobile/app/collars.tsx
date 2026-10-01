import BatteryStatus from "@/components/BatteryStatus";
import { useLiveRefresh } from "@/hooks/use-live-refresh";
import React, { useCallback, useEffect, useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Modal,
  TextInput,
  Alert as RNAlert,
  ActivityIndicator,
  RefreshControl,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useRouter } from "expo-router";

import { useAuth } from "@/contexts/auth-context";
import { ApiError } from "@/services/api";
import { createCollar, deleteCollar, fetchCollars, fetchGoats } from "@/services/goats";
import type { Collar, Goat } from "@/types/api";

export default function CollarsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canEdit = user?.role === "Admin" || user?.role === "Staff";

  const [collars, setCollars] = useState<Collar[]>([]);
  const [goats, setGoats] = useState<Goat[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [addOpen, setAddOpen] = useState(false);
  const [collarCode, setCollarCode] = useState("");
  const [devEui, setDevEui] = useState("");
  const [batteryLevel, setBatteryLevel] = useState("");
  const [assignedGoatId, setAssignedGoatId] = useState<number | null>(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [collarData, goatData] = await Promise.all([fetchCollars(), fetchGoats()]);
      setCollars(collarData.collars);
      setGoats(goatData.goats);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load collars.");
    }
  }, []);

  useLiveRefresh(load);

  useEffect(() => {
    (async () => {
      setLoading(true);
      await load();
      setLoading(false);
    })();
  }, [load]);

  const onRefresh = async () => {
    setRefreshing(true);
    await load();
    setRefreshing(false);
  };

  const resetForm = () => {
    setCollarCode("");
    setDevEui("");
    setBatteryLevel("");
    setAssignedGoatId(null);
  };

  const submitAddCollar = async () => {
    if (!collarCode.trim()) {
      RNAlert.alert("Missing details", "Collar code is required.");
      return;
    }
    setSaving(true);
    try {
      await createCollar({
        collar_code: collarCode.trim(),
        dev_eui: devEui.trim() || undefined,
        battery_level: batteryLevel ? Number(batteryLevel) : undefined,
        goat_id: assignedGoatId ?? undefined,
      });
      resetForm();
      setAddOpen(false);
      await load();
    } catch (e) {
      RNAlert.alert(
        "Could not add collar",
        e instanceof ApiError ? e.message : "Please try again."
      );
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = (collar: Collar) => {
    RNAlert.alert(
      "Delete collar",
      `Delete collar ${collar.collar_code}? This unassigns it from its goat. This cannot be undone.`,
      [
        { text: "Cancel", style: "cancel" },
        {
          text: "Delete",
          style: "destructive",
          onPress: async () => {
            try {
              await deleteCollar(collar.id);
              await load();
            } catch (e) {
              RNAlert.alert(
                "Could not delete",
                e instanceof ApiError ? e.message : "Please try again."
              );
            }
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <Stack.Screen options={{ headerShown: false }} />

      <View style={styles.topRow}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.page}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        <Text style={styles.title}>Collars</Text>
        <Text style={styles.subtitle}>
          Collar-to-goat assignment, battery, and connectivity status.
        </Text>

        {error && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorBannerText}>{error}</Text>
          </View>
        )}

        {canEdit && (
          <TouchableOpacity style={styles.fullAddButton} onPress={() => setAddOpen(true)}>
            <Text style={styles.addButtonText}>+ Add Collar</Text>
          </TouchableOpacity>
        )}

        {loading ? (
          <ActivityIndicator color="#16A34A" style={{ marginTop: 20 }} />
        ) : collars.length === 0 ? (
          <Text style={styles.emptyText}>No collars registered yet.</Text>
        ) : (
          collars.map((collar) => (
            <View key={collar.id} style={styles.card}>
              <View style={styles.cardTop}>
                <Text style={styles.collarCode}>{collar.collar_code}</Text>
                {canEdit && (
                  <TouchableOpacity onPress={() => handleDelete(collar)}>
                    <Text style={styles.deleteText}>🗑 Delete</Text>
                  </TouchableOpacity>
                )}
              </View>
              <Text style={styles.cardRow}>Dev EUI: {collar.dev_eui || "—"}</Text>
              <BatteryStatus level={collar.battery_level} lastSeen={collar.last_seen} />
              <Text style={styles.cardRow}>
                Status:{" "}
                <Text style={collar.device_status === "Active" ? styles.statusActive : styles.statusInactive}>
                  {collar.device_status || "Unknown"}
                </Text>
              </Text>
              <Text style={styles.cardRow}>
                Last seen: {collar.last_seen ? new Date(collar.last_seen).toLocaleString() : "Never"}
              </Text>
            </View>
          ))
        )}
      </ScrollView>

      <Modal visible={addOpen} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.formModal}>
            <Text style={styles.modalTitleText}>Add Collar</Text>

            <TextInput
              style={styles.input}
              placeholder="Collar code (required), e.g. COL-001"
              placeholderTextColor="#94A3B8"
              value={collarCode}
              onChangeText={setCollarCode}
            />
            <TextInput
              style={styles.input}
              placeholder="Dev EUI, e.g. AABBCCDDEEFF0011"
              placeholderTextColor="#94A3B8"
              value={devEui}
              onChangeText={setDevEui}
              autoCapitalize="characters"
            />
            <TextInput
              style={styles.input}
              placeholder="Battery %, e.g. 100"
              placeholderTextColor="#94A3B8"
              value={batteryLevel}
              onChangeText={setBatteryLevel}
              keyboardType="numeric"
            />

            <Text style={styles.label}>Assign to goat</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipRow}>
              <TouchableOpacity
                style={[styles.chip, assignedGoatId === null && styles.chipActive]}
                onPress={() => setAssignedGoatId(null)}
              >
                <Text style={[styles.chipText, assignedGoatId === null && styles.chipTextActive]}>
                  Unassigned
                </Text>
              </TouchableOpacity>
              {goats.map((goat) => (
                <TouchableOpacity
                  key={goat.id}
                  style={[styles.chip, assignedGoatId === goat.id && styles.chipActive]}
                  onPress={() => setAssignedGoatId(goat.id)}
                >
                  <Text style={[styles.chipText, assignedGoatId === goat.id && styles.chipTextActive]}>
                    {goat.name} ({goat.code})
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.cancelButton}
                onPress={() => {
                  resetForm();
                  setAddOpen(false);
                }}
                disabled={saving}
              >
                <Text style={styles.cancelText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.saveButton} onPress={submitAddCollar} disabled={saving}>
                {saving ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.saveButtonText}>Add Collar</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: "#F8FAFC" },
  topRow: { paddingHorizontal: 18, paddingTop: 12 },
  backText: { color: "#16A34A", fontWeight: "700", fontSize: 15 },
  page: { padding: 18, paddingBottom: 60 },
  title: { fontSize: 28, fontWeight: "800", color: "#111827" },
  subtitle: { color: "#64748B", fontSize: 14, marginTop: 5, marginBottom: 18, lineHeight: 20 },
  errorBanner: { backgroundColor: "#FEE2E2", borderRadius: 14, padding: 14, marginBottom: 16 },
  errorBannerText: { color: "#B91C1C", fontSize: 13, fontWeight: "600" },
  fullAddButton: {
    backgroundColor: "#16A34A",
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: "center",
    marginBottom: 18,
  },
  addButtonText: { color: "#FFFFFF", fontWeight: "800", fontSize: 13 },
  emptyText: { color: "#64748B", fontSize: 14, marginTop: 12 },
  card: {
    backgroundColor: "#FFFFFF",
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    marginBottom: 14,
  },
  cardTop: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 8 },
  collarCode: { fontSize: 18, fontWeight: "800", color: "#111827" },
  deleteText: { color: "#DC2626", fontWeight: "700", fontSize: 13 },
  cardRow: { color: "#475569", fontSize: 13, marginBottom: 3 },
  statusActive: { color: "#16A34A", fontWeight: "700" },
  statusInactive: { color: "#DC2626", fontWeight: "700" },
  modalOverlay: { flex: 1, backgroundColor: "rgba(15, 23, 42, 0.45)", justifyContent: "flex-end" },
  formModal: { backgroundColor: "#FFFFFF", borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20 },
  modalTitleText: { fontSize: 22, fontWeight: "800", color: "#111827", marginBottom: 16 },
  label: { fontSize: 13, fontWeight: "700", color: "#334155", marginBottom: 8 },
  input: {
    backgroundColor: "#F8FAFC",
    borderWidth: 1,
    borderColor: "#CBD5E1",
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 13,
    marginBottom: 12,
    color: "#111827",
    fontSize: 15,
  },
  chipRow: { marginBottom: 16 },
  chip: {
    borderWidth: 1,
    borderColor: "#CBD5E1",
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 9,
    marginRight: 8,
  },
  chipActive: { backgroundColor: "#DCFCE7", borderColor: "#16A34A" },
  chipText: { color: "#475569", fontWeight: "600", fontSize: 13 },
  chipTextActive: { color: "#15803D" },
  modalActions: { flexDirection: "row", gap: 10 },
  cancelButton: { flex: 1, paddingVertical: 13, borderRadius: 14, borderWidth: 1, borderColor: "#CBD5E1", alignItems: "center" },
  cancelText: { color: "#111827", fontWeight: "800" },
  saveButton: { flex: 1, backgroundColor: "#16A34A", paddingVertical: 13, borderRadius: 14, alignItems: "center" },
  saveButtonText: { color: "#FFFFFF", fontWeight: "800" },
});
