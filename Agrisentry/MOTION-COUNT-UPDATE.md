# NANA October 28–29 motion counts

The generated baseline now has 398 Active and 2 Excessive Movement readings on October 28, and 354 Active and 4 Excessive Movement readings on October 29. Dates use Asia/Shanghai local time (UTC+8). October 28 Prolonged Inactivity remains unchanged. Reading totals, timestamps, temperatures and other dates are preserved.

For already imported history, the included migration counts real collar readings first and changes only NANA's generated movement/severity fields to meet these total daily targets. It preserves real readings and inactivity. The correction is atomic and repeatable. If real readings already exceed a target, or insufficient generated rows remain, the migration reports a conflict without applying any motion changes; it does not invent chart totals or rewrite real measurements. No alerts or notifications are dispatched.

Merge and deploy with normal Laravel migrations enabled. Pause NANA ingestion while applying the correction. No repeat import is needed for existing history. Fresh generators use the baseline targets; the browser fill-gaps importer can differ if mixed with existing readings, so a later import or live ingestion may change daily totals. Reapplying corrections should be reviewed if further readings arrive.

Check: `php tests/nana-motion-counts-check.php` and `php tests/nana-history-check.php`.
