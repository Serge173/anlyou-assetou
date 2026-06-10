<?php

declare(strict_types=1);

function getSettings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM settings WHERE id = 1');
    $settings = $stmt->fetch();
    if (!$settings) {
        return [];
    }
    $settings['wedding_passed'] = (bool) ($settings['wedding_passed'] ?? false);
    $settings['album_enabled'] = (bool) ($settings['album_enabled'] ?? false);
    $settings['countdown_enabled'] = (bool) ($settings['countdown_enabled'] ?? true);
    return $settings;
}

function updateSettings(PDO $pdo, array $data): bool
{
    $fields = [
        'bride_name', 'groom_name', 'wedding_date', 'start_time', 'end_time',
        'civil_venue', 'religious_venue', 'reception_venue', 'gps_lat', 'gps_lng',
        'welcome_title', 'welcome_message', 'invitation_text', 'hero_image',
        'contact_email', 'contact_phone', 'wedding_passed', 'album_enabled',
        'countdown_title', 'countdown_message_past', 'countdown_enabled',
    ];
    $sets = [];
    $values = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $data)) {
            $sets[] = "{$field} = ?";
            $values[] = $data[$field];
        }
    }
    if (empty($sets)) {
        return false;
    }
    $now = isPostgres($pdo) ? date('c') : date('Y-m-d H:i:s');
    $sets[] = 'updated_at = ?';
    $values[] = $now;
    $values[] = 1;
    $sql = 'UPDATE settings SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

function getDashboardStats(PDO $pdo): array
{
    $confirmations = (int) $pdo->query('SELECT COUNT(*) FROM rsvp_confirmations')->fetchColumn();
    $declines = (int) $pdo->query('SELECT COUNT(*) FROM rsvp_declines')->fetchColumn();
    $guestbook = (int) $pdo->query('SELECT COUNT(*) FROM guestbook')->fetchColumn();
    $wishes = (int) $pdo->query("SELECT COUNT(*) FROM guestbook WHERE status = 'approved'")->fetchColumn();
    $companions = (int) $pdo->query('SELECT COALESCE(SUM(companions), 0) FROM rsvp_confirmations')->fetchColumn();
    return [
        'total_responses' => $confirmations + $declines,
        'confirmations' => $confirmations,
        'declines' => $declines,
        'messages' => $confirmations + $declines,
        'wishes' => $wishes,
        'guestbook_total' => $guestbook,
        'total_guests' => $confirmations + $companions,
    ];
}

function getConfirmations(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM rsvp_confirmations ORDER BY created_at DESC')->fetchAll();
}

function getDeclines(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM rsvp_declines ORDER BY created_at DESC')->fetchAll();
}

