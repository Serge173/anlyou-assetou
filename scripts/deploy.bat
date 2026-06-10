@echo off
cd /d "%~dp0.."
echo === Connexion Vercel (obligatoire une fois) ===
vercel login
if errorlevel 1 exit /b 1

echo.
echo === Deploiement production ===
vercel deploy --prod --yes
if errorlevel 1 exit /b 1

echo.
echo === IMPORTANT ===
echo Ajoutez ces variables sur https://vercel.com (Settings - Environment Variables) :
echo   DATABASE_URL = votre URL Neon postgresql://...
echo   DB_DRIVER = pgsql
echo   APP_DEBUG = false
echo   APP_URL = https://invitationdebaby.vercel.app
echo.
echo Puis initialisez la base :
echo   set DATABASE_URL=postgresql://...
echo   set DB_DRIVER=pgsql
echo   php database/init.php
echo.
echo Admin : /admin/login.php
echo Identifiants : admin / admin123
pause
