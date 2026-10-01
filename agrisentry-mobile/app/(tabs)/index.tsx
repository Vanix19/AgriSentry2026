import BatteryStatus from "@/components/BatteryStatus";
import { useLiveRefresh } from "@/hooks/use-live-refresh";
import DateFilter from "@/components/DateFilter";
import React, { useCallback, useEffect, useMemo, useState } from "react";

import {
  useWindowDimensions,
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  Modal,
  TextInput,
  Alert as RNAlert,
  KeyboardAvoidingView,
  Platform,
  ActivityIndicator,
  RefreshControl,
  Image,
} from "react-native";

import { SafeAreaView } from "react-native-safe-area-context";
import { useRouter } from "expo-router";
import { CameraView, useCameraPermissions, type BarcodeScanningResult, type BarcodeSettings } from "expo-camera";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from 'expo-image-picker';
import { adviceForm, deleteTemporaryPhoto, pickerPhoto, validatePhoto, type AdvicePhoto } from '@/services/advice-photo';

import { useAuth } from "@/contexts/auth-context";
import { useAppTheme } from "@/contexts/theme-context";
import { apiFetch, ApiError } from "@/services/api";
import {
  createGoat,
  fetchAlerts,
  fetchGoats,
  fetchHealthLogs,
} from "@/services/goats";
import type { Alert as AlertRecord, Goat, HealthLog } from "@/types/api";
import { tempBandColor } from "@/constants/temperature";

type ScreenName =
  | "dashboard"
  | "healthLogs"
  | "qrScanner"
  | "aiVet"
  | "settings";

