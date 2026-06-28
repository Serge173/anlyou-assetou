<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$coverPath = 'assets/images/couple-couverture.png';
$pdo = getDatabase();
$pdo->prepare('UPDATE settings SET invitation_card_image = ? WHERE id = 1')->execute([$coverPath]);

echo 'Couverture de la carte mise à jour : ' . $coverPath . PHP_EOL;
