import React, { useCallback, useEffect, useRef, useState } from "react";
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Stack, useRouter } from "expo-router";

import {downloadExport} from "@/services/downloads";
import DateFilter from "@/components/DateFilter";
import { useAppTheme } from "@/contexts/theme-context";
import { ApiError } from "@/services/api";
import { fetchReports } from "@/services/goats";
import type { ReportPeriod, ReportSummary } from "@/types/api";

const PERIODS: { key: ReportPeriod; label: string }[] = [
  { key: "day", label: "Day" },
  { key: "week", label: "Week" },
  { key: "month", label: "Month" },
  { key: "year", label: "Year" },
  { key: "all", label: "All" },
];

export default function ReportsScreen() {
  const router = useRouter();
  const {isDark}=useAppTheme();
  const styles=makeStyles(isDark);
  const [start,setStart]=useState("");
  const [end,setEnd]=useState("");
  const [range,setRange]=useState<{start:string;end:string}|undefined>();
  const requestId=useRef(0);
  const [exporting,setExporting]=useState(false);
  async function exportReport(format:"pdf"|"csv") {
    setExporting(true);setError(null);
    try {const query=new URLSearchParams({period,format});if(range){query.set("start_date",range.start);query.set("end_date",range.end);}
      await downloadExport("/reports/export?"+query,"agrisentry-report-"+(range?range.start+"_"+range.end:period)+"."+format,format==="pdf"?"application/pdf":"text/csv");
    } catch(e){setError(e instanceof Error?e.message:"Download failed.");}finally{setExporting(false);}
  }
  const [period, setPeriod] = useState<ReportPeriod>("all");
  const [report, setReport] = useState<ReportSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (p: ReportPeriod) => {
    const id=++requestId.current;
    setLoading(true);
    setError(null);
    try {
      const data = await fetchReports(p,range);
      if(id===requestId.current) setReport(data);
    } catch (e) {
      if(id===requestId.current) setError(e instanceof ApiError ? e.message : "Could not load the report.");
    } finally {
      if(id===requestId.current) setLoading(false);
    }
  }, [range]);

  useEffect(() => {
    load(period);
  }, [period, load]);

  return (
    <SafeAreaView style={styles.safeArea}>
      <Stack.Screen options={{ headerShown: false }} />

      <View style={styles.topRow}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.page}>
        <Text style={styles.title}>Report Analytics</Text>
        <Text style={styles.subtitle}>Herd health summary for the selected period.</Text>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.periodRow}>
          {PERIODS.map((p) => (
            <TouchableOpacity
              key={p.key}
              style={[styles.periodChip, !range && period === p.key && styles.periodChipActive]}
              onPress={() => {setRange(undefined);setStart("");setEnd("");setError(null);setPeriod(p.key);}}
            >
              <Text style={[styles.periodChipText, !range && period === p.key && styles.periodChipTextActive]}>
                {p.label}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        <View style={styles.dateCard}>
          <Text style={styles.subtitle}>Choose a date range, or select the same date for a single day.</Text>
          <DateFilter label="Start date" value={start} onChange={setStart}/><DateFilter label="End date" value={end} onChange={setEnd}/>
          <View style={{flexDirection:'row',gap:10,flexWrap:'wrap'}}><TouchableOpacity style={[styles.periodChip,styles.periodChipActive]} onPress={()=>{if(!start||!end||start>end){setError('Choose an end date on or after the start date.');return;}setRange({start,end});}}><Text style={styles.periodChipTextActive}>Apply dates</Text></TouchableOpacity>
          <TouchableOpacity style={styles.periodChip} onPress={()=>{setStart('');setEnd('');setRange(undefined);setPeriod('all');setError(null);}}><Text style={styles.periodChipText}>Clear dates</Text></TouchableOpacity></View>
        </View>
        {report && !loading && !error && <Text style={styles.subtitle}>{report.period==='custom' ? 'Showing '+report.start_date+' to '+report.end_date : 'Showing '+(PERIODS.find(p=>p.key===report.period)?.label || 'All')+' records'}. Status uses the latest health record per goat; temperatures use recorded readings.</Text>}
        <View style={{flexDirection:"row",gap:10,flexWrap:"wrap",justifyContent:"flex-end",marginBottom:16}}><TouchableOpacity disabled={loading} style={[styles.periodChip,styles.periodChipActive]} onPress={()=>load(period)}><Text style={styles.periodChipTextActive}>Refresh Report</Text></TouchableOpacity>{(["pdf","csv"] as const).map(format=><TouchableOpacity key={format} disabled={exporting||loading} style={[styles.periodChip,styles.periodChipActive]} onPress={()=>exportReport(format)}><Text style={styles.periodChipTextActive}>{exporting?"Preparing…":(format==="pdf"?"Download PDF":"Export CSV")}</Text></TouchableOpacity>)}</View>
        {error && (
          <View style={styles.errorBanner}>
            <Text style={styles.errorBannerText}>{error}</Text>
          </View>
        )}

        {loading ? (
          <ActivityIndicator color="#16A34A" style={{ marginTop: 30 }} />
        ) : report && !error ? (
          <>
            <View style={styles.grid}>
              <StatCard label="Urgent" value={String(report.urgent_goats)} color="#DC2626" />
              <StatCard label="Monitoring" value={String(report.monitoring_goats)} color="#F59E0B" />
              <StatCard label="Normal" value={String(report.normal_goats)} color="#16A34A" />
              <StatCard label="Medical Records" value={String(report.medical_records_count)} />
              <StatCard label="Health Logs" value={String(report.health_logs_count)} />
              <StatCard label="Motion readings" value={String(report.motion_readings_count ?? 0)} />
              <StatCard label="Prolonged Inactivity" value={String(report.prolonged_inactivity_count ?? 0)} />
              <StatCard label="Excessive Movement" value={String(report.excessive_movement_count ?? 0)} />
              <StatCard label="Other motion readings" value={String(report.other_motion_count ?? 0)} />
              <StatCard
                label="Avg Temp"
                value={report.temp_avg !== null ? `${report.temp_avg}°C` : "N/A"}
              />
              <StatCard
                label="Highest Temp"
                value={report.temp_high !== null ? `${report.temp_high}°C` : "N/A"}
                color="#DC2626"
              />
              <StatCard
                label="Lowest Temp"
                value={report.temp_low !== null ? `${report.temp_low}°C` : "N/A"}
                color="#2563EB"
              />
            </View>
          </>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

function StatCard({ label, value, color }: { label: string; value: string; color?: string }) {
  const {isDark:dark}=useAppTheme();
  const styles=makeStyles(dark);
  return (
    <View style={styles.statCard}>
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={[styles.statValue, { color:color || (dark?"#F8FAFC":"#111827") }]}>{value}</Text>
    </View>
  );
}

const makeStyles = (dark:boolean) => StyleSheet.create({
  dateCard:{padding:16,borderRadius:18,borderWidth:1,borderColor:dark?"#2A3642":"#E5E7EB",backgroundColor:dark?"#07121A":"#FFFFFF",marginBottom:16},
  safeArea: { flex: 1, backgroundColor: dark?"#020B12":"#F8FAFC" },
  topRow: { paddingHorizontal: 18, paddingTop: 12 },
  backText: { color: "#16A34A", fontWeight: "700", fontSize: 15 },
  page: { padding: 18, paddingBottom: 60 },
  title: { fontSize: 28, fontWeight: "800", color: dark?"#F8FAFC":"#111827" },
  subtitle: { color: dark?"#98A3B7":"#64748B", fontSize: 14, marginTop: 5, marginBottom: 18, lineHeight: 20 },
  periodRow: { marginBottom: 18 },
  periodChip: {
    borderWidth: 1,
    borderColor: "#CBD5E1",
    borderRadius: 999,
    paddingHorizontal: 16,
    paddingVertical: 9,
    marginRight: 8,
    backgroundColor: dark?"#07121A":"#FFFFFF",
  },
  periodChipActive: { backgroundColor: "#16A34A", borderColor: "#16A34A" },
  periodChipText: { color: dark?"#CBD5E1":"#475569", fontWeight: "700", fontSize: 13 },
  periodChipTextActive: { color: "#FFFFFF" },
  errorBanner: { backgroundColor: "#FEE2E2", borderRadius: 14, padding: 14, marginBottom: 16 },
  errorBannerText: { color: "#B91C1C", fontSize: 13, fontWeight: "600" },
  grid: { flexDirection: "row", flexWrap: "wrap", justifyContent: "space-between" },
  statCard: {
    width: "48%",
    backgroundColor: dark?"#07121A":"#FFFFFF",
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: dark?"#2A3642":"#E5E7EB",
    marginBottom: 12,
  },
  statLabel: { color: dark?"#98A3B7":"#64748B", fontSize: 13, marginBottom: 8 },
  statValue: { fontSize: 24, fontWeight: "800" },
});
