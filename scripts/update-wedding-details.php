<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$pdo = getDatabase();

$welcomeTitle = 'Bienvenue à notre mariage';
$welcomeMessage = '« Et parmi Ses signes, Il a créé de vous, pour vous, des épouses afin que vous viviez en tranquillité avec elles et Il a mis entre vous affection et miséricorde. Il y a certes là des signes pour des gens qui réfléchissent. » — Sourate Ar-Rum (30:21)';
$invitationText = "Si vous avez reçu ce lien, c'est que vous êtes notre heureux invité ou notre heureuse invitée VVIP.\n\nNous avons l'immense joie de vous inviter à célébrer notre union : la célébration du mariage à la Mosquée Salam, la réception à la Salle TENE BRAHIMA OUATTARA, et la danse de réjouissance au centre social Attécoubé. Votre présence sera notre plus belle joie.";

$religiousVenue = "Mosquée Salam, en face du super marché Bon Prix d'Attécoubé\nJeudi 30 juillet 2026 à 09h00";
$receptionVenue = "Salle TENE BRAHIMA OUATTARA, Hôtel des Armées (État Major) du Plateau, Abidjan\nJeudi 30 juillet 2026 à 11h00";
$civilVenue = "Centre social Attécoubé\nDimanche 2 août 2026 à 14h00";

$stmt = $pdo->prepare(
    'UPDATE settings SET
        wedding_date = ?,
        start_time = ?,
        end_time = ?,
        religious_venue = ?,
        reception_venue = ?,
        civil_venue = ?,
        welcome_title = ?,
        welcome_message = ?,
        invitation_text = ?,
        gps_lat = ?,
        gps_lng = ?,
        countdown_title = ?
     WHERE id = 1'
);

$stmt->execute([
    '2026-07-30',
    '09:00',
    '23:00',
    $religiousVenue,
    $receptionVenue,
    $civilVenue,
    $welcomeTitle,
    $welcomeMessage,
    $invitationText,
    5.3561,
    -4.0127,
    'Le grand jour approche',
]);

$cardImage = 'assets/images/carte-invitation-assetou.png';
$pdo->prepare('UPDATE settings SET hero_image = ?, invitation_card_image = ? WHERE id = 1')
    ->execute([$cardImage, $cardImage]);

$row = $pdo->query('SELECT wedding_date, start_time, religious_venue, reception_venue, civil_venue FROM settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
echo 'Détails du mariage mis à jour.' . PHP_EOL;
echo 'Date : ' . ($row['wedding_date'] ?? '') . ' à ' . ($row['start_time'] ?? '') . PHP_EOL;
echo 'Célébration : ' . str_replace("\n", ' — ', $row['religious_venue'] ?? '') . PHP_EOL;
echo 'Réception : ' . str_replace("\n", ' — ', $row['reception_venue'] ?? '') . PHP_EOL;
echo 'Danse : ' . str_replace("\n", ' — ', $row['civil_venue'] ?? '') . PHP_EOL;
