# Requires PowerShell 5+
$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
if ((Split-Path -Leaf $PSScriptRoot) -ne 'tools') {
    $Root = $PSScriptRoot
} else {
    $Root = Split-Path -Parent $PSScriptRoot
}

# Script lives in tools/, so root is parent
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path | Split-Path -Parent
if (-not (Test-Path (Join-Path $Root 'artisan'))) {
    $Root = (Get-Location).Path
}

$Bin = Join-Path $Root 'tools\bin'
New-Item -ItemType Directory -Force -Path $Bin | Out-Null

$FfmpegExe = Join-Path $Bin 'ffmpeg.exe'
$FfprobeExe = Join-Path $Bin 'ffprobe.exe'

if (-not (Test-Path $FfmpegExe)) {
    Write-Host 'Downloading FFmpeg essentials...'
    $Zip = Join-Path $env:TEMP 'qaari-ffmpeg.zip'
    $Url = 'https://github.com/GyanD/codexffmpeg/releases/download/7.1/ffmpeg-7.1-essentials_build.zip'
    Invoke-WebRequest -Uri $Url -OutFile $Zip -UseBasicParsing
    $Extract = Join-Path $env:TEMP 'qaari-ffmpeg-extract'
    if (Test-Path $Extract) { Remove-Item $Extract -Recurse -Force }
    Expand-Archive -Path $Zip -DestinationPath $Extract -Force
    $FoundFfmpeg = Get-ChildItem -Path $Extract -Recurse -Filter ffmpeg.exe | Select-Object -First 1
    $FoundFfprobe = Get-ChildItem -Path $Extract -Recurse -Filter ffprobe.exe | Select-Object -First 1
    if (-not $FoundFfmpeg -or -not $FoundFfprobe) {
        throw 'Could not find ffmpeg.exe / ffprobe.exe in the downloaded archive.'
    }
    Copy-Item $FoundFfmpeg.FullName $FfmpegExe -Force
    Copy-Item $FoundFfprobe.FullName $FfprobeExe -Force
    Remove-Item $Zip -Force -ErrorAction SilentlyContinue
    Remove-Item $Extract -Recurse -Force -ErrorAction SilentlyContinue
    Write-Host "Installed: $FfmpegExe"
} else {
    Write-Host "FFmpeg already present: $FfmpegExe"
}

& $FfmpegExe -version | Select-Object -First 1
& $FfprobeExe -version | Select-Object -First 1

$Python = $null
foreach ($candidate in @('python', 'py')) {
    try {
        $ver = & $candidate --version 2>&1
        if ($LASTEXITCODE -eq 0 -or "$ver" -match 'Python') {
            $Python = $candidate
            Write-Host "Python OK: $ver"
            break
        }
    } catch {}
}

if (-not $Python) {
    Write-Host 'Python not found. Installing Python 3.12 via winget...'
    winget install -e --id Python.Python.3.12 --accept-package-agreements --accept-source-agreements --disable-interactivity
    Write-Host 'Open a new terminal, then re-run: php artisan ayah:sync --check'
} else {
    Write-Host 'Done. Next: php artisan ayah:sync --check'
}
