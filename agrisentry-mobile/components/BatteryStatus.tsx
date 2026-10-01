import React from "react";
import { Text, View } from "react-native";
import { useAppTheme } from "@/contexts/theme-context";

const LIVE_WINDOW_MS = 10 * 60 * 1000;

function formatAge(lastSeen: string | null | undefined) {
  if (!lastSeen) return "No collar reading yet";
  const timestamp = new Date(lastSeen).getTime();
  if (!Number.isFinite(timestamp)) return "Reading time unavailable";
  const ageMinutes = Math.max(0, Math.floor((Date.now() - timestamp) / 60000));
  if (ageMinutes < 1) return "Updated just now";
  if (ageMinutes === 1) return "Updated 1 min ago";
  if (ageMinutes < 60) return `Updated ${ageMinutes} min ago`;
  const hours = Math.floor(ageMinutes / 60);
  return `Updated ${hours}h ago`;
}

export default function BatteryStatus({ level, lastSeen }: { level: number | string | null | undefined; lastSeen?: string | null }) {
  const { isDark } = useAppTheme();
  const value = level === null || level === undefined || level === "" ? NaN : Number(String(level).replace("%", ""));
  const valid = Number.isFinite(value) && value >= 0 && value <= 100;
  const lastSeenTime = lastSeen ? new Date(lastSeen).getTime() : NaN;
  const live = Number.isFinite(lastSeenTime) && Date.now() - lastSeenTime <= LIVE_WINDOW_MS;
  const color = valid && value <= 20 ? (isDark ? "#F87171" : "#B91C1C") : (isDark ? "#4ADE80" : "#166534");
  const statusColor = live ? (isDark ? "#4ADE80" : "#166534") : (isDark ? "#FBBF24" : "#9A3412");
  const statusText = valid ? (live ? "Live collar reading" : "Collar reading is stale") : "Waiting for collar reading";
  return <View style={{gap:5,marginVertical:8}}>
    <Text style={{color,fontWeight:"700"}}>Battery: {valid ? `${Math.round(value)}%` : "No reading"}</Text>
    <Text style={{color: statusColor, fontSize: 11, fontWeight: "600"}}>{statusText} · {formatAge(lastSeen)}</Text>
    <View accessibilityLabel={valid ? `Battery ${Math.round(value)} percent, ${statusText}` : "Battery unavailable"} style={{height:8,width:100,borderRadius:4,backgroundColor:isDark ? "#334155" : "#E2E8F0",overflow:"hidden"}}><View style={{height:8,width:valid ? `${value}%` : "0%",backgroundColor:color}} /></View>
  </View>;
}
