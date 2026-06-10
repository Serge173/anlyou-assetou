<?php

declare(strict_types=1);

function normalizeDatabaseUrl(?string $url): ?string
{
    if (!$url) {
        return null;
    }
    // Neon / Vercel use postgres:// or postgresql:// — PDO requires a DSN string
    if (str_starts_with($url, 'postgres://')) {
        return 'pgsql://' . substr($url, 11);
    }
    if (str_starts_with($url, 'postgresql://')) {
        return 'pgsql://' . substr($url, 13);
    }
    return $url;
}

function connectPostgres(string $databaseUrl): PDO
{
    $url = str_starts_with($databaseUrl, 'pgsql://')
        ? 'postgres://' . substr($databaseUrl, 8)
        : $databaseUrl;

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        throw new RuntimeException('Invalid Postgres connection URL.');
    }

    parse_str($parts['query'] ?? '', $query);
    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
        $parts['host'],
        $parts['port'] ?? 5432,
        ltrim($parts['path'] ?? '', '/'),
        $query['sslmode'] ?? 'require'
    );

    return new PDO($dsn, rawurldecode($parts['user'] ?? ''), rawurldecode($parts['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Avoid "cached plan must not change result type" on Neon/PgBouncer after migrations.
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
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

    $host = getenv('PGHOST') ?: getenv('POSTGRES_HOST') ?: ($_ENV['PGHOST'] ?? $_ENV['POSTGRES_HOST'] ?? null);
    $user = getenv('PGUSER') ?: getenv('POSTGRES_USER') ?: ($_ENV['PGUSER'] ?? $_ENV['POSTGRES_USER'] ?? null);
    $password = getenv('PGPASSWORD') ?: getenv('POSTGRES_PASSWORD') ?: ($_ENV['PGPASSWORD'] ?? $_ENV['POSTGRES_PASSWORD'] ?? null);
    $database = getenv('PGDATABASE') ?: getenv('POSTGRES_DATABASE') ?: ($_ENV['PGDATABASE'] ?? $_ENV['POSTGRES_DATABASE'] ?? null);

    if ($host && $user && $database) {
        $passwordPart = rawurlencode((string) $password);
        return "pgsql://{$user}:{$passwordPart}@{$host}/{$database}?sslmode=require";
    }

    return null;
}

function getDatabase(bool $reset = false): PDO
{
    static $pdo = null;
    if ($reset) {
        $pdo = null;
    }
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = resolveDatabaseUrl();
    $driver = getenv('DB_DRIVER') ?: ($_ENV['DB_DRIVER'] ?? null);

    if ($databaseUrl) {
        $pdo = connectPostgres($databaseUrl);
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
            PDO::ATTR_EMULATE_PREPARES => true,
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

function resetDatabaseConnection(): PDO
{
    return getDatabase(true);
}

function invalidatePostgresPlans(PDO $pdo): void
{
    if (!isPostgres($pdo)) {
        return;
    }

    try {
        $pdo->exec('DEALLOCATE ALL');
    } catch (PDOException) {
        // Ignored when the pooler does not allow DEALLOCATE.
    }
}
