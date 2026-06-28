<?php

declare(strict_types=1);

function initSecurity(): void
{
    blockSensitiveRequest();
    if (!headers_sent()) {
        sendSecurityHeaders();
    }
}

function currentRequestPath(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    return rtrim($uri, '/') ?: '/';
}

function isAdminRequest(): bool
{
    $path = currentRequestPath();

    return str_starts_with($path, '/admin');
}

function blockSensitiveRequest(): void
{
    $path = currentRequestPath();
    $query = $_SERVER['QUERY_STRING'] ?? '';

    $blockedPatterns = [
        '#^/\.env#i',
        '#^/\.git#i',
        '#^/composer\.(json|lock)$#i',
        '#^/(includes|config|database|scripts|vendor|templates)(/|$)#i',
        '#^/api/\.\.#i',
        '#\.(sql|sqlite|db|env|log|md|bat|ps1|py|ini)$#i',
        '#^/README#i',
        '#^/\.vercel#i',
    ];

    foreach ($blockedPatterns as $pattern) {
        if (preg_match($pattern, $path)) {
            denyAccess(404);
        }
    }

    if ($query !== '' && preg_match('#(^|&)(file|path|dir|source|include|page)=#i', $query)) {
        denyAccess(403);
    }
}

function denyAccess(int $status = 403): void
{
    http_response_code($status);
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo 'Not Found';
    exit;
}

function sendSecurityHeaders(): void
{
    @header_remove('X-Powered-By');

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), browsing-topics=()');
    header('X-Download-Options: noopen');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (isAdminRequest()) {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
    }

    if (!appConfig()['debug']) {
        header('Content-Security-Policy: ' . contentSecurityPolicy());
    }
}

function contentSecurityPolicy(): string
{
    $directives = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "script-src 'self' https://cdn.jsdelivr.net",
        "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'",
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
        "img-src 'self' data: blob: https:",
        "media-src 'self' blob: https:",
        "connect-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com",
        "worker-src 'self' blob:",
        "manifest-src 'self'",
        "upgrade-insecure-requests",
    ];

    return implode('; ', $directives);
}

function isProductionSite(): bool
{
    return !appConfig()['debug'];
}
