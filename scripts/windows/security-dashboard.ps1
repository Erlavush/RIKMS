[CmdletBinding()]
param(
    [string]$Target = "http://127.0.0.1:8000",
    [ValidateSet("local", "staging", "production")]
    [string]$Environment = "local",
    [int]$Port = 8888,
    [switch]$View,
    [switch]$StartApp,
    [switch]$StartOllama,
    [switch]$Code,
    [switch]$Passive,
    [switch]$AI,
    [switch]$Zap,
    [switch]$Active,
    [switch]$NoBrowser,
    [string]$AiUrl = "http://127.0.0.1:11434",
    [string]$AiModel = "qwen3.5:4b"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$appProcess = $null
$ollamaProcess = $null

function Test-TcpPort {
    param([string]$HostName, [int]$PortNumber)
    $client = [System.Net.Sockets.TcpClient]::new()
    try {
        $task = $client.ConnectAsync($HostName, $PortNumber)
        if (-not $task.Wait(350)) { return $false }
        return $client.Connected
    }
    catch { return $false }
    finally { $client.Dispose() }
}

function Wait-ForLocalApp {
    param([int]$PortNumber)
    $deadline = [DateTime]::UtcNow.AddSeconds(30)
    while ([DateTime]::UtcNow -lt $deadline) {
        if (Test-TcpPort -HostName "127.0.0.1" -PortNumber $PortNumber) { return }
        Start-Sleep -Milliseconds 350
    }
    throw "RIKMS did not become reachable on 127.0.0.1:$PortNumber within 30 seconds."
}

Push-Location $repoRoot
try {
    if ($View -and ($Code -or $Passive -or $AI -or $Zap)) {
        throw "-View opens the dashboard without an initial scan. Remove -View or remove the scan switches."
    }
    if (-not $env:SECURITY_ALLOWED_TARGETS -and $Environment -eq "local") {
        $env:SECURITY_ALLOWED_TARGETS = "http://127.0.0.1:8000,http://localhost:8000"
    }
    if ($Active) {
        $env:SECURITY_ACTIVE_SCAN_ENABLED = "true"
    }

    if ($StartApp -and -not (Test-TcpPort -HostName "127.0.0.1" -PortNumber 8000)) {
        if (-not (Get-Command "php" -ErrorAction SilentlyContinue)) {
            throw "PHP was not found. Run scripts\windows\setup-local.ps1 first."
        }
        $appArguments = @(
            "-d", "upload_max_filesize=25M",
            "-d", "post_max_size=27M",
            "artisan", "serve",
            "--host=127.0.0.1",
            "--port=8000"
        )
        $appProcess = Start-Process -FilePath "php" -ArgumentList $appArguments -WorkingDirectory $repoRoot -PassThru
        Wait-ForLocalApp -PortNumber 8000
        Write-Host "RIKMS started at http://127.0.0.1:8000" -ForegroundColor Green
    }

    if ($StartOllama -and -not (Test-TcpPort -HostName "127.0.0.1" -PortNumber 11434)) {
        if (-not (Get-Command "ollama" -ErrorAction SilentlyContinue)) {
            throw "Ollama was not found. Install Ollama first or omit -StartOllama."
        }
        $ollamaProcess = Start-Process -FilePath "ollama" -ArgumentList @("serve") -WorkingDirectory $repoRoot -PassThru
        $deadline = [DateTime]::UtcNow.AddSeconds(30)
        while ([DateTime]::UtcNow -lt $deadline -and -not (Test-TcpPort -HostName "127.0.0.1" -PortNumber 11434)) {
            Start-Sleep -Milliseconds 350
        }
        if (-not (Test-TcpPort -HostName "127.0.0.1" -PortNumber 11434)) {
            throw "Ollama did not become reachable on 127.0.0.1:11434 within 30 seconds."
        }
        Write-Host "Ollama started at http://127.0.0.1:11434" -ForegroundColor Green
    }

    $labArguments = @(
        "-m", "security.lab",
        "--target=$Target",
        "--environment=$Environment",
        "--port=$Port",
        "--ai-url=$AiUrl",
        "--ai-model=$AiModel"
    )
    if ($Active) { $labArguments += "--active" }
    if ($NoBrowser) { $labArguments += "--no-browser" }
    if ($Code) { $labArguments += @("--run", "code") }
    if ($Passive) { $labArguments += @("--run", "passive") }
    if ($AI) { $labArguments += @("--run", "ai") }
    if ($Zap) { $labArguments += @("--run", "zap") }

    Write-Host "Opening Jaylord's local security workbench..." -ForegroundColor Magenta
    Write-Host "The dashboard does not require a RIKMS login. Authenticated scans still require synthetic test credentials." -ForegroundColor DarkGray

    if (Get-Command "py" -ErrorAction SilentlyContinue) {
        & py -3.12 @labArguments
    }
    elseif (Get-Command "python" -ErrorAction SilentlyContinue) {
        & python @labArguments
    }
    else {
        throw "Python 3.12 was not found through 'py' or 'python'."
    }
    exit $LASTEXITCODE
}
finally {
    if ($null -ne $appProcess -and -not $appProcess.HasExited) {
        Stop-Process -Id $appProcess.Id -ErrorAction SilentlyContinue
    }
    if ($null -ne $ollamaProcess -and -not $ollamaProcess.HasExited) {
        Stop-Process -Id $ollamaProcess.Id -ErrorAction SilentlyContinue
    }
    Pop-Location
}
