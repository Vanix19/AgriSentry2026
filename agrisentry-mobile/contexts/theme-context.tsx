import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import { Platform } from "react-native";
import * as SecureStore from "expo-secure-store";

type AppTheme = "light" | "dark";
type ThemeContextValue = { theme: AppTheme; isDark: boolean; toggleTheme: () => void };

const STORAGE_KEY = "agrisentry_theme";
const ThemeContext = createContext<ThemeContextValue | null>(null);

export function AppThemeProvider({ children }: { children: React.ReactNode }) {
  const [theme, setTheme] = useState<AppTheme>("dark");

  useEffect(() => {
    (async () => {
      try {
        const saved = Platform.OS === "web"
          ? localStorage.getItem(STORAGE_KEY)
          : await SecureStore.getItemAsync(STORAGE_KEY);
        if (saved === "light" || saved === "dark") setTheme(saved);
      } catch {}
    })();
  }, []);

  const value = useMemo<ThemeContextValue>(() => ({
    theme,
    isDark: theme === "dark",
    toggleTheme: () => setTheme((current) => {
      const next = current === "dark" ? "light" : "dark";
      try {
        if (Platform.OS === "web") localStorage.setItem(STORAGE_KEY, next);
        else SecureStore.setItemAsync(STORAGE_KEY, next).catch(() => undefined);
      } catch {}
      return next;
    }),
  }), [theme]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useAppTheme() {
  const context = useContext(ThemeContext);
  if (!context) throw new Error("useAppTheme must be used inside AppThemeProvider");
  return context;
}
