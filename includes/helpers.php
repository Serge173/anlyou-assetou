<?php

declare(strict_types=1);

function projectRoot(): string
{
    return dirname(__DIR__);
}

function publicRoot(): string
{
    return projectRoot() . '/public';
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

function appConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }
    return $config;
}

function siteUrl(): string
{
    return rtrim((string) (appConfig()['url'] ?? ''), '/');
}

function invitationSiteUrl(): string
{
    return siteUrl() . '/';
}

function defaultInvitationShareMessage(array $settings): string
{
    $couple = coupleLabel($settings);
    $date = formatFrenchDate($settings['wedding_date'] ?? '');
    $startTime = trim($settings['start_time'] ?? '');
    $dateLine = $date;
    if ($startTime !== '') {
        $dateLine .= ' à ' . substr($startTime, 0, 5);
    }

    return "Bonjour,\n\n"
        . "C'est avec une immense joie que nous vous invitons à célébrer notre mariage.\n\n"
        . "💍 {$couple}\n"
        . "📅 {$dateLine}\n\n"
        . "Consultez votre invitation et confirmez votre présence via le lien ci-dessous.\n\n"
        . "Votre présence sera notre plus belle joie.\n\n"
        . "Avec toute notre affection,\n"
        . "{$couple}";
}

function invitationShareMessage(array $settings): string
{
    $custom = trim($settings['invitation_share_message'] ?? '');

    return $custom !== '' ? $custom : defaultInvitationShareMessage($settings);
}

function invitationShareText(array $settings): string
{
    $message = invitationShareMessage($settings);
    $link = invitationSiteUrl();

    if (str_contains($message, $link) || str_contains($message, siteUrl())) {
        return $message;
    }

    return $message . "\n\n🔗 " . $link;
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function coupleInitial(?string $name): string
{
    $name = trim($name ?? '');
    if ($name === '') {
        return '';
    }

    return mb_strtoupper(mb_substr($name, 0, 1));
}

function coupleLabel(array $settings, string $separator = ' & '): string
{
    $bride = trim($settings['bride_name'] ?? '');
    $groom = trim($settings['groom_name'] ?? '');

    if ($bride === '' && $groom === '') {
        return '';
    }
    if ($bride === '') {
        return $groom;
    }
    if ($groom === '') {
        return $bride;
    }

    return $groom . $separator . $bride;
}

function coupleInitials(array $settings): string
{
    $bride = coupleInitial($settings['bride_name'] ?? null);
    $groom = coupleInitial($settings['groom_name'] ?? null);

    if ($bride === '' && $groom === '') {
        return '';
    }

    return $groom . '&' . $bride;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function formatFrenchDate(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    $months = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];
    $day = (int) date('j', $timestamp);
    $month = $months[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp);
    return "{$day} {$month} {$year}";
}

function getInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    }
    return array_merge($_GET, $_POST);
}

function relationshipOptions(): array
{
    return ['Ami(e)', 'Frère', 'Sœur', 'Cousin(e)', 'Parent', 'Collègue', 'Voisin(e)', 'Autre'];
}

function declineReasonOptions(): array
{
    return ['Déplacement professionnel', 'Voyage', 'Raisons familiales', 'Raisons médicales', 'Engagement personnel', 'Autre'];
}

function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '');
    return trim($text, '-') ?: 'section-' . uniqid();
}

function mediaUrl(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

function assetUrl(string $relativePath): string
{
    $relativePath = ltrim($relativePath, '/');
    $fullPath = publicRoot() . '/' . $relativePath;

    if (isProductionSite() && is_file($fullPath)) {
        $minPath = preg_replace('/\.(js|css)$/', '.min.$1', $relativePath);
        if ($minPath !== null && is_file(publicRoot() . '/' . $minPath)) {
            return mediaUrl($minPath);
        }
    }

    return mediaUrl($relativePath);
}

function brandName(): string
{
    return 'Invitation de Baby';
}

function brandLogoUrl(): string
{
    return mediaUrl('assets/images/logo-invitationdebaby.png');
}

function weddingDatetimeIso(array $settings): string
{
    $date = trim($settings['wedding_date'] ?? '') ?: '2026-09-15';
    $time = trim($settings['start_time'] ?? '') ?: '14:00';
    if (strlen($time) === 5) {
        $time .= ':00';
    }

    return $date . 'T' . $time;
}

function countdownTitle(array $settings): string
{
    $title = trim($settings['countdown_title'] ?? '');
    return $title !== '' ? $title : 'Le grand jour approche';
}

function countdownPastMessage(array $settings): string
{
    $message = trim($settings['countdown_message_past'] ?? '');
    return $message !== '' ? $message : "C'est aujourd'hui — le grand jour est arrivé !";
}

function isCountdownEnabled(array $settings): bool
{
    return (bool) ($settings['countdown_enabled'] ?? true);
}

function defaultKounkoliVenueText(): string
{
    return "Lavage de la tête de la mariée\nAu domicile de la mariée\n15h00 — 17h30";
}

function kounkoliVenueText(array $settings): string
{
    $text = trim($settings['kounkoli_venue'] ?? '');

    return $text !== '' ? $text : defaultKounkoliVenueText();
}

function weddingProgramDays(array $settings): array
{
    return [
        [
            'heading' => 'Jeudi 30 juillet 2026',
            'events' => [
                [
                    'title' => 'Célébration du mariage',
                    'icon' => 'moon-stars',
                    'text' => trim($settings['religious_venue'] ?? ''),
                ],
                [
                    'title' => 'La réception',
                    'icon' => 'cup-straw',
                    'text' => trim($settings['reception_venue'] ?? ''),
                ],
                [
                    'title' => 'Kounkoli',
                    'icon' => 'balloon-heart',
                    'text' => kounkoliVenueText($settings),
                ],
            ],
        ],
        [
            'heading' => 'Dimanche 2 août 2026',
            'events' => [
                [
                    'title' => 'Danse de réjouissance',
                    'icon' => 'music-note-beamed',
                    'text' => trim($settings['civil_venue'] ?? ''),
                ],
            ],
        ],
    ];
}

function mapCoordinates(array $settings): array
{
    return [
        'lat' => (float) ($settings['gps_lat'] ?? 5.3561),
        'lng' => (float) ($settings['gps_lng'] ?? -4.0127),
    ];
}

function mapEmbedUrl(array $settings, float $delta = 0.015): string
{
    $coords = mapCoordinates($settings);
    $lat = $coords['lat'];
    $lng = $coords['lng'];
    $bbox = implode(',', [$lng - $delta, $lat - $delta, $lng + $delta, $lat + $delta]);

    return 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox)
        . '&layer=mapnik&marker=' . rawurlencode("{$lat},{$lng}");
}

