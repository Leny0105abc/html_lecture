param(
    [ValidateRange(1024, 65535)]
    [int]$Port = 8000,
    [string]$ServerIP
)

$ErrorActionPreference = 'Stop'
$projectPath = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'PHP 8.3+ must be available in PATH on the server.'
}
foreach ($required in @('.env', 'vendor/autoload.php', 'public/build/manifest.json')) {
    if (-not (Test-Path -LiteralPath (Join-Path $projectPath $required))) {
        throw "Missing $required. Complete the installation and npm run build first."
    }
}
if (Test-Path -LiteralPath (Join-Path $projectPath 'public/hot')) {
    throw 'Stop npm run dev / composer run dev, remove public/hot if it remains, and run npm run build before starting LAN mode.'
}

$addresses = @(Get-NetIPAddress -AddressFamily IPv4 | Where-Object {
    $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' -and $_.AddressState -eq 'Preferred'
})
if ($ServerIP) {
    if ($ServerIP -notin $addresses.IPAddress) {
        throw 'ServerIP must be an IPv4 address assigned to this server.'
    }
} elseif ($addresses.Count -eq 1) {
    $ServerIP = $addresses[0].IPAddress
} else {
    $addresses | Select-Object IPAddress, InterfaceAlias | Format-Table | Out-Host
    throw 'Choose the lab network address above and rerun with -ServerIP <address>.'
}

# Overrides belong only to this process and its children; preserve the local .env.
# Bypass a cached development configuration without deleting it.
$overrides = @{
    APP_ENV = 'production'
    APP_DEBUG = 'false'
    APP_URL = "http://${ServerIP}:$Port"
    APP_CONFIG_CACHE = Join-Path $projectPath ('storage/framework/lan-config-' + [guid]::NewGuid().ToString() + '.php')
    SESSION_DOMAIN = 'null'
    SESSION_SECURE_COOKIE = 'false'
    LAB_OFFLINE = 'true'
    ASSET_URL = 'null'
    MAIL_MAILER = 'log'
}
$previous = @{}
foreach ($name in $overrides.Keys) {
    $previous[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
    [Environment]::SetEnvironmentVariable($name, $overrides[$name], 'Process')
}

try {
    Write-Host "Students open http://${ServerIP}:$Port on the lab network."
    Write-Host 'Offline mode: AI and email delivery disabled. Keep this terminal open; Ctrl+C stops the server.'
    Write-Host 'This launcher is for a LAN trial. Use Apache/Nginx for regular whole-class sessions (see docs/LAN.md).'
    Push-Location (Join-Path $projectPath 'public')
    try {
        & php -S "${ServerIP}:$Port" (Join-Path $projectPath 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')
        if ($LASTEXITCODE -ne 0) { throw "PHP server exited with code $LASTEXITCODE." }
    } finally {
        Pop-Location
    }
} finally {
    foreach ($name in $overrides.Keys) {
        [Environment]::SetEnvironmentVariable($name, $previous[$name], 'Process')
    }
}
