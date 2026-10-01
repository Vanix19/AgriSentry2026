# Supplied Heltec sketch integration

The supplied `Agrisentry.ino` and `Sensors.ino` send five bytes:

| Bytes | Meaning |
| --- | --- |
| 0–1 | Signed big-endian temperature × 10; -1 means unavailable |
| 2 | Motion: 0 Normal, 1 Prolonged Inactivity, 2 Active, 3 Excessive Movement |
| 3 | Flags: 0x04 temperature error, 0x20 motion offline, 0x40 temperature offline |
| 4 | Measured battery percentage, 0–100 |

The Laravel receiver now accepts this layout as well as the existing six-byte
repository firmware. When TTN provides `frm_payload`, raw bytes take precedence
over an outdated decoded payload. No Arduino upload is needed for this receiver fix.

For consistent values in TTN itself, replace its custom uplink formatter with
`agrisentry-collar/ttn-payload-formatter.js`. It supports both layouts.

The supplied Motion.ino intentionally requires 90 minutes of continuous inactivity
or 15 seconds of sustained excessive movement before reporting an anomaly.
Normal short rests do not produce an inactivity alert.

Battery values update when a new uplink reaches the app; the dashboard refreshes
periodically. A disconnected collar cannot provide a new percentage. Check Last
Seen in Collars to distinguish an old reading from a new one. Existing stored
percentages are not fabricated or changed by this fix: the next uplink replaces them.

If successive raw packets still have the same fifth byte, inspect battery sensing
on the device. The supplied Sensors.ino converts ADC voltage to an estimated
percentage using board-specific pin, enable polarity, and divider settings. Verify
those settings against the exact board revision and a measured battery voltage
before changing them. The software tests verify packet decoding, not physical
voltage calibration or radio delivery.
