$project = Split-Path $PSScriptRoot -Parent
$artisan = Join-Path $project 'artisan'
$existing = Get-CimInstance Win32_Process -Filter "name = 'php.exe'" | Where-Object { $_.CommandLine -like '*queue:work*email-alerts*' }
if ($existing) { Write-Host 'Email alert worker is already running.'; exit }
Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList "`"$artisan`" queue:work database --queue=email-alerts --sleep=5 --tries=3 --timeout=45" -WorkingDirectory $project -WindowStyle Hidden -RedirectStandardOutput (Join-Path $PSScriptRoot 'email-worker.log') -RedirectStandardError (Join-Path $PSScriptRoot 'email-worker-error.log')
Write-Host 'Email alert worker started.'