export default function AgriSentryDashboard() {
  const {width:screenWidth}=useWindowDimensions();
  const router = useRouter();
  const { user, logout } = useAuth();
  const { isDark, toggleTheme } = useAppTheme();
  styles = isDark ? darkStyles : lightStyles;

  const [goats, setGoats] = useState<Goat[]>([]);
  const [logPeriod, setLogPeriod] = useState("all");
  const [motionFilter,setMotionFilter]=useState("All motion");
  const [logDate, setLogDate] = useState("");
  const [healthLogs, setHealthLogs] = useState<HealthLog[]>([]);
  const [alerts, setAlerts] = useState<AlertRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [screen, setScreen] = useState<ScreenName>("dashboard");
  const [addGoatOpen, setAddGoatOpen] = useState(false);
  const [notificationsOpen, setNotificationsOpen] = useState(false);

  const [newGoatName, setNewGoatName] = useState("");
  const [newGoatBreed, setNewGoatBreed] = useState("");
  const [newGoatSex, setNewGoatSex] = useState("");
  const [newGoatAge, setNewGoatAge] = useState("");
  const [newGoatWeight, setNewGoatWeight] = useState("");
  const [newGoatOwner, setNewGoatOwner] = useState("");
  const [newGoatEarTag, setNewGoatEarTag] = useState("");
  const [newGoatColor, setNewGoatColor] = useState("");
  const [newGoatCollarId, setNewGoatCollarId] = useState("");
  const [savingGoat, setSavingGoat] = useState(false);

  const [lookupCode, setLookupCode] = useState("");
  const [lookupError, setLookupError] = useState<string | null>(null);

  const [chatInput, setChatInput] = useState("");
  const [aiLoading, setAiLoading] = useState(false);
  const aiBusy = React.useRef(false);
  const [aiPhoto, setAiPhoto] = useState<AdvicePhoto | null>(null);
  const [photoMenuOpen, setPhotoMenuOpen] = useState(false);
  const [photoBusy, setPhotoBusy] = useState(false);
  const ownedPhotos = React.useRef<AdvicePhoto[]>([]);
  const chatScrollRef = React.useRef<ScrollView | null>(null);
  const dashboardScrollRef = React.useRef<ScrollView | null>(null);

  const [chatMessages, setChatMessages] = useState<{ from: string; message: string; photoUri?: string }[]>([
    {
      from: "AI Vet Advice",
      message:
        "Hello. I can help explain goat temperature alerts, movement concerns, vaccines, and basic care guidance.",
    },
  ]);

  useEffect(() => () => {
    for (const photo of ownedPhotos.current) {
      if (photo.uri.startsWith('blob:')) URL.revokeObjectURL(photo.uri);
      void deleteTemporaryPhoto(photo).catch(() => {});
    }
  }, []);

  const attachPhoto = (photo: AdvicePhoto) => {
    ownedPhotos.current.push(photo);
    setAiPhoto(photo);
  };

  const chooseAdvicePhoto = async () => {
    if (aiBusy.current || photoBusy) return;
    setPhotoMenuOpen(false);
    setPhotoBusy(true);
    try {
      {
        const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8 });
        if (!result.canceled) attachPhoto(await pickerPhoto(result.assets[0]));
      }
    } catch (error) {
      setChatMessages(current => [...current, { from: 'System', message: error instanceof Error ? error.message : 'Could not attach this photo.' }]);
    } finally { setPhotoBusy(false); }
  };

  const pasteAdvicePhoto = (event: React.ClipboardEvent) => {
    const item = Array.from(event.clipboardData.items).find(item => item.kind === 'file' && item.type.startsWith('image/'));
    const file = item?.getAsFile();
    if (!file) return;
    event.preventDefault();
    if (aiBusy.current || photoBusy) return;
    try {
      validatePhoto(file.type, file.size);
      attachPhoto({ uri: URL.createObjectURL(file), name: file.name, mimeType: file.type, file });
    } catch (error) {
      setChatMessages(current => [...current, { from: 'System', message: error instanceof Error ? error.message : 'Could not paste this photo.' }]);
    }
  };

  const loadData = useCallback(async () => {
    setLoadError(null);
    try {
      const [goatData, logData, alertData] = await Promise.all([
        user?.permissions?.["goats.read"] === false ? Promise.resolve({goats: []}) : fetchGoats(),
        user?.permissions?.["health-logs.read"] === false ? Promise.resolve({health_logs: []}) : fetchHealthLogs(),
        user?.permissions?.["alerts.read"] === false ? Promise.resolve({alerts: []}) : fetchAlerts(),
      ]);
      setGoats(goatData.goats);
      setHealthLogs(logData.health_logs);
      setAlerts(alertData.alerts);
    } catch (e) {
      setLoadError(
        e instanceof ApiError
          ? e.message
          : "Could not load data from the AgriSentry server."
      );
    }
  }, [user]);

  useLiveRefresh(loadData);

  useEffect(() => {
    (async () => {
      setLoading(true);
      await loadData();
      setLoading(false);
    })();
  }, [loadData]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  }, [loadData]);

  useEffect(() => {
    setTimeout(() => {
      chatScrollRef.current?.scrollToEnd({ animated: true });
    }, 100);
  }, [chatMessages]);

  useEffect(() => {
    if (screen === "dashboard") {
      requestAnimationFrame(() => dashboardScrollRef.current?.scrollTo({ y: 0, animated: false }));
    }
  }, [screen]);

  const sortedGoats = useMemo(() => {
    const priority: Record<string, number> = {
      Urgent: 1,
      Warning: 2,
      Monitoring: 2,
      Normal: 3,
    };
    return [...goats].sort(
      (a, b) => (priority[a.status] ?? 9) - (priority[b.status] ?? 9)
    );
  }, [goats]);

  const totalGoats = goats.length;
  const filteredHealthLogs = useMemo(() => healthLogs.filter(log => {
                if(motionFilter==="Any anomaly" && !log.motion_anomaly)return false;
                if(motionFilter==="No anomaly" && log.motion_anomaly)return false;
                if(!["All motion","Any anomaly","No anomaly"].includes(motionFilter) && log.motion_anomaly!==motionFilter)return false;
                const date = new Date(log.created_at);
                if (logDate) return /^\d{4}-\d{2}-\d{2}$/.test(logDate) && date.toLocaleDateString() === new Date(logDate+"T00:00:00").toLocaleDateString();
                if (logPeriod === "all") return true;
                const start = new Date(); start.setHours(0,0,0,0);
                if (logPeriod === "weekly") start.setDate(start.getDate()-6);
                if (logPeriod === "monthly") start.setDate(1);
                return date >= start && date <= new Date();
              }), [healthLogs, logPeriod, logDate, motionFilter]);

  const urgentCount = goats.filter((g) => g.status === "Urgent").length;
  const monitoringCount = goats.filter((g) => g.status === "Warning" || g.status === "Monitoring").length;
  const normalCount = goats.filter((g) => g.status === "Normal").length;

  const addGoat = async () => {
    if (user?.role !== "Admin" && !user?.permissions?.["goats.write"]) { RNAlert.alert("Access denied", "Your Admin has disabled goat registration for your account."); return; }
    if (!newGoatName || !newGoatEarTag.trim() || !newGoatBreed) {
      RNAlert.alert("Missing Details", "Please fill in all goat details.");
      return;
    }

    if (!/^[A-Za-z0-9][A-Za-z0-9_-]*$/.test(newGoatEarTag.trim())) { RNAlert.alert("Invalid Ear Tag", "Use letters, numbers, hyphens, or underscores."); return; }

    setSavingGoat(true);
    try {
      await createGoat({
        name: newGoatName,
        breed: newGoatBreed,
        sex: newGoatSex,
        age: newGoatAge,
        weight: newGoatWeight,
        owner: newGoatOwner,
        ear_tag: newGoatEarTag.trim(),
        color: newGoatColor,
        collar_id: newGoatCollarId,
      });
      setNewGoatName("");
      setNewGoatBreed("");
      setNewGoatSex("");
      setNewGoatAge("");
      setNewGoatWeight("");
      setNewGoatOwner("");
      setNewGoatEarTag("");
      setNewGoatColor("");
      setNewGoatCollarId("");
      setAddGoatOpen(false);
      await loadData();
    } catch (e) {
      RNAlert.alert(
        "Could not add goat",
        e instanceof ApiError ? e.message : "Please try again."
      );
    } finally {
      setSavingGoat(false);
    }
  };

  const lookupByCode = () => {
    const code = lookupCode.trim().toLowerCase();
    if (!code) {
      setLookupError("Enter a goat code first.");
      return;
    }
    const found = goats.find((g) => String(g.code || "").toLowerCase() === code);
    if (!found) {
      setLookupError(`No goat found with code "${lookupCode.trim()}".`);
      return;
    }
    setLookupError(null);
    setLookupCode("");
    router.push(`/goat/${found.id}`);
  };

  const sendAiMessage = async () => {
    if ((!chatInput.trim() && !aiPhoto) || aiBusy.current || photoBusy) return;

    const userMessage = chatInput.trim();
    const photo = aiPhoto;
    aiBusy.current = true;
    setChatMessages((current) => [...current, { from: "You", message: userMessage || 'Photo for AI Vet Advice', photoUri: photo?.uri }]);
    setChatInput("");
    setAiLoading(true);

    try {
      const data = await apiFetch<{ advice?: string }>("/gemini-advice", {
        method: "POST",
        body: adviceForm(userMessage, photo),
      });
      setAiPhoto(null);

      setChatMessages((current) => [
        ...current,
        {
          from: "AI Vet Advice",
          message: data.advice || "No advice returned from the server.",
        },
      ]);
    } catch (error) {
      setChatInput(current => current || userMessage);
      setChatMessages((current) => [
        ...current,
        {
          from: "AI Vet Advice",
          message:
            error instanceof ApiError
              ? error.message
              : "Sorry, I cannot connect to the AI Vet server right now.",
        },
      ]);
    } finally {
      aiBusy.current = false;
      setAiLoading(false);
    }
  };

  const confirmLogout = () => {
    // Alert.alert's button callbacks never fire on react-native-web, so web needs
    // its own confirm path. Just clear the session here — don't navigate. Calling
    // router.replace() in the same tick as the state update races Stack.Protected's
    // guard re-evaluation (it can still see the old, logged-in state and ignore the
    // navigation). _layout.tsx handles redirecting to /login once the logged-out
    // state has actually rendered.
    if (Platform.OS === "web") {
      if (window.confirm("Are you sure you want to log out?")) logout();
      return;
    }
    RNAlert.alert("Log out", "Are you sure you want to log out?", [
      { text: "Cancel", style: "cancel" },
      { text: "Log Out", style: "destructive", onPress: () => logout() },
    ]);
  };

  if (loading) {
    return (
      <SafeAreaView style={[styles.safeArea, styles.centerFill]}>
        <ActivityIndicator size="large" color="#16A34A" />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle={isDark ? "light-content" : "dark-content"} backgroundColor={isDark ? "#020B11" : "#F4F7F5"} />

      <View style={styles.header}>
        <TouchableOpacity accessibilityRole="button" accessibilityLabel="Back to livestock selection" onPress={() => router.replace('/species')} style={{ padding: 8 }}>
          <Ionicons name="arrow-back" size={24} color={isDark ? '#F8FAFC' : '#10231B'} />
        </TouchableOpacity>
        <View style={styles.headerLogoBadge}>
          <Image
            source={require("@/assets/images/agrisentry-logo-v2-transparent.png")}
            style={styles.headerLogoImage}
            resizeMode="contain"
          />
        </View>
        <View>
          <Text style={styles.logo}>AgriSentry</Text>
          <Text style={styles.headerSubtitle}>Goat Health Monitoring</Text>
        </View>
        <TouchableOpacity
          style={styles.notificationWrap}
          onPress={() => setNotificationsOpen(true)}
          accessibilityRole="button"
          accessibilityLabel={`Open notifications, ${alerts.length} alert${alerts.length === 1 ? "" : "s"}`}
        >
          <Ionicons name="notifications-outline" size={28} color={isDark ? "#F8FAFC" : "#10231B"} />
          {alerts.length > 0 && <Text style={styles.notificationBadge}>{alerts.length > 9 ? "9+" : alerts.length}</Text>}
        </TouchableOpacity>
      </View>

      <View style={styles.contentArea}>
        {screen === "dashboard" && (
          <ScrollView showsVerticalScrollIndicator={false}
            ref={dashboardScrollRef}
            contentContainerStyle={styles.page}
            
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
            }
          >
            <View style={styles.heroIntro}>
              <Image source={require("@/assets/images/goat-hero-clear.png")} style={styles.heroGoats} resizeMode="contain" />
              <Text style={styles.title}>Herd Dashboard</Text>
              <Text style={styles.subtitle}>
                Urgent goat alerts are automatically shown first.
              </Text>

              {loadError && (
                <View style={styles.errorBanner}>
                  <Text style={styles.errorBannerText}>{loadError}</Text>
                </View>
              )}

              <TouchableOpacity
                style={styles.fullAddButton}
                onPress={() => setAddGoatOpen(true)}
              >
                <Text style={styles.addButtonText}>+ Add Goat</Text>
              </TouchableOpacity>
            </View>

            <View style={styles.summaryGrid}>
              <SummaryCard label="Total Goats" value={String(totalGoats)} />
              <SummaryCard label="Urgent Alerts" value={String(urgentCount)} color="#DC2626" />
              <SummaryCard label="Warning" value={String(monitoringCount)} color="#F59E0B" />
              <SummaryCard label="Normal" value={String(normalCount)} color="#16A34A" />
            </View>

            <Text style={styles.sectionTitle}>Individual Animal Tiles</Text>

            {sortedGoats.length === 0 && (
              <Text style={styles.emptyText}>No goats registered yet.</Text>
            )}

            <View style={{flexDirection:"row",flexWrap:"wrap",gap:12}}>
            {sortedGoats.map((goat) => (
              <View key={goat.id} style={{width:screenWidth>=700?"48%":"100%"}}>
              <GoatTile
                key={goat.id}
                goat={goat}
                onView={() => router.push(`/goat/${goat.id}`)}
              />
              </View>
            ))}
            </View>
          </ScrollView>
        )}

        {screen === "healthLogs" && (
          <ScrollView
            contentContainerStyle={styles.page}
            showsVerticalScrollIndicator={false}
            refreshControl={
              <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
            }
          >
            <View style={styles.heroIntro}>
              <Image source={require("@/assets/images/goat-hero-clear.png")} style={styles.heroGoats} resizeMode="contain" />
              <Text style={styles.title}>Health Logs</Text>
              <Text style={styles.subtitle}>All recent sensor and health events.</Text>
            </View>

            <View style={{flexDirection:"row", flexWrap:"wrap", gap:12, marginBottom:12}}>{["all","daily","weekly","monthly"].map(period => <TouchableOpacity key={period} onPress={() => {setLogPeriod(period); setLogDate("");}}><Text style={[styles.logTitle, {textDecorationLine:logPeriod === period ? "underline" : "none"}]}>{period === "all" ? "All dates" : period[0].toUpperCase()+period.slice(1)}</Text></TouchableOpacity>)}</View>
            <DateFilter value={logDate} onChange={setLogDate} />
            <View style={{flexDirection:"row",flexWrap:"wrap",gap:8,marginBottom:16}}>{["All motion","Any anomaly","Prolonged Inactivity","Excessive Movement","No anomaly"].map(filter=><TouchableOpacity key={filter} onPress={()=>setMotionFilter(filter)} style={{padding:10,borderRadius:9,borderWidth:1,borderColor:motionFilter===filter?"#16A34A":"#94A3B8",backgroundColor:motionFilter===filter?"#16A34A":"transparent"}}><Text style={[styles.logTitle,motionFilter===filter&&{color:"white"}]}>{filter}</Text></TouchableOpacity>)}</View>
            <View style={styles.logsCard}>
              {filteredHealthLogs.length === 0 && (
                <Text style={styles.emptyText}>No health logs found for this date or period.</Text>
              )}
              {filteredHealthLogs.map((log) => {
                const isAnomaly =
                  (log.severity && !["normal", "info"].includes(log.severity.toLowerCase())) ||
                  (log.event_type && !["telemetry", "normal"].includes(log.event_type.toLowerCase()));

                return (
                  <View key={log.id} style={[styles.logItem, {borderWidth:2, borderRadius:12, padding:12, marginBottom:10, borderColor:["urgent","high","critical"].includes((log.severity || "").toLowerCase()) ? "#DC2626" : ["warning","monitoring"].includes((log.severity || "").toLowerCase()) ? "#EA580C" : (isDark ? "#334155" : "#CBD5E1")}]}>
                    <View
                      style={[
                        styles.logDot,
                        {
                          backgroundColor:
                            log.temperature !== null
                              ? tempBandColor(log.temperature)
                              : isAnomaly
                                ? "#DC2626"
                                : "#16A34A",
                        },
                      ]}
                    />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.logTitle}>
                        {log.goat ? `${log.goat.name} (${log.goat.code})` : `Goat #${log.goat_id}`} — {log.motion_anomaly || log.event_type}
                      </Text>
                      <Text style={styles.logText}>
                        Temperature: {log.temperature ?? "N/A"}°C • Movement: {log.movement || "N/A"}
                      </Text>
                      {log.description ? (
                        <Text style={styles.logText}>{log.description}</Text>
                      ) : null}
                      <Text style={styles.logTimestamp}>
                        {new Date(log.created_at).toLocaleString()}
                      </Text>
                    </View>
                  </View>
                );
              })}
            </View>
          </ScrollView>
        )}

        {screen === "qrScanner" && (
          <View style={styles.qrScreen}>
            <View style={styles.qrTopFixed}>
              <Text style={styles.title}>Scan QR</Text>
              <Text style={styles.subtitle}>
                Point the camera at a goat&apos;s printed QR tag.
              </Text>

              {/* Camera preview must stay OUTSIDE any ScrollView — on Android its
                  SurfaceView renders black when scrolled/composited inside one. */}
              <QrScannerPanel
                active={screen === "qrScanner"}
                goats={goats}
                onFound={(goatId) => router.push(`/goat/${goatId}`)}
              />
            </View>

            {false && <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
            <View style={styles.dividerRow}>
              <View style={styles.dividerLine} />
              <Text style={styles.dividerText}>OR</Text>
              <View style={styles.dividerLine} />
            </View>

            <View style={styles.placeholderCard}>
              <Text style={styles.placeholderIcon}>▣</Text>
              <Text style={styles.placeholderTitle}>Find a goat by code</Text>

              {lookupError && (
                <View style={styles.lookupErrorBox}>
                  <Text style={styles.lookupErrorText}>{lookupError}</Text>
                </View>
              )}

              <TextInput
                style={[styles.input, styles.lookupInput]}
                placeholder="e.g. GC-001"
                placeholderTextColor="#64748B"
                autoCapitalize="characters"
                autoCorrect={false}
                value={lookupCode}
                onChangeText={(value) => {
                  setLookupCode(value);
                  if (lookupError) setLookupError(null);
                }}
                onSubmitEditing={lookupByCode}
              />

              <TouchableOpacity
                style={[styles.fullAddButton, styles.lookupButton]}
                onPress={lookupByCode}
              >
                <Text style={styles.addButtonText}>Look Up</Text>
              </TouchableOpacity>
            </View>
            </ScrollView>}
          </View>
        )}

        {screen === "aiVet" && (
          <KeyboardAvoidingView
            style={styles.aiVetContainer}
            behavior={Platform.OS === "ios" ? "padding" : "height"}
          >
            <ScrollView
              contentContainerStyle={styles.aiVetPage}
              keyboardShouldPersistTaps="handled"
            >
              <Text style={styles.title}>AI Vet Advice</Text>
              <Text style={styles.subtitle}>
                Ask in your preferred language, or attach a photo for advice.
              </Text>
              <Text style={styles.aiDisclaimer}>Temperature: Low below 33.0°C · Normal 33.0–38.5°C · High above 38.5°C</Text>
              <Text style={styles.aiDisclaimer}>Advisory: Urgent Low below 32.0°C · Warning Low 32.0–32.9°C · Normal 33.0–38.5°C · Warning High 38.6–39.5°C · Urgent High above 39.5°C</Text>
              <Text style={styles.aiDisclaimer}>
                AI-generated guidance only — not a medical diagnosis. Gemini may occasionally provide
                inaccurate information. Always confirm with a licensed veterinarian.
              </Text>

              <View style={styles.chatBox}>
                <ScrollView
                  ref={chatScrollRef}
                  contentContainerStyle={styles.chatContent}
                  nestedScrollEnabled
                  showsVerticalScrollIndicator
                >
                  {chatMessages.map((chat, index) => (
                    <View
                      key={index}
                      style={[styles.chatBubble, chat.from === "You" && styles.userBubble]}
                    >
                      <Text style={styles.chatFrom}>{chat.from}</Text>
                      <Text style={styles.chatMessage}>
                        {chat.from === 'You' ? chat.message : chat.message.split(/(\*\*[^*]+\*\*)/g).map((part, partIndex) =>
                          part.startsWith('**') && part.endsWith('**')
                            ? <Text key={partIndex} style={{ fontWeight: '700' }}>{part.slice(2, -2)}</Text>
                            : part
                        )}
                      </Text>
                      {chat.photoUri && <Image source={{ uri: chat.photoUri }} style={{ width: 200, height: 160, maxWidth: '100%', borderRadius: 10, marginTop: 8 }} resizeMode="contain" accessibilityLabel="Photo sent for AI Vet Advice" />}
                    </View>
                  ))}

                  {aiLoading && (
                    <View style={styles.chatBubble}>
                      <Text style={styles.chatFrom}>AI Vet Advice</Text>
                      <View style={styles.loadingRow}>
                        <ActivityIndicator />
                        <Text style={styles.loadingText}>Thinking...</Text>
                      </View>
                    </View>
                  )}
                </ScrollView>
              </View>

              {aiPhoto && <View style={{ marginBottom: 10, flexDirection: 'row', alignItems: 'center', gap: 12 }}>
                <Image source={{ uri: aiPhoto.uri }} style={{ width: 90, height: 90, borderRadius: 10 }} resizeMode="cover" accessibilityLabel="Attached photo preview" />
                <View style={{ flex: 1 }}>
                  <Text style={styles.chatMessage} numberOfLines={1}>{aiPhoto.name}</Text>
                  <TouchableOpacity disabled={aiLoading} accessibilityRole="button" accessibilityLabel="Remove attached photo" onPress={() => setAiPhoto(null)}>
                    <Text style={styles.chatFrom}>Remove photo</Text>
                  </TouchableOpacity>
                </View>
              </View>}
              <View style={styles.chatInputRow}>
                <TextInput
                  style={styles.chatInput}
                  placeholder={aiPhoto ? 'Ask about this photo (optional)…' : 'Ask about your herd...'}
                  placeholderTextColor="#64748B"
                  value={chatInput}
                  onChangeText={setChatInput}
                  multiline
                  {...(Platform.OS === 'web' ? { onPaste: pasteAdvicePhoto } : {})}
                />
                <TouchableOpacity
                  style={[styles.sendButton, { paddingHorizontal: 12 }, (aiLoading || photoBusy) && { opacity: 0.5 }]}
                  accessibilityRole="button"
                  accessibilityLabel="Attach or paste a photo for AI advice"
                  onPress={() => setPhotoMenuOpen(true)}
                  disabled={aiLoading || photoBusy}
                >
                  {photoBusy ? <ActivityIndicator color="#fff" /> : <Ionicons name="image-outline" size={25} color="#fff" />}
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.sendButton, aiLoading && { backgroundColor: "#94A3B8" }]}
                  onPress={sendAiMessage}
                  disabled={aiLoading || photoBusy}
                >
                  <Text style={styles.sendButtonText}>{aiLoading ? "..." : "Send"}</Text>
                </TouchableOpacity>
              </View>

              <Modal visible={photoMenuOpen} transparent animationType="fade" onRequestClose={() => setPhotoMenuOpen(false)}>
                <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 28 }}>
                  <View style={[styles.chatBubble, { padding: 22 }]}>
                    <Text style={styles.sectionTitle}>Attach a photo</Text>
                    <TouchableOpacity accessibilityRole="button" style={styles.promptButton} onPress={() => chooseAdvicePhoto()}>
                      <Ionicons name="images-outline" size={22} color={isDark ? '#55E875' : '#15803D'} /><Text style={styles.promptText}>Choose from photos</Text>
                    </TouchableOpacity>
                    <Text style={styles.aiDisclaimer}>JPG, PNG, or WebP up to 2 MB. Add a question if you like, then press Send.</Text>
                    <TouchableOpacity accessibilityRole="button" onPress={() => setPhotoMenuOpen(false)}><Text style={styles.chatFrom}>Cancel</Text></TouchableOpacity>
                  </View>
                </View>
              </Modal>

              <View style={styles.quickPrompts}>
                <Text style={styles.sectionTitle}>Quick Prompts</Text>
                {[
                  "What is the normal temperature range for goats?",
                  "What should I do for high temperature?",
                  "What does low movement mean?",
                  "What vaccine schedule should I follow?",
                ].map((prompt) => (
                  <TouchableOpacity
                    key={prompt}
                    style={styles.promptButton}
                    onPress={() => setChatInput(prompt)}
                  >
                    <View style={styles.promptIcon}><Ionicons name="thermometer-outline" size={20} color="#55E875" /></View>
                    <Text style={styles.promptText}>{prompt}</Text>
                    <Ionicons name="chevron-forward" size={20} color="#55E875" />
                  </TouchableOpacity>
                ))}
              </View>
            </ScrollView>
          </KeyboardAvoidingView>
        )}

        {screen === "settings" && (
          <ScrollView contentContainerStyle={styles.page}>
            <Text style={styles.title}>More</Text>
            <Text style={styles.subtitle}>
              Signed in to AgriSentry. Alerts: {alerts.length} active.
            </Text>

            <View style={styles.settingsCard}>
              <Text style={styles.cardTitle}>Signed in as</Text>
              <View style={styles.userRow}>
                <View>
                  <Text style={styles.userName}>{user?.name}</Text>
                  <Text style={styles.userEmail}>@{user?.username}</Text>
                </View>
                <View style={styles.userStatus}>
                  <Text style={styles.userStatusText}>{user?.role}</Text>
                </View>
              </View>
            </View>

            <View style={styles.settingsCard}>
              <Text style={styles.cardTitle}>Herd sections</Text>
              <MenuRow label="Collars" desc="Assignment, battery & connectivity" onPress={() => router.push("/collars")} />
              <MenuRow label="Report Analytics" desc="Herd health stats by period" onPress={() => router.push("/reports")} />
              <MenuRow label="Medical Records" desc="All vaccination & treatment history" onPress={() => router.push("/medical-records")} last />
            </View>

            <View style={styles.settingsCard}>
              <Text style={styles.cardTitle}>Appearance</Text>
              <TouchableOpacity style={[styles.menuRow, styles.menuRowLast]} onPress={toggleTheme} accessibilityRole="button" accessibilityLabel={`Switch to ${isDark ? "light" : "dark"} mode`}>
                <View style={styles.menuIcon}>
                  <Ionicons name={isDark ? "moon-outline" : "sunny-outline"} size={24} color="#55E875" />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.menuRowLabel}>{isDark ? "Dark mode" : "Light mode"}</Text>
                  <Text style={styles.menuRowDesc}>Tap to switch to {isDark ? "light" : "dark"} mode</Text>
                </View>
                <View style={[styles.themeToggleTrack, !isDark && styles.themeToggleTrackLight]}>
                  <View style={[styles.themeToggleThumb, !isDark && styles.themeToggleThumbLight]} />
                </View>
              </TouchableOpacity>
            </View>

            <View style={styles.settingsCard}>
              <Text style={styles.cardTitle}>User &amp; role management</Text>
              <Text style={styles.settingsNote}>
                Adding, editing, or removing Admin / Staff / Caretaker accounts is done from the
                AgriSentry web dashboard&apos;s &quot;Manage Users&quot; page.
              </Text>
            </View>

            <TouchableOpacity style={styles.logoutButton} onPress={() => router.push("/account")}><Text style={styles.logoutButtonText}>Profile & password settings</Text></TouchableOpacity>
            <TouchableOpacity style={styles.logoutButton} onPress={confirmLogout}>
              <Text style={styles.logoutButtonText}>Log Out</Text>
            </TouchableOpacity>
          </ScrollView>
        )}
      </View>

      <BottomNav currentScreen={screen} onSelect={setScreen} />

      <AddGoatModal
        visible={addGoatOpen}
        onClose={() => setAddGoatOpen(false)}
        name={newGoatName}
        breed={newGoatBreed}
        sex={newGoatSex}
        age={newGoatAge}
        weight={newGoatWeight}
        owner={newGoatOwner}
        earTag={newGoatEarTag}
        color={newGoatColor}
        collarId={newGoatCollarId}
        setName={setNewGoatName}
        setBreed={setNewGoatBreed}
        setSex={setNewGoatSex}
        setAge={setNewGoatAge}
        setWeight={setNewGoatWeight}
        setOwner={setNewGoatOwner}
        setEarTag={setNewGoatEarTag}
        setColor={setNewGoatColor}
        setCollarId={setNewGoatCollarId}
        onSave={addGoat}
        saving={savingGoat}
      />

      <Modal visible={notificationsOpen} transparent animationType="slide" onRequestClose={() => setNotificationsOpen(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.notificationPanel}>
            <View style={styles.notificationHeader}>
              <View>
                <Text style={styles.modalTitle}>Notifications</Text>
                <Text style={styles.notificationSubtitle}>{alerts.length} health alert{alerts.length === 1 ? "" : "s"}</Text>
              </View>
              <TouchableOpacity style={styles.notificationClose} onPress={() => setNotificationsOpen(false)} accessibilityLabel="Close notifications">
                <Ionicons name="close" size={24} color={isDark ? "#F8FAFC" : "#10231B"} />
              </TouchableOpacity>
            </View>
            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.notificationList}>
              {alerts.length === 0 ? (
                <View style={styles.notificationEmpty}>
                  <Ionicons name="checkmark-circle-outline" size={46} color="#16A34A" />
                  <Text style={styles.placeholderTitle}>No active alerts</Text>
                  <Text style={styles.placeholderText}>The herd has no notifications right now.</Text>
                </View>
              ) : alerts.map((alert) => {
                const urgent = String(alert.severity || "").toLowerCase() === "urgent" || String(alert.severity || "").toLowerCase() === "high";
                return (
                  <TouchableOpacity
                    key={alert.id}
                    style={[styles.notificationItem, urgent && styles.notificationItemUrgent]}
                    activeOpacity={alert.goat_id ? 0.75 : 1}
                    onPress={() => {
                      if (!alert.goat_id) return;
                      setNotificationsOpen(false);
                      router.push(`/goat/${alert.goat_id}`);
                    }}
                  >
                    <View style={[styles.notificationSeverityDot, { backgroundColor: urgent ? "#DC2626" : "#F59E0B" }]} />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.notificationTitle}>{alert.alert_type || "Health alert"}</Text>
                      <Text style={styles.notificationMeta}>
                        {alert.goat ? `${alert.goat.name} (${alert.goat.code})` : `Goat #${alert.goat_id}`} · {alert.severity || "Alert"}
                      </Text>
                      {alert.message ? <Text style={styles.notificationMessage}>{alert.message}</Text> : null}
                      {alert.recommendation ? <Text style={styles.notificationAdvice}>Recommended: {alert.recommendation}</Text> : null}
                      <Text style={styles.notificationTime}>{new Date(alert.created_at).toLocaleString()}</Text>
                    </View>
                    {alert.goat_id ? <Ionicons name="chevron-forward" size={18} color="#8290A5" /> : null}
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

function BottomNav({
  currentScreen,
  onSelect,
}: {
  currentScreen: ScreenName;
  onSelect: (screen: ScreenName) => void;
}) {
  const { isDark } = useAppTheme();
  const navIcons: Record<ScreenName, keyof typeof Ionicons.glyphMap> = {
    dashboard: "home-outline",
    healthLogs: "clipboard-outline",
    qrScanner: "qr-code-outline",
    aiVet: "hardware-chip-outline",
    settings: "settings-outline",
  };
  const items: { label: string; screen: ScreenName; icon: string }[] = [
    { label: "Home", screen: "dashboard", icon: "🏠" },
    { label: "Logs", screen: "healthLogs", icon: "📋" },
    { label: "QR", screen: "qrScanner", icon: "▣" },
    { label: "AI", screen: "aiVet", icon: "🤖" },
    { label: "More", screen: "settings", icon: "⚙️" },
  ];

  return (
    <View style={styles.bottomNav}>
      {items.map((item) => {
        const active = currentScreen === item.screen;
        return (
          <TouchableOpacity
            key={item.screen}
            style={styles.bottomNavItem}
            onPress={() => onSelect(item.screen)}
          >
            <View style={[styles.bottomIconWrap, item.screen === "qrScanner" && styles.qrNavCircle, active && styles.bottomIconActive]}>
              <Ionicons name={navIcons[item.screen]} size={item.screen === "qrScanner" ? 28 : 24} color={active ? (isDark ? "#55E875" : "#15803D") : (isDark ? "#8290A5" : "#52625A")} />
            </View>
            <Text style={[styles.bottomNavText, active && styles.bottomNavTextActive]}>
              {item.label}
            </Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

function GoatTile({ goat, onView }: { goat: Goat; onView: () => void }) {
  const statusColor =
    goat.status === "Urgent" ? "#DC2626" : goat.status === "Warning" || goat.status === "Monitoring" ? "#F59E0B" : "#16A34A";
  const statusBg =
    goat.status === "Urgent" ? "#3A151B" : goat.status === "Warning" || goat.status === "Monitoring" ? "#33260C" : "#07331F";

  return (
    <View style={[styles.goatTile, goat.status === "Urgent" && styles.urgentTile]}>
      {goat.status === "Urgent" && (
        <View style={styles.urgentBanner}>
          <Text style={styles.urgentBannerText}>URGENT ALERT</Text>
        </View>
      )}

      <View style={styles.tileTop}>
        <Image source={require("@/assets/images/goat-3d-v2.png")} style={styles.goatAvatar} resizeMode="contain" />
        <View style={{ flex: 1 }}>
          <Text style={styles.goatName}>{goat.name}</Text>
          <Text style={styles.goatCode}>
            {goat.code} • {goat.breed || "N/A"}
          </Text>
        </View>
        <View style={[styles.statusBadge, { backgroundColor: statusBg }]}>
          <Text style={[styles.statusText, { color: statusColor }]}>{goat.status}</Text>
        </View>
      </View>

      <View style={styles.metricRow}>
        <View style={styles.metricBox}>
          <Text style={styles.metricLabel}>Temperature</Text>
          <Text style={[styles.metricValue, { color: tempBandColor(goat.temperature, statusColor) }]}>
            {goat.temperature ?? "N/A"}°C
          </Text>
        </View>
        <View style={styles.metricBox}>
          <Text style={styles.metricLabel}>Movement Detector</Text>
          <Text style={styles.metricValue}>{goat.movement || "N/A"}</Text>
        </View>
      </View>

      {goat.alert_reason ? (
        <Text style={styles.alertReason}>{goat.alert_reason}</Text>
      ) : null}

      <View style={styles.tileBottom}>
        <Text style={styles.smallInfo}>Collar: {goat.collar?.collar_code || "Not assigned"}</Text>
        <BatteryStatus level={goat.collar?.battery_level ?? goat.battery} lastSeen={goat.collar?.last_seen} />
      </View>

      <TouchableOpacity style={styles.viewButton} onPress={onView}>
        <Text style={styles.viewButtonText}>View Animal Profile</Text>
      </TouchableOpacity>
    </View>
  );
}

function SummaryCard({
  label,
  value,
  color = "#F8FAFC",
}: {
  label: string;
  value: string;
  color?: string;
}) {
  const { isDark } = useAppTheme();
  const valueColor = !isDark && color === "#F8FAFC" ? "#10231B" : color;
  return (
    <View style={styles.summaryCard}>
      <View style={[styles.summaryIcon, { borderColor: color === "#F8FAFC" ? "#237A46" : color }]}>
        <Ionicons
          name={label === "Total Goats" ? "paw" : label === "Urgent Alerts" ? "notifications" : label === "Warning" ? "pulse" : "shield-checkmark"}
          size={27}
          color={color === "#F8FAFC" ? (isDark ? "#55E875" : "#15803D") : color}
        />
      </View>
      <View>
        <Text style={styles.summaryLabel}>{label}</Text>
        <Text style={[styles.summaryValue, { color: valueColor }]}>{value}</Text>
      </View>
    </View>
  );
}

function MenuRow({
  label,
  desc,
  onPress,
  last = false,
}: {
  label: string;
  desc: string;
  onPress: () => void;
  last?: boolean;
}) {
  const { isDark } = useAppTheme();
  return (
    <TouchableOpacity
      style={[styles.menuRow, last && styles.menuRowLast]}
      onPress={onPress}
    >
      <View style={styles.menuIcon}>
        <Ionicons name={label === "Collars" ? "hardware-chip-outline" : label === "Report Analytics" ? "bar-chart-outline" : "medkit-outline"} size={24} color={label === "Report Analytics" ? "#B66A00" : label === "Medical Records" ? "#2563EB" : isDark ? "#55E875" : "#15803D"} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={styles.menuRowLabel}>{label}</Text>
        <Text style={styles.menuRowDesc}>{desc}</Text>
      </View>
      <Text style={styles.menuRowArrow}>›</Text>
    </TouchableOpacity>
  );
}

function AddGoatModal({
  visible,
  onClose,
  name,
  breed,
  sex,
  age,
  weight,
  owner,
  earTag,
  color,
  collarId,
  setName,
  setBreed,
  setSex,
  setAge,
  setWeight,
  setOwner,
  setEarTag,
  setColor,
  setCollarId,
  onSave,
  saving,
}: {
  visible: boolean;
  onClose: () => void;
  name: string;
  breed: string;
  sex: string;
  age: string;
  weight: string;
  owner: string;
  earTag: string;
  color: string;
  collarId: string;
  setName: (value: string) => void;
  setBreed: (value: string) => void;
  setSex: (value: string) => void;
  setAge: (value: string) => void;
  setWeight: (value: string) => void;
  setOwner: (value: string) => void;
  setEarTag: (value: string) => void;
  setColor: (value: string) => void;
  setCollarId: (value: string) => void;
  onSave: () => void;
  saving: boolean;
}) {
  const [breedOpen, setBreedOpen] = useState(false);
  const [sexOpen, setSexOpen] = useState(false);
  return (
    <Modal visible={visible} transparent animationType="slide">
      <View style={styles.modalOverlay}>
        <ScrollView style={[styles.formModal,{maxHeight:"90%"}]} keyboardShouldPersistTaps="handled">
          <Text style={styles.modalTitle}>Add Goat</Text>
          <Text style={styles.modalSubtitle}>Register a new animal in the herd</Text>

          <Text style={styles.formLabel}>Name <Text style={styles.requiredMark}>*</Text></Text>
          <TextInput style={styles.input} placeholder="e.g. Bituin" placeholderTextColor="#64748B" value={name} onChangeText={setName} />

          <Text style={styles.formLabel}>Breed</Text>
          <TouchableOpacity style={styles.selectInput} onPress={()=>{setBreedOpen(!breedOpen);setSexOpen(false);}} accessibilityRole="button" accessibilityLabel="Select breed">
            <Text style={breed ? styles.selectText : styles.selectPlaceholder}>{breed || "Select breed"}</Text>
            <Text style={styles.selectArrow}>▾</Text>
          </TouchableOpacity>
          {breedOpen && <View style={styles.selectOptions}>{['Boer','Anglo-Nubian','Saanen','Alpine','Toggenburg','Native','Crossbreed','Other'].map(value=><TouchableOpacity key={value} style={styles.selectOption} onPress={()=>{setBreed(value);setBreedOpen(false);}}><Text style={styles.selectOptionText}>{value}</Text></TouchableOpacity>)}</View>}

          <Text style={styles.formLabel}>Sex</Text>
          <TouchableOpacity style={styles.selectInput} onPress={()=>{setSexOpen(!sexOpen);setBreedOpen(false);}} accessibilityRole="button" accessibilityLabel="Select sex">
            <Text style={sex ? styles.selectText : styles.selectPlaceholder}>{sex || "— Select —"}</Text>
            <Text style={styles.selectArrow}>▾</Text>
          </TouchableOpacity>
          {sexOpen && <View style={styles.selectOptions}>{['Male','Female'].map(value=><TouchableOpacity key={value} style={styles.selectOption} onPress={()=>{setSex(value);setSexOpen(false);}}><Text style={styles.selectOptionText}>{value}</Text></TouchableOpacity>)}</View>}

          <Text style={styles.formLabel}>Age</Text>
          <TextInput style={styles.input} placeholder="e.g. 2 years" placeholderTextColor="#64748B" value={age} onChangeText={setAge} />

          <Text style={styles.formLabel}>Weight</Text>
          <TextInput style={styles.input} placeholder="e.g. 34 kg" placeholderTextColor="#64748B" value={weight} onChangeText={setWeight} />

          <Text style={styles.formLabel}>Owner</Text>
          <TextInput style={styles.input} placeholder="Owner name" placeholderTextColor="#64748B" value={owner} onChangeText={setOwner} />

          <Text style={styles.formLabel}>Ear Tag <Text style={styles.requiredMark}>*</Text></Text>
          <TextInput style={styles.input} placeholder="e.g. GT-014 or 014" placeholderTextColor="#64748B" value={earTag} onChangeText={setEarTag} maxLength={100} autoCapitalize="characters" />
          <Text style={styles.formHint}>This is also the Goat ID. Enter it only once.</Text>

          <Text style={styles.formLabel}>Goat Color</Text>
          <TextInput style={styles.input} placeholder="Goat color" placeholderTextColor="#64748B" value={color} onChangeText={setColor} maxLength={100} />

          <Text style={styles.formLabel}>Collar ID</Text>
          <TextInput style={styles.input} placeholder="e.g. COLLAR-014" placeholderTextColor="#64748B" value={collarId} onChangeText={setCollarId} />

          <View style={styles.modalActions}>
            <TouchableOpacity style={styles.cancelButton} onPress={onClose} disabled={saving}>
              <Text style={styles.cancelText}>Cancel</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.saveButton} onPress={onSave} disabled={saving}>
              {saving ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.saveButtonText}>Save Goat</Text>
              )}
            </TouchableOpacity>
          </View>
        </ScrollView>
      </View>
    </Modal>
  );
}

// Declared once at module scope so CameraView always receives the same object
// reference — a fresh object on every render can make the native camera
// session keep reconfiguring itself instead of settling into a live preview.
const QR_BARCODE_SETTINGS: BarcodeSettings = { barcodeTypes: ["qr"] };

function resolveGoatIdFromScan(data: string, goats: Goat[]): number | null {
  // Goat QR codes encode a link to the web profile, e.g. https://host/goat/5/profile
  const urlMatch = data.match(/\/goat\/(\d+)\/profile/);
  if (urlMatch) return Number(urlMatch[1]);

  // Fall back to treating the scanned text as a goat code, in case someone
  // scans a printed code instead of the QR image.
  const byCode = goats.find((g) => String(g.code || "").toLowerCase() === data.trim().toLowerCase());
  return byCode ? byCode.id : null;
}

function QrScannerPanel({
  active,
  goats,
  onFound,
}: {
  active: boolean;
  goats: Goat[];
  onFound: (goatId: number) => void;
}) {
  const [permission, requestPermission] = useCameraPermissions();
  const [scanned, setScanned] = useState(false);
  const [scanError, setScanError] = useState<string | null>(null);

  const handleScan = (result: BarcodeScanningResult) => {
    if (scanned) return;
    setScanned(true);

    const goatId = resolveGoatIdFromScan(result.data, goats);
    if (goatId) {
      setScanError(null);
      onFound(goatId);
    } else {
      setScanError("That QR code isn't a recognized AgriSentry goat tag.");
    }
  };

  if (!active) return null;

  if (!permission) {
    return (
      <View style={styles.cameraCard}>
        <ActivityIndicator color="#16A34A" />
      </View>
    );
  }

  if (!permission.granted) {
    return (
      <View style={styles.cameraCard}>
        <Text style={styles.placeholderIcon}>📷</Text>
        <Text style={styles.placeholderTitle}>Camera access needed</Text>
        <Text style={styles.placeholderText}>
          AgriSentry uses your camera to scan a goat&apos;s QR tag.
        </Text>
        <TouchableOpacity style={styles.fullAddButton} onPress={requestPermission}>
          <Text style={styles.addButtonText}>Grant Camera Permission</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.cameraWrap}>
      <CameraView
        style={styles.camera}
        facing="back"
        barcodeScannerSettings={QR_BARCODE_SETTINGS}
        onBarcodeScanned={scanned ? undefined : handleScan}
      />
      <View style={styles.cameraFrame} pointerEvents="none" />

      {scanError && (
        <View style={styles.scanErrorBanner}>
          <Text style={styles.lookupErrorText}>{scanError}</Text>
        </View>
      )}

      {scanned && (
        <TouchableOpacity
          style={styles.scanAgainButton}
          onPress={() => {
            setScanned(false);
            setScanError(null);
          }}
        >
          <Text style={styles.addButtonText}>Scan Again</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const darkStyles = StyleSheet.create({
  safeArea: {
    flex: 1,
    width: "100%",
    maxWidth: Platform.OS === "web" ? 440 : undefined,
    alignSelf: "center",
    backgroundColor: "#020B11",
    borderLeftWidth: Platform.OS === "web" ? 1 : 0,
    borderRightWidth: Platform.OS === "web" ? 1 : 0,
    borderColor: "#1B2933",
  },
  centerFill: { alignItems: "center", justifyContent: "center" },
  contentArea: { flex: 1, paddingBottom: 88 },
  header: {
    backgroundColor: "#032A1D",
    marginHorizontal: 12,
    marginTop: 8,
    paddingHorizontal: 16,
    minHeight: 102,
    paddingVertical: 14,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: "#11683C",
    overflow: "hidden",
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
  },
  headerLogoBadge: {
    width: 68,
    height: 68,
    borderRadius: 18,
    backgroundColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
    padding: 6,
  },
  headerLogoImage: { width: "100%", height: "100%" },
  logo: { color: "#FFFFFF", fontSize: 28, fontWeight: "800" },
  headerSubtitle: { color: "#55E875", fontSize: 14, marginTop: 2 },
  notificationWrap: { marginLeft: "auto", position: "relative", padding: 8 },
  themeButton: { width: 42, height: 42, borderRadius: 14, alignItems: "center", justifyContent: "center", borderWidth: 1, borderColor: "#11683C", backgroundColor: "#08251A" },
  themeToggleTrack: { width: 48, height: 28, borderRadius: 14, padding: 3, justifyContent: "center", backgroundColor: "#0B4928", borderWidth: 1, borderColor: "#257446" },
  themeToggleTrackLight: { backgroundColor: "#16A34A" },
  themeToggleThumb: { width: 20, height: 20, borderRadius: 10, backgroundColor: "#FFFFFF" },
  themeToggleThumbLight: { alignSelf: "flex-end" },
  notificationBadge: { position: "absolute", right: 0, top: 0, minWidth: 20, height: 20, borderRadius: 10, backgroundColor: "#EF2D2D", color: "#FFFFFF", textAlign: "center", fontSize: 11, fontWeight: "800", lineHeight: 20 },
  notificationPanel: { maxHeight: "82%", backgroundColor: "#09141D", borderTopLeftRadius: 24, borderTopRightRadius: 24, borderWidth: 1, borderColor: "#2A3642", paddingTop: 20 },
  notificationHeader: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: 20, paddingBottom: 14, borderBottomWidth: 1, borderBottomColor: "#2A3642" },
  notificationSubtitle: { color: "#98A3B7", fontSize: 12, marginTop: -10 },
  notificationClose: { width: 40, height: 40, borderRadius: 13, alignItems: "center", justifyContent: "center", borderWidth: 1, borderColor: "#2A3642" },
  notificationList: { padding: 14, paddingBottom: 30 },
  notificationEmpty: { minHeight: 220, alignItems: "center", justifyContent: "center", gap: 8 },
  notificationItem: { flexDirection: "row", alignItems: "flex-start", gap: 11, padding: 14, borderWidth: 1, borderColor: "#2A3642", backgroundColor: "#07121A", borderRadius: 16, marginBottom: 10 },
  notificationItemUrgent: { borderColor: "#6B2A30" },
  notificationSeverityDot: { width: 10, height: 10, borderRadius: 5, marginTop: 5 },
  notificationTitle: { color: "#F8FAFC", fontSize: 15, fontWeight: "800" },
  notificationMeta: { color: "#98A3B7", fontSize: 11, fontWeight: "700", marginTop: 3 },
  notificationMessage: { color: "#A6B0C1", fontSize: 13, lineHeight: 19, marginTop: 8 },
  notificationAdvice: { color: "#55E875", fontSize: 12, lineHeight: 18, marginTop: 8 },
  notificationTime: { color: "#748198", fontSize: 10, marginTop: 8 },
  page: { padding: 20, paddingBottom: 145 },
  heroIntro: { minHeight: 138, justifyContent: "center", position: "relative", overflow: "hidden" },
  heroGoats: { position: "absolute", width: 190, height: 130, right: -25, bottom: -8, opacity: 0.36 },
  qrScreen: { flex: 1 },
  qrTopFixed: { padding: 18, paddingBottom: 0 },
  title: { fontSize: 28, fontWeight: "800", color: "#F8FAFC" },
  subtitle: { color: "#98A3B7", fontSize: 15, marginTop: 6, marginBottom: 20, lineHeight: 23 },
  aiDisclaimer: { color: "#A6B0C1", fontSize: 12, lineHeight: 18, marginTop: -8, marginBottom: 18, padding: 14, backgroundColor: "#07121A", borderWidth: 1, borderColor: "#294033", borderRadius: 16 },
  errorBanner: {
    backgroundColor: "#FEE2E2",
    borderRadius: 14,
    padding: 14,
    marginBottom: 16,
  },
  errorBannerText: { color: "#B91C1C", fontSize: 13, fontWeight: "600" },
  fullAddButton: {
    backgroundColor: "#087B37",
    paddingVertical: 14,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#45DB6B",
    alignItems: "center",
    marginBottom: 18,
  },
  addButtonText: { color: "#FFFFFF", fontWeight: "800", fontSize: 13 },
  summaryGrid: { flexDirection: "row", flexWrap: "wrap", justifyContent: "space-between", marginBottom: 22 },
  summaryCard: {
    width: "48%",
    flexDirection: "row",
    alignItems: "center",
    gap: 13,
    backgroundColor: "#09141D",
    borderRadius: 18,
    minHeight: 94,
    padding: 13,
    borderWidth: 1,
    borderColor: "#2A3642",
    marginBottom: 12,
  },
  summaryIcon: { width: 52, height: 52, borderRadius: 16, backgroundColor: "#08251A", borderWidth: 1, alignItems: "center", justifyContent: "center" },
  summaryLabel: { color: "#98A3B7", fontSize: 13, marginBottom: 3 },
  summaryValue: { fontSize: 27, fontWeight: "800" },
  sectionTitle: { fontSize: 22, fontWeight: "800", color: "#F8FAFC", marginBottom: 14 },
  emptyText: { color: "#64748B", fontSize: 14, marginBottom: 16 },
  goatTile: {
    backgroundColor: "#07121A",
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: "#2B3742",
    marginBottom: 16,
  },
  urgentTile: { borderColor: "#F04444", backgroundColor: "#0C1117", shadowColor: "#EF4444", shadowOpacity: .25, shadowRadius: 12 },
  urgentBanner: {
    backgroundColor: "#DC2626",
    alignSelf: "flex-start",
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
    marginBottom: 12,
  },
  urgentBannerText: { color: "#FFFFFF", fontSize: 11, fontWeight: "800" },
  tileTop: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", gap: 12, marginBottom: 14 },
  goatAvatar: { width: 64, height: 64, borderRadius: 32, backgroundColor: "#10221B" },
  goatName: { fontSize: 23, fontWeight: "800", color: "#F8FAFC" },
  goatCode: { color: "#98A3B7", fontSize: 13, marginTop: 4 },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  statusText: { fontSize: 12, fontWeight: "800" },
  metricRow: { flexDirection: "row", gap: 10, marginBottom: 12 },
  metricBox: { flex: 1, backgroundColor: "#0A151E", borderWidth: 1, borderColor: "#2A3642", borderRadius: 14, padding: 13 },
  metricLabel: { color: "#98A3B7", fontSize: 12, marginBottom: 7 },
  metricValue: { color: "#F8FAFC", fontSize: 16, fontWeight: "800" },
  alertReason: { color: "#F05252", fontSize: 13, marginBottom: 12, lineHeight: 19 },
  tileBottom: { marginBottom: 12 },
  smallInfo: { fontSize: 12, color: "#98A3B7", marginBottom: 3 },
  viewButton: { backgroundColor: "#0B4928", paddingVertical: 13, borderRadius: 14, alignItems: "center", borderWidth:1, borderColor:"#257446" },
  viewButtonText: { color: "#FFFFFF", fontWeight: "800" },
  modalOverlay: { flex: 1, backgroundColor: "rgba(15, 23, 42, 0.45)", justifyContent: "flex-end" },
  bottomNav: {
    position: "absolute",
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: "#07121A",
    borderTopWidth: 1,
    borderTopColor: "#2B3742",
    flexDirection: "row",
    justifyContent: "space-around",
    minHeight: 88,
    paddingTop: 14,
    paddingBottom: 14,
    marginHorizontal: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: "#2B3742",
    borderRadius: 28,
  },
  bottomNavItem: { alignItems: "center", justifyContent: "center", flex: 1 },
  bottomNavIcon: { fontSize: 18, marginBottom: 3 },
  bottomIconWrap: { width: 38, height: 32, alignItems:"center", justifyContent:"center", borderRadius:18 },
  qrNavCircle: { width:58, height:58, marginTop:-28, borderRadius:29, backgroundColor:"#063B22", borderWidth:1, borderColor:"#32D45C" },
  bottomIconActive: { backgroundColor:"rgba(34,197,94,.12)" },
  bottomNavText: { fontSize: 12, color: "#8290A5", fontWeight: "700", marginTop: 4 },
  bottomNavTextActive: { color: "#55E875" },
  formModal: { backgroundColor: "#09141D", borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 20, borderWidth: 1, borderColor: "#2A3642" },
  modalTitle: { fontSize: 22, fontWeight: "800", color: "#F8FAFC", marginBottom: 4 },
  modalSubtitle: { color: "#98A3B7", fontSize: 13, marginBottom: 18 },
  formLabel: { color: "#F8FAFC", fontSize: 14, fontWeight: "700", marginBottom: 7 },
  requiredMark: { color: "#EF4444" },
  formHint: { color: "#98A3B7", fontSize: 12, marginTop: -5, marginBottom: 14 },
  selectInput: { minHeight: 50, flexDirection: "row", alignItems: "center", justifyContent: "space-between", backgroundColor: "#07121A", borderWidth: 1, borderColor: "#2A3642", borderRadius: 14, paddingHorizontal: 14, marginBottom: 12 },
  selectTextInput: { flex: 1, color: "#F8FAFC", fontSize: 15 },
  selectText: { color: "#F8FAFC", fontSize: 15 },
  selectPlaceholder: { color: "#64748B", fontSize: 15 },
  selectArrow: { color: "#98A3B7", fontSize: 18, marginLeft: 10 },
  selectOptions: { backgroundColor: "#07121A", borderWidth: 1, borderColor: "#2A3642", borderRadius: 12, marginTop: -6, marginBottom: 12, overflow: "hidden" },
  selectOption: { paddingHorizontal: 14, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: "#26323D" },
  selectOptionText: { color: "#F8FAFC", fontSize: 15 },
  input: {
    backgroundColor: "#07121A",
    borderWidth: 1,
    borderColor: "#2A3642",
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 13,
    marginBottom: 12,
    fontSize: 15,
    color: "#F8FAFC",
  },
  modalActions: { flexDirection: "row", gap: 10, marginTop: 4 },
  cancelButton: { flex: 1, paddingVertical: 13, borderRadius: 14, borderWidth: 1, borderColor: "#3A4754", alignItems: "center" },
  cancelText: { color: "#F8FAFC", fontWeight: "800" },
  saveButton: { flex: 1, backgroundColor: "#087B37", paddingVertical: 13, borderRadius: 14, alignItems: "center", borderWidth: 1, borderColor: "#45DB6B" },
  saveButtonText: { color: "#FFFFFF", fontWeight: "800" },
  logsCard: { backgroundColor: "#07121A", borderRadius: 20, borderWidth: 1, borderColor: "#2A3642", padding: 16 },
  logItem: { flexDirection: "row", gap: 12, borderBottomWidth: 1, borderBottomColor: "#26323D", paddingVertical: 18 },
  logDot: { width: 12, height: 12, borderRadius: 6, marginTop: 5 },
  logTitle: { fontSize: 15, fontWeight: "800", color: "#F8FAFC", marginBottom: 7 },
  logText: { color: "#A6B0C1", fontSize: 13, lineHeight: 20 },
  logTimestamp: { color: "#748198", fontSize: 11, marginTop: 6 },
  placeholderCard: { backgroundColor: "#07121A", borderRadius: 20, padding: 22, borderWidth: 1, borderColor: "#2A3642", alignItems: "center" },
  placeholderIcon: { fontSize: 60, color: "#16A34A", marginBottom: 12 },
  placeholderTitle: { fontSize: 20, fontWeight: "800", color: "#F8FAFC", marginBottom: 8 },
  placeholderText: { color: "#98A3B7", textAlign: "center", lineHeight: 21 },
  lookupInput: { width: "100%", marginTop: 4, textAlign: "center" },
  lookupButton: { width: "100%", marginBottom: 0 },
  lookupErrorBox: { backgroundColor: "#FEE2E2", borderRadius: 12, padding: 10, marginBottom: 12, width: "100%" },
  lookupErrorText: { color: "#B91C1C", fontSize: 13, fontWeight: "600", textAlign: "center" },
  dividerRow: { flexDirection: "row", alignItems: "center", marginVertical: 18, gap: 10 },
  dividerLine: { flex: 1, height: 1, backgroundColor: "#34414E" },
  dividerText: { color: "#55E875", fontSize: 12, fontWeight: "800" },
  cameraCard: {
    backgroundColor: "#07121A",
    borderRadius: 20,
    padding: 22,
    borderWidth: 1,
    borderColor: "#2A3642",
    alignItems: "center",
  },
  cameraWrap: {
    // No borderRadius/overflow:hidden here — clipping a native camera
    // SurfaceView on Android is a known cause of a black preview.
    height: 320,
    backgroundColor: "#111827",
    position: "relative",
  },
  camera: { flex: 1 },
  cameraFrame: {
    position: "absolute",
    top: "20%",
    left: "20%",
    right: "20%",
    bottom: "20%",
    borderWidth: 3,
    borderColor: "#16A34A",
    borderRadius: 20,
  },
  scanErrorBanner: {
    position: "absolute",
    bottom: 12,
    left: 12,
    right: 12,
    backgroundColor: "#FEE2E2",
    borderRadius: 12,
    padding: 10,
  },
  scanAgainButton: {
    position: "absolute",
    bottom: 12,
    alignSelf: "center",
    backgroundColor: "#16A34A",
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 999,
  },
  aiVetContainer: { flex: 1 },
  aiVetPage: { padding: 18, paddingBottom: 120 },
  chatBox: { backgroundColor: "#07121A", borderRadius: 20, borderWidth: 1, borderColor: "#2A3642", height: 340, marginBottom: 12, overflow: "hidden" },
  chatContent: { padding: 14, paddingBottom: 20 },
  chatBubble: { backgroundColor: "#0A151E", borderRadius: 14, padding: 14, marginBottom: 10, borderWidth: 1, borderColor: "#2A3642" },
  userBubble: { backgroundColor: "#07331F", borderColor: "#207844" },
  chatFrom: { fontSize: 12, fontWeight: "800", color: "#55E875", marginBottom: 4 },
  chatMessage: { color: "#E7ECF3", lineHeight: 20 },
  chatInputRow: { flexDirection: "row", gap: 10, marginBottom: 18 },
  chatInput: {
    flex: 1,
    backgroundColor: "#07121A",
    borderWidth: 1,
    borderColor: "#2A3642",
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    minHeight: 48,
    maxHeight: 100,
    color: "#F8FAFC",
  },
  sendButton: { backgroundColor: "#087B37", paddingHorizontal: 18, justifyContent: "center", borderRadius: 14, borderWidth: 1, borderColor: "#45DB6B" },
  sendButtonText: { color: "#FFFFFF", fontWeight: "800" },
  loadingRow: { flexDirection: "row", alignItems: "center", gap: 8 },
  loadingText: { color: "#98A3B7", fontWeight: "600" },
  quickPrompts: { backgroundColor: "#07121A", borderRadius: 20, padding: 16, borderWidth: 1, borderColor: "#2A3642" },
  promptButton: { flexDirection: "row", alignItems: "center", gap: 12, borderWidth: 1, borderColor: "#2A3642", backgroundColor: "#09141D", borderRadius: 14, padding: 13, marginBottom: 10 },
  promptIcon: { width: 38, height: 38, borderRadius: 19, backgroundColor: "#09271B", alignItems: "center", justifyContent: "center" },
  promptText: { color: "#E7ECF3", fontWeight: "600", flex: 1 },
  settingsCard: { backgroundColor: "#07121A", borderRadius: 20, padding: 18, borderWidth: 1, borderColor: "#2A3642", marginBottom: 16 },
  cardTitle: { fontSize: 17, fontWeight: "800", color: "#F8FAFC", marginBottom: 12 },
  settingsNote: { color: "#98A3B7", fontSize: 13, lineHeight: 20 },
  menuRow: {
    flexDirection: "row",
    gap: 12,
    justifyContent: "space-between",
    alignItems: "center",
    paddingVertical: 13,
    borderBottomWidth: 1,
    borderBottomColor: "#26323D",
  },
  menuIcon: { width: 46, height: 46, borderRadius: 14, backgroundColor: "#0A2119", borderWidth: 1, borderColor: "#214531", alignItems: "center", justifyContent: "center" },
  menuRowLast: { borderBottomWidth: 0 },
  menuRowLabel: { fontSize: 15, fontWeight: "700", color: "#F8FAFC" },
  menuRowDesc: { fontSize: 12, color: "#98A3B7", marginTop: 2 },
  menuRowArrow: { fontSize: 22, color: "#8290A5" },
  userRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  userName: { fontWeight: "800", color: "#F8FAFC", fontSize: 17 },
  userEmail: { color: "#98A3B7", fontSize: 13, marginTop: 2 },
  userStatus: { backgroundColor: "#07331F", paddingHorizontal: 12, paddingVertical: 6, borderRadius: 999, borderWidth: 1, borderColor: "#207844" },
  userStatusText: { fontSize: 12, fontWeight: "800", color: "#55E875" },
  logoutButton: {
    borderWidth: 1,
    borderColor: "#EF4444",
    backgroundColor: "#160D11",
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: "center",
  },
  logoutButtonText: { color: "#DC2626", fontWeight: "800" },
});

const LIGHT_COLORS: Record<string, string> = {
  "#020B11": "#F4F7F5", "#032A1D": "#E7F7ED", "#07121A": "#FFFFFF",
  "#09141D": "#FFFFFF", "#0A151E": "#F7FAF8", "#0C1117": "#FFF7F7",
  "#1B2933": "#DCE5E0", "#2A3642": "#DCE5E0", "#2B3742": "#DCE5E0",
  "#26323D": "#E2E8E4", "#294033": "#D5E4DA", "#34414E": "#DCE5E0",
  "#3A4754": "#C9D5CE", "#F8FAFC": "#10231B", "#98A3B7": "#5F6F67",
  "#A6B0C1": "#52625A", "#8290A5": "#66766E", "#748198": "#687870",
  "#55E875": "#15803D", "#10221B": "#E7F7ED", "#08251A": "#E1F5E8",
  "#E7ECF3": "#26362E", "#09271B": "#E1F5E8", "#94A3B8": "#64748B",
  "#0A2119": "#E7F7ED", "#214531": "#BBDCC7", "#07331F": "#DCFCE7",
  "#063B22": "#DCFCE7", "#0B4928": "#15803D", "#160D11": "#FFF1F2", "#06311F": "#DCFCE7", "#082A1B": "#DCFCE7",
};

function createLightStyles(source: typeof darkStyles): typeof darkStyles {
  const mapped = Object.fromEntries(Object.entries(source).map(([name, value]) => {
    const flat = StyleSheet.flatten(value) as Record<string, unknown>;
    const next = Object.fromEntries(Object.entries(flat).map(([key, item]) => [key, typeof item === "string" ? (key === "color" ? lightTextColor(item, name) : (LIGHT_COLORS[item.toUpperCase()] || item)) : item]));
    return [name, next];
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
const lightStyles = createLightStyles(darkStyles);
let styles = darkStyles;
