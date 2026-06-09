<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
logoutAdmin();
redirect('/admin/login.php');
