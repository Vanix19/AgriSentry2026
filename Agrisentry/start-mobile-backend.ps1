# Starts the Laravel API reachable from anywhere (LAN + internet) for the mobile app / built APK.
# Run this instead of `php artisan serve` whenever you're testing the mobile app or building an APK.

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

& C:\xampp\php\php.exe (Join-Path $PSScriptRoot 'local-server\check-database.php')
if ($LASTEXITCODE -ne 0) { throw 'Backend startup stopped because the database is unavailable.' }

Write-Host "Starting Laravel on 0.0.0.0:3000..."
if (-not (Get-NetTCPConnection -LocalPort 3000 -State Listen -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath "C:\xampp\php\php.exe" -ArgumentList "artisan serve --host=0.0.0.0 --port=3000" -WorkingDirectory $PSScriptRoot -WindowStyle Hidden
}

Start-Sleep -Seconds 2
$health = Invoke-WebRequest -Uri 'http://127.0.0.1:3000/up' -UseBasicParsing -TimeoutSec 10
if ($health.StatusCode -ne 200) { throw 'Backend health check failed.' }

Write-Host "Starting ngrok tunnel on the reserved domain..."
if (-not (Get-Process ngrok -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath "ngrok" -ArgumentList "http 3000 --domain=pending-untaken-shampoo.ngrok-free.dev" -WindowStyle Hidden
}

Start-Sleep -Seconds 3
Write-Host "API should now be reachable at: https://pending-untaken-shampoo.ngrok-free.dev/api"
Write-Host "This matches EXPO_PUBLIC_API_URL in agrisentry-mobile/.env and eas.json."
