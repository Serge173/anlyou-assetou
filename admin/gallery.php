<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$pdo = initAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_story_section') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            createGalleryAlbum($pdo, $name, trim($_POST['description'] ?? '') ?: null);
        }
        redirect('/admin/gallery.php?tab=sections&saved=1');
    }

    if ($action === 'update_story_section') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id && $name !== '') {
            updateGalleryAlbum($pdo, $id, $name, trim($_POST['description'] ?? '') ?: null);
        }
        redirect('/admin/gallery.php?tab=sections&saved=1');
    }

    if ($action === 'delete_story_section') {
        deleteGalleryAlbum($pdo, (int) ($_POST['id'] ?? 0));
        redirect('/admin/gallery.php?tab=sections&deleted=1');
    }

    if ($action === 'add_wedding_section') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            createWeddingSection($pdo, $name, trim($_POST['description'] ?? '') ?: null);
        }
        redirect('/admin/gallery.php?tab=wedding-sections&saved=1');
    }

    if ($action === 'update_wedding_section') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id && $name !== '') {
            updateWeddingSection($pdo, $id, $name, trim($_POST['description'] ?? '') ?: null);
        }
        redirect('/admin/gallery.php?tab=wedding-sections&saved=1');
    }

    if ($action === 'delete_wedding_section') {
        deleteWeddingSection($pdo, (int) ($_POST['id'] ?? 0));
        redirect('/admin/gallery.php?tab=wedding-sections&deleted=1');
    }

    if ($action === 'add_photo') {
        $path = resolveMediaPath(
            handleMediaUpload($_FILES['photo'] ?? [], 'photo'),
            $_POST['photo_url'] ?? ''
        );
        if ($path) {
            addGalleryPhoto($pdo, [
                'album_id' => (int) $_POST['album_id'],
                'title' => $_POST['title'] ?? '',
                'caption' => $_POST['caption'] ?? '',
                'file_path' => $path,
            ]);
        }
        redirect('/admin/gallery.php?tab=photos&added=1');
    }

    if ($action === 'delete_photo') {
        deleteGalleryPhoto($pdo, (int) $_POST['id']);
        redirect('/admin/gallery.php?tab=photos&deleted=1');
    }

    if ($action === 'add_wedding') {
        $path = resolveMediaPath(
            handleMediaUpload($_FILES['media'] ?? [], 'wedding'),
            $_POST['media_url'] ?? ''
        );
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mediaType = in_array($ext, ['mp4', 'webm', 'mov']) ? 'video' : 'image';
            addWeddingAlbumItem($pdo, [
                'title' => $_POST['title'] ?? 'Photo',
                'caption' => $_POST['caption'] ?? '',
                'file_path' => $path,
                'media_type' => $mediaType,
                'section_id' => (int) ($_POST['section_id'] ?? 0),
            ]);
        }
        redirect('/admin/gallery.php?tab=wedding&added=1');
    }

    if ($action === 'delete_wedding') {
        deleteWeddingAlbumItem($pdo, (int) $_POST['id']);
        redirect('/admin/gallery.php?tab=wedding&deleted=1');
    }
}

$tab = $_GET['tab'] ?? 'sections';
$albums = getGalleryAlbums($pdo);
$photos = getGalleryPhotos($pdo);
$weddingSections = getWeddingSections($pdo);
$weddingAlbum = getWeddingAlbum($pdo);
$isServerless = isServerless();

ob_start();
?>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Section enregistrée.</div><?php endif; ?>
<?php if (isset($_GET['added'])): ?><div class="alert alert-success">Élément ajouté.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Élément supprimé.</div><?php endif; ?>

<?php if ($isServerless): ?>
<div class="alert alert-info">
    <i class="bi bi-cloud me-2"></i>
    Sur Vercel, utilisez une <strong>URL d'image</strong> (Cloudinary, Imgur, Google Drive public…) car le stockage local n'est pas persistant.
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab === 'sections' ? 'active' : '' ?>" href="?tab=sections">Sections — Notre Histoire</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'photos' ? 'active' : '' ?>" href="?tab=photos">Photos — Notre Histoire</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'wedding-sections' ? 'active' : '' ?>" href="?tab=wedding-sections">Sections — Album Mariage</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'wedding' ? 'active' : '' ?>" href="?tab=wedding">Médias — Album Mariage</a></li>
</ul>

