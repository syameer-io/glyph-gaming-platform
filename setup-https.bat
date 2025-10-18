@echo off
REM =====================================================================
REM  HTTPS Setup Script for Laragon - Voice Chat Fix
REM  This script automates the HTTPS configuration for socialgaminghub.test
REM =====================================================================

echo.
echo =====================================================================
echo  HTTPS Setup for Glyph (socialgaminghub.test)
echo  Voice Chat Fix - Enable Secure Context for getUserMedia
echo =====================================================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] This script must be run as Administrator!
    echo.
    echo Right-click this file and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo [Step 1/6] Checking Laragon installation...
if not exist "C:\laragon\laragon.exe" (
    echo [ERROR] Laragon not found at C:\laragon
    echo Please update the path in this script if Laragon is installed elsewhere.
    pause
    exit /b 1
)
echo [OK] Laragon found

echo.
echo [Step 2/6] Creating SSL certificate directory...
if not exist "C:\laragon\etc\ssl\socialgaminghub.test" (
    mkdir "C:\laragon\etc\ssl\socialgaminghub.test"
    echo [OK] Directory created: C:\laragon\etc\ssl\socialgaminghub.test
) else (
    echo [OK] Directory already exists
)

echo.
echo [Step 3/6] Generating self-signed SSL certificate...
echo This may take a few seconds...

REM Find OpenSSL in Laragon
set OPENSSL_PATH=C:\laragon\bin\openssl\openssl.exe
if not exist "%OPENSSL_PATH%" (
    REM Try Apache bin directory
    for /d %%i in (C:\laragon\bin\apache\httpd-*) do (
        if exist "%%i\bin\openssl.exe" (
            set OPENSSL_PATH=%%i\bin\openssl.exe
        )
    )
)

if not exist "%OPENSSL_PATH%" (
    echo [WARNING] OpenSSL not found automatically.
    echo You may need to generate the certificate manually using Laragon's menu.
    echo Go to: Laragon Menu ^> Apache ^> SSL ^> socialgaminghub.test
    goto :skip_cert_generation
)

echo Using OpenSSL: %OPENSSL_PATH%

"%OPENSSL_PATH%" req -x509 -nodes -days 365 -newkey rsa:2048 ^
    -keyout "C:\laragon\etc\ssl\socialgaminghub.test\server.key" ^
    -out "C:\laragon\etc\ssl\socialgaminghub.test\server.crt" ^
    -subj "/C=US/ST=Development/L=Local/O=Glyph/CN=socialgaminghub.test" >nul 2>&1

if %errorLevel% equ 0 (
    echo [OK] SSL certificate generated successfully
) else (
    echo [WARNING] Certificate generation may have failed.
    echo You can generate it manually via Laragon menu: Apache ^> SSL ^> socialgaminghub.test
)

:skip_cert_generation

echo.
echo [Step 4/6] Backing up current virtual host configuration...
if exist "C:\laragon\etc\apache2\sites-enabled\auto.socialgaminghub.test.conf" (
    copy /y "C:\laragon\etc\apache2\sites-enabled\auto.socialgaminghub.test.conf" ^
         "C:\laragon\etc\apache2\sites-enabled\auto.socialgaminghub.test.conf.backup" >nul 2>&1
    echo [OK] Backup created
) else (
    echo [INFO] No existing configuration to backup
)

echo.
echo [Step 5/6] Installing HTTPS virtual host configuration...
if exist "apache-vhost-ssl.conf" (
    copy /y "apache-vhost-ssl.conf" ^
         "C:\laragon\etc\apache2\sites-enabled\auto.socialgaminghub.test.conf" >nul 2>&1
    echo [OK] Virtual host configuration installed
) else (
    echo [ERROR] apache-vhost-ssl.conf not found in current directory!
    echo Please run this script from the project root directory.
    pause
    exit /b 1
)

echo.
echo [Step 6/6] Clearing Laravel configuration cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo [OK] Laravel cache cleared

echo.
echo =====================================================================
echo  HTTPS Setup Complete!
echo =====================================================================
echo.
echo NEXT STEPS:
echo.
echo 1. Restart Laragon:
echo    - Right-click Laragon tray icon
echo    - Select "Stop All"
echo    - Wait 5 seconds
echo    - Select "Start All"
echo.
echo 2. Trust the SSL certificate in your browser:
echo    - Open Chrome/Edge
echo    - Navigate to: https://socialgaminghub.test
echo    - Click "Advanced" on security warning
echo    - Click "Proceed to socialgaminghub.test (unsafe)"
echo    - Browser will remember this choice
echo.
echo 3. Verify HTTPS is working:
echo    - Open: https://socialgaminghub.test
echo    - Check for padlock icon in address bar
echo    - Open Developer Console (F12)
echo    - Type: window.isSecureContext
echo    - Should return: true
echo.
echo 4. Test voice chat:
echo    - Join a voice channel
echo    - Browser should prompt for microphone permission
echo    - Allow access and test voice communication
echo.
echo For troubleshooting, see: HTTPS_SETUP_GUIDE.md
echo.
echo =====================================================================
echo.
pause
