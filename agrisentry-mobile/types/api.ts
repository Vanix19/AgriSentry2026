// Shapes mirror the JSON returned by the Laravel API (routes/api.php).
// Keep these in sync with app/Models/*.php and the Api/*Controller resources.

export type UserRole = "Admin" | "Staff" | "Caretaker";

export type AuthUser = {
  id: number;
  name: string;
  username: string;
  role: UserRole;
  password_change_required?: boolean;
  permissions?: Record<string, boolean>;
};

export type GoatStatus = "Normal" | "Warning" | "Monitoring" | "Urgent" | string;

export type Collar = {
  id: number;
  goat_id: number | null;
  collar_code: string;
  dev_eui: string | null;
  battery_level: number | null;
  device_status: string | null;
  last_seen: string | null;
};

export type MedicalRecord = {
  id: number;
  goat_id: number;
  record_type: string | null;
  title: string | null;
  description: string | null;
  date_given: string | null;
  next_due_date: string | null;
  administered_by: string | null;
  reference_photo_path?: string | null;
  reference_photo_url?: string | null;
  created_at: string;
  goat?: { id: number; name: string; code: string };
};

export type HealthLog = {
  id: number;
  goat_id: number;
  event_type: string;
  motion_anomaly?: string | null;
  description: string | null;
  temperature: number | null;
  movement: string | null;
  led_status: string | null;
  severity: string | null;
  created_at: string;
  goat?: { id: number; name: string; code: string };
};

export type Alert = {
  id: number;
  goat_id: number;
  alert_type: string | null;
  message: string | null;
  recommendation: string | null;
  severity: string | null;
  status: string | null;
  created_at: string;
  goat?: { id: number; name: string; code: string };
};

export type ReportPeriod = "day" | "week" | "month" | "year" | "all";

export type ReportSummary = {
  period: ReportPeriod | "custom";
  start_date?: string | null;
  end_date?: string;
  urgent_goats: number;
  monitoring_goats: number;
  normal_goats: number;
  medical_records_count: number;
  health_logs_count: number;
  motion_readings_count: number;
  prolonged_inactivity_count: number;
  excessive_movement_count: number;
  other_motion_count: number;
  temp_avg: number | null;
  temp_high: number | null;
  temp_low: number | null;
};

export type Goat = {
  id: number;
  name: string;
  code: string;
  breed: string | null;
  age: string | null;
  sex: string | null;
  weight: string | null;
  owner: string | null;
  barn: string | null;
  ear_tag?: string | null;
  color?: string | null;
  collar_id?: string | null;
  temperature: number | null;
  movement: string | null;
  battery: string | number | null;
  status: GoatStatus;
  alert_reason: string | null;
  collar?: Collar | null;
  medical_records?: MedicalRecord[];
  health_logs?: HealthLog[];
  alerts?: Alert[];
};
