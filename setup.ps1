# ============================================
# KLEER - Windows Setup Script (setup.ps1)
# Chạy với: powershell -ExecutionPolicy Bypass -File setup.ps1
# ============================================

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  KLEER - WordPress WooCommerce Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# --- Check Docker ---
try {
    docker version -f "{{.Server.Version}}" | Out-Null
    Write-Host "[OK] Docker Desktop is running." -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Docker Desktop is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}

# --- Check Docker Compose ---
try {
    docker compose version | Out-Null
    Write-Host "[OK] Docker Compose is available." -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Docker Compose is not available." -ForegroundColor Red
    exit 1
}

# --- Copy .env if not exists ---
if (-Not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Write-Host ""
        Write-Host "[INFO] Copying .env.example to .env..." -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        Write-Host "[OK] Created .env file. Please edit it with your settings." -ForegroundColor Yellow
    } else {
        Write-Host "[ERROR] .env.example not found!" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "[OK] .env already exists." -ForegroundColor Green
}

# --- Create necessary directories ---
Write-Host ""
Write-Host "[INFO] Creating required directories..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "wp-content/uploads" | Out-Null
New-Item -ItemType Directory -Force -Path "init-scripts" | Out-Null
New-Item -ItemType Directory -Force -Path "wp-content/themes/kleer-theme" | Out-Null
New-Item -ItemType Directory -Force -Path "wp-content/plugins/kleer-plugin" | Out-Null
Write-Host "[OK] Directories created." -ForegroundColor Green

# --- Download WordPress core if missing ---
if (-Not (Test-Path "index.php")) {
    Write-Host "[INFO] WordPress core not found. Downloading..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri "https://wordpress.org/latest.tar.gz" -OutFile "wordpress-latest.tar.gz"
        tar -xf wordpress-latest.tar.gz
        Copy-Item "wordpress\*" "." -Recurse -Force
        Remove-Item "wordpress-latest.tar.gz", "wordpress" -Recurse -Force
        Write-Host "[OK] WordPress core downloaded." -ForegroundColor Green
    } catch {
        Write-Host "[ERROR] Failed to download WordPress." -ForegroundColor Red
        exit 1
    }
}

# --- Create wp-config.php if not exists ---
if (-Not (Test-Path "wp-config.php") -and (Test-Path "wp-config-sample.php")) {
    $envValues = @{}
    Get-Content ".env" | Where-Object { $_ -match '^\s*[A-Z0-9_]+=' -and $_ -notmatch '^\s*#' } | ForEach-Object {
        $key, $value = $_ -split '=', 2
        $envValues[$key.Trim()] = $value.Trim()
    }

    $dbName = if ($envValues.ContainsKey('WORDPRESS_DB_NAME')) { $envValues['WORDPRESS_DB_NAME'] } else { 'kleer_db' }
    $dbUser = if ($envValues.ContainsKey('WORDPRESS_DB_USER')) { $envValues['WORDPRESS_DB_USER'] } else { 'kleer_user' }
    $dbPassword = if ($envValues.ContainsKey('WORDPRESS_DB_PASSWORD')) { $envValues['WORDPRESS_DB_PASSWORD'] } else { 'kleer_password_here_change_me' }
    $dbHost = if ($envValues.ContainsKey('WORDPRESS_DB_HOST')) { $envValues['WORDPRESS_DB_HOST'] } else { 'mariadb:3306' }
    $config = Get-Content "wp-config-sample.php" -Raw
    $config = $config.Replace("database_name_here", $dbName).Replace("username_here", $dbUser).Replace("password_here", $dbPassword).Replace("localhost", $dbHost)
    $config = $config -replace "put your unique phrase here", [guid]::NewGuid().ToString()
    Set-Content "wp-config.php" $config -Encoding UTF8
    Write-Host "[OK] Created wp-config.php from the WordPress sample." -ForegroundColor Green
}

# --- Start Docker containers ---
Write-Host ""
Write-Host "[INFO] Starting Docker containers..." -ForegroundColor Cyan
docker compose up -d

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Failed to start containers." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[INFO] Waiting for MariaDB to be ready..." -ForegroundColor Cyan
$maxAttempts = 30
$attempt = 0
while ($attempt -lt $maxAttempts) {
    $status = docker inspect --format='{{.State.Health.Status}}' kleer-mariadb 2>$null
    if ($status -eq "healthy") {
        Write-Host "[OK] MariaDB is ready!" -ForegroundColor Green
        break
    }
    $attempt++
    Start-Sleep -Seconds 2
    Write-Progress -Activity "Waiting for MariaDB" -Status "Attempt $attempt/$maxAttempts"
}

# --- Check all containers ---
Write-Host ""
Write-Host "[INFO] Checking container status..." -ForegroundColor Cyan
docker compose ps

# --- Get port info ---
$NGINX_PORT = (Get-Content .env | Select-String "^NGINX_PORT=" | ForEach-Object { $_.Line.Split("=")[1] }) -replace "`r`n", ""
if ([string]::IsNullOrWhiteSpace($NGINX_PORT)) { $NGINX_PORT = "80" }

$PHPMYADMIN_PORT = (Get-Content .env | Select-String "^PHPMYADMIN_PORT=" | ForEach-Object { $_.Line.Split("=")[1] }) -replace "`r`n", ""
if ([string]::IsNullOrWhiteSpace($PHPMYADMIN_PORT)) { $PHPMYADMIN_PORT = "8080" }

# --- Print URLs ---
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  SETUP COMPLETE!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "  WordPress:  http://localhost:$NGINX_PORT" -ForegroundColor White
Write-Host "  phpMyAdmin: http://localhost:$PHPMYADMIN_PORT" -ForegroundColor White
Write-Host "  MariaDB:    localhost:3307 (user: kleer_user, db: kleer_db)" -ForegroundColor White
Write-Host ""
Write-Host "  Next steps:" -ForegroundColor Yellow
Write-Host "  1. Open http://localhost:$NGINX_PORT in your browser" -ForegroundColor Yellow
Write-Host "  2. Complete the WordPress installation wizard" -ForegroundColor Yellow
Write-Host "  3. Edit .env file if you need to change settings" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Useful commands:" -ForegroundColor Cyan
Write-Host "  docker compose down        - Stop all services" -ForegroundColor Gray
Write-Host "  docker compose logs -f     - View all logs" -ForegroundColor Gray
Write-Host "  docker compose restart php - Restart PHP only" -ForegroundColor Gray
Write-Host "========================================" -ForegroundColor Cyan
