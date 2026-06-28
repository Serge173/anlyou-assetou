<?php

declare(strict_types=1);

$source = $argv[1] ?? 'D:\Downloads\video assetou.mp4';
$targetDir = dirname(__DIR__) . '/public/assets/videos';
$target = $targetDir . '/video-assetou.mp4';

if (!is_file($source)) {
    fwrite(STDERR, "Fichier introuvable : {$source}\n");
    exit(1);
}

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

copy($source, $target);
echo 'Vidéo copiée : public/assets/videos/video-assetou.mp4' . PHP_EOL;
echo 'Visible dans la section « Galerie des Souvenirs » sur la page d\'accueil.' . PHP_EOL;
