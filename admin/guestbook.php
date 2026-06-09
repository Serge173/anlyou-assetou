<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'approve') {
        updateGuestbookStatus($pdo, $id, 'approved');
    } elseif ($action === 'hide') {
        updateGuestbookStatus($pdo, $id, 'hidden');
    } elseif ($action === 'delete') {
        deleteGuestbook($pdo, $id);
    }
    redirect('/admin/guestbook.php?updated=1');
}

$entries = getGuestbookEntries($pdo);

ob_start();
?>
<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success">Mise à jour effectuée.</div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Livre d'or (<?= count($entries) ?> vœux)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>Nom</th><th>Prénom</th><th>Message</th><th>Statut</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $e): ?>
            <tr>
                <td><?= sanitize($e['last_name']) ?></td>
                <td><?= sanitize($e['first_name']) ?></td>
                <td style="max-width:300px"><?= sanitize($e['message']) ?></td>
                <td>
                    <?php
                    $badge = match ($e['status']) {
                        'approved' => 'success',
                        'hidden' => 'secondary',
                        default => 'warning',
                    };
                    ?>
                    <span class="badge bg-<?= $badge ?>"><?= sanitize($e['status']) ?></span>
                </td>
                <td><?= sanitize(substr($e['created_at'], 0, 10)) ?></td>
                <td class="text-nowrap">
                    <?php if ($e['status'] !== 'approved'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button class="btn btn-sm btn-success" title="Valider"><i class="bi bi-check-lg"></i></button>
                    </form>
                    <?php endif; ?>
                    <?php if ($e['status'] !== 'hidden'): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="hide">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button class="btn btn-sm btn-secondary" title="Masquer"><i class="bi bi-eye-slash"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button class="btn btn-sm btn-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
adminLayout('Livre d\'or', $content, 'guestbook');
