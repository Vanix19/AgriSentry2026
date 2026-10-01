import {downloadExport} from "@/services/downloads";
import BreedSelect from "@/components/BreedSelect";
import BatteryStatus from "@/components/BatteryStatus";
import { useLiveRefresh } from "@/hooks/use-live-refresh";
import DateFilter from "@/components/DateFilter";
import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
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
  Linking,
  Platform,
  Animated,
  Easing,
  Image,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useLocalSearchParams, useRouter } from "expo-router";
import QRCode from "react-native-qrcode-svg";
import Svg, { Circle, Line, Polyline, Rect } from "react-native-svg";
import * as ImagePicker from "expo-image-picker";

import { useAuth } from "@/contexts/auth-context";
import { useAppTheme } from "@/contexts/theme-context";
import { ApiError } from "@/services/api";
import { addMedicalRecord, deleteGoat, fetchGoat, updateGoat } from "@/services/goats";
import { WEB_ORIGIN } from "@/constants/config";
import { tempBandColor } from "@/constants/temperature";
import type { Goat, HealthLog } from "@/types/api";

type TabKey = "vitals" | "medical";
type VitalsPeriod = "daily" | "weekly" | "monthly";

type ChartBucket = { label: string; temperatures: number[]; anomalies: number };

function isMotionAnomaly(log: HealthLog) { return Boolean(log.motion_anomaly); }

function buildVitalsBuckets(logs: HealthLog[], period: VitalsPeriod, selectedDate = ""): ChartBucket[] {
  const now = selectedDate ? new Date(selectedDate + "T00:00:00") : new Date();
  if (selectedDate) { now.setDate(now.getDate()+1); period = "daily"; }
  const buckets: (ChartBucket & { start: number; end: number })[] = [];

  for (let index = 11; index >= 0; index -= 1) {
    let start: Date;
    let end: Date;
    let label: string;

    if (period === "daily") {
      end = new Date(now.getTime() - index * 2 * 60 * 60 * 1000);
      start = new Date(end.getTime() - 2 * 60 * 60 * 1000);
      label = end.toLocaleTimeString([], { hour: "numeric" });
    } else if (period === "weekly") {
      end = new Date(now.getTime() - index * 7 * 24 * 60 * 60 * 1000);
      start = new Date(end.getTime() - 7 * 24 * 60 * 60 * 1000);
      label = end.toLocaleDateString([], { month: "short", day: "numeric" });
    } else {
      start = new Date(now.getFullYear(), now.getMonth() - index, 1);
      end = new Date(now.getFullYear(), now.getMonth() - index + 1, 1);
      label = start.toLocaleDateString([], { month: "short" });
    }

    buckets.push({ label, temperatures: [], anomalies: 0, start: start.getTime(), end: end.getTime() });
  }

  for (const log of logs) {
    const timestamp = new Date(log.created_at).getTime();
    const bucket = buckets.find((item) => timestamp >= item.start && timestamp < item.end);
    if (!bucket) continue;
    if (log.temperature !== null && Number.isFinite(Number(log.temperature))) bucket.temperatures.push(Number(log.temperature));
    if (isMotionAnomaly(log)) bucket.anomalies += 1;
  }

  return buckets.map(({ label, temperatures, anomalies }) => ({ label, temperatures, anomalies }));
}

