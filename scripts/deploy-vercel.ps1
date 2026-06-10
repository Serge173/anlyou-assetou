# Déploiement Vercel + Neon
# Prérequis : vercel login (dans le terminal)

param(
    [Parameter(Mandatory = $true)]
    [string]$DatabaseUrl
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

Write-Host "==> Vérification Vercel..."
vercel whoami

Write-Host "==> Variables d'environnement..."
"pgsql" | vercel env add DB_DRIVER production --force
"false" | vercel env add APP_DEBUG production --force
$DatabaseUrl | vercel env add DATABASE_URL production --force
"https://invitationdebaby.vercel.app" | vercel env add APP_URL production --force

Write-Host "==> Déploiement production..."
vercel deploy --prod --yes

Write-Host "==> Initialisation Neon..."
$env:DATABASE_URL = $DatabaseUrl
$env:DB_DRIVER = "pgsql"
php database/init.php

Write-Host ""
Write-Host "Terminé."
Write-Host "Admin : /admin/login.php"
Write-Host "Login : admin / admin123"
