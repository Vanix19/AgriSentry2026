# Render deployment

Keep the Docker runtime and Apache. In Render use root directory `Agrisentry`,
<<<<<<< Updated upstream
Docker build context `.`, Dockerfile path `Dockerfile`, and no Docker
=======
Docker build context `.`, Dockerfile path `Dockerfile/Dockerfile`, and no Docker
>>>>>>> Stashed changes
command override (so the new startup script runs). Health check: `/up`.

The local `.env` is deliberately excluded from the image. Set these in Render:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://agrisentry2026.onrender.com
APP_KEY=<retain your existing valid Laravel application key>
LOG_CHANNEL=stderr
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=<online MySQL hostname reachable from Render>
DB_PORT=3306
DB_DATABASE=agrisentry_new_db
DB_USERNAME=<online username>
DB_PASSWORD=<online password>
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FIREBASE_QUEUE_CONNECTION=sync
SANCTUM_STATEFUL_DOMAINS=agrisentry2026.onrender.com
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*
FIREBASE_ENABLED=true
FIREBASE_FARM_ID=agrisentry
FIREBASE_CREDENTIALS=/etc/secrets/firebase-credentials.json
```

Remove stale `DB_URL` and `DB_SOCKET` overrides when using the individual MySQL
variables. If your MySQL provider requires a CA, mount it and set
`MYSQL_ATTR_SSL_CA` to its path. Allow Render to reach the provider's MySQL port.
Do not point DB_HOST at localhost: that refers to the container, not XAMPP.

Upload the Firebase service account as a Render secret file named
`firebase-credentials.json`. Set the existing `VITE_FIREBASE_API_KEY`,
`VITE_FIREBASE_AUTH_DOMAIN`, `VITE_FIREBASE_DATABASE_URL`,
`VITE_FIREBASE_PROJECT_ID`, and `VITE_FIREBASE_APP_ID` variables; the project ID
must match the service account. Firebase is separate from SQL authentication.

Login chooses email for a valid non-`.local` email, otherwise phone. Email OTP
requires an actual mail transport, for example `MAIL_MAILER=smtp` plus the
provider's `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
`MAIL_SCHEME`, and `MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME`. Confirm the provider's
port is reachable from your Render service. `log` and `array` cannot deliver OTPs.
Phone OTP requires a valid `SEMAPHORE_API_KEY` and provider-approved
`SEMAPHORE_SENDER_NAME`, plus a registered phone number. Missing/rejected delivery
now returns a validation error, never fabricated delivery success.

## Diagnosis and database requirements

`bootstrap/app.php` manually maps every QueryException on JSON/API requests to
503 with “The server is temporarily unavailable. Please wait a moment and try
again.” This includes login user lookup, `login_otps` writes, and database cache
used by throttle middleware. HTML password recovery does not meet that JSON/API
condition, so the same database exception uses Laravel's 500 response. The exact
production SQLSTATE is not available from this checkout; do not assume the host,
credentials, or schema is correct based on GET /login succeeding.

New sanitized error logs include exception type, driver message, code, file and
line without SQL bindings or stack arguments. Check Render logs after retrying
both requests to identify connection refusal, access denial, or missing tables.
Missing Semaphore alone previously returned false; it does not explain an
uncaught 500. The prior recovery controller nevertheless displayed a normal
response when delivery failed; that behavior is fixed.

An online MySQL database with the existing AgriSentry schema and accounts is
required for this production configuration. Firebase does not replace it.
Authentication uses `users`, `caretaker_phone_numbers`, `login_otps`,
`password_otps`, and other account tables. Relevant existing migrations include
`2026_09_12_000001_add_account_controls.php` and
`2026_09_13_000001_add_login_verification.php`.

No migrations or database modifications were run against your database. If logs
show missing tables, first review `php artisan migrate:status` on the intended
database and arrange a backup and migration review before applying any pending
migrations. Redeploy alone cannot add missing tables or provision MySQL.

Once environment/secrets/schema are correct, commit these changes and redeploy.
Startup clears config/routes/views and rebuilds config using runtime variables.
No manual cache commands are needed. File sessions are local to each container
and can be lost on restart; password changes cannot revoke other file sessions
by deleting SQL session rows. This preserves the requested file-session setup.
