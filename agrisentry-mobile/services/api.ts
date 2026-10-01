import { Platform } from "react-native";
import * as SecureStore from "expo-secure-store";
import { API_URL } from "@/constants/config";

const TOKEN_KEY = "agrisentry_token";

// expo-secure-store has no web implementation (there's no OS-level secure storage in a
// browser), so fall back to localStorage there. Native (iOS/Android) keeps using SecureStore.
async function readStoredToken(): Promise<string | null> {
  if (Platform.OS === "web") {
    try {
      return typeof localStorage === "undefined" ? null : localStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }
  return SecureStore.getItemAsync(TOKEN_KEY);
}

async function writeStoredToken(token: string | null) {
  if (Platform.OS === "web") {
    try {
      if (typeof localStorage === "undefined") return;
      if (token) localStorage.setItem(TOKEN_KEY, token);
      else localStorage.removeItem(TOKEN_KEY);
    } catch {
      // Storage unavailable (e.g. private browsing) — session just won't persist on web.
    }
    return;
  }
  if (token) {
    await SecureStore.setItemAsync(TOKEN_KEY, token);
  } else {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
  }
}

let cachedToken: string | null | undefined;

export async function getToken(): Promise<string | null> {
  if (cachedToken !== undefined) return cachedToken;
  cachedToken = await readStoredToken();
  return cachedToken;
}

export async function setToken(token: string | null) {
  cachedToken = token;
  await writeStoredToken(token);
}

export class ApiError extends Error {
  status: number;
  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

/** Fired when a request comes back 401 so the app can drop back to the login screen. */
let onUnauthorized: (() => void) | null = null;
export function setOnUnauthorized(handler: (() => void) | null) {
  onUnauthorized = handler;
}

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const token = await getToken();

  const isMultipart = typeof FormData !== "undefined" && options.body instanceof FormData;
  const headers: Record<string, string> = {
    Accept: "application/json",
    // Bypasses the ngrok free-tier browser warning interstitial, which otherwise
    // returns an HTML page instead of the API response for every request.
    "ngrok-skip-browser-warning": "true",
    ...(options.body && !isMultipart ? { "Content-Type": "application/json" } : {}),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...((options.headers as Record<string, string>) || {}),
  };

  const controller = new AbortController();
  const cancel = () => controller.abort();
  options.signal?.addEventListener('abort', cancel, { once: true });
  if (options.signal?.aborted) controller.abort();
  let timedOut = false;
  // AI/photo requests need more time than ordinary navigation and account actions.
  const timeout = setTimeout(() => { timedOut = true; controller.abort(); }, path.includes('gemini-advice') ? 90000 : 25000);
  try {
  const response = await fetch(`${API_URL}${path}`, { ...options, headers, signal: controller.signal });

  if (response.status === 401) {
    onUnauthorized?.();
    throw new ApiError("Your session expired. Please log in again.", 401);
  }

  const data = await response.json().catch(() => null);
  if (!data || typeof data !== 'object') throw new ApiError('The server returned an incomplete response. Please try again.', response.status);

  if (!response.ok) {
    if (response.status >= 500) {
      throw new ApiError('The server is temporarily unavailable. Please wait a moment and try again.', response.status);
    }
    const message =
      data?.message ||
      (data?.errors ? Object.values(data.errors).flat().join(" ") : null) ||
      `Request to ${path} failed (${response.status}).`;
    throw new ApiError(message, response.status);
  }

  return data as T;
  } catch (error) {
    if (timedOut) throw new ApiError('The server took too long to respond. Check your connection and try again.', 0);
    if (error instanceof ApiError) throw error;
    if (options.signal?.aborted) throw new ApiError('Request cancelled.', 0);
    throw new ApiError('Could not reach the AgriSentry server. Check your connection and try again.', 0);
  } finally {
    clearTimeout(timeout);
    options.signal?.removeEventListener('abort', cancel);
  }
}