<?php if ($tab === 'sections'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="admin-card">
            <h3>Ajouter une section</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_story_section">
                <div class="mb-3">
                    <label class="form-label">Nom de la section *</label>
                    <input name="name" class="form-control" placeholder="Ex: Première rencontre" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description <small class="text-muted">(facultatif)</small></label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Courte description de cette section..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Ajouter</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="admin-card">
            <h3>Sections (<?= count($albums) ?>)</h3>
            <?php if (empty($albums)): ?>
            <p class="text-muted">Aucune section. Créez votre première section photo.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nom</th><th>Description</th><th>Photos</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($albums as $a):
                        $photoCount = count(array_filter($photos, fn($p) => (int)$p['album_id'] === (int)$a['id']));
                    ?>
                    <tr>
                        <td><strong><?= sanitize($a['name']) ?></strong></td>
                        <td class="text-muted"><?= sanitize($a['description'] ?? '—') ?></td>
                        <td><span class="badge bg-light text-dark"><?= $photoCount ?></span></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStory<?= $a['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette section et toutes ses photos ?')">
                                <input type="hidden" name="action" value="delete_story_section">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <div class="modal fade" id="editStory<?= $a['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <input type="hidden" name="action" value="update_story_section">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <div class="modal-header"><h5 class="modal-title">Modifier la section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-3"><label class="form-label">Nom</label><input name="name" class="form-control" value="<?= sanitize($a['name']) ?>" required></div>
                                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= sanitize($a['description'] ?? '') ?></textarea></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php elseif ($tab === 'photos'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="admin-card">
            <h3>Ajouter une photo</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_photo">
                <div class="mb-3">
                    <label class="form-label">Section *</label>
                    <select name="album_id" class="form-select" required>
                        <?php if (empty($albums)): ?>
                        <option value="">Créez d'abord une section</option>
                        <?php else: ?>
                        <?php foreach ($albums as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= sanitize($a['name']) ?></option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Titre</label><input name="title" class="form-control"></div>
                <?php if (!$isServerless): ?>
                <div class="mb-3"><label class="form-label">Fichier</label><input type="file" name="photo" class="form-control" accept="image/*"></div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label"><?= $isServerless ? 'URL de l\'image *' : 'Ou URL de l\'image' ?></label>
                    <input type="url" name="photo_url" class="form-control" placeholder="https://..." <?= $isServerless ? 'required' : '' ?>>
                </div>
                <button type="submit" class="btn btn-primary w-100" <?= empty($albums) ? 'disabled' : '' ?>>Ajouter</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="admin-card">
            <h3>Photos (<?= count($photos) ?>)</h3>
            <div class="row g-3">
                <?php foreach ($photos as $p): ?>
                <div class="col-md-4">
                    <div class="gallery-admin-item">
                        <img src="<?= sanitize(mediaUrl($p['file_path'])) ?>" alt="">
                        <div class="gallery-admin-info">
                            <small><?= sanitize($p['album_name']) ?></small>
                            <strong><?= sanitize($p['title'] ?? '') ?></strong>
                            <form method="POST" onsubmit="return confirm('Supprimer ?')">
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-danger mt-1"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'wedding-sections'): ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="admin-card">
            <h3>Ajouter une section</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_wedding_section">
                <div class="mb-3">
                    <label class="form-label">Nom de la section *</label>
                    <input name="name" class="form-control" placeholder="Ex: Cérémonie" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description <small class="text-muted">(facultatif)</small></label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Ajouter</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="admin-card">
            <h3>Sections album mariage (<?= count($weddingSections) ?>)</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nom</th><th>Description</th><th>Médias</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($weddingSections as $s):
                        $mediaCount = count(array_filter($weddingAlbum, fn($w) => (int)($w['section_id'] ?? 0) === (int)$s['id']));
                    ?>
                    <tr>
                        <td><strong><?= sanitize($s['name']) ?></strong></td>
                        <td class="text-muted"><?= sanitize($s['description'] ?? '—') ?></td>
                        <td><span class="badge bg-light text-dark"><?= $mediaCount ?></span></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editWedding<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette section ?')">
                                <input type="hidden" name="action" value="delete_wedding_section">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <div class="modal fade" id="editWedding<?= $s['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <input type="hidden" name="action" value="update_wedding_section">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <div class="modal-header"><h5 class="modal-title">Modifier la section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="mb-3"><label class="form-label">Nom</label><input name="name" class="form-control" value="<?= sanitize($s['name']) ?>" required></div>
                                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"><?= sanitize($s['description'] ?? '') ?></textarea></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="admin-card">
            <h3>Ajouter un média</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_wedding">
                <div class="mb-3">
                    <label class="form-label">Section *</label>
                    <select name="section_id" class="form-select" required>
                        <?php foreach ($weddingSections as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Titre *</label><input name="title" class="form-control" required></div>
                <?php if (!$isServerless): ?>
                <div class="mb-3"><label class="form-label">Fichier</label><input type="file" name="media" class="form-control" accept="image/*,video/*"></div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label"><?= $isServerless ? 'URL du média *' : 'Ou URL du média' ?></label>
                    <input type="url" name="media_url" class="form-control" placeholder="https://..." <?= $isServerless ? 'required' : '' ?>>
                </div>
                <button type="submit" class="btn btn-primary w-100" <?= empty($weddingSections) ? 'disabled' : '' ?>>Ajouter</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="admin-card">
            <h3>Médias (<?= count($weddingAlbum) ?>)</h3>
            <div class="row g-3">
                <?php foreach ($weddingAlbum as $w): ?>
                <div class="col-md-4">
                    <div class="gallery-admin-item">
                        <?php if ($w['media_type'] === 'video'): ?>
                        <video src="<?= sanitize(mediaUrl($w['file_path'])) ?>" style="width:100%;height:120px;object-fit:cover"></video>
                        <?php else: ?>
                        <img src="<?= sanitize(mediaUrl($w['file_path'])) ?>" alt="">
                        <?php endif; ?>
                        <div class="gallery-admin-info">
                            <strong><?= sanitize($w['title']) ?></strong>
                            <form method="POST" onsubmit="return confirm('Supprimer ?')">
                                <input type="hidden" name="action" value="delete_wedding">
                                <input type="hidden" name="id" value="<?= $w['id'] ?>">
                                <button class="btn btn-sm btn-danger mt-1"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
adminLayout('Gestion de la galerie', $content, 'gallery');
