$configPath = Join-Path $PSScriptRoot 'httpd.conf'
& C:\xampp\php\php.exe (Join-Path $PSScriptRoot 'check-database.php')
if ($LASTEXITCODE -ne 0) { throw 'Backend startup stopped because the database is unavailable.' }
& (Join-Path $PSScriptRoot 'start-email-worker.ps1')
& C:\xampp\apache\bin\httpd.exe -t -f $configPath
if ($LASTEXITCODE -ne 0) { throw 'Apache configuration check failed.' }
$listener = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if ($listener) { Write-Host 'Port 8000 is already in use. Check the running server before starting another instance.'; exit }
Start-Process -FilePath C:\xampp\apache\bin\httpd.exe -ArgumentList "-f `"$configPath`"" -WindowStyle Hidden
Write-Host 'AgriSentry Apache started on http://127.0.0.1:8000. Use ngrok http http://127.0.0.1:8000.'