function mapDirectionsUrl(array $settings): string
{
    $coords = mapCoordinates($settings);

    return 'https://www.google.com/maps/search/?api=1&query='
        . rawurlencode($coords['lat'] . ',' . $coords['lng']);
}

function invitationCardImageUrl(array $settings): ?string
{
    $path = trim($settings['invitation_card_image'] ?? '');
    if ($path === '') {
        return null;
    }

    return mediaUrl($path);
}

function coupleVideoPath(): ?string
{
    $path = 'assets/videos/video-assetou.mp4';
    if (!is_file(publicRoot() . '/' . $path)) {
        return null;
    }

    return $path;
}

function coupleVideoUrl(): ?string
{
    $path = coupleVideoPath();

    return $path !== null ? mediaUrl($path) : null;
}

function ambientMusicPresets(): array
{
    return [
        'ambient' => [
            'path' => 'assets/audio/ambient.mp3',
            'label' => 'Piano romantique (mariage)',
        ],
        'wedding-mountain' => [
            'path' => 'assets/audio/wedding-mountain.mp3',
            'label' => 'Mélodie festive de mariage',
        ],
    ];
}

function defaultAmbientMusicPath(): string
{
    return ambientMusicPresets()['ambient']['path'];
}

function ambientMusicPreset(array $settings): string
{
    $preset = trim($settings['ambient_music_preset'] ?? '');
    if ($preset === '003') {
        $preset = 'wedding-mountain';
    }
    if ($preset !== '' && isset(ambientMusicPresets()[$preset])) {
        return $preset;
    }

    $path = trim($settings['ambient_music'] ?? '');
    if ($path !== '') {
        foreach (ambientMusicPresets() as $key => $meta) {
            if ($path === $meta['path']) {
                return $key;
            }
        }

        return 'custom';
    }

    return 'ambient';
}

function ambientMusicPath(array $settings): string
{
    $preset = ambientMusicPreset($settings);
    if ($preset === 'custom') {
        $path = trim($settings['ambient_music'] ?? '');

        return $path !== '' ? $path : defaultAmbientMusicPath();
    }

    return ambientMusicPresets()[$preset]['path'];
}

function ambientMusicLabel(array $settings): string
{
    $preset = ambientMusicPreset($settings);
    if ($preset === 'custom') {
        return basename(ambientMusicPath($settings));
    }

    return ambientMusicPresets()[$preset]['label'];
}

function ambientMusicUrl(array $settings): string
{
    return mediaUrl(ambientMusicPath($settings));
}

function ambientMusicMime(string $path): string
{
    $clean = parse_url($path, PHP_URL_PATH) ?: $path;
    $ext = strtolower(pathinfo($clean, PATHINFO_EXTENSION));

    return match ($ext) {
        'ogg' => 'audio/ogg',
        'wav' => 'audio/wav',
        'm4a', 'aac' => 'audio/mp4',
        default => 'audio/mpeg',
    };
}

function handleMediaUpload(array $file, string $prefix = 'media'): ?string
{
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (isServerless()) {
        return null;
    }

    $uploadDir = publicRoot() . '/assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $prefix . '_' . uniqid() . ($ext ? ".{$ext}" : '');
    $dest = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'assets/uploads/' . $filename;
    }

    return null;
}

function handleAudioUpload(array $file, string $prefix = 'music'): ?string
{
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    if (isServerless()) {
        return null;
    }

    $allowed = ['mp3', 'mpeg', 'ogg', 'wav', 'm4a', 'aac'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }

    $uploadDir = publicRoot() . '/assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = $prefix . '_' . uniqid() . ".{$ext}";
    $dest = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'assets/uploads/' . $filename;
    }

    return null;
}

function resolveMediaPath(?string $uploadedPath, ?string $urlField): ?string
{
    $url = trim($urlField ?? '');
    if ($url !== '') {
        return $url;
    }
    return $uploadedPath;
}
