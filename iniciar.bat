@echo off
cd /d "%~dp0"

if not exist .env copy .env.example .env

if "%PORT%"=="" set PORT=8088

echo Servidor iniciado em http://127.0.0.1:%PORT%
echo Frontend: http://127.0.0.1:%PORT%/frontend/html/login.html
echo API: http://127.0.0.1:%PORT%/api/dashboard
php -S 127.0.0.1:%PORT% router.php
pause
