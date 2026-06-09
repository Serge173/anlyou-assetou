<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/models.php';

loadEnv(dirname(__DIR__) . '/.env');
startSession();

function bootstrapDatabase(): PDO
{
    $pdo = getDatabase();
    require_once __DIR__ . '/../database/init.php';
    try {
        $pdo->query('SELECT 1 FROM settings LIMIT 1');
        runMigrations($pdo);
    } catch (PDOException) {
        initializeDatabase($pdo);
    }
    return $pdo;
}

function adminRoot(): string
{
    return projectRoot() . '/admin';
}
