// Same matching rules as the web dashboard (Agrisentry/public/js/dashboard-language.js).
import { WEB_TRANSLATION_ROWS } from "@/constants/translations-web";
import { MOBILE_TRANSLATION_ROWS } from "@/constants/translations-mobile";

export const LANGUAGES = { en: "English", ceb: "Cebuano", fil: "Filipino", es: "Español (Spanish)" } as const;
export type Language = keyof typeof LANGUAGES;
type Entry = Record<Language, string>;

const normalize = (text: string) => text.replace(/\s+/g, " ").trim().toLowerCase();
const escapeRegex = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
const entries = new Map<string, Entry>();
const templates: { pattern: RegExp; keys: string[]; entry: Entry }[] = [];
for (const row of `${WEB_TRANSLATION_ROWS}\n${MOBILE_TRANSLATION_ROWS}`.trim().split("\n")) {
  const [en, ceb, fil, es] = row.split("|");
  if (!en || !ceb || !fil || !es) continue;
  const entry = { en, ceb, fil, es };
  entries.set(normalize(en), entry);
  if (en.includes("{")) {
    const keys = [...en.matchAll(/\{(\w+)\}/g)].map(match => match[1]);
    templates.push({ pattern: new RegExp("^" + en.split(/\{\w+\}/).map(escapeRegex).join("(.+?)") + "$", "i"), keys, entry });
  }
}

let current: Language = "en";
let cache = new Map<string, string>();
export function setCurrentLanguage(language: Language) {
  if (language !== current) cache = new Map();
  current = language;
}
export const currentLanguage = () => current;

function lookup(trimmed: string, depth: number): string {
  const entry = entries.get(normalize(trimmed));
  if (entry) return entry[current];
  for (const template of templates) {
    const match = trimmed.match(template.pattern);
    if (!match) continue;
    return template.entry[current].replace(/\{(\w+)\}/g, (_, key: string) => {
      const value = match[template.keys.indexOf(key) + 1];
      return ["field", "unit", "status", "period"].includes(key) ? translateText(value, depth + 1) : value;
    });
  }
  const parts = trimmed.split(/(\s*[—–·•:→←›‹📊🗑+⚠]+\s*|(?<=[.!?])\s+(?=[A-Z])|\s+[(/]|[)])/u);
  return parts.length > 1 ? parts.map(part => translateText(part, depth + 1)).join("") : trimmed;
}

/** Translates English UI text into the selected language; unknown text is returned unchanged. */
export function translateText(source: string, depth = 0): string {
  if (current === "en" || depth > 5 || !source.trim()) return source;
  if (depth === 0) {
    const cached = cache.get(source);
    if (cached !== undefined) return cached;
  }
  const trimmed = source.trim();
  const start = source.indexOf(trimmed);
  const output = source.slice(0, start) + lookup(trimmed, depth) + source.slice(start + trimmed.length);
  if (depth === 0 && cache.size < 5000) cache.set(source, output);
  return output;
}
