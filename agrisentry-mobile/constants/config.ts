import { Platform } from "react-native";

// The online Render backend works on mobile data (SIM) and any Wi-Fi network.
export const API_URL = process.env.EXPO_PUBLIC_API_URL || "https://agrisentry2026.onrender.com/api";

// The Laravel web app's own origin (API_URL minus the trailing /api) — goat QR codes
// encode a link to the web profile page at this origin, e.g. `${WEB_ORIGIN}/goat/5/profile`.
export const WEB_ORIGIN = API_URL.replace(/\/api\/?$/, "");

// Release builds must never send account credentials over an unencrypted connection.
if (!__DEV__ && Platform.OS !== "web" && !API_URL.startsWith("https://")) {
  throw new Error("A secure HTTPS API URL is required for this app build.");
}
