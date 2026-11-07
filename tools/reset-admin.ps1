# ============================================
# KLEER - Reset tai khoan admin chuan cho ca team
# Chay voi: powershell -ExecutionPolicy Bypass -File tools/reset-admin.ps1
# ============================================
#
# Script nay dua MOI database local ve dung 1 tai khoan duy nhat:
#   Username: admin
#   Password: admin
#
# Ly do can script nay (thay vi push DB len Git):
# - Database nam trong Docker volume, KHONG nam trong Git nen khong the push.
# - Truoc day moi thanh vien tu cai WordPress voi password rieng -> nhieu tai
#   khoan roi rac, khong ai dang nhap duoc may cua nhau.
# - Chay script nay sau `git pull` + `docker compose up -d` de dong bo.
#
# Yeu cau: container php + mariadb dang chay, WordPress da duoc cai dat
# (da chay qua man hinh Install WordPress hoac `wp core install`).

# NOTE: de "Continue" (khong phai "Stop") vi `docker compose` luon in warning
# ra stderr (bien C/A5au9/I trong salts cua .env); voi "Stop" moi warning se
# bien thanh loi fatal va script chet oan. Loi that duoc bat bang $LASTEXITCODE.
$ErrorActionPreference = "Continue"

function Invoke-Wp {
    <#
    .SYNOPSIS
    Chay 1 lenh wp-cli trong container php, tra ve @{ Output; ExitCode }.
    Chi giu lai dong stdout dang string (bo warning stderr cua compose).
    #>
    param([string[]]$WpArgv)
    $raw = @(docker compose exec -T php php /tmp/wp-cli.phar @WpArgv --path=/var/www/html --allow-root 2>&1)
    $code = $LASTEXITCODE
    $lines = @($raw | ForEach-Object { "$_".Trim() } | Where-Object { $_ -ne "" -and $_ -notmatch '^(time=|level=warning|.*Defaulting to a blank string)' })
    return @{ Output = $lines; ExitCode = $code }
}

Write-Host "== KLEER reset-admin ==" -ForegroundColor Cyan

# 0. Dang dung trong thu muc KLEER?
if (-not (Test-Path "docker-compose.yml")) {
    Write-Host "[ERROR] Khong thay docker-compose.yml. Hay cd vao thu muc KLEER roi chay lai." -ForegroundColor Red
    exit 1
}

# 1. Nap wp-cli vao container neu chua co (container mat file nay khi recreate)
Write-Host "[1/5] Kiem tra wp-cli trong container php..." -ForegroundColor Yellow
$prev = $ErrorActionPreference; $ErrorActionPreference = "Continue"
docker compose exec -T php sh -c "test -x /tmp/wp-cli.phar || (curl -sfL -o /tmp/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x /tmp/wp-cli.phar)" 2>$null | Out-Null
$dlCode = $LASTEXITCODE; $ErrorActionPreference = $prev
if ($dlCode -ne 0) {
    Write-Host "[ERROR] Khong tai duoc wp-cli (can mang) hoac container php chua chay." -ForegroundColor Red
    Write-Host "        Hay chay 'docker compose up -d' truoc." -ForegroundColor Red
    exit 1
}

# 2. WordPress da duoc cai dat chua?
Write-Host "[2/5] Kiem tra WordPress da cai dat..." -ForegroundColor Yellow
$r = Invoke-Wp @("core", "is-installed")
if ($r.ExitCode -ne 0) {
    Write-Host "[ERROR] WordPress chua duoc cai dat (DB con trong)." -ForegroundColor Red
    Write-Host "        Hay mo http://localhost de cai dat lan dau, hoac chay:" -ForegroundColor Red
    Write-Host "        docker compose exec php php /tmp/wp-cli.phar core install --path=/var/www/html --allow-root --url=http://localhost --title=KLEER --admin_user=admin --admin_password=admin --admin_email=admin@kleer.local --skip-email" -ForegroundColor Red
    exit 1
}

# 3. Tao moi hoac reset mat khau admin -> admin
Write-Host "[3/5] Dong bo tai khoan admin / admin..." -ForegroundColor Yellow
$r = Invoke-Wp @("user", "get", "admin", "--field=ID")
if ($r.ExitCode -ne 0) {
    $r = Invoke-Wp @("user", "create", "admin", "admin@kleer.local", "--role=administrator", "--user_pass=admin")
    if ($r.ExitCode -ne 0) { Write-Host "[ERROR] Khong tao duoc user admin." -ForegroundColor Red; exit 1 }
    Write-Host "      Da tao moi user admin." -ForegroundColor Green
} else {
    $r = Invoke-Wp @("user", "update", "admin", "--user_pass=admin", "--role=administrator", "--user_email=admin@kleer.local")
    if ($r.ExitCode -ne 0) { Write-Host "[ERROR] Khong reset duoc mat khau admin." -ForegroundColor Red; exit 1 }
    Write-Host "      Da reset mat khau admin -> admin." -ForegroundColor Green
}

$adminId = (Invoke-Wp @("user", "get", "admin", "--field=ID")).Output | Select-Object -First 1

# 4. Xoa moi tai khoan khac, chuyen bai viet ve cho admin
Write-Host "[4/5] Xoa cac tai khoan thua (giu lai duy nhat admin)..." -ForegroundColor Yellow
$others = (Invoke-Wp @("user", "list", "--field=user_login")).Output | Where-Object { $_ -ne "" -and $_ -ne "admin" }
if ($others.Count -eq 0) {
    Write-Host "      Khong co tai khoan thua." -ForegroundColor Green
} else {
    foreach ($u in $others) {
        Write-Host "      Xoa user thua: $u" -ForegroundColor Yellow
        $r = Invoke-Wp @("user", "delete", $u, "--reassign=$adminId", "--yes")
        if ($r.ExitCode -ne 0) { Write-Host "[ERROR] Khong xoa duoc user $u." -ForegroundColor Red; exit 1 }
    }
}

# 5. Xoa cache + bao danh sach cuoi
Write-Host "[5/5] Xoa cache va chot danh sach..." -ForegroundColor Yellow
Invoke-Wp @("cache", "flush") | Out-Null
(Invoke-Wp @("user", "list", "--fields=ID,user_login,roles")).Output | ForEach-Object { Write-Host "      $_" }

Write-Host ""
Write-Host "[OK] Xong! Dang nhap tai http://localhost/wp-login.php voi admin / admin" -ForegroundColor Green
