<#
.SYNOPSIS
    Automated Sync PTK Tracker Database from Production to Laragon
    
.DESCRIPTION
    Script ini menggabungkan proses backup dari server dan restore ke lokal secara otomatis.
    1. SSH ke server Ubuntu dan export database.
    2. Download file backup via SCP.
    3. Import langsung ke database Laragon MySQL.
    
.NOTES
    Server: peroniks@10.88.8.46
    Local: ptk_tracker (Laragon)
#>

# ============================================
# KONFIGURASI SERVER (PROD)
# ============================================
$ServerUser = "peroniks"
$ServerHost = "10.88.8.46"
$ServerSSH = "$ServerUser@$ServerHost"
$DockerContainer = "ptk_db"
$DbNameProd = "ptk_tracker"
$DbUserProd = "ptk_user"
$DbPassProd = "ptk_pass"
$ServerBackupPath = "/tmp/sync_latest.sql"

# ============================================
# KONFIGURASI LOKAL (LARAGON)
# ============================================
$MysqlPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$DbHostLocal = "127.0.0.1"
$DbNameLocal = "ptk_tracker"
$DbUserLocal = "root"
$DbPassLocal = "123456788"
$LocalBackupDir = "C:\laragon\www\ptk-tracker\backups"
$LocalBackupPath = "$LocalBackupDir\sync_latest.sql"

# ============================================
# FUNGSI LOGGING
# ============================================
function Log-Info($message) { Write-Host "[INFO] $message" -ForegroundColor Cyan }
function Log-Success($message) { Write-Host "[SUCCESS] $message" -ForegroundColor Green }
function Log-Error($message) { Write-Host "[ERROR] $message" -ForegroundColor Red }
function Log-Warning($message) { Write-Host "[WARNING] $message" -ForegroundColor Yellow }

# ============================================
# MAIN SCRIPT
# ============================================

Write-Host "`n============================================" -ForegroundColor Magenta
Write-Host "  PTK TRACKER PRODUCTION SYNC SCRIPT" -ForegroundColor Magenta
Write-Host "============================================`n" -ForegroundColor Magenta

# 0. Verifikasi MySQL Lokal
if (!(Test-Path $MysqlPath)) {
    # Coba cari path mysql lain jika versi beda
    $AlternativePath = Get-ChildItem "C:\laragon\bin\mysql\mysql-*\bin\mysql.exe" | Select-Object -First 1
    if ($AlternativePath) {
        $MysqlPath = $AlternativePath.FullName
        Log-Info "Menggunakan MySQL di: $MysqlPath"
    }
    else {
        Log-Error "MySQL tidak ditemukan di Laragon. Harap cek path di script ini."
        exit 1
    }
}

# 1. Export di Server
Log-Info "Mengekspor database di server production..."
$exportCommand = "docker exec $DockerContainer mysqldump -u $DbUserProd -p$DbPassProd $DbNameProd > $ServerBackupPath"
ssh $ServerSSH $exportCommand
if ($LASTEXITCODE -ne 0) { Log-Error "Gagal ekspor di server."; exit 1 }
Log-Success "Ekspor di server berhasil."

# 2. Download ke Lokal
if (!(Test-Path $LocalBackupDir)) { New-Item -ItemType Directory -Path $LocalBackupDir -Force | Out-Null }
Log-Info "Mendownload file backup..."
scp "${ServerSSH}:$ServerBackupPath" $LocalBackupPath
if ($LASTEXITCODE -ne 0) { Log-Error "Gagal download file."; exit 1 }
Log-Success "Download berhasil."

# 3. Import ke Laragon
Log-Info "Mengimport ke database Laragon ($DbNameLocal)..."
# Buat DB jika belum ada
& $MysqlPath -h $DbHostLocal -u $DbUserLocal "-p$DbPassLocal" -e "CREATE DATABASE IF NOT EXISTS $DbNameLocal;"

try {
    $importProcess = Start-Process -FilePath $MysqlPath `
        -ArgumentList "-h", $DbHostLocal, "-u", $DbUserLocal, "-p$DbPassLocal", $DbNameLocal `
        -RedirectStandardInput $LocalBackupPath `
        -NoNewWindow -Wait -PassThru
    
    if ($importProcess.ExitCode -eq 0) {
        Log-Success "Sync SELESAI! Database lokal sekarang sama dengan production."
    }
    else {
        Log-Error "Gagal import ke MySQL lokal. Exit code: $($importProcess.ExitCode)"
        exit 1
    }
}
catch {
    Log-Error "Terjadi kesalahan saat import: $_"
    exit 1
}

# 4. Cleanup
Log-Info "Membersihkan file temporer..."
ssh $ServerSSH "rm -f $ServerBackupPath"
# Simpan copy dengan timestamp untuk histori
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
Copy-Item $LocalBackupPath "$LocalBackupDir\ptk_sync_$timestamp.sql"

Write-Host "`n============================================" -ForegroundColor Green
Write-Host "  SYNC DATABASE BERHASIL!" -ForegroundColor Green
Write-Host "============================================`n" -ForegroundColor Green
