# Account and logbook updates

## Settings and report dates

Open **Settings** in the web dashboard or **More → Profile & password settings** on mobile. Users can update their name, username, recovery email, and primary phone after confirming their current password. Roles remain assigned by Admin. The Settings page also provides password changes and, for Admins, links to account management and access/activity controls. Account screens share the dashboard typography and theme colors.

Reports Analytics supports inclusive start/end dates; use the same date for a single day. **Clear dates** restores all records, and the existing Day/Week/Month/Year presets remain available. Dates use the application's timezone (currently UTC). Temperature statistics are calculated from health logs in the period; status counts use each goat's latest health record in the period. Empty periods display zero counts and unavailable temperatures. Medical records use `date_given`, falling back to their creation timestamp when no date was saved.

Login identifies Admin, Cooperative Staff (`Staff` internally), or Caretaker from the account. The first login offers a password change; users can also change it later from the dashboard or mobile settings.

Admins can open **Manage Users → Access control & activity logs** (`/admin/access`) to configure view/write permissions for Staff and Caretakers and review login, logout, password, and successful API change events. Logs start when this update is installed. Changes to permissions are enforced by the API on subsequent requests. Reopen the mobile app to refresh displayed permissions. Existing Firebase live grants expire within 15 minutes; restricted roles cannot renew them. Standard API refresh remains available.

Register a real recovery email in Manage Users. Existing placeholder `@agrisentry.local` addresses cannot receive OTPs; use **Edit recovery contact** to replace them. Phone recovery uses the primary registered phone (or the first registered number).

Email delivery requires an SMTP or other production mail transport configured using the existing `config/mail.php` settings (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). The log and array transports deliberately do not issue recovery codes. SMS delivery uses the existing `SEMAPHORE_API_KEY` and sender settings. Supply credentials through your local environment; do not commit them. Run `php artisan config:clear` after changing configuration. Actual inbox/SMS delivery must be verified with a registered test account after configuring a provider.

OTPs expire after 10 minutes, allow five failed attempts, and can be used once. Requests are throttled. Password reset invalidates existing API tokens and database-backed sessions. No plaintext OTP is saved to the activity or SMS notification tables.

New registrations use Ear Tag as the single identifier; `code` (Goat ID) is copied from it automatically. Letters, numbers, hyphens, and underscores are accepted, and the identifier must be unique. Existing records are retained; editing an ear tag updates its Goat ID. Older clients submitting only a GT-format code remain compatible. Registration also includes Goat Color and a breed dropdown, with no Barn or manual temperature entry.

Motion history counts only specific motion anomalies. Health record responses include `motion_anomaly` for Prolonged Inactivity or Excessive Movement when present in the stored movement, event, or description. Historical records without a specific anomaly are not assigned an invented diagnosis.

For another installation, apply the additive migration with `php artisan migrate`. Backend regression tests run with `php artisan test`; mobile types with `npx tsc --noEmit` after Expo generates route types.

## Downloads, filtering, and branding

Report Analytics exports PDF or CSV using the applied period or date range. Medical Records exports all record types together in one paginated PDF, with an optional goat-specific download on the profile. PDFs contain the saved record details; reference attachments are listed by filename and remain available separately in the app. Export access follows the feature's view permission and downloads are recorded in the activity log. Android uses the system folder picker to save; iOS opens the share/save sheet.

Health Logs supports all motion, any anomaly, Prolonged Inactivity, Excessive Movement, and no-anomaly filters together with dates and temperature categories. Web CSV export uses those filters. Settings separates personal information, security, and Admin functions. Screen branding now uses LOGO AGRISENTRY (V2).png without the goat.

## Three-step recovery and sign-in OTP

Recovery now requests a code, verifies it, then shows the new-password form. Entering six digits submits verification automatically; the Verify button remains available. The server exchanges a valid recovery OTP for an account-bound, single-use reset authorization that expires in 10 minutes. Clients must POST /api/password/verify with username and otp, then POST /api/password/reset with username, reset_token, password, and password_confirmation.

Every new web/mobile sign-in requires the password plus an OTP delivered to the registered email, or registered phone when no valid email is available. No authenticated session or mobile access token is created before verification. Mobile login returns a challenge; submit it with otp to /api/mobile/login/verify. Resend uses /api/mobile/login/resend and returns a replacement challenge. Codes expire in 10 minutes and lock after five incorrect attempts. A password change invalidates pending login challenges.

Apply the new migration with php artisan migrate. Configure a real mail transport or Semaphore SMS delivery before users sign out: log/array mail transports and missing SMS credentials cannot deliver OTPs, and sign-in fails closed. Existing signed-in sessions are retained. Verify actual delivery with a registered test account after provider setup; automated tests use fake delivery.
