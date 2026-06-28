<?php

declare(strict_types=1);

function defaultCouplePhotoPath(): string
{
    return 'assets/images/carte-invitation-assetou.png';
}

function defaultStoryGalleryPhotos(): array
{
    return [
        1 => [
            'title' => 'Notre premier regard',
            'path' => 'assets/images/gallery/story-1.jpg',
        ],
        2 => [
            'title' => 'La demande',
            'path' => 'assets/images/gallery/story-2.jpg',
        ],
        3 => [
            'title' => 'Aventure à deux',
            'path' => 'assets/images/gallery/story-3.jpg',
        ],
        4 => [
            'title' => 'Anniversaire surprise',
            'path' => 'assets/images/gallery/story-4.jpg',
        ],
        5 => [
            'title' => 'Ensemble pour toujours',
            'path' => 'assets/images/gallery/story-5.jpg',
        ],
        6 => [
            'title' => 'Portrait du couple',
            'path' => 'assets/images/gallery/story-6.jpg',
        ],
    ];
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
        )'
    );
    $stmt->execute([$couplePhoto]);

    if (!tableExists($pdo, 'gallery_photos')) {
        return;
    }

    $updateStoryPhoto = $pdo->prepare(
        'UPDATE gallery_photos SET file_path = ?, title = ? WHERE album_id = ? AND file_path LIKE ?'
    );
    foreach (defaultStoryGalleryPhotos() as $albumId => $photo) {
        $updateStoryPhoto->execute([$photo['path'], $photo['title'], $albumId, '%.svg']);
    }

    $repairStoryPhoto = $pdo->prepare(
        'UPDATE gallery_photos SET file_path = ?, title = ? WHERE album_id = ? AND file_path LIKE ?'
    );
    foreach (defaultStoryGalleryPhotos() as $albumId => $photo) {
        foreach (brokenStoryGalleryUnsplashIds() as $brokenId) {
            $repairStoryPhoto->execute([$photo['path'], $photo['title'], $albumId, '%' . $brokenId . '%']);
        }
    }

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
