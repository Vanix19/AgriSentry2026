# Configured monitoring thresholds

Temperature rules agreed for this installation:

- Urgent Low: below 32.0 C
- Warning Low: 32.0 C up to, but not including, 33.0 C
- Normal: 33.0 C through 38.5 C inclusive
- Warning High: above 38.5 C through 39.5 C inclusive
- Urgent High: above 39.5 C

At one-decimal display precision the warning bands are 32.0–32.9 C and 38.6–39.5 C. Comparisons use the supplied numeric reading without a gap between bands.

Motion: prolonged inactivity at 90 minutes; excessive movement only after more than 15 seconds. The collar firmware determines motion duration and sends the anomaly label to Laravel. Flash the updated `agrisentry-collar/agrisentry-collar.ino` onto collars to apply its new timing and temperature rules. Server changes alone cannot alter firmware already installed on a collar.

The mobile temperature display source has also been updated; an installed APK must be rebuilt and updated to receive those client-side changes. Backend AI recommendations and new server alerts use the updated rules immediately. Existing goat status is refreshed on its next telemetry update. Saved alert history is retained.
