Param(
    [string] $OutputPath = ''
)

$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Split-Path -Parent $scriptDir
$distDir = Join-Path $repoRoot 'dist'
$stagingRoot = Join-Path $distDir '_staging'
$pluginFolderName = 'tsemou-core'
$stageDir = Join-Path $stagingRoot $pluginFolderName
$zipPath = if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    Join-Path $distDir 'tsemou-core.zip'
} else {
    [System.IO.Path]::GetFullPath($OutputPath)
}

$zipDir = Split-Path -Parent $zipPath
if ([string]::IsNullOrWhiteSpace($zipDir)) {
    throw 'Invalid OutputPath: could not determine destination directory.'
}

New-Item -ItemType Directory -Path $distDir -Force | Out-Null
New-Item -ItemType Directory -Path $zipDir -Force | Out-Null

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

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$stageDirNormalized = [System.IO.Path]::GetFullPath($stageDir)
$stagePrefix = $stageDirNormalized.TrimEnd('\') + '\'
$createdEntries = @{}

function New-ZipEntryName {
    param(
        [Parameter(Mandatory = $true)]
        [string] $FullPath,
        [switch] $IsDirectory
    )

    $fullPathNormalized = [System.IO.Path]::GetFullPath($FullPath)
    if (-not $fullPathNormalized.StartsWith($stagePrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Path is outside staging directory: $FullPath"
    }

    $relative = $fullPathNormalized.Substring($stagePrefix.Length).Replace('\', '/')
    $entryName = if ($relative) { "$pluginFolderName/$relative" } else { $pluginFolderName }

    if ($IsDirectory -and -not $entryName.EndsWith('/')) {
        $entryName += '/'
    }

    return $entryName
}

function Add-ZipDirectoryEntry {
    param(
        [Parameter(Mandatory = $true)]
        [System.IO.Compression.ZipArchive] $Archive,
        [Parameter(Mandatory = $true)]
        [string] $EntryName
    )

    if ($createdEntries.ContainsKey($EntryName)) {
        return
    }

    $null = $Archive.CreateEntry($EntryName)
    $createdEntries[$EntryName] = $true
}

function Add-ZipFileEntry {
    param(
        [Parameter(Mandatory = $true)]
        [System.IO.Compression.ZipArchive] $Archive,
        [Parameter(Mandatory = $true)]
        [string] $SourcePath,
        [Parameter(Mandatory = $true)]
        [string] $EntryName
    )

    if ($createdEntries.ContainsKey($EntryName)) {
        return
    }

    $entry = $Archive.CreateEntry($EntryName, [System.IO.Compression.CompressionLevel]::Optimal)
    $entryStream = $entry.Open()
    $fileStream = [System.IO.File]::OpenRead($SourcePath)

    try {
        $fileStream.CopyTo($entryStream)
    }
    finally {
        $fileStream.Dispose()
        $entryStream.Dispose()
    }

    $createdEntries[$EntryName] = $true
}

$zipArchive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    Add-ZipDirectoryEntry -Archive $zipArchive -EntryName "$pluginFolderName/"

    $mainPluginFile = Join-Path $stageDir 'tsemou-core.php'
    Add-ZipFileEntry -Archive $zipArchive -SourcePath $mainPluginFile -EntryName "$pluginFolderName/tsemou-core.php"

    foreach ($topDir in @('assets', 'config', 'includes', 'modules', 'storage')) {
        $dirPath = Join-Path $stageDir $topDir
        if (Test-Path $dirPath) {
            Add-ZipDirectoryEntry -Archive $zipArchive -EntryName "$pluginFolderName/$topDir/"
        }
    }

    $rootFiles = Get-ChildItem -Path $stageDir -File | Sort-Object Name
    foreach ($file in $rootFiles) {
        if ($file.Name -eq 'tsemou-core.php') {
            continue
        }

        Add-ZipFileEntry -Archive $zipArchive -SourcePath $file.FullName -EntryName (New-ZipEntryName -FullPath $file.FullName)
    }

    $allDirectories = Get-ChildItem -Path $stageDir -Directory -Recurse | Sort-Object FullName
    foreach ($directory in $allDirectories) {
        Add-ZipDirectoryEntry -Archive $zipArchive -EntryName (New-ZipEntryName -FullPath $directory.FullName -IsDirectory)
    }

    $allFiles = Get-ChildItem -Path $stageDir -File -Recurse | Sort-Object FullName
    foreach ($file in $allFiles) {
        if ($file.FullName -eq $mainPluginFile) {
            continue
        }

        Add-ZipFileEntry -Archive $zipArchive -SourcePath $file.FullName -EntryName (New-ZipEntryName -FullPath $file.FullName)
    }
}
finally {
    $zipArchive.Dispose()
}

$zip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$entries = $zip.Entries | Select-Object -ExpandProperty FullName
$zip.Dispose()

$rawBackslashEntries = $entries | Where-Object { $_ -match '\\' }
if ($rawBackslashEntries) {
    throw 'ZIP contains Windows-style path separators (\\) in entry names, which is not deployment-safe.'
}

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
