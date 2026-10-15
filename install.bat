@echo off
REM Memasang E-Rapor: dependensi, .env, migrasi, data contoh, dan aset frontend.
REM Berhenti dengan pesan yang jelas bila ada yang kurang.

setlocal enabledelayedexpansion
cd /d "%~dp0"

echo ==^> Memeriksa perkakas

where php >nul 2>&1 || (echo error: PHP tidak ditemukan. Pasang PHP 8.3 atau lebih baru. & exit /b 1)
where composer >nul 2>&1 || (echo error: Composer tidak ditemukan. Lihat https://getcomposer.org. & exit /b 1)
where npm >nul 2>&1 || (echo error: npm tidak ditemukan. Pasang Node.js 22 atau lebih baru. & exit /b 1)

for /f "delims=" %%v in ('php -r "echo version_compare(PHP_VERSION, \'8.3.0\', \'>=\') ? \'ok\' : \'no\';"') do set PHP_OK=%%v
if not "!PHP_OK!"=="ok" (
    for /f "delims=" %%v in ('php -r "echo PHP_VERSION;"') do echo error: PHP %%v terlalu lama. Diperlukan 8.3 atau lebih baru.
    exit /b 1
)

echo     ok PHP, Composer, dan npm tersedia

REM --- .env ---
if not exist .env (
    echo ==^> Membuat .env dari .env.example
    copy /y .env.example .env >nul
    echo     ok .env dibuat
) else (
    echo     ok .env sudah ada, dibiarkan apa adanya
)

REM --- Dependensi ---
echo ==^> Memasang dependensi PHP
call composer install --no-interaction --prefer-dist || exit /b 1

echo ==^> Memasang dependensi JavaScript
call npm install --no-audit --no-fund || exit /b 1

REM --- Kunci aplikasi ---
findstr /r /c:"^APP_KEY=." .env >nul 2>&1
if errorlevel 1 (
    echo ==^> Mengisi APP_KEY
    php artisan key:generate --ansi || exit /b 1
)
echo     ok APP_KEY terisi

REM --- Basis data ---
echo ==^> Memeriksa basis data

REM Pembacaannya lewat Laravel, bukan parse .env, karena .env memuat komentar
REM dan tanda kutip yang mudah salah dibaca.
for /f "delims=" %%v in ('php scripts\check-database.php 2^>nul ^| findstr /b "target="') do set DB_TARGET=%%v
set DB_TARGET=!DB_TARGET:target==
for /f "tokens=1,2 delims=@/" %%a in ("!DB_TARGET!") do (
    set DB_USER=%%a
    set DB_NAME=%%b
)

php scripts\check-database.php >nul 2>&1
if errorlevel 1 (
    echo.
    echo error: Basis data belum bisa dihubungi.
    echo.
    echo   Sasaran: !DB_TARGET!
    echo.
    echo   Buat basis datanya lebih dahulu:
    echo.
    echo       mysql -u!DB_USER! -p -e "CREATE DATABASE !DB_NAME! CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo.
    echo   Pastikan juga peladen MySQL atau MariaDB sudah berjalan, lalu periksa
    echo   DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di .env.
    exit /b 1
)
echo     ok Basis data terhubung (!DB_TARGET!)

REM --- Migrasi dan data contoh ---
echo ==^> Menjalankan migrasi dan mengisi data contoh
php artisan migrate --force --ansi || exit /b 1
php artisan db:seed --force --ansi || exit /b 1

REM --- Penyimpanan dan aset ---
echo ==^> Menautkan penyimpanan
php artisan storage:link --ansi >nul 2>&1

echo ==^> Membangun aset frontend
call npm run build || exit /b 1

echo.
echo     ok Pemasangan selesai
echo.
echo   Jalankan aplikasinya:
echo.
echo       run.bat
echo.
echo   Masuk dengan kata sandi rahasia-kuat:
echo.
echo       sari@tkcktc.sch.id      Guru Wali Kelas
echo       ratna@tkcktc.sch.id     Guru Mata Pelajaran
echo       andre@tkcktc.sch.id     Tata Usaha
echo       mei@tkcktc.sch.id       Kepala Sekolah
echo.

endlocal
