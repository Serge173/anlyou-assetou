<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    deleteDecline($pdo, (int) $_POST['id']);
    redirect('/admin/declines.php?deleted=1');
}

$declines = getDeclines($pdo);

ob_start();
?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Absence supprimée.</div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Liste des absences (<?= count($declines) ?>)</h3>
        <a href="/admin/export.php?type=declines&format=csv" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Exporter</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>Nom</th><th>Prénom</th><th>Motif</th><th>Message</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($declines as $d): ?>
            <tr>
                <td><?= sanitize($d['last_name']) ?></td>
                <td><?= sanitize($d['first_name']) ?></td>
                <td><span class="badge bg-warning text-dark"><?= sanitize($d['reason']) ?></span></td>
                <td class="text-truncate" style="max-width:200px"><?= sanitize($d['message'] ?? '—') ?></td>
                <td><?= sanitize(substr($d['created_at'], 0, 10)) ?></td>
                <td>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($declines)): ?>
            <tr><td colspan="6" class="text-muted text-center">Aucune absence enregistrée</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
adminLayout('Gestion des absences', $content, 'declines');
