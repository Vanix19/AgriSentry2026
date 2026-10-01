import React from "react";
import { Image, StyleSheet, Text, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";

const ITEMS: { icon: keyof typeof Ionicons.glyphMap; label: string }[] = [
  { icon: "shield-checkmark-outline", label: "Secure" },
  { icon: "wifi-outline", label: "Connected" },
  { icon: "bar-chart-outline", label: "Insightful" },
];

export default function TrustBar() {
  return (
    <View style={styles.bar}>
      <View style={styles.brandRow}>
        <Image
          source={require("@/assets/images/anuvimco-logo.png")}
          style={styles.logo}
          resizeMode="contain"
        />
        <Text style={styles.brandText} numberOfLines={2}>
          <Text style={styles.brandName}>AgriSentry</Text> · Smart livestock. Healthier herds.
        </Text>
      </View>
      <View style={styles.items}>
        {ITEMS.map((item) => (
          <View key={item.label} style={styles.item}>
            <Ionicons name={item.icon} size={14} color="#16A34A" />
            <Text style={styles.itemText}>{item.label}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
    backgroundColor: "#FFFFFF",
    paddingHorizontal: 18,
    paddingVertical: 12,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    flexWrap: "wrap",
    gap: 10,
  },
  brandRow: { flexDirection: "row", alignItems: "center", gap: 8, flexShrink: 1, maxWidth: "55%" },
  logo: { width: 20, height: 20 },
  brandText: { fontSize: 11, color: "#64748B", flexShrink: 1 },
  brandName: { fontWeight: "800", color: "#111827" },
  items: { flexDirection: "row", gap: 14 },
  item: { flexDirection: "row", alignItems: "center", gap: 4 },
  itemText: { fontSize: 11, color: "#475569", fontWeight: "600" },
});
