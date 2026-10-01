# Local server for ngrok

AgriSentry uses a dedicated XAMPP Apache instance on 127.0.0.1:8000. This replaces `php artisan serve` for the public tunnel and does not change the main XAMPP Apache configuration.

After restarting Windows, run `powershell -ExecutionPolicy Bypass -File .\local-server\start.ps1` from the Laravel directory. Then run `ngrok http http://127.0.0.1:8000`. Do not also run `php artisan serve` on port 8000.

The server serves only the public directory. Its configuration uses this installation's absolute paths; update them if moving the project. Logs and PID files remain in this folder and are ignored by Git.

This single-server installation uses `CACHE_STORE=file` to keep cache and rate-limit traffic off MySQL. Keep `storage/framework/cache/data` writable by Apache. Use `APP_DEBUG=false` on the public tunnel. After environment changes, run `php artisan config:cache`. Database sessions and application records still use MySQL.

`DB_PERSISTENT=true` enables PDO connection reuse for this server to reduce MySQL TCP connection churn. It defaults off for other installations. Apache preloads the matching PHP OpenSSL and SSH libraries so cURL does not load incompatible copies from Apache's bin folder. Dashboard automatic refresh downloads only newly saved health logs and skips unchanged panels; manual Refresh reloads the complete history. Fallback polling is every 30 seconds, with live updates grouped over five seconds.

Windows TCP port exhaustion remained intermittent during verification (over 17,000 TIME_WAIT connections observed). The system-wide port range has NOT been changed. `adjust-tcp-capacity.ps1` is a proposed, owner-approved mitigation only: it doubles IPv4 TCP dynamic ports to 32768–65535. `-Restore` restores the observed original 49152–65535 range. Review other services before approving; expanding capacity does not cure a process that continually exhausts connections. Microsoft reference: https://learn.microsoft.com/en-us/troubleshoot/windows-client/networking/tcp-ip-port-exhaustion-troubleshooting

Urgent health emails use the existing SMTP mail settings and the dedicated `email-alerts` database queue. `start.ps1` starts its hidden worker, even when Apache is already running. To start it separately, run `powershell -ExecutionPolicy Bypass -File .\local-server\start-email-worker.ps1`. After changing worker code, restart the worker. Check `email-worker-error.log`, Laravel logs, and `php artisan queue:failed` for delivery failures. Gmail transport acceptance does not guarantee inbox placement.

New active High/Urgent/Critical alerts notify Admin, Staff and Caretaker accounts with valid registered email addresses; placeholder `.local` addresses are skipped. Existing historical alerts are not emailed. The evaluator suppresses the same active finding for 30 minutes. Each email retries failures up to three times; successful delivery attempts are cached for seven days to prevent routine retries from resending. SMTP cannot guarantee exactly-once delivery if the process stops after acceptance. Resolved alerts are skipped when a queued email is processed. Semaphore is used only if configured.
