@echo off
REM Ejecutar sincronización diaria de tablas ERP
REM Programar en Windows Task Scheduler todos los días a las 04:00
REM
REM Acción: iniciar programa
REM   Programa: C:\ruta-a\php\php.exe
REM   Argumentos: "C:\perfushopping\web\src\sync_tables.php"
REM   Iniciar en: C:\perfushopping\web

set LOG_FILE=C:\perfushopping\web\storage\logs\sync_daily.log
echo [%date% %time%] Iniciando sync... >> "%LOG_FILE%"

C:\xampp\php\php.exe "C:\perfushopping\web\src\sync_tables.php" >> "%LOG_FILE%" 2>&1

echo [%date% %time%] Sync finalizado >> "%LOG_FILE%"
