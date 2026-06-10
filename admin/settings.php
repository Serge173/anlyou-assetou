<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();
$settings = getSettings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'bride_name' => trim($_POST['bride_name'] ?? ''),
        'groom_name' => trim($_POST['groom_name'] ?? ''),
        'wedding_date' => $_POST['wedding_date'] ?? '',
        'start_time' => $_POST['start_time'] ?? '',
        'end_time' => $_POST['end_time'] ?? '',
        'civil_venue' => trim($_POST['civil_venue'] ?? ''),
        'religious_venue' => trim($_POST['religious_venue'] ?? ''),
        'reception_venue' => trim($_POST['reception_venue'] ?? ''),
        'gps_lat' => (float) ($_POST['gps_lat'] ?? 0),
        'gps_lng' => (float) ($_POST['gps_lng'] ?? 0),
        'welcome_title' => trim($_POST['welcome_title'] ?? ''),
        'welcome_message' => trim($_POST['welcome_message'] ?? ''),
        'invitation_text' => trim($_POST['invitation_text'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'wedding_passed' => isset($_POST['wedding_passed']) ? 1 : 0,
        'album_enabled' => isset($_POST['album_enabled']) ? 1 : 0,
        'countdown_title' => trim($_POST['countdown_title'] ?? ''),
        'countdown_message_past' => trim($_POST['countdown_message_past'] ?? ''),
        'countdown_enabled' => isset($_POST['countdown_enabled']) ? 1 : 0,
    ];

    $heroPath = resolveMediaPath(
        handleMediaUpload($_FILES['hero_image'] ?? [], 'hero'),
        $_POST['hero_image_url'] ?? ''
    );
    if ($heroPath) {
        $data['hero_image'] = $heroPath;
    }

    if (!empty($_POST['remove_invitation_card_image'])) {
        $data['invitation_card_image'] = '';
    } else {
        $cardPath = resolveMediaPath(
            handleMediaUpload($_FILES['invitation_card_image'] ?? [], 'invite_card'),
            $_POST['invitation_card_image_url'] ?? ''
        );
        if ($cardPath) {
            $data['invitation_card_image'] = $cardPath;
        }
    }

    updateSettings($pdo, $data);
    $settings = getSettings($pdo);
    $saved = true;
}

