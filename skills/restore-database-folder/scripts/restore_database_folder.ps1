param(
    [string]$Root = "."
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path -Path $Root
$databasePath = Join-Path $projectRoot "database"
$requiredDirectories = @(
    $databasePath,
    (Join-Path $databasePath "factories"),
    (Join-Path $databasePath "migrations"),
    (Join-Path $databasePath "seeders")
)

foreach ($directory in $requiredDirectories) {
    if (-not (Test-Path -Path $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }
}

$gitIgnorePath = Join-Path $databasePath ".gitignore"
if (-not (Test-Path -Path $gitIgnorePath)) {
    @"
*.sqlite
*.sqlite-journal
*.sqlite-wal
*.sqlite-shm
"@ | Set-Content -Path $gitIgnorePath
}

$subDirectories = @(
    (Join-Path $databasePath "factories"),
    (Join-Path $databasePath "migrations"),
    (Join-Path $databasePath "seeders")
)

foreach ($subDirectory in $subDirectories) {
    $hasFiles = (Get-ChildItem -Path $subDirectory -Force | Measure-Object).Count -gt 0
    $gitkeepPath = Join-Path $subDirectory ".gitkeep"

    if (-not $hasFiles -and -not (Test-Path -Path $gitkeepPath)) {
        New-Item -ItemType File -Path $gitkeepPath -Force | Out-Null
    }
}

$envPath = Join-Path $projectRoot ".env"
if (Test-Path -Path $envPath) {
    $usesSqlite = Select-String -Path $envPath -Pattern "^DB_CONNECTION=sqlite$" -Quiet

    if ($usesSqlite) {
        $sqlitePath = Join-Path $databasePath "database.sqlite"
        if (-not (Test-Path -Path $sqlitePath)) {
            New-Item -ItemType File -Path $sqlitePath -Force | Out-Null
        }
    }
}

Write-Output "Restored Laravel database folder structure at: $databasePath"
