# Configured monitoring thresholds

Temperature rules agreed for this installation:

- Urgent Low: below 34.5 C
- Warning Low: 34.5 C up to, but not including, 35.5 C
- Normal: 35.5 C through 37.2 C inclusive
- Warning High: above 37.2 C through 38.2 C inclusive
- Urgent High: above 38.2 C

At one-decimal display precision the warning bands are 34.5–35.4 C and 37.3–38.2 C. Comparisons use the supplied numeric reading without a gap between bands.

Motion: prolonged inactivity at 90 minutes; excessive movement only after more than 15 seconds. The collar firmware determines motion duration and sends the anomaly label to Laravel. Flash the updated `agrisentry-collar/agrisentry-collar.ino` onto collars to apply its new timing and temperature rules. Server changes alone cannot alter firmware already installed on a collar.

The mobile temperature display source has also been updated; an installed APK must be rebuilt and updated to receive those client-side changes. Backend AI recommendations and new server alerts use the updated rules immediately. Existing goat status is refreshed on its next telemetry update. Saved alert history is retained.
