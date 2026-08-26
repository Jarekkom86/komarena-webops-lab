#requires -Version 5.1

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function New-KomArenaAgentSignature {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][string]$Timestamp,
        [Parameter(Mandatory = $true)][string]$Nonce,
        [Parameter(Mandatory = $true)][string]$Method,
        [Parameter(Mandatory = $true)][string]$Route,
        [Parameter(Mandatory = $true)][AllowEmptyString()][string]$Body,
        [Parameter(Mandatory = $true)][string]$Secret
    )

    $canonical = @(
        $Timestamp,
        $Nonce,
        $Method.ToUpperInvariant(),
        $Route,
        $Body
    ) -join "`n"

    $encoding = [System.Text.Encoding]::UTF8
    $keyBytes = $encoding.GetBytes($Secret)
    $messageBytes = $encoding.GetBytes($canonical)
    $hmac = New-Object System.Security.Cryptography.HMACSHA256($keyBytes)

    try {
        $hashBytes = $hmac.ComputeHash($messageBytes)
        return ([System.BitConverter]::ToString($hashBytes)).Replace('-', '').ToLowerInvariant()
    }
    finally {
        $hmac.Dispose()
    }
}

function Invoke-KomArenaAgentRequest {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][ValidatePattern('^https://')][string]$SiteUrl,
        [Parameter(Mandatory = $true)][string]$SiteId,
        [Parameter(Mandatory = $true)][string]$Secret,
        [Parameter(Mandatory = $true)][ValidateSet('GET', 'POST')][string]$Method,
        [Parameter(Mandatory = $true)][ValidateSet('status', 'capabilities', 'tasks')][string]$Endpoint,
        [Parameter()][AllowNull()][object]$BodyObject
    )

    $base = $SiteUrl.TrimEnd('/')
    $route = "/komarena-agent/v1/$Endpoint"
    $uri = "$base/wp-json$route"
    $timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds().ToString()
    $nonce = [guid]::NewGuid().ToString('N')

    $body = ''
    if ($Method -eq 'POST') {
        if ($null -eq $BodyObject) {
            throw 'POST request requires BodyObject.'
        }
        $body = $BodyObject | ConvertTo-Json -Depth 20 -Compress
    }

    $signature = New-KomArenaAgentSignature `
        -Timestamp $timestamp `
        -Nonce $nonce `
        -Method $Method `
        -Route $route `
        -Body $body `
        -Secret $Secret

    $headers = @{
        'X-KomArena-Site'      = $SiteId
        'X-KomArena-Timestamp' = $timestamp
        'X-KomArena-Nonce'     = $nonce
        'X-KomArena-Signature' = $signature
        'Accept'               = 'application/json'
    }

    $params = @{
        Method      = $Method
        Uri         = $uri
        Headers     = $headers
        ErrorAction = 'Stop'
    }

    if ($Method -eq 'POST') {
        $params.ContentType = 'application/json; charset=utf-8'
        $params.Body = $body
    }

    Invoke-RestMethod @params
}

function Get-KomArenaAgentStatus {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][ValidatePattern('^https://')][string]$SiteUrl,
        [Parameter(Mandatory = $true)][string]$SiteId,
        [Parameter(Mandatory = $true)][string]$Secret
    )

    Invoke-KomArenaAgentRequest -SiteUrl $SiteUrl -SiteId $SiteId -Secret $Secret -Method GET -Endpoint status
}

function Get-KomArenaAgentCapabilities {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][ValidatePattern('^https://')][string]$SiteUrl,
        [Parameter(Mandatory = $true)][string]$SiteId,
        [Parameter(Mandatory = $true)][string]$Secret
    )

    Invoke-KomArenaAgentRequest -SiteUrl $SiteUrl -SiteId $SiteId -Secret $Secret -Method GET -Endpoint capabilities
}

function Invoke-KomArenaAgentTask {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][ValidatePattern('^https://')][string]$SiteUrl,
        [Parameter(Mandatory = $true)][string]$SiteId,
        [Parameter(Mandatory = $true)][string]$Secret,
        [Parameter(Mandatory = $true)][string]$TaskId,
        [Parameter(Mandatory = $true)][string]$IdempotencyKey,
        [Parameter(Mandatory = $true)][ValidateSet(
            'site.inspect',
            'health.check',
            'plugin.list',
            'post.read',
            'post.update',
            'cache.purge',
            'rollback.execute'
        )][string]$Action,
        [Parameter()][hashtable]$Payload = @{},
        [Parameter()][string]$ExpectedBeforeSha256
    )

    $task = [ordered]@{
        task_id         = $TaskId
        idempotency_key = $IdempotencyKey
        action          = $Action
        payload         = $Payload
    }

    if ($ExpectedBeforeSha256) {
        $task.expected_before_sha256 = $ExpectedBeforeSha256.ToLowerInvariant()
    }

    Invoke-KomArenaAgentRequest `
        -SiteUrl $SiteUrl `
        -SiteId $SiteId `
        -Secret $Secret `
        -Method POST `
        -Endpoint tasks `
        -BodyObject $task
}

<#
Example (do not commit real credentials):

$siteId = '<copy from WordPress Settings > KomArena Agent Core>'
$secret = Read-Host 'Pairing secret'

Get-KomArenaAgentStatus `
    -SiteUrl 'https://komarena.sk' `
    -SiteId $siteId `
    -Secret $secret

Invoke-KomArenaAgentTask `
    -SiteUrl 'https://komarena.sk' `
    -SiteId $siteId `
    -Secret $secret `
    -TaskId 'repair-smoke-001' `
    -IdempotencyKey 'repair-smoke-001-attempt-1' `
    -Action 'health.check'
#>
