$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$ToolDir = Join-Path $ProjectRoot 'tools\piper'
$VoiceDir = Join-Path $ProjectRoot 'storage\app\navigation_tts\voices'
$TmpDir = Join-Path $ProjectRoot 'storage\app\navigation_tts\setup_tmp'

$PiperZip = Join-Path $TmpDir 'piper_windows_amd64.zip'
$PiperExtract = Join-Path $TmpDir 'piper_extract'
$Model = Join-Path $VoiceDir 'vi_VN-vais1000-medium.onnx'
$ModelConfig = Join-Path $VoiceDir 'vi_VN-vais1000-medium.onnx.json'
$ModelCard = Join-Path $VoiceDir 'MODEL_CARD_vais1000.txt'

$PiperUrl = 'https://github.com/rhasspy/piper/releases/download/2023.11.14-2/piper_windows_amd64.zip'
$PiperFallbackUrl = 'https://downloads.sourceforge.net/project/piper-tts.mirror/2023.11.14-2/piper_windows_amd64.zip'
$ModelUrl = 'https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/vi/vi_VN/vais1000/medium/vi_VN-vais1000-medium.onnx?download=true'
$ConfigUrl = 'https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/vi/vi_VN/vais1000/medium/vi_VN-vais1000-medium.onnx.json?download=true'
$CardUrl = 'https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/vi/vi_VN/vais1000/medium/MODEL_CARD?download=true'
$ExpectedModelSha256 = 'ec7c89e2c85f4d1edc24b6120c18aaf1bda614f06b511567eb9c7c0de15e2dab'

function Download-File([string]$Url, [string]$OutFile) {
    Write-Host "Dang tai: $Url" -ForegroundColor Cyan
    $dir = Split-Path -Parent $OutFile
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    if (Test-Path $OutFile) { Remove-Item -Force $OutFile }

    try {
        Invoke-WebRequest -UseBasicParsing -Uri $Url -OutFile $OutFile -MaximumRedirection 10 -Headers @{ 'User-Agent' = 'ChillDrink-DATN-Piper-Installer/1.0' }
    } catch {
        if (Get-Command curl.exe -ErrorAction SilentlyContinue) {
            & curl.exe -L --fail --retry 2 --connect-timeout 15 -A 'ChillDrink-DATN-Piper-Installer/1.0' -o $OutFile $Url
            if ($LASTEXITCODE -ne 0) { throw }
        } else {
            throw
        }
    }

    if (!(Test-Path $OutFile) -or (Get-Item $OutFile).Length -lt 100) {
        throw "File tai ve khong hop le: $OutFile"
    }
}

New-Item -ItemType Directory -Force -Path $ToolDir, $VoiceDir, $TmpDir | Out-Null

# 1) Piper Windows runtime
$PiperExe = Join-Path $ToolDir 'piper.exe'
if (!(Test-Path $PiperExe)) {
    try {
        Download-File $PiperUrl $PiperZip
    } catch {
        Write-Host 'GitHub tai cham/bi chan, thu mirror SourceForge...' -ForegroundColor Yellow
        Download-File $PiperFallbackUrl $PiperZip
    }

    if (Test-Path $PiperExtract) { Remove-Item -Recurse -Force $PiperExtract }
    Expand-Archive -Path $PiperZip -DestinationPath $PiperExtract -Force
    $FoundExe = Get-ChildItem -Path $PiperExtract -Filter 'piper.exe' -Recurse | Select-Object -First 1
    if (!$FoundExe) { throw 'Khong tim thay piper.exe trong goi Windows.' }

    # Copy toan bo runtime cung cap DLL + espeak-ng-data, khong chi moi piper.exe.
    Copy-Item -Path (Join-Path $FoundExe.Directory.FullName '*') -Destination $ToolDir -Recurse -Force
}

if (!(Test-Path $PiperExe)) { throw "Thieu piper.exe tai $PiperExe" }
Write-Host '[OK] Piper Windows runtime' -ForegroundColor Green

# 2) Voice Vietnamese single-speaker
if (!(Test-Path $Model)) { Download-File $ModelUrl $Model }
$ActualSha = (Get-FileHash -Algorithm SHA256 $Model).Hash.ToLowerInvariant()
if ($ActualSha -ne $ExpectedModelSha256) {
    Remove-Item -Force $Model
    throw "SHA256 model khong khop. Mong doi $ExpectedModelSha256, nhan $ActualSha"
}
Write-Host '[OK] Model vi_VN-vais1000-medium (SHA256 dung)' -ForegroundColor Green

if (!(Test-Path $ModelConfig)) { Download-File $ConfigUrl $ModelConfig }
if (!(Test-Path $ModelCard)) { Download-File $CardUrl $ModelCard }
Write-Host '[OK] Config + model card' -ForegroundColor Green

# 3) Smoke check executable (khong tong hop de tranh loi encoding cua console)
& $PiperExe --version | Out-Host
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Canh bao: piper.exe khong tra version, nhung file da duoc cai. Hay test tren man dan duong.' -ForegroundColor Yellow
}

# 4) Clean temp
try { Remove-Item -Recurse -Force $TmpDir } catch { }

Write-Host ''
Write-Host 'HOAN TAT.' -ForegroundColor Green
Write-Host "Piper : $PiperExe"
Write-Host "Voice : $Model"
Write-Host 'Khong can AZURE_SPEECH_KEY / AZURE_SPEECH_REGION.'
