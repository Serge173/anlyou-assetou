<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$pdo = getDatabase();
$stmt = $pdo->prepare('UPDATE settings SET bride_name = ?, groom_name = ? WHERE id = 1');
$stmt->execute(['Koné Assetou', 'Koné Anlyou']);

$row = $pdo->query('SELECT bride_name, groom_name FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
echo 'Noms mis à jour : ' . ($row['bride_name'] ?? '') . ' & ' . ($row['groom_name'] ?? '') . PHP_EOL;
