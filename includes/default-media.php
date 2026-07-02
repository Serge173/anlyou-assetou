<?php

declare(strict_types=1);

function defaultCouplePhotoPath(): string
{
    return 'assets/images/carte-invitation-assetou.png';
}

function defaultInvitationCoverPath(): string
{
    return 'assets/images/couple-couverture.png';
}

function defaultStoryGalleryPhotos(): array
{
    return [
        ['title' => 'Notre premier regard', 'path' => 'assets/images/gallery/assetou-1.png'],
        ['title' => 'La demande', 'path' => 'assets/images/gallery/assetou-2.png'],
        ['title' => 'Aventure à deux', 'path' => 'assets/images/gallery/assetou-3.png'],
        ['title' => 'Anniversaire surprise', 'path' => 'assets/images/gallery/assetou-4.png'],
        ['title' => 'Ensemble pour toujours', 'path' => 'assets/images/gallery/assetou-5.png'],
        ['title' => 'Portrait du couple', 'path' => 'assets/images/gallery/assetou-5.png'],
    ];
}

function mergeStoryGalleryIntoSingleBlock(PDO $pdo): void
{
    if (!tableExists($pdo, 'gallery_albums') || !tableExists($pdo, 'gallery_photos')) {
        return;
    }

    $albumCount = (int) $pdo->query('SELECT COUNT(*) FROM gallery_albums')->fetchColumn();
    if ($albumCount <= 1) {
        $albumId = (int) $pdo->query('SELECT id FROM gallery_albums ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn();
        if ($albumId > 0) {
            $stmt = $pdo->prepare(
                'UPDATE gallery_albums SET name = ?, slug = ?, description = ?, sort_order = 1 WHERE id = ?'
            );
            $stmt->execute([
                'Notre histoire',
                'notre-histoire',
                'Les moments qui ont écrit notre belle histoire',
                $albumId,
            ]);
        }

        return;
    }

    $existing = $pdo->query("SELECT id FROM gallery_albums WHERE slug = 'notre-histoire' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $targetId = (int) $existing['id'];
    } else {
        if (isPostgres($pdo)) {
            $pdo->prepare(
                'INSERT INTO gallery_albums (name, slug, description, sort_order) VALUES (?, ?, ?, ?)'
            )->execute([
                'Notre histoire',
                'notre-histoire',
                'Les moments qui ont écrit notre belle histoire',
                1,
            ]);
            $targetId = (int) $pdo->query("SELECT id FROM gallery_albums WHERE slug = 'notre-histoire' LIMIT 1")->fetchColumn();
        } else {
            $pdo->prepare(
                'INSERT INTO gallery_albums (name, slug, description, sort_order) VALUES (?, ?, ?, ?)'
            )->execute([
                'Notre histoire',
                'notre-histoire',
                'Les moments qui ont écrit notre belle histoire',
                1,
            ]);
            $targetId = (int) $pdo->lastInsertId();
        }
    }

    $photos = $pdo->query(
        'SELECT p.id
         FROM gallery_photos p
         JOIN gallery_albums a ON p.album_id = a.id
         ORDER BY a.sort_order ASC, a.id ASC, p.sort_order ASC, p.id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    $update = $pdo->prepare('UPDATE gallery_photos SET album_id = ?, sort_order = ? WHERE id = ?');
    $sort = 1;
    foreach ($photos as $photo) {
        $update->execute([$targetId, $sort++, (int) $photo['id']]);
    }

    $pdo->prepare('DELETE FROM gallery_albums WHERE id != ?')->execute([$targetId]);
    $pdo->prepare(
        'UPDATE gallery_albums SET name = ?, slug = ?, description = ?, sort_order = 1 WHERE id = ?'
    )->execute([
        'Notre histoire',
        'notre-histoire',
        'Les moments qui ont écrit notre belle histoire',
        $targetId,
    ]);
}

/** @return list<string> Unsplash IDs retirés du CDN — à remplacer en prod */
function brokenStoryGalleryUnsplashIds(): array
{
    return [
        'photo-1522673607200-84343a3e1e1',
        'photo-1520854221256-17451cc791d2',
        'photo-1606216794074-735e91aa2a92',
    ];
}

function defaultWeddingAlbumPhotos(): array
{
    return [
        ['section_id' => 1, 'title' => 'La cérémonie', 'url' => 'https://images.unsplash.com/photo-1465492759716-5663f7965cc6?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 1, 'title' => 'L\'échange des vœux', 'url' => 'https://images.unsplash.com/photo-1519225422580-d49467f2272e?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 2, 'title' => 'La réception', 'url' => 'https://images.unsplash.com/photo-1470229722913-7c0bdf2c344e?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 2, 'title' => 'Première danse', 'url' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 3, 'title' => 'Avec nos proches', 'url' => 'https://images.unsplash.com/photo-1523438885200-893aeb409055?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 3, 'title' => 'Joie partagée', 'url' => 'https://images.unsplash.com/photo-1537633552985-df8429e8048b?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 4, 'title' => 'Souvenirs du jour J', 'url' => 'https://images.unsplash.com/photo-1469371670803-135ccf25df93?auto=format&fit=crop&w=900&q=80'],
        ['section_id' => 4, 'title' => 'Moments magiques', 'url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=900&q=80'],
    ];
}

function upgradeDefaultMedia(PDO $pdo): void
{
    if (!tableExists($pdo, 'settings')) {
        return;
    }

    $couplePhoto = defaultCouplePhotoPath();
    $coverPhoto = defaultInvitationCoverPath();

    $syncHero = $pdo->prepare(
        'UPDATE settings SET hero_image = invitation_card_image
         WHERE id = 1 AND invitation_card_image IS NOT NULL AND TRIM(invitation_card_image) <> \'\''
    );
    $syncHero->execute();

    $stmt = $pdo->prepare(
        'UPDATE settings SET hero_image = ? WHERE id = 1 AND (
            hero_image IS NULL OR hero_image = \'\' OR hero_image LIKE ? OR hero_image LIKE ?
        )'
    );
    $stmt->execute([$couplePhoto, '%hero.svg%', '%hero.jpg%']);

    $stmt = $pdo->prepare(
        'UPDATE settings SET invitation_card_image = ? WHERE id = 1 AND (
            invitation_card_image IS NULL OR invitation_card_image = \'\'
            OR invitation_card_image LIKE ? OR invitation_card_image = ?
        )'
    );
    $stmt->execute([$coverPhoto, '%invitation-card-bg%', $couplePhoto]);

    upgradeAssetouGalleryPhotos($pdo);

    if (!tableExists($pdo, 'wedding_album') || !tableExists($pdo, 'wedding_sections')) {
        return;
    }

    $albumCount = (int) $pdo->query('SELECT COUNT(*) FROM wedding_album')->fetchColumn();
    if ($albumCount > 0) {
        return;
    }

    $sectionCount = (int) $pdo->query('SELECT COUNT(*) FROM wedding_sections')->fetchColumn();
    if ($sectionCount === 0) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO wedding_album (title, caption, file_path, media_type, section_id, sort_order, allow_download)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $sort = 1;
    $lastSection = 0;
    foreach (defaultWeddingAlbumPhotos() as $item) {
        if ($lastSection !== $item['section_id']) {
            $sort = 1;
            $lastSection = $item['section_id'];
        }
        $allowDownload = isPostgres($pdo) ? true : 1;
        $insert->execute([
            $item['title'],
            '',
            $item['url'],
            'image',
            $item['section_id'],
            $sort++,
            $allowDownload,
        ]);
    }
}

function upgradeAssetouGalleryPhotos(PDO $pdo): void
{
    if (!tableExists($pdo, 'gallery_photos') || !tableExists($pdo, 'gallery_albums')) {
        return;
    }

    mergeStoryGalleryIntoSingleBlock($pdo);

    $albumId = (int) $pdo->query('SELECT id FROM gallery_albums ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn();
    if ($albumId <= 0) {
        return;
    }

    $photoCount = (int) $pdo->query('SELECT COUNT(*) FROM gallery_photos')->fetchColumn();
    $defaults = defaultStoryGalleryPhotos();

    if ($photoCount === 0) {
        $insert = $pdo->prepare(
            'INSERT INTO gallery_photos (album_id, title, file_path, sort_order) VALUES (?, ?, ?, ?)'
        );
        foreach ($defaults as $index => $photo) {
            $insert->execute([$albumId, $photo['title'], $photo['path'], $index + 1]);
        }

        return;
    }

    $needsUpgrade = (int) $pdo->query(
        "SELECT COUNT(*) FROM gallery_photos WHERE file_path NOT LIKE '%assetou-%'"
    )->fetchColumn();

    if ($needsUpgrade === 0) {
        return;
    }

    $existingStmt = $pdo->prepare(
        'SELECT id FROM gallery_photos WHERE album_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $existingStmt->execute([$albumId]);
    $existingIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

    $updateStoryPhoto = $pdo->prepare(
        'UPDATE gallery_photos SET file_path = ?, title = ?, sort_order = ? WHERE id = ?'
    );
    $insertStoryPhoto = $pdo->prepare(
        'INSERT INTO gallery_photos (album_id, title, file_path, sort_order) VALUES (?, ?, ?, ?)'
    );

    foreach ($defaults as $index => $photo) {
        $sortOrder = $index + 1;
        if (isset($existingIds[$index])) {
            $updateStoryPhoto->execute([$photo['path'], $photo['title'], $sortOrder, (int) $existingIds[$index]]);
            continue;
        }

        $insertStoryPhoto->execute([$albumId, $photo['title'], $photo['path'], $sortOrder]);
    }
}
