<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();

$type = $_GET['type'] ?? 'confirmations';
$format = $_GET['format'] ?? 'csv';

if ($type === 'confirmations') {
    $data = getConfirmations($pdo);
    $headers = ['Nom', 'Prénom', 'Téléphone', 'Lien', 'Accompagnants', 'Message', 'Date'];
    $rows = array_map(fn($r) => [
        $r['last_name'], $r['first_name'], $r['phone'] ?? '',
        $r['relationship'], $r['companions'], $r['message'] ?? '', $r['created_at'],
    ], $data);
    $title = 'Confirmations de presence';
} else {
    $data = getDeclines($pdo);
    $headers = ['Nom', 'Prénom', 'Motif', 'Message', 'Date'];
    $rows = array_map(fn($r) => [
        $r['last_name'], $r['first_name'], $r['reason'], $r['message'] ?? '', $r['created_at'],
    ], $data);
    $title = 'Absences';
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, $headers, ';');
    foreach ($rows as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

// PDF export (HTML-based, printable)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= sanitize($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        h1 { color: #c9a962; border-bottom: 2px solid #c9a962; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #faf8f5; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="padding:10px 20px;background:#c9a962;color:#fff;border:none;cursor:pointer;margin-bottom:1rem">Imprimer / PDF</button>
    <h1><?= sanitize($title) ?> — <?= date('d/m/Y') ?></h1>
    <table>
        <thead><tr><?php foreach ($headers as $h): ?><th><?= sanitize($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
        <tr><?php foreach ($row as $cell): ?><td><?= sanitize((string) $cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
