# Publishes the already-running Expo dev server (plain `npx expo start`, port 8081) to a
# public HTTPS URL, without touching ngrok — ngrok's free plan only allows one endpoint,
# and that one is already used by the Laravel API tunnel (see Agrisentry/start-mobile-backend.ps1).
# Do NOT use `expo start --tunnel` — it also wants ngrok and will fail with "failed to start tunnel".
#
# The URL below changes every time this script runs; it's for ad-hoc preview only, never baked
# into a build.

& "C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://localhost:8081
