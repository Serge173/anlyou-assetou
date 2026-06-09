<?php

declare(strict_types=1);

$publicDir = __DIR__;
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (str_starts_with($uri, '/assets/') && file_exists($publicDir . $uri)) {
    return false;
}

require_once dirname($publicDir) . '/includes/router.php';
dispatchRequest();
