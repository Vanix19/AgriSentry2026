# Firebase live sensor feed

Laravel remains responsible for usernames/passwords, roles, goat profiles, health logs and LoRaWAN ingestion. Firebase mirrors the latest collar readings; it is not a replacement backend and does not fix an unavailable Laravel tunnel.

## Activate

1. Save the Firebase service-account JSON for this project outside the public directory, for example `storage/app/private/firebase-service-account.json`. Never put its contents in JavaScript, mobile builds, firmware or version control.
2. Set `FIREBASE_CREDENTIALS` to its absolute path in Laravel's `.env`. The existing Firebase web configuration has been copied into `VITE_FIREBASE_*` variables there. Use `.env.example` for a fresh installation.
3. Review and deploy `database.rules.json` to the intended Firebase project. With Firebase CLI installed and logged in: `firebase deploy --only database --project agrisentry-dc290` from this directory. These rules deny all browser/device writes; only authenticated users with unexpired server-issued farm grants can read telemetry. Existing database paths are denied by these rules, so review before deployment if the database already contains another application.
4. Set `FIREBASE_QUEUE_CONNECTION=database`. Run `npm run build` in the Laravel directory. Set `FIREBASE_ENABLED=true`, then run `php artisan config:clear`. Restart the mobile development server after installing its dependencies; rebuild installed APKs to include the Firebase SDK.
5. Log in normally. The app exchanges its Laravel session for a Firebase custom token. It renews the farm read grant every five minutes; grants expire after fifteen minutes without a valid Laravel session. Firebase auth is held in memory and signed out when the app session ends. The current app has one shared farm, matching its existing roles and herd access.
6. Send an authenticated LoRaWAN reading. Confirm `farms/agrisentry/telemetry/{collarId}` appears in Realtime Database and that the screens update. Existing API polling remains available if Firebase fails. Firebase publishing runs on the database queue with five attempts and increasing delays. Run `php artisan queue:work database --sleep=1 --tries=5 --timeout=30` continuously. Ensure `php artisan migrate` has created the queue tables. Use one worker for this telemetry queue to preserve write order. Inspect exhausted jobs with `php artisan queue:failed` and retry them after fixing the connection with `php artisan queue:retry all`. Each attempt loads the latest collar state. Queue dispatch failures are logged; local readings are retained.

## Devices and optional App Check

Keep ESP32 â†’ LoRaWAN â†’ trusted network-server webhook â†’ Laravel â†’ Firebase. Devices do not receive service-account keys or database write permission. Set a nonempty `LORAWAN_SHARED_SECRET` and configure the same `X-LoRaWAN-Secret` in the network-server webhook before exposing ingestion publicly. LoRaWAN device authentication continues to use the device OTAA keys.

App Check is not enabled by this change. It needs separate web/native app registration and provider setup; enabling enforcement before that setup would block clients. Firebase live updates still depend on the collar's transmission interval.

## Verification

Run `php artisan test`, `npm run build`, and mobile `npx tsc --noEmit`. Tests use local fixtures and do not publish real sensor records. A live end-to-end test requires the service-account file, deployed rules and a working Laravel endpoint.

References: [Custom authentication](https://firebase.google.com/docs/auth/admin/create-custom-tokens), [REST server authentication](https://firebase.google.com/docs/database/rest/auth), [Database rules](https://firebase.google.com/docs/database/security/rules-conditions).

