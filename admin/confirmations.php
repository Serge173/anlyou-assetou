<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update' && !empty($_POST['id'])) {
        updateConfirmation($pdo, (int) $_POST['id'], $_POST);
        redirect('/admin/confirmations.php?updated=1');
    }
    if ($_POST['action'] === 'delete' && !empty($_POST['id'])) {
        deleteConfirmation($pdo, (int) $_POST['id']);
        redirect('/admin/confirmations.php?deleted=1');
    }
}

$confirmations = getConfirmations($pdo);
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editItem = null;
if ($editId) {
    foreach ($confirmations as $c) {
        if ((int) $c['id'] === $editId) {
            $editItem = $c;
            break;
        }
    }
}

ob_start();
?>
<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success">Confirmation mise à jour.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Confirmation supprimée.</div>
<?php endif; ?>

<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h3>Liste des présences (<?= count($confirmations) ?>)</h3>
        <div class="btn-group">
            <a href="/admin/export.php?type=confirmations&format=csv" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
            <a href="/admin/export.php?type=confirmations&format=pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nom</th><th>Prénom</th><th>Catégorie</th><th>Tél.</th>
                    <th>Accomp.</th><th>Message</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($confirmations as $c): ?>
            <tr>
                <td><?= sanitize($c['last_name']) ?></td>
                <td><?= sanitize($c['first_name']) ?></td>
                <td><span class="badge bg-light text-dark"><?= sanitize($c['relationship']) ?></span></td>
                <td><?= sanitize($c['phone'] ?? '—') ?></td>
                <td><?= (int) $c['companions'] ?></td>
                <td class="text-truncate" style="max-width:150px"><?= sanitize($c['message'] ?? '—') ?></td>
                <td><?= sanitize(substr($c['created_at'], 0, 10)) ?></td>
                <td>
                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($editItem): ?>
<div class="admin-card">
    <h3>Modifier la confirmation</h3>
    <form method="POST" class="row g-3">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
        <div class="col-md-6"><label class="form-label">Prénom</label><input name="first_name" class="form-control" value="<?= sanitize($editItem['first_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Nom</label><input name="last_name" class="form-control" value="<?= sanitize($editItem['last_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Téléphone</label><input name="phone" class="form-control" value="<?= sanitize($editItem['phone'] ?? '') ?>"></div>
        <div class="col-md-6">
            <label class="form-label">Lien</label>
            <select name="relationship" class="form-select" required>
                <?php foreach (relationshipOptions() as $opt): ?>
                <option value="<?= sanitize($opt) ?>" <?= $editItem['relationship'] === $opt ? 'selected' : '' ?>><?= sanitize($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Accompagnants</label><input type="number" name="companions" class="form-control" value="<?= (int) $editItem['companions'] ?>" min="0"></div>
        <div class="col-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="2"><?= sanitize($editItem['message'] ?? '') ?></textarea></div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/admin/confirmations.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
adminLayout('Gestion des présences', $content, 'confirmations');
