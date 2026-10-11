// Drop-in replacements for React Native's Text and TextInput that show UI text in the
// selected language. Pass `noTranslate` for user data such as names and codes.
import React, { forwardRef } from "react";
import { Text as RNText, TextInput as RNTextInput, type TextInputProps, type TextProps } from "react-native";
import { useLanguage } from "@/contexts/language-context";

function translateChildren(children: React.ReactNode, t: (text: string) => string): React.ReactNode {
  const list = React.Children.toArray(children);
  // JSX like {"Tap to switch to "}{mode} is split into parts; translate the joined sentence when possible.
  if (list.length > 1 && list.every(child => typeof child === "string" || typeof child === "number")) return t(list.join(""));
  if (typeof children === "string") return t(children);
  if (!Array.isArray(children)) return children;
  return children.map(child => (typeof child === "string" ? t(child) : child));
}

export const Text = forwardRef<RNText, TextProps & { noTranslate?: boolean }>(function Text({ children, noTranslate, ...props }, ref) {
  const { t, language } = useLanguage();
  return <RNText ref={ref} {...props}>{noTranslate || language === "en" ? children : translateChildren(children, t)}</RNText>;
});

export const TextInput = forwardRef<RNTextInput, TextInputProps>(function TextInput({ placeholder, ...props }, ref) {
  const { t } = useLanguage();
  return <RNTextInput ref={ref} {...props} placeholder={placeholder === undefined ? placeholder : t(placeholder)} />;
});
