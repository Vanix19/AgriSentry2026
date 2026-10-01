import { apiFetch } from "@/services/api";
import type { Alert, Collar, Goat, HealthLog, MedicalRecord, ReportSummary } from "@/types/api";

export function fetchGoats() {
  return apiFetch<{ goats: Goat[] }>("/goats");
}

export function fetchGoat(id: number | string) {
  return apiFetch<{ goat: Goat }>(`/goats/${id}`);
}

export function createGoat(payload: Partial<Goat>) {
  return apiFetch<{ message: string; goat: Goat }>("/goats", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function deleteGoat(id: number | string) {
  return apiFetch<{ message: string }>(`/goats/${id}`, { method: "DELETE" });
}

export function fetchCollars() {
  return apiFetch<{ collars: Collar[] }>("/collars");
}

export function createCollar(payload: {
  collar_code: string;
  dev_eui?: string;
  battery_level?: number;
  goat_id?: number | string;
}) {
  return apiFetch<{ message: string; collar: Collar }>("/collars", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function deleteCollar(id: number | string) {
  return apiFetch<{ message: string }>(`/collars/${id}`, { method: "DELETE" });
}

export function fetchHealthLogs() {
  return apiFetch<{ health_logs: HealthLog[] }>("/health-logs");
}

export function fetchAlerts() {
  return apiFetch<{ alerts: Alert[] }>("/alerts");
}

export function fetchMedicalRecords() {
  return apiFetch<{ medical_records: MedicalRecord[] }>("/medical-records");
}

export function fetchReports(period: "day" | "week" | "month" | "year" | "all", range?: {start:string;end:string}) {
  const query=new URLSearchParams({period});
  if(range){query.set("start_date",range.start);query.set("end_date",range.end);}
  return apiFetch<ReportSummary>(`/reports?${query}`);
}

export function addMedicalRecord(
  goatId: number | string,
  payload: {
    record_type: string;
    title: string;
    description?: string;
    date_given?: string;
    next_due_date?: string;
    administered_by?: string;
    reference_photo?: { uri: string; name?: string | null; mimeType?: string | null } | null;
  }
) {
  if (payload.reference_photo) {
    const form = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
      if (key === "reference_photo" || value === undefined || value === null) return;
      form.append(key, String(value));
    });
    form.append("reference_photo", {
      uri: payload.reference_photo.uri,
      name: payload.reference_photo.name || `medical-reference-${Date.now()}.jpg`,
      type: payload.reference_photo.mimeType || "image/jpeg",
    } as any);
    return apiFetch<{ message: string; medical_record: MedicalRecord }>(
      `/goats/${goatId}/medical-records`,
      { method: "POST", body: form }
    );
  }
  return apiFetch<{ message: string; medical_record: MedicalRecord }>(
    `/goats/${goatId}/medical-records`,
    { method: "POST", body: JSON.stringify(payload) }
  );
}

export function updateGoat(id: number | string, payload: Partial<Goat>) {
  return apiFetch<{ message: string; goat: Goat }>(`/goats/${id}`, { method: "PUT", body: JSON.stringify(payload) });
}
