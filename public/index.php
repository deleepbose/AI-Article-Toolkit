<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Deleep\ArticleToolkit\Api\Router;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv::createImmutable(__DIR__ . '/..')->load();
    foreach ($_ENV as $key => $value) {
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($path, '/api/')) {
    (new Router())->dispatch($method, $path);
    return;
}

if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/views/home.php';
    return;
}

http_response_code(404);
echo "Not found.";
