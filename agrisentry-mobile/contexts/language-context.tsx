import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import { Alert, Platform } from "react-native";
import * as SecureStore from "expo-secure-store";
import { LANGUAGES, setCurrentLanguage, translateText, type Language } from "@/services/i18n";

const STORAGE_KEY = "agrisentry_language";
type LanguageContextValue = { language: Language; setLanguage: (language: Language) => void; t: (text: string) => string };
const LanguageContext = createContext<LanguageContextValue>({ language: "en", setLanguage: () => undefined, t: text => text });

// Alert dialogs are shown outside React rendering, so translate them at the call site once.
const originalAlert = Alert.alert;
Alert.alert = (title, message, buttons, options) => originalAlert(
  translateText(title),
  message === undefined ? message : translateText(message),
  buttons?.map(button => ({ ...button, text: button.text === undefined ? button.text : translateText(button.text) })),
  options,
);

export function LanguageProvider({ children }: { children: React.ReactNode }) {
  const [language, setLanguageState] = useState<Language>("en");

  useEffect(() => {
    (async () => {
      try {
        const saved = Platform.OS === "web" ? localStorage.getItem(STORAGE_KEY) : await SecureStore.getItemAsync(STORAGE_KEY);
        if (saved && saved in LANGUAGES) { setCurrentLanguage(saved as Language); setLanguageState(saved as Language); }
      } catch {}
    })();
  }, []);

  const value = useMemo<LanguageContextValue>(() => ({
    language,
    setLanguage: next => {
      setCurrentLanguage(next);
      setLanguageState(next);
      try {
        if (Platform.OS === "web") localStorage.setItem(STORAGE_KEY, next);
        else SecureStore.setItemAsync(STORAGE_KEY, next).catch(() => undefined);
      } catch {}
    },
    t: text => translateText(text),
  }), [language]);

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>;
}

export const useLanguage = () => useContext(LanguageContext);
