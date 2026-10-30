# NANA simulated October history

This optional Artisan command adds synthetic demonstration history for NANA. It does not run during deployment or migrations. Every row is labeled `Simulated Reading` and its description states that it is not a sensor measurement.

After merging and deploying, run from the Laravel application directory in Render Shell:

```sh
php artisan history:simulate-nana
php artisan history:simulate-nana --write
```

The first command previews without writing. Defaults are goat ID **4**, expected name **NANA** (case insensitive), year **2026**, timezone **Asia/Shanghai**. Override with `--goat-id=4 --year=2026 --timezone=Asia/Shanghai` if needed. Local times are converted to UTC for storage, matching the application's default UTC database timestamps. If the deployment uses a different timestamp convention, reconcile it before importing. Screens that display UTC will show shifted calendar-day boundaries; the daily counts here use the selected local timezone.

| Local date | Temperature readings | Motion readings | Period |
| --- | ---: | ---: | --- |
| October 28 | 720 | 720 | 00:00–23:58 |
| October 29 | 720 | 720 | 00:00–23:58 |
| October 30 | 720 | 720 | 00:00–23:58 |
| October 31 | 450 | 450 | 00:00–14:58 (15:00 exclusive cutoff) |

There are **2,610 combined rows**, each containing both temperature and movement, spaced two minutes apart. Normal temperature readings stay within **35.5–37.2°C**. Daily low spikes at 02:00–02:18 range from 35.10–35.37°C; high spikes at 12:00–12:18 range from 37.30–37.66°C. Excessive Movement appears at **07:00, 09:00, and 11:00 each day**. On October 28, 90 minutes of Low Movement precede a **Prolonged Inactivity** event at **01:30**. The schedule and temperature variation are deterministic.

The command checks all four local calendar days and refuses overlapping existing history, including readings after the October 31 cutoff. It never deletes or replaces existing readings. An exact repeat is a no-op; partial or altered imports fail instead of duplicating rows. Cooperating imports lock the goat row and insert in a single transaction. Stop live collar ingestion for this goat during import: the ordinary ingestion path does not share this command's lock.

Only history rows are inserted. NANA's current state is not overwritten, no alerts or SMS/email are sent, and nothing is published to Firebase. Simulated rows are visible in existing history and report queries and will therefore contribute to analytics. October 31 data can be future-dated if imported before that date.

Verification: `php artisan test --filter=NanaSimulatedHistoryTest`. The standalone generator check can also run without Laravel dependencies: `php tests/nana-history-check.php`.
