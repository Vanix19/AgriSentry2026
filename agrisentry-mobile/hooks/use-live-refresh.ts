import { useEffect } from "react";
import { AppState } from "react-native";
import { onTelemetryUpdate } from "@/services/firebase-live";
import { useIsFocused } from "@react-navigation/native";

export function useLiveRefresh(refresh: () => Promise<void>) {
  const focused = useIsFocused();
  useEffect(() => {
    if (!focused) return;
    let pending = false;
    const update = async () => {
      if (pending || AppState.currentState !== "active") return;
      pending = true;
      try { await refresh(); } finally { pending = false; }
    };
    const timer = setInterval(update, 15000);
    let liveTimer: ReturnType<typeof setTimeout> | undefined;
    const unsubscribe = onTelemetryUpdate(() => {
      if (liveTimer) return;
      liveTimer = setTimeout(() => { liveTimer = undefined; void update(); }, 2000);
    });
    const subscription = AppState.addEventListener("change", state => { if (state === "active") void update(); });
    return () => { clearInterval(timer); clearTimeout(liveTimer); subscription.remove(); unsubscribe(); };
  }, [refresh, focused]);
}
