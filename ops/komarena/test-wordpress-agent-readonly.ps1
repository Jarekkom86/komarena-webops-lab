#requires -Version 5.1

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][ValidatePattern('^https://')][string]$SiteUrl,
    [Parameter(Mandatory = $true)][string]$SiteId,
    [Parameter()][string]$ClientPath = (Join-Path $PSScriptRoot 'nexus-wordpress-agent-client.ps1'),
    [Parameter()][string]$OutputPath
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $ClientPath)) {
    throw "Nexus WordPress Agent client not found: $ClientPath"
}

. $ClientPath

$secret = $env:KOMARENA_AGENT_SECRET
if ([string]::IsNullOrWhiteSpace($secret)) {
    $secure = Read-Host 'KomArena Agent pairing secret' -AsSecureString
    $ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        $secret = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
    }
}

if ([string]::IsNullOrWhiteSpace($secret)) {
    throw 'Pairing secret is required.'
}

$started = [DateTimeOffset]::UtcNow
$checks = [System.Collections.Generic.List[object]]::new()

function Add-CheckResult {
    param([string]$Name, [scriptblock]$Script)

    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $data = & $Script
        $sw.Stop()
        $checks.Add([pscustomobject]@{
            name = $Name
            ok = $true
            duration_ms = [int]$sw.ElapsedMilliseconds
            data = $data
            error = $null
        })
    }
    catch {
        $sw.Stop()
        $checks.Add([pscustomobject]@{
            name = $Name
            ok = $false
            duration_ms = [int]$sw.ElapsedMilliseconds
            data = $null
            error = $_.Exception.Message
        })
    }
}

Add-CheckResult 'status' {
    Get-KomArenaAgentStatus -SiteUrl $SiteUrl -SiteId $SiteId -Secret $secret
}

Add-CheckResult 'capabilities' {
    Get-KomArenaAgentCapabilities -SiteUrl $SiteUrl -SiteId $SiteId -Secret $secret
}

Add-CheckResult 'site.inspect' {
    Invoke-KomArenaAgentTask `
        -SiteUrl $SiteUrl `
        -SiteId $SiteId `
        -Secret $secret `
        -TaskId ('smoke-site-' + [guid]::NewGuid().ToString('N')) `
        -IdempotencyKey ('smoke-site-' + [guid]::NewGuid().ToString('N')) `
        -Action 'site.inspect'
}

Add-CheckResult 'health.check' {
    Invoke-KomArenaAgentTask `
        -SiteUrl $SiteUrl `
        -SiteId $SiteId `
        -Secret $secret `
        -TaskId ('smoke-health-' + [guid]::NewGuid().ToString('N')) `
        -IdempotencyKey ('smoke-health-' + [guid]::NewGuid().ToString('N')) `
        -Action 'health.check'
}

Add-CheckResult 'plugin.list' {
    Invoke-KomArenaAgentTask `
        -SiteUrl $SiteUrl `
        -SiteId $SiteId `
        -Secret $secret `
        -TaskId ('smoke-plugins-' + [guid]::NewGuid().ToString('N')) `
        -IdempotencyKey ('smoke-plugins-' + [guid]::NewGuid().ToString('N')) `
        -Action 'plugin.list'
}

$finished = [DateTimeOffset]::UtcNow
$passed = @($checks | Where-Object ok).Count
$failed = @($checks | Where-Object { -not $_.ok }).Count

$report = [ordered]@{
    schema = 'komarena-agent-smoke/v1'
    read_only = $true
    site_url = $SiteUrl
    site_id = $SiteId
    started_at = $started.ToString('o')
    finished_at = $finished.ToString('o')
    passed = $passed
    failed = $failed
    checks = @($checks)
}

if (-not $OutputPath) {
    $safeHost = ([uri]$SiteUrl).Host -replace '[^a-zA-Z0-9.-]', '_'
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $OutputPath = Join-Path $PSScriptRoot "wordpress-agent-smoke-$safeHost-$stamp.json"
}

$parent = Split-Path -Parent $OutputPath
if ($parent -and -not (Test-Path -LiteralPath $parent)) {
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
}

$report | ConvertTo-Json -Depth 30 | Set-Content -LiteralPath $OutputPath -Encoding UTF8

$checks | Select-Object name, ok, duration_ms, error | Format-Table -AutoSize
Write-Host "Read-only smoke report: $OutputPath"

if ($failed -gt 0) {
    exit 2
}

exit 0
