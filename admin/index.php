<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();
$stats = getDashboardStats($pdo);
$settings = getSettings($pdo);
$coupleNames = coupleLabel($settings);
$invitationLink = invitationSiteUrl();
$invitationMessage = invitationShareMessage($settings);
$invitationFullText = invitationShareText($settings);
$messageSaved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_invitation_message') {
    updateSettings($pdo, [
        'invitation_share_message' => trim($_POST['invitation_share_message'] ?? ''),
    ]);
    $settings = getSettings($pdo);
    $invitationMessage = invitationShareMessage($settings);
    $invitationFullText = invitationShareText($settings);
    $messageSaved = true;
}

ob_start();
?>
<?php if ($messageSaved): ?>
<div class="alert alert-success">Texte d'invitation enregistré.</div>
<?php endif; ?>
<?php if ($coupleNames !== ''): ?>
<div class="couple-dashboard-banner mb-4">
    <div>
        <h2><?= sanitize($coupleNames) ?></h2>
        <p>Invitation de <?= sanitize($coupleNames) ?> — modifiable dans les paramètres</p>
    </div>
    <a href="/admin/settings.php" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Modifier les noms</a>
</div>
<?php endif; ?>

<div class="admin-card invite-share-card mb-4">
    <div class="invite-share-header">
        <div>
            <h3><i class="bi bi-send me-2"></i>Partager l'invitation</h3>
            <p class="text-muted mb-0">Copiez le lien et le message à envoyer à vos invités (WhatsApp, SMS, e-mail…)</p>
        </div>
    </div>

    <div class="invite-share-link mb-4">
        <label class="form-label" for="invitationLink">Lien de l'invitation</label>
        <div class="input-group">
            <input type="text" class="form-control" id="invitationLink" value="<?= sanitize($invitationLink) ?>" readonly>
            <button type="button" class="btn btn-primary" id="copyInvitationLink" data-copy-target="invitationLink">
                <i class="bi bi-link-45deg me-1"></i> Copier le lien
            </button>
        </div>
        <small class="text-success copy-feedback d-none" data-copy-feedback="invitationLink">Lien copié !</small>
    </div>

    <form method="POST" class="invite-share-form">
        <input type="hidden" name="action" value="save_invitation_message">
        <label class="form-label" for="invitationMessage">Texte d'invitation</label>
        <textarea class="form-control invite-share-textarea" id="invitationMessage" name="invitation_share_message" rows="12"><?= htmlspecialchars(trim($settings['invitation_share_message'] ?? '') !== '' ? ($settings['invitation_share_message'] ?? '') : invitationShareMessage($settings), ENT_QUOTES, 'UTF-8') ?></textarea>
        <p class="text-muted small mt-2 mb-3">Le lien sera ajouté automatiquement à la fin si vous copiez le message complet. Personnalisez le texte puis enregistrez.</p>
        <textarea class="visually-hidden" id="invitationFullText" readonly><?= sanitize($invitationFullText) ?></textarea>
        <div class="invite-share-actions">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-check-lg me-1"></i> Enregistrer le texte
            </button>
            <button type="button" class="btn btn-outline-secondary" id="copyInvitationMessage" data-copy-textarea="invitationMessage">
                <i class="bi bi-chat-left-text me-1"></i> Copier le texte
            </button>
            <button type="button" class="btn btn-gold" id="copyInvitationFull" data-invitation-link="<?= sanitize($invitationLink) ?>">
                <i class="bi bi-clipboard-check me-1"></i> Copier message + lien
            </button>
        </div>
        <small class="text-success copy-feedback d-none" data-copy-feedback="invitationMessage">Texte copié !</small>
        <small class="text-success copy-feedback d-none" data-copy-feedback="invitationFull">Message complet copié !</small>
    </form>
</div>

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
