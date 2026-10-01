import React from "react";
import { Image, ScrollView, StatusBar, StyleSheet, Text, TouchableOpacity, useWindowDimensions, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { SafeAreaView } from "react-native-safe-area-context";
import { useRouter } from "expo-router";
import TrustBar from "@/components/TrustBar";
import { useAuth } from "@/contexts/auth-context";

const SPECIES = [
  { key: "goat", art: require("@/assets/images/goat-hero-clear.png"), name: "Goat", desc: "ANMPC herd monitoring", enabled: true },
  { key: "pig", art: require("@/assets/images/pig-hero-clear.png"), name: "Pig", desc: "Future improvement", enabled: false },
  { key: "cow", art: require("@/assets/images/cow-hero-clear.png"), name: "Cow", desc: "Future improvement", enabled: false },
];

export default function SpeciesSelectScreen() {
  const router = useRouter();
  const { logout } = useAuth();
  const { width } = useWindowDimensions();
  const desktop = width >= 720;

  return (
    <View style={styles.safeArea}>
      <Image source={require("@/assets/images/farm-hero-v2.png")} style={StyleSheet.absoluteFillObject} resizeMode="cover" />
      <View style={styles.photoOverlay} pointerEvents="none" />
      <SafeAreaView style={styles.flex}>
        <StatusBar barStyle="dark-content" />
        <ScrollView
          style={styles.scrollView}
          contentContainerStyle={[styles.scroll, desktop && styles.scrollDesktop]}
          showsVerticalScrollIndicator={false}
        >
          <View style={[styles.card, desktop && styles.cardDesktop]}>
            <TouchableOpacity accessibilityRole="button" onPress={() => void logout()} style={{ alignSelf: 'center', flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 12, marginBottom: 12 }}>
              <Ionicons name="arrow-back" size={20} color="#16833B" />
              <Text style={{ color: '#16833B', fontWeight: '700' }}>Back to sign in</Text>
            </TouchableOpacity>
            <View style={styles.brandBlock}>
              <Image source={require("@/assets/images/agrisentry-logo-v2-transparent.png")} style={styles.logoImage} resizeMode="contain" />
              <Text style={styles.title}>AgriSentry</Text>
              <Text style={styles.subtitle}>IoT-based smart collar health monitoring — choose your livestock</Text>
            </View>

            <View style={[styles.cards, desktop && styles.cardsDesktop]}>
              {SPECIES.map((species) => (
                <TouchableOpacity
                  key={species.key}
                  activeOpacity={species.enabled ? 0.82 : 1}
                  disabled={!species.enabled}
                  onPress={() => router.push("/(tabs)")}
                  style={[styles.speciesCard, desktop && styles.speciesCardDesktop, species.enabled ? styles.cardActive : styles.cardDisabled]}
                >
                  {species.enabled ? (
                    <View style={styles.selected}><Ionicons name="checkmark" size={14} color="#FFFFFF" /></View>
                  ) : (
                    <View style={styles.comingSoon}><Text style={styles.comingSoonText}>COMING SOON</Text></View>
                  )}
                  <Image source={species.art} style={styles.animalArt} resizeMode="contain" />
                  <Text style={[styles.cardName, !species.enabled && styles.muted]}>{species.name}</Text>
                  <Text style={[styles.cardDescription, !species.enabled && styles.muted]}>{species.desc}</Text>
                  <View style={[styles.action, !species.enabled && styles.actionMuted]}>
                    <Ionicons name={species.enabled ? "arrow-forward" : "lock-closed-outline"} size={18} color={species.enabled ? "#16833B" : "#8B929C"} />
                  </View>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        </ScrollView>
        <TrustBar />
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: "#eef7f2" },
  flex: { flex: 1 },
  photoOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: "rgba(239,248,243,0.56)" },
  scrollView: { flex: 1 },
  scroll: { flexGrow: 1, justifyContent: "flex-start" },
  scrollDesktop: { justifyContent: "center", paddingHorizontal: 32, paddingVertical: 32 },
  card: {
    backgroundColor: "rgba(255,255,255,0.94)",
    borderTopLeftRadius: 22,
    borderTopRightRadius: 22,
    paddingHorizontal: 22,
    paddingTop: 36,
    paddingBottom: 24,
  },
  cardDesktop: { maxWidth: 1060, width: "100%", alignSelf: "center", borderRadius: 22 },
  brandBlock: { alignSelf: "center", width: "100%", alignItems: "center", marginBottom: 28 },
  logoImage: { alignSelf: "center", width: 112, height: 112, marginBottom: 12 },
  title: { color: "#082B29", fontSize: 34, lineHeight: 40, fontWeight: "800", letterSpacing: -1 },
  subtitle: { marginTop: 8, color: "#66707C", fontSize: 15, textAlign: "center", maxWidth: 560, lineHeight: 22 },
  cards: { alignItems: "center", gap: 16 },
  cardsDesktop: { flexDirection: "row", justifyContent: "center", alignItems: "stretch", gap: 22 },
  speciesCard: {
    width: "100%", maxWidth: 320, borderRadius: 20, backgroundColor: "#FFFFFF",
    borderWidth: 1, borderColor: "#E7EBE9", padding: 20, alignItems: "center", justifyContent: "center",
    shadowColor: "#1E362A", shadowOpacity: 0.09, shadowRadius: 16, shadowOffset: { width: 0, height: 8 }, elevation: 3,
  },
  speciesCardDesktop: { flex: 1, maxWidth: 310, minHeight: 320 },
  cardActive: { borderColor: "#249148", borderWidth: 1.5 },
  cardDisabled: { opacity: 1 },
  selected: { position: "absolute", top: 16, left: 16, width: 26, height: 26, borderRadius: 13, backgroundColor: "#249148", alignItems: "center", justifyContent: "center" },
  comingSoon: { position: "absolute", top: 16, right: 16, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 99, backgroundColor: "#FAF2DF" },
  comingSoonText: { color: "#9C7A41", fontSize: 10, fontWeight: "800" },
  animalArt: { width: 150, height: 124, marginTop: 6, marginBottom: 4 },
  cardName: { color: "#092E2B", fontSize: 22, fontWeight: "800" },
  cardDescription: { color: "#68717C", fontSize: 13, marginTop: 4, textAlign: "center" },
  muted: { color: "#858C96" },
  action: { width: 42, height: 42, marginTop: 14, borderRadius: 21, borderWidth: 1.2, borderColor: "#249148", alignItems: "center", justifyContent: "center" },
  actionMuted: { borderColor: "#D6DADF" },
});
