<?php

declare(strict_types=1);

return [
    'name' => 'Koné Assetou & Kone Anlyou',
    'url' => getenv('APP_URL') ?: 'http://localhost:8080',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'session_name' => 'fairepart_session',
    'upload_max_size' => 5 * 1024 * 1024,
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
];
