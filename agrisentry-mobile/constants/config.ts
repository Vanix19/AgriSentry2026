import { Platform } from "react-native";

// A local browser preview should use the local Laravel server even when the
// native app's configured public tunnel is unavailable.
const localPreview = Platform.OS === "web" && typeof window !== "undefined"
  && ["localhost", "127.0.0.1"].includes(window.location.hostname);
export const API_URL = localPreview
  ? `http://${window.location.hostname}:3000/api`
  : process.env.EXPO_PUBLIC_API_URL || "http://127.0.0.1:3000/api";

// The Laravel web app's own origin (API_URL minus the trailing /api) — goat QR codes
// encode a link to the web profile page at this origin, e.g. `${WEB_ORIGIN}/goat/5/profile`.
export const WEB_ORIGIN = API_URL.replace(/\/api\/?$/, "");

// Release builds must never send account credentials over an unencrypted connection.
if (!__DEV__ && Platform.OS !== "web" && !API_URL.startsWith("https://")) {
  throw new Error("A secure HTTPS API URL is required for this app build.");
}
