<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$pdo = getDatabase();

$welcomeMessage = 'Nous sommes heureux de vous compter parmi les personnes qui ont marqué notre histoire.';
$invitationText = "Si vous avez reçu ce lien, c'est que vous êtes notre heureux invité ou notre heureuse invitée VVIP.\n\nNous vous invitons à célébrer avec nous ce jour unique.";

$stmt = $pdo->prepare('UPDATE settings SET welcome_message = ?, invitation_text = ? WHERE id = 1');
$stmt->execute([$welcomeMessage, $invitationText]);

echo "Textes mis à jour.\n";
