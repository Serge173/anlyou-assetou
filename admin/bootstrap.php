<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

function adminLayout(string $title, string $content, string $activePage = ''): void
{
    $settings = getSettings(bootstrapDatabase());
    $coupleNames = coupleLabel($settings);
    $coupleMonogram = coupleInitials($settings);

    $pages = [
        'dashboard' => ['icon' => 'speedometer2', 'label' => 'Tableau de bord', 'url' => '/admin/'],
        'confirmations' => ['icon' => 'people', 'label' => 'Présences', 'url' => '/admin/confirmations.php'],
        'declines' => ['icon' => 'person-x', 'label' => 'Absences', 'url' => '/admin/declines.php'],
        'guestbook' => ['icon' => 'book', 'label' => 'Livre d\'or', 'url' => '/admin/guestbook.php'],
        'gallery' => ['icon' => 'images', 'label' => 'Galerie', 'url' => '/admin/gallery.php'],
        'settings' => ['icon' => 'gear', 'label' => 'Paramètres', 'url' => '/admin/settings.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($title) ?> — <?= sanitize(brandName()) ?></title>
    <link rel="icon" type="image/png" href="<?= sanitize(brandLogoUrl()) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= sanitize(assetUrl('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" aria-hidden="true"></div>
    <aside class="admin-sidebar" id="adminSidebar">
        <button type="button" class="admin-sidebar-close" id="adminSidebarClose" aria-label="Fermer le menu">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <div class="sidebar-brand">
            <img src="<?= sanitize(brandLogoUrl()) ?>" alt="<?= sanitize(brandName()) ?>" class="sidebar-brand-logo">
            <span class="sidebar-brand-title">
                <?php if ($coupleNames !== ''): ?>
                <?= sanitize($coupleNames) ?>
                <?php else: ?>
                <?= sanitize(brandName()) ?>
                <?php endif; ?>
            </span>
            <?php if ($coupleMonogram !== ''): ?>
            <small class="sidebar-couple-initials"><?= sanitize($coupleMonogram) ?></small>
            <?php endif; ?>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($pages as $key => $page): ?>
            <a href="<?= $page['url'] ?>" class="sidebar-link <?= $activePage === $key ? 'active' : '' ?>">
                <i class="bi bi-<?= $page['icon'] ?>"></i>
                <?= $page['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="/" target="_blank" class="sidebar-link"><i class="bi bi-box-arrow-up-right"></i> Voir le site</a>
            <a href="/admin/logout.php" class="sidebar-link text-danger"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header-start">
                <button type="button" class="admin-menu-toggle" id="adminMenuToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="adminSidebar">
                    <i class="bi bi-list admin-menu-icon-open" aria-hidden="true"></i>
                    <i class="bi bi-x-lg admin-menu-icon-close" aria-hidden="true"></i>
                </button>
                <h1><?= sanitize($title) ?></h1>
            </div>
            <span class="admin-user"><i class="bi bi-person-circle"></i> <?= sanitize($_SESSION['admin_username'] ?? 'Admin') ?></span>
        </header>
        <div class="admin-content">
            <?= $content ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= sanitize(assetUrl('assets/js/admin.js')) ?>"></script>
</body>
</html>
    <?php
}

function initAdmin(): PDO
{
    $pdo = bootstrapDatabase();
    requireAdmin();
    return $pdo;
}
