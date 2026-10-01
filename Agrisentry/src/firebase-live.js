import { initializeApp, getApps } from 'firebase/app';
import { signInWithCustomToken, signOut, setPersistence, inMemoryPersistence, getAuth } from 'firebase/auth';
import { ref, onValue, getDatabase } from 'firebase/database';

let unsubscribe, auth, renewal, updateTimer, pending = false, stopped = false;
function disconnect() {
    unsubscribe?.();
    unsubscribe = undefined;
    clearTimeout(updateTimer);
}
async function renew() {
    if (pending || stopped) return;
    pending = true;
    try {
        const response = await fetch('/api/firebase/session', {
            method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        });
        if (!response.ok) throw new Error('Live feed unavailable');
        const session = await response.json();
        if (stopped) return;
        if (!session.enabled) {
            disconnect();
            if (auth) await signOut(auth);
            return;
        }
        const app = getApps().find(app => app.name === 'agrisentry-live')
            ?? initializeApp(session.config, 'agrisentry-live');
        auth = getAuth(app);
        await setPersistence(auth, inMemoryPersistence);
        await signInWithCustomToken(auth, session.token);
        if (stopped) { await signOut(auth); return; }
        disconnect();
        unsubscribe = onValue(ref(getDatabase(app), session.path), snapshot => {
            clearTimeout(updateTimer);
            updateTimer = setTimeout(() => {
                window.dispatchEvent(new CustomEvent('agrisentry:telemetry', { detail: snapshot.val() }));
            }, 250);
        }, disconnect);
    } catch {
        disconnect();
        if (auth) await signOut(auth).catch(() => {});
        // Existing API polling continues while Firebase is unavailable.
    } finally { pending = false; }
}
function start() {
    stopped = false;
    void renew();
    clearInterval(renewal);
    renewal = setInterval(renew, 5 * 60 * 1000);
}
start();
window.addEventListener('pageshow', event => { if (event.persisted) start(); });
window.addEventListener('pagehide', () => {
    stopped = true;
    clearInterval(renewal);
    disconnect();
    if (auth) void signOut(auth).catch(() => {});
});
