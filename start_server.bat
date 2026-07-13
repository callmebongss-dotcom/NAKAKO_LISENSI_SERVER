@echo off
title NAKAKO LICENSE SERVER
echo ========================================
echo   NAKAKO LICENSE SERVER
echo   Starting PHP Server...
echo ========================================
echo.
php -S 0.0.0.0:8080 -t public public/index.php
pause