ob_start();
?>
<?php if (!empty($saved)): ?>
<div class="alert alert-success">Paramètres enregistrés avec succès.</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="couple-names-form">
    <div class="admin-card mb-4">
        <h3><i class="bi bi-heart me-2"></i>Noms des mariés</h3>
        <p class="text-muted mb-4">Ces prénoms apparaissent sur le site public : intro cinématique, carte d'invitation, en-tête, page d'accueil et pied de page.</p>
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="bride_name">Prénom de la mariée</label>
                        <input id="bride_name" name="bride_name" class="form-control form-control-lg" value="<?= sanitize($settings['bride_name'] ?? '') ?>" required data-couple-preview>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="groom_name">Prénom du marié</label>
                        <input id="groom_name" name="groom_name" class="form-control form-control-lg" value="<?= sanitize($settings['groom_name'] ?? '') ?>" required data-couple-preview>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="couple-preview-card">
                    <div class="couple-preview-monogram" id="couplePreviewMonogram"><?= sanitize(coupleInitials($settings)) ?></div>
                    <p class="couple-preview-names" id="couplePreviewNames">
                        <span id="couplePreviewBride"><?= sanitize($settings['bride_name'] ?? '') ?></span>
                        <span class="preview-amp">&amp;</span>
                        <span id="couplePreviewGroom"><?= sanitize($settings['groom_name'] ?? '') ?></span>
                    </p>
                    <p class="couple-preview-note">Aperçu tel qu'affiché sur le faire-part</p>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <h3><i class="bi bi-hourglass-split me-2"></i>Compte à rebours</h3>
        <p class="text-muted mb-4">Configure la section « Le grand jour approche » affichée sur la page d'accueil. La date et l'heure servent aussi aux détails du mariage.</p>
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="countdown_enabled" class="form-check-input" id="countdownEnabled" <?= isCountdownEnabled($settings) ? 'checked' : '' ?> data-countdown-preview>
                            <label class="form-check-label" for="countdownEnabled">Afficher le compte à rebours sur le site</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="countdown_title">Titre de la section</label>
                        <input id="countdown_title" name="countdown_title" class="form-control" value="<?= sanitize($settings['countdown_title'] ?? countdownTitle($settings)) ?>" placeholder="Le grand jour approche" data-countdown-preview>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="wedding_date">Date du mariage</label>
                        <input type="date" id="wedding_date" name="wedding_date" class="form-control" value="<?= sanitize($settings['wedding_date'] ?? '') ?>" required data-countdown-preview>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="start_time">Heure du décompte</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" value="<?= sanitize($settings['start_time'] ?? '14:00') ?>" data-countdown-preview>
                        <small class="text-muted">Le compte à rebours se termine à cette heure.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="countdown_message_past">Message quand le jour est arrivé</label>
                        <input id="countdown_message_past" name="countdown_message_past" class="form-control" value="<?= sanitize($settings['countdown_message_past'] ?? countdownPastMessage($settings)) ?>" data-countdown-preview>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="countdown-preview-card">
                    <p class="countdown-preview-label" id="countdownPreviewTitle"><?= sanitize(countdownTitle($settings)) ?></p>
                    <div class="countdown-preview-grid">
                        <div><strong id="countdownPreviewDays">—</strong><span>Jours</span></div>
                        <div class="countdown-preview-sep">:</div>
                        <div><strong id="countdownPreviewHours">—</strong><span>Heures</span></div>
                        <div class="countdown-preview-sep">:</div>
                        <div><strong id="countdownPreviewMinutes">—</strong><span>Minutes</span></div>
                        <div class="countdown-preview-sep">:</div>
                        <div><strong id="countdownPreviewSeconds">—</strong><span>Secondes</span></div>
                    </div>
                    <p class="countdown-preview-date" id="countdownPreviewDate"><?= formatFrenchDate($settings['wedding_date'] ?? '') ?></p>
                    <p class="couple-preview-note">Aperçu du compte à rebours</p>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        <h3><i class="bi bi-envelope-heart me-2"></i>Carte d'invitation 3D</h3>
        <p class="text-muted mb-4">Photo de fond affichée sur la couverture de la carte (écran d'ouverture). Sur Vercel, utilisez une URL d'image (Cloudinary, ImgBB…).</p>
        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <?php if (!isServerless()): ?>
                <div class="mb-3">
                    <label class="form-label">Changer la photo</label>
                    <input type="file" name="invitation_card_image" class="form-control" accept="image/*">
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">URL de l'image</label>
                    <input type="url" name="invitation_card_image_url" class="form-control" placeholder="https://..." value="<?= str_starts_with($settings['invitation_card_image'] ?? '', 'http') ? sanitize($settings['invitation_card_image']) : '' ?>">
                </div>
                <?php if (!empty($settings['invitation_card_image'])): ?>
                <div class="form-check">
                    <input type="checkbox" name="remove_invitation_card_image" class="form-check-input" id="removeInvitationCardImage" value="1">
                    <label class="form-check-label text-danger" for="removeInvitationCardImage">Supprimer la photo (fond par défaut)</label>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <?php if ($previewCard = invitationCardImageUrl($settings)): ?>
                <div class="invite-card-preview">
                    <img src="<?= sanitize($previewCard) ?>" alt="Aperçu carte d'invitation">
                    <span class="invite-card-preview-label">Aperçu couverture</span>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">Aucune photo — fond sombre par défaut.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="admin-card">
                <h3>Photo principale</h3>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Photo principale (Hero)</label>
                        <?php if (!isServerless()): ?>
                        <input type="file" name="hero_image" class="form-control mb-2" accept="image/*">
                        <?php endif; ?>
                        <input type="url" name="hero_image_url" class="form-control" placeholder="https://... (URL de l'image)" value="<?= str_starts_with($settings['hero_image'] ?? '', 'http') ? sanitize($settings['hero_image']) : '' ?>">
                    </div>
                    <?php if (!empty($settings['hero_image'])): ?>
                    <div class="col-12"><img src="<?= sanitize(mediaUrl($settings['hero_image'])) ?>" style="max-height:120px;border-radius:4px" alt="Aperçu hero"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card">
                <h3>Lieux du mariage</h3>
                <p class="text-muted small mb-3">La date et l'heure sont configurées dans la section « Compte à rebours » ci-dessus.</p>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Heure de fin</label><input type="time" name="end_time" class="form-control" value="<?= sanitize($settings['end_time'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label">Lieu cérémonie civile</label><input name="civil_venue" class="form-control" value="<?= sanitize($settings['civil_venue'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label">Lieu cérémonie religieuse</label><input name="religious_venue" class="form-control" value="<?= sanitize($settings['religious_venue'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label">Lieu réception</label><input name="reception_venue" class="form-control" value="<?= sanitize($settings['reception_venue'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">GPS Latitude</label><input type="number" step="any" name="gps_lat" class="form-control" value="<?= sanitize((string)($settings['gps_lat'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">GPS Longitude</label><input type="number" step="any" name="gps_lng" class="form-control" value="<?= sanitize((string)($settings['gps_lng'] ?? '')) ?>"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card">
                <h3>Textes d'accueil</h3>
                <div class="mb-3"><label class="form-label">Titre de bienvenue</label><input name="welcome_title" class="form-control" value="<?= sanitize($settings['welcome_title'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Message de bienvenue</label><textarea name="welcome_message" class="form-control" rows="2" placeholder="Premier message d'accueil..."><?= sanitize($settings['welcome_message'] ?? '') ?></textarea></div>
                <div class="mb-3">
                    <label class="form-label">Texte VVIP &amp; invitation</label>
                    <textarea name="invitation_text" class="form-control" rows="4" placeholder="Ligne VVIP (1er paragraphe)&#10;&#10;Invitation finale (2e paragraphe)"><?= sanitize($settings['invitation_text'] ?? '') ?></textarea>
                    <small class="text-muted">Séparez le message VVIP et l'invitation finale par une ligne vide.</small>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card">
                <h3>Contact &amp; Options</h3>
                <div class="mb-3"><label class="form-label">Email de contact</label><input type="email" name="contact_email" class="form-control" value="<?= sanitize($settings['contact_email'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label">Téléphone</label><input name="contact_phone" class="form-control" value="<?= sanitize($settings['contact_phone'] ?? '') ?>"></div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="wedding_passed" class="form-check-input" id="weddingPassed" <?= ($settings['wedding_passed'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="weddingPassed">Le mariage a eu lieu</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="album_enabled" class="form-check-input" id="albumEnabled" <?= ($settings['album_enabled'] ?? false) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="albumEnabled">Activer l'album du mariage</label>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-lg me-2"></i>Enregistrer les paramètres</button>
    </div>
</form>
<?php
$content = ob_get_clean();
adminLayout('Paramètres du mariage', $content, 'settings');
