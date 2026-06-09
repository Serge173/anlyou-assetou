<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function dispatchRequest(): void
{
    $pdo = bootstrapDatabase();

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = rtrim($uri, '/') ?: '/';
    $method = $_SERVER['REQUEST_METHOD'];

    if (str_starts_with($uri, '/api/')) {
        handleApi($pdo, $uri, $method);
        return;
    }

    if (str_starts_with($uri, '/admin')) {
        handleAdmin($uri);
        return;
    }

    renderHomePage($pdo);
}

function handleApi(PDO $pdo, string $uri, string $method): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    $input = getInput();

    switch ($uri) {
        case '/api/settings':
            jsonResponse(['success' => true, 'data' => getSettings($pdo)]);
            break;

        case '/api/rsvp/confirm':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }
            foreach (['first_name', 'last_name', 'relationship'] as $field) {
                if (empty($input[$field])) {
                    jsonResponse(['success' => false, 'message' => "Le champ {$field} est requis"], 422);
                }
            }
            $id = createConfirmation($pdo, $input);
            jsonResponse(['success' => true, 'message' => 'Votre présence a été confirmée avec succès !', 'id' => $id]);
            break;

        case '/api/rsvp/decline':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }
            foreach (['first_name', 'last_name', 'reason'] as $field) {
                if (empty($input[$field])) {
                    jsonResponse(['success' => false, 'message' => "Le champ {$field} est requis"], 422);
                }
            }
            $id = createDecline($pdo, $input);
            jsonResponse(['success' => true, 'message' => 'Votre message a été envoyé avec succès.', 'id' => $id]);
            break;

        case '/api/guestbook':
            if ($method === 'GET') {
                jsonResponse(['success' => true, 'data' => getPublicGuestbook($pdo)]);
            }
            if ($method === 'POST') {
                foreach (['first_name', 'last_name', 'message'] as $field) {
                    if (empty($input[$field])) {
                        jsonResponse(['success' => false, 'message' => "Le champ {$field} est requis"], 422);
                    }
                }
                $id = createGuestbookEntry($pdo, $input);
                jsonResponse(['success' => true, 'message' => 'Votre vœu a été soumis et sera publié après validation.', 'id' => $id]);
            }
            jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            break;

        case '/api/gallery':
            jsonResponse(['success' => true, 'data' => [
                'albums' => getGalleryAlbumsWithPhotos($pdo),
            ]]);
            break;

        case '/api/wedding-album':
            $settings = getSettings($pdo);
            if (!$settings['album_enabled'] && !$settings['wedding_passed']) {
                jsonResponse(['success' => true, 'available' => false, 'data' => []]);
            }
            jsonResponse(['success' => true, 'available' => true, 'data' => getWeddingSectionsWithMedia($pdo)]);
            break;

        default:
            handleAdminApi($pdo, $uri, $method, $input);
    }
}

function handleAdminApi(PDO $pdo, string $uri, string $method, array $input): void
{
    if (preg_match('#^/api/admin/guestbook/(\d+)$#', $uri, $m)) {
        requireAdmin();
        $id = (int) $m[1];
        if ($method === 'PUT') {
            $status = $input['status'] ?? '';
            if (!in_array($status, ['approved', 'hidden', 'pending'], true)) {
                jsonResponse(['success' => false, 'message' => 'Statut invalide'], 422);
            }
            updateGuestbookStatus($pdo, $id, $status);
            jsonResponse(['success' => true, 'message' => 'Statut mis à jour']);
        }
        if ($method === 'DELETE') {
            deleteGuestbook($pdo, $id);
            jsonResponse(['success' => true, 'message' => 'Entrée supprimée']);
        }
    }
    if (preg_match('#^/api/admin/confirmations/(\d+)$#', $uri, $m)) {
        requireAdmin();
        $id = (int) $m[1];
        if ($method === 'PUT') {
            updateConfirmation($pdo, $id, $input);
            jsonResponse(['success' => true, 'message' => 'Confirmation mise à jour']);
        }
        if ($method === 'DELETE') {
            deleteConfirmation($pdo, $id);
            jsonResponse(['success' => true, 'message' => 'Confirmation supprimée']);
        }
    }
    if (preg_match('#^/api/admin/declines/(\d+)$#', $uri, $m)) {
        requireAdmin();
        $id = (int) $m[1];
        if ($method === 'DELETE') {
            deleteDecline($pdo, $id);
            jsonResponse(['success' => true, 'message' => 'Absence supprimée']);
        }
    }
    jsonResponse(['success' => false, 'message' => 'Route non trouvée'], 404);
}

function handleAdmin(string $uri): void
{
    $adminRoot = adminRoot();

    if ($uri === '/admin' || $uri === '/admin/') {
        require $adminRoot . '/index.php';
        return;
    }

    $script = $adminRoot . substr($uri, 6);
    if (is_dir($script)) {
        $script = rtrim($script, '/') . '/index.php';
    }
    if (!str_ends_with($script, '.php')) {
        $script .= '.php';
    }

    if (file_exists($script)) {
        require $script;
        return;
    }

    http_response_code(404);
    echo 'Page admin introuvable';
}

function renderHomePage(PDO $pdo): void
{
    $settings = getSettings($pdo);
    $galleryAlbums = getGalleryAlbumsWithPhotos($pdo);
    $galleryPhotos = getGalleryPhotos($pdo);
    $guestbookEntries = getPublicGuestbook($pdo);
    $weddingSections = ($settings['album_enabled'] || $settings['wedding_passed'])
        ? getWeddingSectionsWithMedia($pdo)
        : [];

    require publicRoot() . '/templates/home.php';
}