function getGuestbookEntries(PDO $pdo, ?string $status = null): array
{
    if ($status) {
        $stmt = $pdo->prepare('SELECT * FROM guestbook WHERE status = ? ORDER BY created_at DESC');
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT * FROM guestbook ORDER BY created_at DESC')->fetchAll();
}

function getPublicGuestbook(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT first_name, last_name, message, created_at FROM guestbook WHERE status = 'approved' ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function getGalleryAlbums(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM gallery_albums ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function getGalleryAlbumsWithPhotos(PDO $pdo): array
{
    $albums = getGalleryAlbums($pdo);
    foreach ($albums as &$album) {
        $album['photos'] = getGalleryPhotos($pdo, (int) $album['id']);
    }
    return $albums;
}

function getGalleryAlbum(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM gallery_albums WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function createGalleryAlbum(PDO $pdo, string $name, ?string $description = null): int
{
    $slug = slugify($name);
    $existing = $pdo->prepare('SELECT COUNT(*) FROM gallery_albums WHERE slug = ?');
    $existing->execute([$slug]);
    if ((int) $existing->fetchColumn() > 0) {
        $slug .= '-' . substr(uniqid(), -4);
    }
    $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_albums')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO gallery_albums (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $slug, $description ?: null, $maxOrder + 1]);
    return lastInsertId($pdo, 'gallery_albums');
}

function updateGalleryAlbum(PDO $pdo, int $id, string $name, ?string $description = null): bool
{
    $stmt = $pdo->prepare('UPDATE gallery_albums SET name = ?, description = ? WHERE id = ?');
    return $stmt->execute([$name, $description ?: null, $id]);
}

function deleteGalleryAlbum(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM gallery_albums WHERE id = ?');
    return $stmt->execute([$id]);
}

function getWeddingSections(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM wedding_sections ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function getWeddingSectionsWithMedia(PDO $pdo): array
{
    $sections = getWeddingSections($pdo);
    foreach ($sections as &$section) {
        $stmt = $pdo->prepare('SELECT * FROM wedding_album WHERE section_id = ? ORDER BY sort_order ASC, created_at DESC');
        $stmt->execute([(int) $section['id']]);
        $section['media'] = $stmt->fetchAll();
    }
    return $sections;
}

function createWeddingSection(PDO $pdo, string $name, ?string $description = null): int
{
    $slug = slugify($name);
    $existing = $pdo->prepare('SELECT COUNT(*) FROM wedding_sections WHERE slug = ?');
    $existing->execute([$slug]);
    if ((int) $existing->fetchColumn() > 0) {
        $slug .= '-' . substr(uniqid(), -4);
    }
    $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM wedding_sections')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO wedding_sections (name, slug, description, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $slug, $description ?: null, $maxOrder + 1]);
    return lastInsertId($pdo, 'wedding_sections');
}

function updateWeddingSection(PDO $pdo, int $id, string $name, ?string $description = null): bool
{
    $stmt = $pdo->prepare('UPDATE wedding_sections SET name = ?, description = ? WHERE id = ?');
    return $stmt->execute([$name, $description ?: null, $id]);
}

function deleteWeddingSection(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM wedding_sections WHERE id = ?');
    return $stmt->execute([$id]);
}

function getGalleryPhotos(PDO $pdo, ?int $albumId = null): array
{
    if ($albumId) {
        $stmt = $pdo->prepare('SELECT p.*, a.name as album_name FROM gallery_photos p JOIN gallery_albums a ON p.album_id = a.id WHERE p.album_id = ? ORDER BY p.sort_order ASC');
        $stmt->execute([$albumId]);
        return $stmt->fetchAll();
    }
    return $pdo->query('SELECT p.*, a.name as album_name, a.slug as album_slug FROM gallery_photos p JOIN gallery_albums a ON p.album_id = a.id ORDER BY a.sort_order, p.sort_order')->fetchAll();
}

function getWeddingAlbum(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM wedding_album ORDER BY sort_order ASC, created_at DESC')->fetchAll();
}

function createConfirmation(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO rsvp_confirmations (first_name, last_name, phone, email, relationship, companions, message) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['phone'] ?? null,
        $data['email'] ?? null,
        $data['relationship'],
        (int) ($data['companions'] ?? 0),
        $data['message'] ?? null,
    ]);
    return lastInsertId($pdo, 'rsvp_confirmations');
}

function createDecline(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO rsvp_declines (first_name, last_name, reason, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['reason'],
        $data['message'] ?? null,
    ]);
    return lastInsertId($pdo, 'rsvp_declines');
}

function createGuestbookEntry(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO guestbook (first_name, last_name, message, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['message'],
        'pending',
    ]);
    return lastInsertId($pdo, 'guestbook');
}

function updateGuestbookStatus(PDO $pdo, int $id, string $status): bool
{
    $stmt = $pdo->prepare('UPDATE guestbook SET status = ?, updated_at = ? WHERE id = ?');
    $now = isPostgres($pdo) ? date('c') : date('Y-m-d H:i:s');
    return $stmt->execute([$status, $now, $id]);
}

function deleteConfirmation(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM rsvp_confirmations WHERE id = ?');
    return $stmt->execute([$id]);
}

function deleteDecline(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM rsvp_declines WHERE id = ?');
    return $stmt->execute([$id]);
}

function deleteGuestbook(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM guestbook WHERE id = ?');
    return $stmt->execute([$id]);
}

function updateConfirmation(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare('UPDATE rsvp_confirmations SET first_name=?, last_name=?, phone=?, email=?, relationship=?, companions=?, message=?, updated_at=? WHERE id=?');
    $now = isPostgres($pdo) ? date('c') : date('Y-m-d H:i:s');
    return $stmt->execute([
        $data['first_name'], $data['last_name'], $data['phone'] ?? null,
        $data['email'] ?? null, $data['relationship'], (int) ($data['companions'] ?? 0),
        $data['message'] ?? null, $now, $id,
    ]);
}

function addGalleryPhoto(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO gallery_photos (album_id, title, caption, file_path, sort_order) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['album_id'], $data['title'] ?? null, $data['caption'] ?? null,
        $data['file_path'], (int) ($data['sort_order'] ?? 0),
    ]);
    return lastInsertId($pdo, 'gallery_photos');
}

function deleteGalleryPhoto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM gallery_photos WHERE id = ?');
    return $stmt->execute([$id]);
}

function addWeddingAlbumItem(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO wedding_album (title, caption, file_path, media_type, section_id, sort_order, allow_download) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['title'], $data['caption'] ?? null, $data['file_path'],
        $data['media_type'] ?? 'image', (int) ($data['section_id'] ?? 0),
        (int) ($data['sort_order'] ?? 0), (int) ($data['allow_download'] ?? 1),
    ]);
    return lastInsertId($pdo, 'wedding_album');
}

function deleteWeddingAlbumItem(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM wedding_album WHERE id = ?');
    return $stmt->execute([$id]);
}
