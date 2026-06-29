$ErrorActionPreference = 'Stop'

$backendRoot = 'c:\laragon\www\growai\backend'
$outFile = Join-Path $backendRoot 'status-write-audit.txt'

# Use plain-text patterns only (no regex, no quotes/backticks) to avoid PowerShell parsing issues.
$searchPatterns = @(
  '->status',
  ' setStatus(',
  'setStatus(',
  'OrderStateMachine',
  'canTransitionTo(',
  'transitionTo(',
  'OrderStatusChanged',
  'status_changed',
  'LogOrderHistory',
  'order_status_hist',
  'status_history',
  'ChangeOrderStatusHandler',
  'BulkUpdateOrderStatus',
  'changeOrderStatus',
  'ChangeOrderStatus'
)

$targets = @('app','routes','database')
$out = New-Object System.Collections.Generic.List[string]

foreach ($t in $targets) {
  foreach ($pat in $searchPatterns) {
    $out.Add("===== TARGET=$t PATTERN=$pat =====")

    $basePath = Join-Path $backendRoot $t
    if (-not (Test-Path $basePath)) { continue }

    Get-ChildItem -Path $basePath -Recurse -Filter '*.php' | ForEach-Object {
      $f = $_.FullName

      try {
        # Select-String with -SimpleMatch avoids regex parsing.
        $matches = Select-String -Path $f -Pattern $pat -SimpleMatch -AllMatches
        foreach ($m in $matches) {
          $out.Add(("{0}:{1}`n{2}`n---" -f $m.Path, $m.LineNumber, $m.Line))
        }
      } catch {
        # ignore unreadable files
      }
    }

  }
}

$out.Add('===== DONE =====')
$out | Out-File -FilePath $outFile -Encoding utf8
Write-Host "WROTE: $outFile"
