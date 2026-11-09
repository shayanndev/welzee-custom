<#
.SYNOPSIS
  Builds the Welzee production deployment package into deployment_output/.
.DESCRIPTION
  - Copies ONLY whitelisted runtime files into deployment_output/stage/public_html
  - Creates welzee-production.zip (index.php at ZIP root, forward-slash entries for Linux)
  - Generates welzee-database.sql (schema + seed, no CREATE DATABASE / USE, phpMyAdmin-importable)
  - Copies the private config template
  - Scans the stage for secrets/dev leftovers and lints every PHP file
  The Git history and the original project are never modified.
  Usage:  powershell -ExecutionPolicy Bypass -File tools\build_deployment.ps1
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$out  = Join-Path $root 'deployment_output'
$stage = Join-Path $out 'stage\public_html'
$privateOut = Join-Path $out 'private'

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force -Path $stage, $privateOut | Out-Null

# ---------- 1. whitelist of runtime files ----------
$files = @('index.php','about.php','blog.php','contact.php','custom_order.php','faq.php','post.php','privacy.php',
           'returns.php','services.php','shipping.php','sitemap.php','terms.php','404.php','500.php','robots.txt','.htaccess')
foreach ($f in $files) {
    Copy-Item (Join-Path $root $f) (Join-Path $stage $f) -Force
}
function Copy-Tree($rel, $excludePattern) {
    $src = Join-Path $root $rel
    Get-ChildItem $src -Recurse -File -Force | ForEach-Object {
        $r = $_.FullName.Substring($root.Length + 1)
        if ($excludePattern -and $r -match $excludePattern) { return }
        $dest = Join-Path $stage $r
        New-Item -ItemType Directory -Force -Path (Split-Path $dest) | Out-Null
        Copy-Item $_.FullName $dest -Force
    }
}
Copy-Tree 'api'      $null
Copy-Tree 'includes' $null
Copy-Tree 'PHPMailer' $null
Copy-Tree 'assets'   'assets\\images\\products\\New folder'     # unused design drafts
Copy-Tree 'config'   'config\\(config\.local|config\.sample)\.php$'   # never ship local/sample config
# uploads: structure only (no customer/test files)
foreach ($d in 'uploads\designs','uploads\logos') {
    New-Item -ItemType Directory -Force -Path (Join-Path $stage $d) | Out-Null
    Copy-Item (Join-Path $root "$d\.gitkeep") (Join-Path $stage "$d\.gitkeep") -Force
}
Copy-Item (Join-Path $root 'uploads\.htaccess') (Join-Path $stage 'uploads\.htaccess') -Force

# ---------- 2. database file ----------
$sql = Get-Content (Join-Path $root 'database.sql') -Raw
$sql = ($sql -split "`r?`n" | Where-Object { $_ -notmatch '^\s*(CREATE DATABASE|USE)\s' }) -join "`n"
$sql = $sql -replace 'DEFAULT CHARSET=utf8mb4;', 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
$header = @"
-- ============================================================
-- Welzee production database (schema + starter blog posts)
-- Import with cPanel > phpMyAdmin > select YOUR database > Import.
-- * Contains NO CREATE DATABASE / USE / DROP statements (safe on shared hosting).
-- * Re-runnable: tables use IF NOT EXISTS, seed rows use INSERT IGNORE.
-- * Contains no customer data and no credentials.
-- ============================================================
SET NAMES utf8mb4;
SET time_zone = '+05:00';

"@
[System.IO.File]::WriteAllText((Join-Path $out 'welzee-database.sql'), ($header + $sql + "`n"), (New-Object System.Text.UTF8Encoding($false)))   # UTF-8, NO BOM (BOM breaks phpMyAdmin imports)

# ---------- 3. private config template ----------
Copy-Item (Join-Path $root 'config\config.sample.php') (Join-Path $privateOut 'config.php.sample') -Force

# ---------- 4. safety scans ----------
$problems = @()
$bad = @('udah citu','digitizingmillemb','Eilotu@123','u919635044','root@localhost','C:\\xamppnew','customshirtsgen','/customshirtsgen')
Get-ChildItem $stage -Recurse -File -Force | Where-Object { $_.Extension -notin '.png','.jpg','.jpeg','.mp4' } | ForEach-Object {
    $t = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
    foreach ($b in $bad) { if ($t -and $t.Contains($b)) { $problems += "Secret/dev string '$b' in $($_.FullName.Substring($stage.Length+1))" } }
}
$forbidden = '\.git(\\|$)|\.sql$|\.zip$|\.log$|config\.local|config\.sample|composer\.|my\.txt|\\vendor\\|\\designs\\[^\\]+\.png|New folder'
Get-ChildItem $stage -Recurse -File -Force | ForEach-Object {
    $r = $_.FullName.Substring($stage.Length + 1)
    if ($r -match $forbidden -and $r -notmatch '^uploads\\(designs|logos)\\\.gitkeep$') { $problems += "Forbidden file in package: $r" }
}
if (-not (Test-Path (Join-Path $stage 'index.php'))) { $problems += 'index.php missing from stage root' }
$lintFail = 0
Get-ChildItem $stage -Recurse -Filter *.php | ForEach-Object {
    $o = & php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) { $lintFail++; $problems += "PHP lint failed: $($_.FullName.Substring($stage.Length+1)) :: $o" }
}
if ($problems.Count) { $problems | ForEach-Object { Write-Host "PROBLEM: $_" -ForegroundColor Red }; throw "Build aborted: $($problems.Count) problem(s)." }

# ---------- 5. ZIP (forward-slash entry names, dotfiles included) ----------
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zipPath = Join-Path $out 'welzee-production.zip'
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')
try {
    Get-ChildItem $stage -Recurse -File -Force | Sort-Object FullName | ForEach-Object {
        $entry = $_.FullName.Substring($stage.Length + 1).Replace('\', '/')
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entry, [System.IO.Compression.CompressionLevel]::Optimal)
    }
} finally { $zip.Dispose() }

$hashFiles = @('welzee-production.zip', 'welzee-database.sql', 'DEPLOYMENT_GUIDE.md', 'DEPLOYMENT_AUDIT.md', 'ROLLBACK_GUIDE.md')
$hashLines = foreach ($hf in $hashFiles) {
    $p = Join-Path $out $hf
    if (Test-Path $p) {
        $h = (Get-FileHash $p -Algorithm SHA256).Hash
        "$h  $hf"
    }
}
Set-Content (Join-Path $out 'SHA256SUMS.txt') -Value $hashLines
Write-Host "BUILD OK  -> $zipPath" -ForegroundColor Green
Write-Host ("ZIP size: {0:N1} KB" -f ((Get-Item $zipPath).Length / 1KB))
