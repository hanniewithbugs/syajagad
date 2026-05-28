@echo off
setlocal
cd /d "%~dp0"

set "PHP_EXE=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=C:\xampp\php\php.exe"

if not exist "%PHP_EXE%" (
    echo PHP tidak ditemukan. Install Laragon/XAMPP atau sesuaikan path PHP di file ini.
    pause
    exit /b 1
)

if not exist "database\database.sqlite" type nul > "database\database.sqlite"

"%PHP_EXE%" artisan migrate --force
echo.
echo Website berjalan di: http://127.0.0.1:8000
echo Tekan Ctrl+C untuk menghentikan server.
echo.
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000
