<?php

declare(strict_types=1);

return [
    'name' => 'Faire-Part Mariage',
    'url' => getenv('APP_URL') ?: 'http://localhost:8080',
    'debug' => (bool) (getenv('APP_DEBUG') ?: true),
    'session_name' => 'fairepart_session',
    'upload_max_size' => 5 * 1024 * 1024,
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
];
