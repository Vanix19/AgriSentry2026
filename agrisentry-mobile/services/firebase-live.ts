import { initializeApp, getApps, getApp, type FirebaseOptions } from 'firebase/app';
import { getAuth, setPersistence, inMemoryPersistence, signInWithCustomToken, signOut } from 'firebase/auth';
import { getDatabase, onValue, ref } from 'firebase/database';
import { apiFetch } from './api';

type LiveSession = { enabled: boolean; config?: FirebaseOptions; token?: string; path?: string };
const listeners = new Set<() => void>();
export function onTelemetryUpdate(listener: () => void) {
  listeners.add(listener);
  return () => { listeners.delete(listener); };
}

export function startFirebaseLive() {
  let stopped = false;
  let pending = false;
  let unsubscribe: (() => void) | undefined;
  let auth: ReturnType<typeof getAuth> | undefined;
  let updateTimer: ReturnType<typeof setTimeout> | undefined;
  const disconnect = () => { unsubscribe?.(); unsubscribe = undefined; clearTimeout(updateTimer); };
  const renew = async () => {
    if (pending || stopped) return;
    pending = true;
    try {
      const session = await apiFetch<LiveSession>('/firebase/session', { method: 'POST' });
      if (stopped) return;
      if (!session.enabled || !session.config || !session.token || !session.path) {
        disconnect();
        if (auth) await signOut(auth);
        return;
      }
      const app = getApps().length ? getApp() : initializeApp(session.config);
      auth = getAuth(app);
      await setPersistence(auth, inMemoryPersistence);
      await signInWithCustomToken(auth, session.token);
      if (stopped) { await signOut(auth); return; }
      disconnect();
      unsubscribe = onValue(ref(getDatabase(app), session.path), () => {
        clearTimeout(updateTimer);
        updateTimer = setTimeout(() => {
          if (!stopped) for (const listener of listeners) listener();
        }, 250);
      }, disconnect);
    } catch {
      disconnect();
      if (auth) await signOut(auth).catch(() => undefined);
    } finally { pending = false; }
  };
  void renew();
  const timer = setInterval(renew, 5 * 60 * 1000);
  return () => { stopped = true; clearInterval(timer); disconnect(); if (auth) void signOut(auth).catch(() => undefined); };
}
