# Déploiement Vercel — Anlyou & Assetou
# Prérequis : vercel login
#
# Usage :
#   .\scripts\deploy-anlyou-assetou.ps1 -DatabaseUrl "postgresql://..."

param(
    [Parameter(Mandatory = $true)]
    [string]$DatabaseUrl
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$ProjectName = "anlyou-assetou"
$AppUrl = "https://anlyou-assetou.vercel.app"

Write-Host "==> Compte Vercel..."
vercel whoami

Write-Host "==> Liaison projet $ProjectName..."
if (-not (Test-Path ".vercel")) {
    vercel link --project $ProjectName --yes 2>$null
    if ($LASTEXITCODE -ne 0) {
        vercel project add $ProjectName --yes
        vercel link --project $ProjectName --yes
    }
}

Write-Host "==> Variables d'environnement..."
"pgsql" | vercel env add DB_DRIVER production --force
"false" | vercel env add APP_DEBUG production --force
$DatabaseUrl | vercel env add DATABASE_URL production --force
$AppUrl | vercel env add APP_URL production --force

Write-Host "==> Build assets production..."
php scripts/build-prod-assets.php

Write-Host "==> Deploiement production..."
vercel deploy --prod --yes --name $ProjectName

Write-Host "==> Initialisation base Neon..."
$env:DATABASE_URL = $DatabaseUrl
$env:DB_DRIVER = "pgsql"
php database/init.php
php scripts/update-names.php
php scripts/update-wedding-details.php
php scripts/update-card-cover.php

Write-Host ""
Write-Host "Termine."
Write-Host "Site  : $AppUrl"
Write-Host "Admin : $AppUrl/admin/login.php"
Write-Host "Login : admin / admin123"
