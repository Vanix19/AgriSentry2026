import React, { useEffect, useState } from "react";
import { ActivityIndicator, Image, TouchableOpacity, View, type ImageStyle, type StyleProp } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Text } from "@/components/i18n-text";
import { WEB_ORIGIN } from "@/constants/config";
import { getToken } from "@/services/api";
import { downloadExport } from "@/services/downloads";

/** A medical record attachment. The server only serves it to signed-in users, so send the token. */
export default function MedicalAttachment({ url, imageStyle }: { url: string; imageStyle: StyleProp<ImageStyle> }) {
  const [token, setToken] = useState<string | null | undefined>(undefined);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  useEffect(() => { void getToken().then(setToken); }, []);

  const absolute = /^https?:\/\//.test(url) ? url : WEB_ORIGIN + url;
  const name = decodeURIComponent(absolute.split("/").pop() || "attachment");
  const isPdf = name.toLowerCase().endsWith(".pdf");

  if (token === undefined) return <ActivityIndicator color="#16A34A" style={{ marginTop: 10, alignSelf: "flex-start" }} />;
  if (!isPdf) return <Image source={{ uri: absolute, headers: token ? { Authorization: `Bearer ${token}` } : undefined }} style={imageStyle} accessibilityLabel="Medical reference" />;

  return <View style={{ marginTop: 10, gap: 6 }}>
    <TouchableOpacity disabled={busy} accessibilityRole="button" onPress={async () => {
      setBusy(true); setError("");
      try { await downloadExport(absolute, name); } catch (e) { setError(e instanceof Error ? e.message : "Download failed."); } finally { setBusy(false); }
    }} style={{ flexDirection: "row", alignItems: "center", gap: 8, alignSelf: "flex-start", paddingVertical: 10, paddingHorizontal: 14, borderRadius: 10, borderWidth: 1, borderColor: "#16A34A", backgroundColor: "rgba(22,163,74,0.12)", opacity: busy ? 0.6 : 1 }}>
      {busy ? <ActivityIndicator color="#16A34A" /> : <Ionicons name="document-text-outline" size={20} color="#16A34A" />}
      <Text style={{ color: "#16A34A", fontWeight: "700" }}>View attached PDF</Text>
    </TouchableOpacity>
    {!!error && <Text style={{ color: "#DC2626" }}>{error}</Text>}
  </View>;
}
