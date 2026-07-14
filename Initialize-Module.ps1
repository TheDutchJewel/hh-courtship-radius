[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [ValidatePattern('^hh[-_][a-z0-9_-]+$')]
    [string] $ModuleName,
    [Parameter(Mandatory)]
    [ValidatePattern('^[A-Za-z_][A-Za-z0-9_]*$')]
    [string] $ClassName,
    [Parameter(Mandatory)]
    [string] $Title
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
$oldClass = 'HhModuleTemplate'
$oldNamespace = 'Hartenthaler\Webtrees\Module\HhModuleTemplate'
$newNamespace = "Hartenthaler\Webtrees\Module\$ClassName"
$oldClassFile = Join-Path $root "src\$oldClass.php"
$newClassFile = Join-Path $root "src\$ClassName.php"

if (-not (Test-Path -LiteralPath $oldClassFile)) {
    throw 'This template has already been initialized or is incomplete.'
}

$textFiles = Get-ChildItem -LiteralPath $root -Recurse -File |
    Where-Object { $_.Extension -in @('.php', '.phtml', '.md', '.po', '.pot', '.yml', '.yaml', '.txt') -and $_.FullName -notmatch '[\\/]\.git[\\/]' }

foreach ($file in $textFiles) {
    $content = Get-Content -Raw -LiteralPath $file.FullName
    $content = $content.Replace($oldNamespace, $newNamespace)
    $content = $content.Replace($oldClass, $ClassName)
    $content = $content.Replace('hh-module-template', $ModuleName)
    $content = $content.Replace('HH module template', $Title)
    [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.UTF8Encoding]::new($false))
}

Move-Item -LiteralPath $oldClassFile -Destination $newClassFile
Write-Host "Initialized $ModuleName ($ClassName)." -ForegroundColor Green
Write-Host 'Review README.md, CHANGELOG.md, translations, metadata, and the license before committing.'
[System.IO.File]::Delete($PSCommandPath)
