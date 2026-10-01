# Android update 1.0.2

Prepared using the existing com.agrisentry.mobile package and Expo preview APK profile. The existing remote signing credentials and auto-incremented Android version code are required for an in-place update.

Included: three-step recovery, sign-in OTP and resend, assigned roles, profile/settings, date-filtered reports with motion counts, branded PDF and CSV exports, combined medical downloads, full saved movement findings, responsive animal tiles, hidden scroll indicators, resend confirmation banner, and grouped live refreshes.

Release checks: native API URL must use HTTPS; Android session token uses SecureStore; API tokens are issued only after OTP verification; malformed API responses are rejected; server-side feature permissions remain enforced. Backend credentials and local environment files are excluded by .easignore.

The backend URL is the existing ngrok address. Laravel, MySQL, Apache and the tunnel must remain online. This is a targeted regression review, not a full security audit. Installation on the user's device remains to be tested.
