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
  Image,
} from "react-native";
import * as ImagePicker from "expo-image-picker";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useRouter } from "expo-router";

import {downloadExport} from "@/services/downloads";
import { useAuth } from "@/contexts/auth-context";
import { ApiError } from "@/services/api";
import { addMedicalRecord, fetchGoats, fetchMedicalRecords } from "@/services/goats";
import type { Goat, MedicalRecord } from "@/types/api";

export default function MedicalRecordsScreen() {
  const router = useRouter();
  const { user } = useAuth();
  const canEdit = user?.role === "Admin" || user?.role === "Staff";

  const [records, setRecords] = useState<MedicalRecord[]>([]);
  const [goats, setGoats] = useState<Goat[]>([]);
  const [downloading,setDownloading]=useState(false);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [addOpen, setAddOpen] = useState(false);
  const [selectedGoatId, setSelectedGoatId] = useState<number | null>(null);
  const [recordType, setRecordType] = useState("Vaccination");
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [dateGiven, setDateGiven] = useState("");
  const [nextDueDate, setNextDueDate] = useState("");
  const [administeredBy, setAdministeredBy] = useState("");
  const [referencePhoto, setReferencePhoto] = useState<ImagePicker.ImagePickerAsset | null>(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [recordData, goatData] = await Promise.all([fetchMedicalRecords(), fetchGoats()]);
      setRecords(recordData.medical_records);
      setGoats(goatData.goats);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load medical records.");
    }
  }, []);

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
    setSelectedGoatId(null);
    setRecordType("Vaccination");
    setTitle("");
    setDescription("");
    setDateGiven("");
    setNextDueDate("");
    setAdministeredBy("");
    setReferencePhoto(null);
  };

  const chooseReferencePhoto = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      RNAlert.alert("Photo permission needed", "Allow photo-library access to attach a medical reference.");
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ["images"], quality: 0.8 });
    if (!result.canceled) setReferencePhoto(result.assets[0]);
  };

  const submit = async () => {
    if (!selectedGoatId) {
      RNAlert.alert("Missing details", "Please select a goat.");
      return;
    }
    if (!title.trim()) {
      RNAlert.alert("Missing details", "Please enter a title.");
      return;
    }
    setSaving(true);
    try {
      await addMedicalRecord(selectedGoatId, {
        record_type: recordType,
        title: title.trim(),
        description: description || undefined,
        date_given: dateGiven || undefined,
        next_due_date: nextDueDate || undefined,
        administered_by: administeredBy || undefined,
        reference_photo: referencePhoto,
      });
      resetForm();
      setAddOpen(false);
      await load();
    } catch (e) {
      RNAlert.alert(
        "Could not save record",
        e instanceof ApiError ? e.message : "Please try again."
      );
    } finally {
      setSaving(false);
    }
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
        <Text style={styles.title}>Medical Records</Text>
        <Text style={styles.subtitle}>Vaccination, treatment, and medicine history across the herd.</Text>

        <TouchableOpacity style={styles.fullAddButton} disabled={downloading} onPress={async()=>{setDownloading(true);try{await downloadExport("/medical-records/export","agrisentry-medical-records.pdf");}catch(e){setError(e instanceof Error?e.message:"Download failed.");}finally{setDownloading(false);}}}><Text style={styles.addButtonText}>{downloading?"Preparing PDF…":"Download all records (PDF)"}</Text></TouchableOpacity>
        {error && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorBannerText}>{error}</Text>
          </View>
        )}

        {canEdit && (
          <TouchableOpacity style={styles.fullAddButton} onPress={() => setAddOpen(true)}>
            <Text style={styles.addButtonText}>+ Add Record</Text>
          </TouchableOpacity>
        )}

        {loading ? (
          <ActivityIndicator color="#16A34A" style={{ marginTop: 20 }} />
        ) : records.length === 0 ? (
          <Text style={styles.emptyText}>No medical records found yet.</Text>
        ) : (
          records.map((record) => (
            <View key={record.id} style={styles.card}>
              <Text style={styles.cardMeta}>
                {record.date_given || "No date"} ·{" "}
                {record.goat ? `${record.goat.name} (${record.goat.code})` : `Goat #${record.goat_id}`}
              </Text>
              <Text style={styles.cardTitle}>
                {record.title} <Text style={styles.recordTypeTag}>{record.record_type}</Text>
              </Text>
              {record.description ? <Text style={styles.cardDesc}>{record.description}</Text> : null}
              <Text style={styles.cardDesc}>
                Administered by: {record.administered_by || "N/A"}
                {record.next_due_date ? ` · Next due: ${record.next_due_date}` : ""}
              </Text>
              {record.reference_photo_url ? <Image source={{ uri: record.reference_photo_url }} style={styles.referenceImage} /> : null}
            </View>
          ))
        )}
      </ScrollView>

      <Modal visible={addOpen} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <ScrollView contentContainerStyle={styles.formModal} keyboardShouldPersistTaps="handled">
            <Text style={styles.modalTitleText}>Add Medical Record</Text>

            <Text style={styles.label}>Goat</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipRow}>
              {goats.map((goat) => (
                <TouchableOpacity
                  key={goat.id}
                  style={[styles.chip, selectedGoatId === goat.id && styles.chipActive]}
                  onPress={() => setSelectedGoatId(goat.id)}
                >
                  <Text style={[styles.chipText, selectedGoatId === goat.id && styles.chipTextActive]}>
                    {goat.name} ({goat.code})
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <TextInput
              style={styles.input}
              placeholder="Record type (e.g. Vaccination)"
              placeholderTextColor="#94A3B8"
              value={recordType}
              onChangeText={setRecordType}
            />
            <TextInput style={styles.input} placeholder="Title" placeholderTextColor="#94A3B8" value={title} onChangeText={setTitle} />
            <TextInput
              style={styles.input}
              placeholder="Description"
              placeholderTextColor="#94A3B8"
              value={description}
              onChangeText={setDescription}
            />
            <TextInput
              style={styles.input}
              placeholder="Date given (YYYY-MM-DD)"
              placeholderTextColor="#94A3B8"
              value={dateGiven}
              onChangeText={setDateGiven}
            />
            <TextInput
              style={styles.input}
              placeholder="Next due date (YYYY-MM-DD)"
              placeholderTextColor="#94A3B8"
              value={nextDueDate}
              onChangeText={setNextDueDate}
            />
            <TextInput
              style={styles.input}
              placeholder="Administered by"
              placeholderTextColor="#94A3B8"
              value={administeredBy}
              onChangeText={setAdministeredBy}
            />
            <TouchableOpacity style={styles.photoButton} onPress={chooseReferencePhoto}>
              <Text style={styles.photoButtonText}>{referencePhoto ? "Change reference photo" : "+ Attach diagnosis / medication photo"}</Text>
            </TouchableOpacity>
            {referencePhoto ? <Image source={{ uri: referencePhoto.uri }} style={styles.photoPreview} /> : null}

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
              <TouchableOpacity style={styles.saveButton} onPress={submit} disabled={saving}>
                {saving ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.saveButtonText}>Save Record</Text>
                )}
              </TouchableOpacity>
            </View>
          </ScrollView>
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
  cardMeta: { color: "#64748B", fontSize: 12, marginBottom: 6 },
  cardTitle: { fontSize: 16, fontWeight: "800", color: "#111827", marginBottom: 6 },
  recordTypeTag: {
    fontSize: 11,
    fontWeight: "700",
    color: "#2563EB",
    backgroundColor: "#EAF1FF",
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
    overflow: "hidden",
  },
  cardDesc: { color: "#475569", fontSize: 13, lineHeight: 19, marginBottom: 2 },
  referenceImage: { width: "100%", height: 180, borderRadius: 12, marginTop: 12, resizeMode: "cover" },
  modalOverlay: { flex: 1, backgroundColor: "rgba(15, 23, 42, 0.45)", justifyContent: "flex-end" },
  formModal: { backgroundColor: "#FFFFFF", borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20 },
  modalTitleText: { fontSize: 22, fontWeight: "800", color: "#111827", marginBottom: 16 },
  label: { fontSize: 13, fontWeight: "700", color: "#334155", marginBottom: 8 },
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
  photoButton: { borderWidth: 1, borderStyle: "dashed", borderColor: "#16A34A", borderRadius: 14, padding: 13, alignItems: "center", marginBottom: 10 },
  photoButtonText: { color: "#15803D", fontWeight: "700", fontSize: 13 },
  photoPreview: { width: "100%", height: 180, borderRadius: 14, marginBottom: 12, resizeMode: "cover" },
  modalActions: { flexDirection: "row", gap: 10, marginTop: 4 },
  cancelButton: { flex: 1, paddingVertical: 13, borderRadius: 14, borderWidth: 1, borderColor: "#CBD5E1", alignItems: "center" },
  cancelText: { color: "#111827", fontWeight: "800" },
  saveButton: { flex: 1, backgroundColor: "#16A34A", paddingVertical: 13, borderRadius: 14, alignItems: "center" },
  saveButtonText: { color: "#FFFFFF", fontWeight: "800" },
});
