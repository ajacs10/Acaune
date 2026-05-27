@echo off
cd /d "%~dp0"

set MYSQL=C:\xampp\mysql\bin\mysql.exe

if not exist "%MYSQL%" (
  echo MySQL nao encontrado em %MYSQL%
  echo Abra o phpMyAdmin e importe manualmente: backend\mysql\schema.sql
  pause
  exit /b 1
)

"%MYSQL%" -u root < backend\mysql\schema.sql
echo Base de dados importada com sucesso.
pause
