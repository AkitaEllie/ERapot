@echo off
REM Menjalankan E-Rapor di komputer lokal.
REM Menyalakan peladen, pemroses antrean, pemantau log dan Vite sekaligus.

setlocal
cd /d "%~dp0"

if not exist .env (
    echo error: .env belum ada. Jalankan install.bat lebih dahulu.
    exit /b 1
)
if not exist vendor (
    echo error: Dependensi belum terpasang. Jalankan install.bat lebih dahulu.
    exit /b 1
)

for /f "delims=" %%v in ('php scripts\config-value.php app.url') do set APP_URL=%%v

echo E-Rapor berjalan di !APP_URL!
echo Tekan Ctrl+C untuk berhenti.
echo.

REM composer dev menjalankan keempat proses dan menghentikannya bersama-sama.
call composer dev

endlocal
