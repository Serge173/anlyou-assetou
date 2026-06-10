<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();
$stats = getDashboardStats($pdo);
$settings = getSettings($pdo);
$coupleNames = coupleLabel($settings);

ob_start();
?>
<?php if ($coupleNames !== ''): ?>
<div class="couple-dashboard-banner mb-4">
    <div>
        <h2><?= sanitize($coupleNames) ?></h2>
        <p>Invitation de <?= sanitize($coupleNames) ?> — modifiable dans les paramètres</p>
    </div>
    <a href="/admin/settings.php" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Modifier les noms</a>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon bg-primary"><i class="bi bi-envelope-check"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['total_responses'] ?></span>
                <span class="stat-label">Réponses RSVP</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['confirmations'] ?></span>
                <span class="stat-label">Confirmations</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon bg-danger"><i class="bi bi-x-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['declines'] ?></span>
                <span class="stat-label">Refus</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon bg-info"><i class="bi bi-people"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['total_guests'] ?></span>
                <span class="stat-label">Invités total</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon bg-warning"><i class="bi bi-chat-dots"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['guestbook_total'] ?></span>
                <span class="stat-label">Vœux reçus</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="stat-card">
            <div class="stat-icon bg-secondary"><i class="bi bi-book"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['wishes'] ?></span>
                <span class="stat-label">Vœux publiés</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Dernières confirmations</h3>
                <a href="/admin/confirmations.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nom</th><th>Catégorie</th><th>+1</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice(getConfirmations($pdo), 0, 5) as $c): ?>
                    <tr>
                        <td><?= sanitize($c['first_name'] . ' ' . $c['last_name']) ?></td>
                        <td><span class="badge bg-light text-dark"><?= sanitize($c['relationship']) ?></span></td>
                        <td><?= (int) $c['companions'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty(getConfirmations($pdo))): ?>
                    <tr><td colspan="3" class="text-muted text-center">Aucune confirmation</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Derniers vœux</h3>
                <a href="/admin/guestbook.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nom</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice(getGuestbookEntries($pdo), 0, 5) as $g): ?>
                    <tr>
                        <td><?= sanitize($g['first_name'] . ' ' . $g['last_name']) ?></td>
                        <td>
                            <?php
                            $badge = match ($g['status']) {
                                'approved' => 'success',
                                'hidden' => 'secondary',
                                default => 'warning',
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= sanitize($g['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
adminLayout('Tableau de bord', $content, 'dashboard');
