@echo off
set /p pw="Password: "
php "%~dp0index.php" "decrypt" "%~f1" "%pw%"
pause