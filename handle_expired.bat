@echo off
cd /d "C:\laragon\www\ecoride"
C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe bin/console app:handle-expired-carshares >> logs/cron.log 2>&1
