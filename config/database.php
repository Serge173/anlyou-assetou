<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function normalizeDatabaseUrl(?string $url): ?string
{
    if (!$url) {
        return null;
    }
    // Neon uses postgres:// — PDO requires pgsql://
    if (str_starts_with($url, 'postgres://')) {
        return 'pgsql://' . substr($url, 11);
    }
    return $url;
}

function resolveDatabaseUrl(): ?string
{
    $candidates = [
        getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null),
        getenv('POSTGRES_URL') ?: ($_ENV['POSTGRES_URL'] ?? null),
        getenv('POSTGRES_URL_NON_POOLING') ?: ($_ENV['POSTGRES_URL_NON_POOLING'] ?? null),
        getenv('POSTGRES_PRISMA_URL') ?: ($_ENV['POSTGRES_PRISMA_URL'] ?? null),
        getenv('NEON_DATABASE_URL') ?: ($_ENV['NEON_DATABASE_URL'] ?? null),
    ];

    foreach ($candidates as $url) {
        $normalized = normalizeDatabaseUrl($url);
        if ($normalized && str_starts_with($normalized, 'pgsql')) {
            return $normalized;
        }
    }

    return null;
}

function getDatabase(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = resolveDatabaseUrl();
    $driver = getenv('DB_DRIVER') ?: ($_ENV['DB_DRIVER'] ?? null);

    if ($databaseUrl) {
        $pdo = new PDO($databaseUrl, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('SET search_path TO public');
        return $pdo;
    }

    if ($driver === 'pgsql') {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'fairepart';
        $user = getenv('DB_USER') ?: 'postgres';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    if (isServerless()) {
        throw new RuntimeException('Configure DATABASE_URL or POSTGRES_URL for production.');
    }

    $dbPath = __DIR__ . '/../database/fairepart.sqlite';
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function isPostgres(PDO $pdo): bool
{
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
}

function isServerless(): bool
{
    return (bool) (getenv('VERCEL') ?: getenv('AWS_LAMBDA_FUNCTION_NAME'));
}

function dbNow(PDO $pdo): string
{
    return isPostgres($pdo) ? 'NOW()' : "datetime('now')";
}

function lastInsertId(PDO $pdo, string $table): int
{
    if (isPostgres($pdo)) {
        return (int) $pdo->lastInsertId($table . '_id_seq');
    }
    return (int) $pdo->lastInsertId();
}
