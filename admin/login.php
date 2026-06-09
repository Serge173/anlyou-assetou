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