export default function GoatDetailsScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const router = useRouter();
  const { user } = useAuth();
  const { isDark } = useAppTheme();
  styles = isDark ? darkStyles : lightStyles;
  const canEdit = user?.role === "Admin" || !!user?.permissions?.["goats.write"];
  const canAddMedical = user?.role === "Admin" || !!user?.permissions?.["medical-records.write"];

  const [goat, setGoat] = useState<Goat | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<TabKey>("vitals");
  const [vitalsDate, setVitalsDate] = useState("");
  const [vitalsPeriod, setVitalsPeriod] = useState<VitalsPeriod>("monthly");
  const [editOpen, setEditOpen] = useState(false);
  const [draft, setDraft] = useState<Partial<Goat>>({});
  const [savingProfile, setSavingProfile] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const [addRecordOpen, setAddRecordOpen] = useState(false);
  const [recordType, setRecordType] = useState("Vaccination");
  const [recordTitle, setRecordTitle] = useState("");
  const [recordDescription, setRecordDescription] = useState("");
  const [recordDate, setRecordDate] = useState("");
  const [recordAdministeredBy, setRecordAdministeredBy] = useState("");
  const [recordPhoto, setRecordPhoto] = useState<ImagePicker.ImagePickerAsset | null>(null);
  const [savingRecord, setSavingRecord] = useState(false);

  const load = useCallback(async () => {
    if (!id) return;
    setError(null);
    try {
      const data = await fetchGoat(id);
      setGoat(data.goat);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load this goat.");
    }
  }, [id]);

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

  const sortedMedicalRecords = useMemo(
    () =>
      [...(goat?.medical_records ?? [])].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      ),
    [goat?.medical_records]
  );

  const vitalsBuckets = useMemo(
    () => buildVitalsBuckets(goat?.health_logs ?? [], vitalsPeriod, vitalsDate),
    [goat?.health_logs, vitalsPeriod, vitalsDate]
  );

  // Grouped by record_type for the medications/vaccines summary (mirrors the web goat-profile summary).
  const medicalSummary = useMemo(() => {
    const groups: Record<string, { count: number; lastDate: string | null; lastTitle: string }> = {};
    for (const record of sortedMedicalRecords) {
      const key = record.record_type || "Other";
      const dateLabel = record.date_given || record.created_at;
      if (!groups[key]) {
        groups[key] = { count: 0, lastDate: dateLabel, lastTitle: record.title || "Untitled" };
      }
      groups[key].count += 1;
    }
    // Vaccinations/treatments/deworming first since those are the "medications and vaccines" the summary highlights.
    const priority = ["Vaccination", "Treatment", "Deworming", "Checkup", "Other"];
    return Object.entries(groups).sort(
      ([a], [b]) => priority.indexOf(a) - priority.indexOf(b)
    );
  }, [sortedMedicalRecords]);

  const handleDelete = () => {
    if (!goat) return;
    RNAlert.alert(
      "Delete goat",
      `Delete ${goat.name}? This also removes their health logs, medical records, and alerts. This cannot be undone.`,
      [
        { text: "Cancel", style: "cancel" },
        {
          text: "Delete",
          style: "destructive",
          onPress: async () => {
            setDeleting(true);
            try {
              await deleteGoat(goat.id);
              router.back();
            } catch (e) {
              RNAlert.alert(
                "Could not delete",
                e instanceof ApiError ? e.message : "Please try again."
              );
              setDeleting(false);
            }
          },
        },
      ]
    );
  };

  const submitRecord = async () => {
    if (!goat || !recordTitle) {
      RNAlert.alert("Missing details", "Please enter at least a title.");
      return;
    }
    setSavingRecord(true);
    try {
      await addMedicalRecord(goat.id, {
        record_type: recordType,
        title: recordTitle,
        description: recordDescription || undefined,
        date_given: recordDate || undefined,
        administered_by: recordAdministeredBy || undefined,
        reference_photo: recordPhoto,
      });
      setRecordTitle("");
      setRecordDescription("");
      setRecordDate("");
      setRecordAdministeredBy("");
      setRecordPhoto(null);
      setAddRecordOpen(false);
      await load();
    } catch (e) {
      RNAlert.alert(
        "Could not save record",
        e instanceof ApiError ? e.message : "Please try again."
      );
    } finally {
      setSavingRecord(false);
    }
  };

  const chooseRecordPhoto = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      RNAlert.alert("Photo permission needed", "Allow photo-library access to attach a medical reference.");
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ["images"], quality: 0.8 });
    if (!result.canceled) setRecordPhoto(result.assets[0]);
  };

  if (loading) {
    return (
      <SafeAreaView style={[styles.safeArea, styles.centerFill]}>
        <Stack.Screen options={{ headerShown: false }} />
        <ActivityIndicator size="large" color="#16A34A" />
      </SafeAreaView>
    );
  }

  if (error && !goat) {
    return (
      <SafeAreaView style={[styles.safeArea, styles.centerFill]}>
        <Stack.Screen options={{ headerShown: false }} />
        <Text style={{ padding: 20, textAlign: "center", color: "#DC2626" }}>{error}</Text>
        <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  if (!goat) return null;

  return (
    <SafeAreaView style={styles.safeArea}>
      <Stack.Screen options={{ headerShown: false }} />

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        <View style={styles.topRow}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <Text style={styles.backText}>← Back</Text>
          </TouchableOpacity>

          <View style={styles.topActions}>
          {canEdit && <TouchableOpacity style={styles.greenButton} onPress={() => {setDraft(Object.fromEntries(["name","breed","age","sex","weight","owner","ear_tag","color"].map(key => [key, key === "ear_tag" ? (goat.ear_tag || goat.code) : (goat[key as keyof Goat] ?? "")])));setEditOpen(true);}}><Text style={styles.greenButtonText}>Edit profile</Text></TouchableOpacity>}
          {canEdit && (
            <TouchableOpacity
              onPress={handleDelete}
              disabled={deleting}
              style={styles.deleteHeaderButton}
            >
              {deleting ? (
                <ActivityIndicator size="small" color="#DC2626" />
              ) : (
                <Text style={styles.deleteHeaderButtonText}>🗑 Delete</Text>
              )}
            </TouchableOpacity>
          )}
          </View>
        </View>

        <Modal visible={editOpen} transparent animationType="slide" onRequestClose={() => {if (!savingProfile) setEditOpen(false);}}><View style={styles.modalOverlay}><ScrollView style={{maxHeight:"85%"}} contentContainerStyle={styles.formModal} keyboardShouldPersistTaps="handled"><Text style={styles.modalTitleText}>Edit goat profile</Text>
        {(["name","breed","age","sex","weight","owner","ear_tag","color"] as const).map(key => <View key={key}><Text style={styles.recordDetail}>{key === "ear_tag" ? "Ear Tag / Goat ID" : key[0].toUpperCase()+key.slice(1)}</Text>{key === "breed" ? <BreedSelect value={String(draft[key] ?? "")} onChange={value => setDraft(current => ({...current,[key]:value}))} /> : <TextInput accessibilityLabel={key} style={styles.input} value={String(draft[key] ?? "")} onChangeText={value => setDraft(current => ({...current,[key]:value}))} />}</View>)}
        <TouchableOpacity disabled={savingProfile} style={styles.greenButton} onPress={async () => {if (!draft.name?.trim() || !draft.ear_tag?.trim()) {RNAlert.alert("Missing details","Name and ear tag are required.");return;} setSavingProfile(true);try {await updateGoat(goat.id,draft);await load();setEditOpen(false);}catch(e){RNAlert.alert("Could not save", e instanceof Error ? e.message : "Please try again.");}finally{setSavingProfile(false);}}}><Text style={styles.greenButtonText}>{savingProfile ? "Saving…" : "Save changes"}</Text></TouchableOpacity>
        <TouchableOpacity disabled={savingProfile} style={styles.cancelButton} onPress={() => setEditOpen(false)}><Text style={styles.cancelText}>Cancel</Text></TouchableOpacity>
        </ScrollView></View></Modal>
        <Text style={styles.breadcrumb}>
          Animals / {goat.name} / {goat.code}
        </Text>

        <View style={styles.pageWrap}>
          <View style={styles.profileCard}>
            <View style={styles.profileHeader}>
              <Text style={styles.profileHeaderText}>
                WEARABLE ACTIVE • MOVEMENT DETECTOR • COLLAR TAG
              </Text>
            </View>

            <View style={styles.avatarCircle}>
              <Text style={styles.avatarEmoji}>🐐</Text>
            </View>

            <Text style={styles.goatName}>{goat.name}</Text>
            <Text style={styles.goatCode}>
              {goat.code} • {goat.collar?.collar_code || "No collar assigned"}
            </Text>

            <Text style={[styles.healthText, { color: tempBandColor(goat.temperature, "#16A34A") }]}>
              ● {goat.status} — {goat.temperature ?? "N/A"}°C
            </Text>

            <View style={styles.infoGrid}>
              <InfoBox label="Breed" value={goat.breed ?? "N/A"} />
              <InfoBox label="Age" value={goat.age ?? "N/A"} />
              <InfoBox label="Sex" value={goat.sex ?? "N/A"} />
              <InfoBox label="Weight" value={goat.weight ?? "N/A"} />
              <InfoBox label="Owner" value={goat.owner ?? "N/A"} />
              <InfoBox label="Ear Tag" value={goat.ear_tag ?? "N/A"} />
              <InfoBox label="Goat Color" value={goat.color ?? "N/A"} />
            </View>

            <View style={styles.qrCard}>
              <QRCode
                value={`${WEB_ORIGIN}/goat/${goat.id}/profile`}
                size={130}
                color="#1a1a1a"
                backgroundColor="#ffffff"
              />
              <Text style={styles.qrLabel}>QR-Linked Digital Record</Text>
              <Text style={styles.qrSubtext}>
                Scan to open {goat.name}&apos;s profile on the web dashboard.
              </Text>
              <TouchableOpacity
                style={styles.webLinkButton}
                onPress={() => Linking.openURL(`${WEB_ORIGIN}/goat/${goat.id}/profile`)}
              >
                <Text style={styles.webLinkButtonText}>View on Web →</Text>
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.tabBar}>
            <TabButton label="Vitals" active={activeTab === "vitals"} onPress={() => setActiveTab("vitals")} />
            <TabButton label="Medical Records" active={activeTab === "medical"} onPress={() => setActiveTab("medical")} />
          </View>

          {activeTab === "vitals" && (
            <View style={styles.contentCard}>
              <Text style={styles.sectionIntro}>Current Vitals Summary</Text>
              <View style={styles.vitalsGrid}>
                <VitalsBox
                  label="Temperature"
                  value={`${goat.temperature ?? "N/A"}°C`}
                  valueColor={tempBandColor(goat.temperature, "#111827")}
                />
                <VitalsBox label="Movement Detector" value={goat.movement || "N/A"} />
                <BatteryStatus level={goat.collar?.battery_level ?? goat.battery} lastSeen={goat.collar?.last_seen} />
                <VitalsBox label="Status" value={goat.status} />
              </View>

              <DateFilter value={vitalsDate} onChange={setVitalsDate} />
              <View style={styles.periodRow}>
                {(["daily", "weekly", "monthly"] as VitalsPeriod[]).map((period) => (
                  <TouchableOpacity
                    key={period}
                    onPress={() => { setVitalsPeriod(period); setVitalsDate(""); }}
                    style={[styles.periodButton, vitalsPeriod === period && styles.periodButtonActive]}
                  >
                    <Text style={[styles.periodButtonText, vitalsPeriod === period && styles.periodButtonTextActive]}>
                      {period[0].toUpperCase() + period.slice(1)}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              <VitalsChart
                title="Temperature History"
                unit="°C"
                color="#F05252"
                labels={vitalsBuckets.map((bucket) => bucket.label)}
                values={vitalsBuckets.map((bucket) =>
                  bucket.temperatures.length
                    ? bucket.temperatures.reduce((sum, value) => sum + value, 0) / bucket.temperatures.length
                    : null
                )}
              />
              <VitalsChart
                title="Motion Anomaly History"
                unit="events"
                color="#F59E0B"
                labels={vitalsBuckets.map((bucket) => bucket.label)}
                values={vitalsBuckets.map((bucket) => bucket.anomalies)}
                bars
              />
              {(goat.health_logs || []).filter(log => log.motion_anomaly || log.movement || /motion/i.test(log.event_type)).map(log => <View key={log.id} style={{paddingVertical:10}}><Text style={styles.recordDetail}>{log.motion_anomaly || log.movement || 'Motion event (type not specified)'}</Text><Text style={styles.recordDetail}>{new Date(log.created_at).toLocaleString()} — {log.event_type}</Text></View>)}
            </View>
          )}

          {activeTab === "medical" && (
            <View style={styles.contentCard}>
              <View style={styles.medicalHeader}>
                <Text style={styles.medicalTitle}>Medical Records</Text><TouchableOpacity style={styles.greenButton} onPress={async()=>{try{await downloadExport("/medical-records/export?goat_id="+goat.id,"agrisentry-goat-"+goat.id+"-medical-records.pdf");}catch(e){alert(e instanceof Error?e.message:"Download failed.");}}}><Text style={styles.greenButtonText}>Download all records (PDF)</Text></TouchableOpacity>
                {canAddMedical && (
                  <TouchableOpacity style={styles.greenButton} onPress={() => setAddRecordOpen(true)}>
                    <Text style={styles.greenButtonText}>+ Add Record</Text>
                  </TouchableOpacity>
                )}
              </View>

              {medicalSummary.length > 0 && (
                <View style={styles.summaryRow}>
                  {medicalSummary.map(([type, info]) => (
                    <View key={type} style={styles.summaryChip}>
                      <Text style={styles.summaryChipCount}>{info.count}</Text>
                      <Text style={styles.summaryChipLabel}>
                        {type}{info.count === 1 ? "" : "s"}
                      </Text>
                      <Text style={styles.summaryChipLast} numberOfLines={1}>
                        Last: {info.lastTitle} ({info.lastDate ? new Date(info.lastDate).toLocaleDateString() : "N/A"})
                      </Text>
                    </View>
                  ))}
                </View>
              )}

              {sortedMedicalRecords.length === 0 ? (
                <Text style={styles.emptyText}>No medical records yet.</Text>
              ) : (
                sortedMedicalRecords.map((record) => (
                  <View key={record.id} style={styles.medicalRecord}>
                    <View style={[styles.timelineDot, { backgroundColor: "#2563EB" }]} />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.recordDate}>
                        {record.date_given || new Date(record.created_at).toLocaleDateString()}
                      </Text>
                      <Text style={styles.recordTitle}>{record.title}</Text>
                      <Text style={styles.recordDetail}>{record.record_type}</Text>
                      {record.description ? (
                        <Text style={styles.recordDetail}>{record.description}</Text>
                      ) : null}
                      {record.administered_by ? (
                        <Text style={styles.recordDetail}>By: {record.administered_by}</Text>
                      ) : null}
                      {record.reference_photo_url ? <Image source={{ uri: record.reference_photo_url }} style={styles.recordPhoto} /> : null}
                    </View>
                  </View>
                ))
              )}
            </View>
          )}
        </View>
      </ScrollView>

      <Modal visible={addRecordOpen} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.formModal}>
            <Text style={styles.modalTitleText}>Add Medical Record</Text>

            <TextInput
              style={styles.input}
              placeholder="Record type (e.g. Vaccination)"
              placeholderTextColor="#64748B"
              value={recordType}
              onChangeText={setRecordType}
            />
            <TextInput
              style={styles.input}
              placeholder="Title"
              placeholderTextColor="#64748B"
              value={recordTitle}
              onChangeText={setRecordTitle}
            />
            <TextInput
              style={styles.input}
              placeholder="Description"
              placeholderTextColor="#64748B"
              value={recordDescription}
              onChangeText={setRecordDescription}
            />
            <TextInput
              style={styles.input}
              placeholder="Date given (YYYY-MM-DD)"
              placeholderTextColor="#64748B"
              value={recordDate}
              onChangeText={setRecordDate}
            />
            <TextInput
              style={styles.input}
              placeholder="Administered by"
              placeholderTextColor="#64748B"
              value={recordAdministeredBy}
              onChangeText={setRecordAdministeredBy}
            />
            <TouchableOpacity style={styles.photoButton} onPress={chooseRecordPhoto}>
              <Text style={styles.photoButtonText}>{recordPhoto ? "Change reference photo" : "+ Attach diagnosis / medication photo"}</Text>
            </TouchableOpacity>
            {recordPhoto ? <Image source={{ uri: recordPhoto.uri }} style={styles.recordPhotoPreview} /> : null}

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.cancelButton}
                onPress={() => setAddRecordOpen(false)}
                disabled={savingRecord}
              >
                <Text style={styles.cancelText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.greenButton} onPress={submitRecord} disabled={savingRecord}>
                {savingRecord ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.greenButtonText}>Save Record</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

function TabButton({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return (
    <TouchableOpacity onPress={onPress} style={styles.tabButton}>
      <Text style={[styles.tabText, active && styles.activeTabText]}>{label}</Text>
      {active && <View style={styles.activeTabLine} />}
    </TouchableOpacity>
  );
}

function InfoBox({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoBox}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

function VitalsBox({ label, value, valueColor }: { label: string; value: string; valueColor?: string }) {
  return (
    <View style={styles.vitalsBox}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={[styles.recordTitle, valueColor ? { color: valueColor } : null]}>{value}</Text>
    </View>
  );
}

function VitalsChart({
  title,
  unit,
  color,
  labels,
  values,
  bars = false,
}: {
  title: string;
  unit: string;
  color: string;
  labels: string[];
  values: (number | null)[];
  bars?: boolean;
}) {
  const reveal = useRef(new Animated.Value(0)).current;
  const numericValues = values.filter((value): value is number => value !== null);
  const hasData = bars || numericValues.length > 0;

  useEffect(() => {
    reveal.setValue(0);
    Animated.timing(reveal, {
      toValue: 1,
      duration: 420,
      easing: Easing.out(Easing.cubic),
      useNativeDriver: true,
    }).start();
  }, [bars, labels, reveal, values]);

  const width = 320;
  const height = 150;
  const left = 34;
  const right = 10;
  const top = 16;
  const bottom = 24;
  const plotWidth = width - left - right;
  const plotHeight = height - top - bottom;
  const minValue = bars ? 0 : Math.min(...numericValues, 30);
  const maxValue = bars ? Math.max(...numericValues, 1) : Math.max(...numericValues, 42);
  const range = Math.max(maxValue - minValue, 1);
  const xAt = (index: number) => left + (index * plotWidth) / Math.max(values.length - 1, 1);
  const yAt = (value: number) => top + plotHeight - ((value - minValue) / range) * plotHeight;
  const points = values
    .map((value, index) => (value === null ? null : `${xAt(index)},${yAt(value)}`))
    .filter(Boolean)
    .join(" ");

  return (
    <Animated.View
      style={[
        styles.chartCard,
        { opacity: reveal, transform: [{ translateY: reveal.interpolate({ inputRange: [0, 1], outputRange: [10, 0] }) }] },
      ]}
    >
      <View style={styles.chartHeader}>
        <Text style={styles.chartTitle}>{title}</Text>
        <Text style={styles.chartUnit}>{unit}</Text>
      </View>
      {!hasData ? (
        <View style={styles.chartEmpty}><Text style={styles.emptyText}>No readings for this period.</Text></View>
      ) : (
        <>
          <Svg width="100%" height={height} viewBox={`0 0 ${width} ${height}`}>
            {[0, 1, 2, 3].map((line) => {
              const y = top + (line * plotHeight) / 3;
              return <Line key={line} x1={left} y1={y} x2={width - right} y2={y} stroke="#263440" strokeWidth="1" />;
            })}
            {bars ? values.map((value, index) => {
              const amount = value ?? 0;
              const barWidth = Math.max(plotWidth / values.length - 5, 3);
              const y = yAt(amount);
              return <Rect key={index} x={left + index * (plotWidth / values.length) + 2} y={y} width={barWidth} height={top + plotHeight - y} rx="2" fill={color} opacity={0.9} />;
            }) : (
              <>
                <Polyline points={points} fill="none" stroke={color} strokeWidth="3" strokeLinejoin="round" strokeLinecap="round" />
                {values.map((value, index) => value === null ? null : <Circle key={index} cx={xAt(index)} cy={yAt(value)} r="3.5" fill="#07121A" stroke={color} strokeWidth="2" />)}
              </>
            )}
          </Svg>
          <View style={styles.chartLabels}>
            <Text style={styles.chartLabel}>{labels[0]}</Text>
            <Text style={styles.chartLabel}>{labels[Math.floor(labels.length / 2)]}</Text>
            <Text style={styles.chartLabel}>{labels[labels.length - 1]}</Text>
          </View>
        </>
      )}
    </Animated.View>
  );
}

const darkStyles = StyleSheet.create({
  safeArea: { flex: 1, width: "100%", maxWidth: Platform.OS === "web" ? 440 : undefined, alignSelf: "center", backgroundColor: "#020B11" },
  centerFill: { alignItems: "center", justifyContent: "center" },
  scrollContent: { padding: 16, paddingBottom: 40 },
  topRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 10 },
  topActions: { flexDirection: "row", alignItems: "center", gap: 8 },
  backButton: {},
  backText: { color: "#55E875", fontSize: 16, fontWeight: "800" },
  deleteHeaderButton: {
    borderWidth: 1,
    borderColor: "#EF4444",
    backgroundColor: "#160D11",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
  },
  deleteHeaderButtonText: { color: "#F05252", fontWeight: "700", fontSize: 13 },
  breadcrumb: { fontSize: 13, color: "#98A3B7", marginBottom: 14, fontWeight: "600" },
  pageWrap: { flexDirection: "column", gap: 16 },
  profileCard: { backgroundColor: "#07121A", borderRadius: 24, borderWidth: 1, borderColor: "#2A3642", overflow: "hidden" },
  profileHeader: { backgroundColor: "#06311F", paddingVertical: 18, paddingHorizontal: 16, alignItems: "center", borderBottomWidth: 1, borderBottomColor: "#1C5237" },
  profileHeaderText: { fontSize: 12, fontWeight: "700", color: "#55E875", textAlign: "center" },
  avatarCircle: {
    width: 76,
    height: 76,
    borderRadius: 38,
    backgroundColor: "#082119",
    borderWidth: 3,
    borderColor: "#36D760",
    alignSelf: "center",
    marginTop: -18,
    justifyContent: "center",
    alignItems: "center",
  },
  avatarEmoji: { fontSize: 34 },
  goatName: { textAlign: "center", fontSize: 36, fontWeight: "800", color: "#F8FAFC", marginTop: 14 },
  goatCode: { textAlign: "center", fontSize: 14, color: "#98A3B7", marginTop: 6 },
  healthText: { textAlign: "center", fontSize: 15, color: "#16A34A", marginTop: 10, fontWeight: "700" },
  infoGrid: { flexDirection: "row", flexWrap: "wrap", justifyContent: "space-between", padding: 16, gap: 10 },
  infoBox: { width: "48%", backgroundColor: "#09141D", borderRadius: 14, padding: 14, borderWidth: 1, borderColor: "#2A3642" },
  infoLabel: { fontSize: 12, color: "#98A3B7", fontWeight: "700", textTransform: "uppercase", marginBottom: 6 },
  infoValue: { fontSize: 16, color: "#F8FAFC", fontWeight: "700" },
  qrCard: { borderTopWidth: 1, borderTopColor: "#2A3642", padding: 18, alignItems: "center" },
  qrLabel: { fontSize: 12, color: "#55E875", fontWeight: "800", textTransform: "uppercase", marginTop: 12, marginBottom: 6 },
  qrSubtext: { fontSize: 13, color: "#98A3B7", textAlign: "center" },
  webLinkButton: { marginTop: 14, paddingVertical: 10, paddingHorizontal: 18, borderRadius: 12, backgroundColor: "#082A1B", borderWidth: 1, borderColor: "#24834A" },
  webLinkButtonText: { color: "#55E875", fontWeight: "700", fontSize: 13 },
  summaryRow: { flexDirection: "row", flexWrap: "wrap", gap: 10, marginBottom: 16 },
  summaryChip: { flexGrow: 1, minWidth: 140, backgroundColor: "#09141D", borderWidth: 1, borderColor: "#2A3642", borderRadius: 14, padding: 12 },
  summaryChipCount: { fontSize: 20, fontWeight: "800", color: "#F8FAFC" },
  summaryChipLabel: { fontSize: 12, fontWeight: "700", color: "#CBD5E1", textTransform: "uppercase", marginTop: 2 },
  summaryChipLast: { fontSize: 11, color: "#98A3B7", marginTop: 4 },
  tabBar: { flexDirection: "row", flexWrap: "wrap", gap: 16, borderBottomWidth: 1, borderBottomColor: "#2A3642", marginBottom: 14 },
  tabButton: { paddingBottom: 10 },
  tabText: { fontSize: 15, color: "#8290A5", fontWeight: "500" },
  activeTabText: { color: "#55E875", fontWeight: "700" },
  activeTabLine: { marginTop: 8, height: 3, backgroundColor: "#55E875", borderRadius: 999 },
  contentCard: { backgroundColor: "#07121A", borderRadius: 20, borderWidth: 1, borderColor: "#2A3642", padding: 16 },
  sectionIntro: { fontSize: 14, color: "#F8FAFC", fontWeight: "700", marginBottom: 16 },
  emptyText: { color: "#98A3B7", fontStyle: "italic" },
  timelineDot: { width: 12, height: 12, borderRadius: 6, marginTop: 6, marginRight: 12 },
  recordDate: { fontSize: 12, color: "#64748B", marginBottom: 4 },
  recordTitle: { fontSize: 18, fontWeight: "700", color: "#F8FAFC", marginBottom: 6 },
  recordDetail: { fontSize: 14, color: "#A6B0C1", lineHeight: 21, marginBottom: 4 },
  recordPhoto: { width: "100%", height: 170, borderRadius: 12, marginTop: 10, resizeMode: "cover" },
  vitalsGrid: { flexDirection: "row", flexWrap: "wrap", justifyContent: "space-between" },
  vitalsBox: { width: "48%", backgroundColor: "#09141D", borderWidth: 1, borderColor: "#2A3642", borderRadius: 14, padding: 14, marginBottom: 12 },
  periodRow: { flexDirection: "row", gap: 8, marginTop: 6, marginBottom: 14 },
  periodButton: { flex: 1, alignItems: "center", paddingVertical: 9, borderRadius: 10, borderWidth: 1, borderColor: "#2A3642", backgroundColor: "#09141D" },
  periodButtonActive: { borderColor: "#36D760", backgroundColor: "#08311F" },
  periodButtonText: { color: "#8290A5", fontSize: 12, fontWeight: "700" },
  periodButtonTextActive: { color: "#55E875" },
  chartCard: { marginTop: 12, padding: 14, borderRadius: 16, borderWidth: 1, borderColor: "#2A3642", backgroundColor: "#09141D", overflow: "hidden" },
  chartHeader: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", marginBottom: 4 },
  chartTitle: { color: "#F8FAFC", fontSize: 15, fontWeight: "800" },
  chartUnit: { color: "#8290A5", fontSize: 11, fontWeight: "700", textTransform: "uppercase" },
  chartEmpty: { height: 150, alignItems: "center", justifyContent: "center" },
  chartLabels: { marginTop: -17, paddingLeft: 34, flexDirection: "row", justifyContent: "space-between" },
  chartLabel: { color: "#718096", fontSize: 9 },
  medicalHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 16 },
  medicalTitle: { fontSize: 22, fontWeight: "800", color: "#F8FAFC" },
  medicalRecord: { flexDirection: "row", alignItems: "flex-start", paddingVertical: 14, borderTopWidth: 1, borderTopColor: "#2A3642" },
  greenButton: { backgroundColor: "#087B37", paddingVertical: 10, paddingHorizontal: 18, borderRadius: 12, alignItems: "center", justifyContent: "center", borderWidth: 1, borderColor: "#45DB6B" },
  greenButtonText: { color: "#FFFFFF", fontWeight: "700" },
  modalOverlay: { flex: 1, backgroundColor: "rgba(15, 23, 42, 0.45)", justifyContent: "flex-end" },
  formModal: { backgroundColor: "#09141D", borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, borderWidth: 1, borderColor: "#2A3642" },
  modalTitleText: { fontSize: 22, fontWeight: "800", color: "#F8FAFC", marginBottom: 16 },
  input: {
    backgroundColor: "#07121A",
    borderWidth: 1,
    borderColor: "#2A3642",
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 13,
    marginBottom: 12,
    color: "#F8FAFC",
    fontSize: 15,
  },
  photoButton: { borderWidth: 1, borderStyle: "dashed", borderColor: "#36D760", borderRadius: 14, padding: 13, alignItems: "center", marginBottom: 10 },
  photoButtonText: { color: "#55E875", fontWeight: "700", fontSize: 13 },
  recordPhotoPreview: { width: "100%", height: 180, borderRadius: 14, marginBottom: 12, resizeMode: "cover" },
  modalActions: { flexDirection: "row", gap: 10, marginTop: 4 },
  cancelButton: { flex: 1, paddingVertical: 13, borderRadius: 14, borderWidth: 1, borderColor: "#3A4754", alignItems: "center" },
  cancelText: { color: "#F8FAFC", fontWeight: "800" },
});

const PROFILE_LIGHT_COLORS: Record<string, string> = {
  "#020B11": "#F4F7F5", "#07121A": "#FFFFFF", "#09141D": "#FFFFFF",
  "#2A3642": "#DCE5E0", "#263440": "#E2E8E4", "#F8FAFC": "#10231B",
  "#98A3B7": "#5F6F67", "#A6B0C1": "#52625A", "#8290A5": "#66766E",
  "#CBD5E1": "#33443B", "#718096": "#52625A", "#94A3B8": "#64748B",
  "#64748B": "#687870", "#55E875": "#15803D", "#082119": "#E1F5E8",
  "#08311F": "#DCFCE7", "#07311F": "#DCFCE7", "#3A4754": "#C9D5CE",
  "#160D11": "#FFF1F2", "#06311F": "#DCFCE7", "#082A1B": "#DCFCE7", "#07331F": "#DCFCE7", "#08251A": "#DCFCE7", "#09271B": "#DCFCE7", "#0A2119": "#DCFCE7",
};

function createProfileLightStyles(source: typeof darkStyles): typeof darkStyles {
  const mapped = Object.fromEntries(Object.entries(source).map(([name, value]) => {
    const flat = StyleSheet.flatten(value) as Record<string, unknown>;
    return [name, Object.fromEntries(Object.entries(flat).map(([key, item]) => [key, typeof item === "string" ? (key === "color" ? lightTextColor(item, name) : (PROFILE_LIGHT_COLORS[item.toUpperCase()] || item)) : item]))];
  }));
  return StyleSheet.create(mapped as any) as typeof darkStyles;
}

function lightTextColor(value: string, name: string): string {
  if (name === "logo") return "#10231B";
  if (name === "viewButtonText") return "#FFFFFF";
  const color = value.toUpperCase();
  if (["#FFFFFF", "#FFF"].includes(color)) return value; // White labels on filled buttons.
  if (["#55E875", "#36D760", "#16A34A", "#22C55E", "#4ADE80"].includes(color)) return "#166534";
  if (["#F87171", "#EF4444", "#DC2626"].includes(color)) return "#B91C1C";
  if (["#FBBF24", "#F59E0B", "#FB923C"].includes(color)) return "#9A3412";
  if (["#60A5FA", "#93C5FD", "#2563EB"].includes(color)) return "#1D4ED8";
  return "#334155";
}
const lightStyles = createProfileLightStyles(darkStyles);
let styles = darkStyles;
