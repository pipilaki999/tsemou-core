Param()

$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Split-Path -Parent $scriptDir
$distDir = Join-Path $repoRoot 'dist'
$stagingRoot = Join-Path $distDir '_staging'
$pluginFolderName = 'tsemou-core'
$stageDir = Join-Path $stagingRoot $pluginFolderName
$zipPath = Join-Path $distDir 'tsemou-core.zip'

New-Item -ItemType Directory -Path $distDir -Force | Out-Null

if (Test-Path $stagingRoot) {
    Remove-Item $stagingRoot -Recurse -Force
}

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$excludeDirs = @(
    '.git',
    '.vscode',
    'prototype',
    'tests',
    'docs',
    'release',
    'dist',
    'node_modules',
    'scripts'
)

$excludeFiles = @(
    '*.md',
    '*.log',
    '.gitignore',
    '.gitattributes',
    'TSEMOU_*_NOTES.txt'
)

# Copy plugin files into staging using Robocopy, excluding non-runtime content.
$null = robocopy $repoRoot $stageDir /E /NFL /NDL /NJH /NJS /NP /XD $excludeDirs /XF $excludeFiles

$required = @(
    'tsemou-core.php',
    'uninstall.php',
    'includes\class-core.php',
    'assets',
    'config',
    'modules'
)

$missing = @()
foreach ($path in $required) {
    if (-not (Test-Path (Join-Path $stageDir $path))) {
        $missing += $path
    }
}

if ($missing.Count -gt 0) {
    throw ('Missing required packaging paths: ' + ($missing -join ', '))
}

Compress-Archive -Path $stageDir -DestinationPath $zipPath -Force

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$entries = $zip.Entries | Select-Object -ExpandProperty FullName
$zip.Dispose()

$normalizedEntries = $entries | ForEach-Object { $_.Replace('\', '/') }

$topLevel = @($normalizedEntries |
    ForEach-Object { ($_ -split '/')[0] } |
    Where-Object { $_ -ne '' } |
    Select-Object -Unique)

$mainPluginHeader = $normalizedEntries -contains "$pluginFolderName/tsemou-core.php"
$nestedRoot = $normalizedEntries | Where-Object { $_ -match "^$pluginFolderName/$pluginFolderName/" }

if ($topLevel.Count -ne 1 -or -not ($topLevel -contains $pluginFolderName)) {
    throw 'ZIP has invalid top-level structure. Expected only tsemou-core/ at root.'
}

if (-not $mainPluginHeader) {
    throw 'ZIP is missing tsemou-core/tsemou-core.php at plugin root.'
}

if ($nestedRoot) {
    throw 'ZIP has nested plugin root (tsemou-core/tsemou-core/), which is invalid.'
}

Write-Output ('ZIP_PATH=' + $zipPath)
Write-Output ('TOP_LEVEL=' + ($topLevel -join ','))
Write-Output ('HAS_MAIN_PLUGIN_FILE=' + $mainPluginHeader)
Write-Output ('HAS_NESTED_PLUGIN_ROOT=' + [bool]$nestedRoot)
