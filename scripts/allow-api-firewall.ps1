# Run PowerShell as Administrator, then:
#   Set-ExecutionPolicy -Scope Process Bypass
#   .\scripts\allow-api-firewall.ps1

$ruleName = "Monefy API 8081"
$existing = Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue
if ($existing) {
  Write-Host "Firewall rule already exists: $ruleName"
} else {
  New-NetFirewallRule -DisplayName $ruleName -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8888 -Profile Private
  Write-Host "Created firewall rule: $ruleName (Private network)"
}
Write-Host "Done. Ensure your PC network is set to Private, not Public."
