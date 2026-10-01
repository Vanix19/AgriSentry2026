import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from "react";
import { apiFetch, getToken, setOnUnauthorized, setToken } from "@/services/api";
import type { AuthUser } from "@/types/api";
import { startFirebaseLive } from "@/services/firebase-live";

type AuthContextValue = {
  user: AuthUser | null;
  loading: boolean;
  login: (challenge: string, otp: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (user) return startFirebaseLive();
  }, [user]);

  const clearSession = useCallback(async () => {
    await setToken(null);
    setUser(null);
  }, []);

  useEffect(() => {
    setOnUnauthorized(() => {
      clearSession();
    });
    return () => setOnUnauthorized(null);
  }, [clearSession]);

  useEffect(() => {
    (async () => {
      const token = await getToken();
      if (!token) {
        setLoading(false);
        return;
      }
      try {
        const data = await apiFetch<{ user: AuthUser }>("/mobile/me");
        setUser(data.user);
      } catch {
        await clearSession();
      } finally {
        setLoading(false);
      }
    })();
  }, [clearSession]);

  const login = useCallback(async (challenge: string, otp: string) => {
    const data = await apiFetch<{ token: string; user: AuthUser }>(
      "/mobile/login/verify",
      {
        method: "POST",
        body: JSON.stringify({
          challenge,
          otp,
          device_name: "agrisentry-mobile",
        }),
      }
    );
    await setToken(data.token);
    setUser(data.user);
  }, []);

  const refreshUser = useCallback(async () => {
    const data = await apiFetch<{user:AuthUser}>("/mobile/me");
    setUser(data.user);
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiFetch("/mobile/logout", { method: "POST" });
    } catch {
      // Token may already be invalid server-side — clear locally regardless.
    }
    await clearSession();
  }, [clearSession]);

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within an AuthProvider");
  return ctx;
}
