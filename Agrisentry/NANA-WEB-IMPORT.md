# Import NANA history without Render Shell

Merge this change and deploy the latest commit on Render. No paid Shell or one-off job is required. Nothing is inserted by deployment alone.

1. Sign in to AgriSentry as Admin.
2. Open **Settings → Administration → NANA simulated history** (or `/admin/nana-history`).
3. Pause NANA's live collar ingestion for the import.
4. Review existing counts, additions, and totals for October 28–31, 2026, using Asia/Shanghai time. The last day ends before 15:00.
5. Acknowledge that the readings are simulated and click **Import simulated readings**.
6. Open **View NANA's profile** and refresh. Resume live ingestion when ready.

The import preserves all existing history, fills unoccupied two-minute slots, and counts existing rows toward daily targets (720 for full days, 450 for October 31). Every new row contains temperature and motion. Existing rows after the cutoff are preserved and counted, too. If existing data already exceeds the daily target, no readings are added to that day. Available space may be too limited to add all scheduled motion anomalies; the preview reports skipped events. No existing measurement is altered to force an anomaly or temperature band.

The server requires goat #4 to be named NANA and enforces Admin authentication, CSRF protection, throttling, an unchanged session preview, and an atomic import. Repeating an import adds nothing once all slots/targets are filled. Audit logs record imports. Live ingestion does not participate in the import lock, so pause ingestion to maintain the preview counts. UTC storage follows the application's timestamp convention; some screens may display calendar boundaries in UTC instead of the local schedule.

All added rows are labeled simulated and contribute to existing history/report analytics. Current goat state, Firebase, alerts and SMS/email are not modified. This browser workflow supplements the original CLI command: the CLI still refuses overlaps, whereas the browser importer preserves existing rows and fills available slots.

Checks: `php tests/nana-import-check.php`; `php artisan test --filter=NanaHistoryPageTest`.
