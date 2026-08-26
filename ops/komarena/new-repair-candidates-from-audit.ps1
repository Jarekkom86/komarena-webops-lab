#requires -Version 5.1

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$AuditJsonPath,
    [Parameter()][ValidateSet('critical','high','medium','low','info')][string[]]$Severity = @('critical','high','medium'),
    [Parameter()][string]$OutputPath
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $AuditJsonPath)) {
    throw "Audit JSON not found: $AuditJsonPath"
}

$audit = Get-Content -LiteralPath $AuditJsonPath -Raw -Encoding UTF8 | ConvertFrom-Json
if (-not $audit.meta -or $audit.meta.app -notlike 'KomArena Web Auditor*') {
    throw 'Input is not a recognized KomArena Web Auditor JSON export.'
}

$issues = @($audit.issues)
if ($issues.Count -eq 0) {
    throw 'Audit JSON contains no issues array or it is empty.'
}

$selected = @($issues | Where-Object { $Severity -contains ([string]$_.severity).ToLowerInvariant() })

function Get-RootCauseKey {
    param($Issue)

    $title = ([string]$Issue.title).Trim().ToLowerInvariant()
    $category = ([string]$Issue.category).Trim().ToLowerInvariant()
    $fix = ([string]$Issue.fix).Trim().ToLowerInvariant()

    $normalized = $title `
        -replace '\b\d+(?:[.,]\d+)?\s*(?:ms|s|kb|mb|%)\b', '<metric>' `
        -replace '\s+', ' '

    if ($category -eq 'accessibility' -and $title -match 'prístupn|accessible|label|aria') {
        return 'accessibility:form-control-accessible-name'
    }
    if ($category -eq 'performance' -and $title -match 'pomal|slow|http') {
        return 'performance:http-response-latency'
    }
    if ($category -eq 'security' -and ($title -match 'strict-transport-security|hsts' -or $fix -match 'strict-transport-security|hsts')) {
        return 'security:hsts'
    }
    if ($title -match 'alt') {
        return "$category:image-alt"
    }
    if ($title -match 'meta description') {
        return "$category:meta-description"
    }
    if ($title -match '\bh1\b') {
        return "$category:h1"
    }
    if ($title -match 'canonical') {
        return "$category:canonical"
    }
    if ($title -match 'broken|nefunkčn.*odkaz|link') {
        return "$category:links"
    }

    return "$category:$normalized"
}

$candidateGroups = $selected | Group-Object -Property { Get-RootCauseKey $_ }
$candidates = [System.Collections.Generic.List[object]]::new()

foreach ($group in $candidateGroups) {
    $groupIssues = @($group.Group)
    $first = $groupIssues[0]
    $severities = @($groupIssues | ForEach-Object { ([string]$_.severity).ToLowerInvariant() } | Sort-Object -Unique)
    $urls = @($groupIssues | ForEach-Object { [string]$_.url } | Where-Object { $_ } | Sort-Object -Unique)

    $candidateId = 'repair-' + ([BitConverter]::ToString(
        [Security.Cryptography.SHA256]::Create().ComputeHash(
            [Text.Encoding]::UTF8.GetBytes($group.Name)
        )
    ).Replace('-', '').Substring(0, 16).ToLowerInvariant())

    $candidates.Add([pscustomobject]@{
        candidate_id = $candidateId
        root_cause_key = $group.Name
        category = [string]$first.category
        title = [string]$first.title
        severity = $severities
        occurrence_count = $groupIssues.Count
        urls = $urls
        evidence = @($groupIssues | Select-Object severity, category, title, detail, fix, url)
        auditor_fix_hint = [string]$first.fix
        state = 'DETECTED'
        confirmation_required = $true
        write_task_generated = $false
        recommended_next_action = 'CONFIRM_ROOT_CAUSE'
    })
}

$result = [ordered]@{
    schema = 'komarena-repair-candidates/v1'
    source = [ordered]@{
        app = [string]$audit.meta.app
        version = [string]$audit.meta.version
        target = [string]$audit.meta.target
        started_at = [string]$audit.meta.started_at
        finished_at = [string]$audit.meta.finished_at
        pages_scanned = if ($audit.meta.pages_scanned) { [int]$audit.meta.pages_scanned } else { $null }
        audit_sha256 = (Get-FileHash -LiteralPath $AuditJsonPath -Algorithm SHA256).Hash.ToLowerInvariant()
    }
    policy = [ordered]@{
        read_only_planning = $true
        severity_filter = @($Severity)
        auto_write = $false
        require_live_confirmation = $true
        require_backup_before_write = $true
        require_verification_after_write = $true
    }
    counts = [ordered]@{
        issues_total = $issues.Count
        issues_selected = $selected.Count
        repair_candidates = $candidates.Count
    }
    candidates = @($candidates)
}

if (-not $OutputPath) {
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $base = [IO.Path]::GetFileNameWithoutExtension($AuditJsonPath)
    $OutputPath = Join-Path (Split-Path -Parent $AuditJsonPath) "$base-repair-candidates-$stamp.json"
}

$result | ConvertTo-Json -Depth 30 | Set-Content -LiteralPath $OutputPath -Encoding UTF8

Write-Host "Repair candidates created: $($candidates.Count)"
Write-Host "No WordPress write task was generated."
Write-Host "Output: $OutputPath"

$result
