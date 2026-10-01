# Run only after the computer owner approves this system-wide change.
# Usage from an Administrator PowerShell: .\adjust-tcp-capacity.ps1
# Restore the original setting: .\adjust-tcp-capacity.ps1 -Restore
param([switch]$Restore)
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (!$principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script from an Administrator PowerShell after approving the system-wide port-range change.'
}
if ($Restore) {
    netsh int ipv4 set dynamicport tcp start=49152 num=16384
} else {
    netsh int ipv4 set dynamicport tcp start=32768 num=32768
}
if ($LASTEXITCODE -ne 0) { throw 'Windows did not apply the TCP range setting.' }
netsh int ipv4 show dynamicport tcp
