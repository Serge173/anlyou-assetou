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
    ];

    $heroPath = resolveMediaPath(
        handleMediaUpload($_FILES['hero_image'] ?? [], 'hero'),
        $_POST['hero_image_url'] ?? ''
    );
    if ($heroPath) {
        $data['hero_image'] = $heroPath;
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

<form method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="admin-card">
                <h3>Informations du couple</h3>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Prénom mariée</label><input name="bride_name" class="form-control" value="<?= sanitize($settings['bride_name'] ?? '') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Prénom marié</label><input name="groom_name" class="form-control" value="<?= sanitize($settings['groom_name'] ?? '') ?>" required></div>
                    <div class="col-12">
                        <label class="form-label">Photo principale (Hero)</label>
                        <?php if (!isServerless()): ?>
                        <input type="file" name="hero_image" class="form-control mb-2" accept="image/*">
                        <?php endif; ?>
                        <input type="url" name="hero_image_url" class="form-control" placeholder="https://... (URL de l'image)" value="<?= str_starts_with($settings['hero_image'] ?? '', 'http') ? sanitize($settings['hero_image']) : '' ?>">
                    </div>
                    <?php if (!empty($settings['hero_image'])): ?>
                    <div class="col-12"><img src="<?= sanitize(mediaUrl($settings['hero_image'])) ?>" style="max-height:120px;border-radius:4px"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card">
                <h3>Date &amp; Lieux</h3>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Date du mariage</label><input type="date" name="wedding_date" class="form-control" value="<?= sanitize($settings['wedding_date'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Heure début</label><input type="time" name="start_time" class="form-control" value="<?= sanitize($settings['start_time'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label">Heure fin</label><input type="time" name="end_time" class="form-control" value="<?= sanitize($settings['end_time'] ?? '') ?>"></div>
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
