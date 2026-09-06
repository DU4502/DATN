$ErrorActionPreference = 'Stop'

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

$PiperExe = Join-Path $ProjectRoot 'tools\piper\piper.exe'
$Model = Join-Path $ProjectRoot 'storage\app\navigation_tts\voices\vi_VN-vais1000-medium.onnx'
$Config = Join-Path $ProjectRoot 'storage\app\navigation_tts\voices\vi_VN-vais1000-medium.onnx.json'

$OutDir = Join-Path $ProjectRoot 'storage\app\navigation_tts'
$OutFile = Join-Path $OutDir 'TEST_PIPER_TIENG_VIET.wav'
$InputFile = Join-Path $OutDir 'TEST_PIPER_INPUT_UTF8.txt'
$ErrorFile = Join-Path $OutDir 'TEST_PIPER_ERROR.txt'

foreach ($f in @($PiperExe, $Model, $Config)) {
    if (!(Test-Path $f)) {
        throw "Thieu file: $f. Hay chay scripts/install_piper_tts.ps1 truoc."
    }
}

New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

foreach ($f in @($OutFile, $InputFile, $ErrorFile)) {
    if (Test-Path $f) {
        Remove-Item -Force $f
    }
}

$Text = 'Còn 50 mét, chuẩn bị rẽ trái vào đường Ngô Thuyền. Sau đó tiếp tục đi thẳng 300 mét.'

# Ghi UTF-8 không BOM để Piper nhận đúng tiếng Việt.
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($InputFile, $Text + [Environment]::NewLine, $Utf8NoBom)

# Không dùng StandardInputEncoding vì Windows PowerShell 5.1 không có property này.
$CmdLine = '"' + $PiperExe + '"' +
           ' --model "' + $Model + '"' +
           ' --config "' + $Config + '"' +
           ' --output_file "' + $OutFile + '"' +
           ' < "' + $InputFile + '"' +
           ' 2> "' + $ErrorFile + '"'

Write-Host 'Dang test Piper TTS...' -ForegroundColor Cyan
Write-Host "Voice: $Model" -ForegroundColor DarkGray

$Process = Start-Process `
    -FilePath 'cmd.exe' `
    -ArgumentList '/d', '/s', '/c', $CmdLine `
    -WorkingDirectory (Split-Path -Parent $PiperExe) `
    -WindowStyle Hidden `
    -PassThru

if (!$Process.WaitForExit(20000)) {
    try { $Process.Kill() } catch {}
    throw 'Piper test qua 20 giay ma chua xong.'
}

$ErrorText = ''
if (Test-Path $ErrorFile) {
    $ErrorText = [System.IO.File]::ReadAllText($ErrorFile)
}

if ($Process.ExitCode -ne 0) {
    throw "Piper test loi. Exit=$($Process.ExitCode). $ErrorText"
}

if (!(Test-Path $OutFile)) {
    throw "Piper khong tao file audio: $OutFile. $ErrorText"
}

$AudioSize = (Get-Item $OutFile).Length
if ($AudioSize -lt 256) {
    throw "File audio tao ra qua nho ($AudioSize bytes). $ErrorText"
}

Remove-Item -Force $InputFile -ErrorAction SilentlyContinue
Remove-Item -Force $ErrorFile -ErrorAction SilentlyContinue

Write-Host ''
Write-Host '[OK] Da tao audio test:' -ForegroundColor Green
Write-Host $OutFile -ForegroundColor White
Write-Host "Dung luong: $AudioSize bytes" -ForegroundColor DarkGray
Write-Host ''

Start-Process $OutFile
