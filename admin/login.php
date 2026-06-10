<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (isAdminLoggedIn()) {
    redirect('/admin/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = bootstrapDatabase();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginAdmin($pdo, $username, $password)) {
        redirect('/admin/');
    }
    $error = 'Identifiants incorrects.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Fairepartdebaby</title>
    <link rel="icon" type="image/png" href="<?= sanitize(brandLogoUrl()) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-header">
            <img src="<?= sanitize(brandLogoUrl()) ?>" alt="Fairepartdebaby" class="brand-logo">
            <p>Connectez-vous pour gérer votre faire-part</p>
        </div>

        <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php">
            <div class="mb-3">
                <label class="form-label" for="username">Identifiant</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="username">
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
        </form>

        <p class="text-center mt-4 mb-0">
            <a href="/" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Retour au site</a>
        </p>
    </div>
</body>
</html>
