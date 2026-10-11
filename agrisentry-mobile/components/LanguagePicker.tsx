import React from "react";
import { TouchableOpacity, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Text } from "@/components/i18n-text";
import { useLanguage } from "@/contexts/language-context";
import { useAppTheme } from "@/contexts/theme-context";
import { LANGUAGES, type Language } from "@/services/i18n";

export default function LanguagePicker() {
  const { language, setLanguage } = useLanguage();
  const { isDark } = useAppTheme();
  const colors = { text: isDark ? "#F8FAFC" : "#111827", muted: isDark ? "#98A3B7" : "#64748B", card: isDark ? "#07121A" : "#FFFFFF", border: isDark ? "#2A3642" : "#E5E7EB" };
  return <View style={{ padding: 20, borderRadius: 18, borderWidth: 1, borderColor: colors.border, backgroundColor: colors.card, gap: 10 }}>
    <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
      <Ionicons name="language-outline" size={20} color="#16A34A" />
      <Text style={{ fontSize: 17, fontWeight: "700", color: colors.text }}>App language</Text>
    </View>
    <Text style={{ color: colors.muted, lineHeight: 20 }}>Choose the language used across the app.</Text>
    {(Object.keys(LANGUAGES) as Language[]).map(code => {
      const selected = code === language;
      return <TouchableOpacity key={code} onPress={() => setLanguage(code)} accessibilityRole="radio" accessibilityState={{ selected }}
        style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingVertical: 12, paddingHorizontal: 14, borderRadius: 10, borderWidth: 1, borderColor: selected ? "#16A34A" : colors.border, backgroundColor: selected ? (isDark ? "rgba(22,163,74,0.15)" : "#F0FDF4") : "transparent" }}>
        <Text noTranslate style={{ color: colors.text, fontSize: 16, fontWeight: selected ? "700" : "500" }}>{LANGUAGES[code]}</Text>
        {selected && <Ionicons name="checkmark-circle" size={22} color="#16A34A" />}
      </TouchableOpacity>;
    })}
  </View>;
}
