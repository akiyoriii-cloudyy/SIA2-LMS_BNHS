param(
    [string]$Host = "127.0.0.1",
    [int]$Port = 3306,
    [string]$Database = "lms_bnhs",
    [string]$User = "root",
    [string]$Password = "",
    [string]$OutputDir = ".\storage\backups"
)

if (!(Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$outputFile = Join-Path $OutputDir "$Database-$timestamp.sql"

$env:MYSQL_PWD = $Password
try {
    & mysqldump --host=$Host --port=$Port --user=$User --single-transaction --routines --triggers $Database > $outputFile
    Write-Host "Database backup created: $outputFile"
} finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
}
param(
    [string] $EnvPath = ".env",
    [string] $OutFile = ""
)

$ErrorActionPreference = "Stop"

function Get-EnvValue([string] $Key, [hashtable] $Map) {
    if (-not $Map.ContainsKey($Key)) { return "" }
    return [string] $Map[$Key]
}

if (-not (Test-Path $EnvPath)) {
    throw "Missing $EnvPath. Run the app setup first."
}

$envMap = @{}
Get-Content $EnvPath | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq "" -or $line.StartsWith("#")) { return }
    $idx = $line.IndexOf("=")
    if ($idx -lt 1) { return }
    $k = $line.Substring(0, $idx).Trim()
    $v = $line.Substring($idx + 1).Trim().Trim('"')
    $envMap[$k] = $v
}

$dbHost = Get-EnvValue "DB_HOST" $envMap
$dbPort = Get-EnvValue "DB_PORT" $envMap
$dbName = Get-EnvValue "DB_DATABASE" $envMap
$dbUser = Get-EnvValue "DB_USERNAME" $envMap
$dbPass = Get-EnvValue "DB_PASSWORD" $envMap

if ($dbHost -eq "" -or $dbName -eq "" -or $dbUser -eq "") {
    throw "DB config missing in $EnvPath (need DB_HOST, DB_DATABASE, DB_USERNAME)."
}

if ($OutFile -eq "") {
    $ts = (Get-Date).ToString("yyyyMMdd_HHmmss")
    $backupDir = Join-Path (Get-Location) "backups"
    if (-not (Test-Path $backupDir)) { New-Item -ItemType Directory -Path $backupDir | Out-Null }
    $OutFile = Join-Path $backupDir ("${dbName}_${ts}.sql")
}

$mysqldump = "mysqldump"
$xamppDump = "C:\\xampp\\mysql\\bin\\mysqldump.exe"
if (Test-Path $xamppDump) { $mysqldump = $xamppDump }

$args = @("--host=$dbHost", "--user=$dbUser", "--routines", "--events", "--single-transaction", "--quick", "--skip-lock-tables")
if ($dbPort -ne "") { $args += "--port=$dbPort" }
if ($dbPass -ne "") { $args += "--password=$dbPass" }
$args += $dbName

& $mysqldump @args | Out-File -FilePath $OutFile -Encoding utf8

Write-Host "Backup written to: $OutFile"

