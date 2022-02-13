@echo off
set /p pw="Password: "
php "%~dp0index.php" "encrypt" "%~f1" "%pw%"
pause