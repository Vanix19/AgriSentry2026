// Mirrors the web app's band()/LED thresholds (agrisentry.blade.php):
// Normal 35.5–37.2°C; Warning 34.5–35.4°C / 37.3–38.2°C; Urgent outside those limits.
export type TempBand = "low" | "normal" | "high";

export function tempBand(temp: number | null | undefined): TempBand | null {
  if (temp === null || temp === undefined || Number.isNaN(temp)) return null;
  if (temp > 37.2) return "high";
  if (temp < 35.5) return "low";
  return "normal";
}

export function tempBandColor(temp: number | null | undefined, fallback = "#16A34A"): string {
  const band = tempBand(temp);
  if (band === "high") return "#DC2626";
  if (band === "low") return "#2563EB";
  if (band === "normal") return "#16A34A";
  return fallback;
}
