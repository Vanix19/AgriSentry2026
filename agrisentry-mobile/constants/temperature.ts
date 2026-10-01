// Mirrors the web app's band()/LED thresholds (agrisentry.blade.php):
// Normal 33.0–38.5°C; Warning 32.0–32.9°C / 38.6–39.5°C; Urgent outside those limits.
export type TempBand = "low" | "normal" | "high";

export function tempBand(temp: number | null | undefined): TempBand | null {
  if (temp === null || temp === undefined || Number.isNaN(temp)) return null;
  if (temp > 38.5) return "high";
  if (temp < 33.0) return "low";
  return "normal";
}

export function tempBandColor(temp: number | null | undefined, fallback = "#16A34A"): string {
  const band = tempBand(temp);
  if (band === "high") return "#DC2626";
  if (band === "low") return "#2563EB";
  if (band === "normal") return "#16A34A";
  return fallback;
}
