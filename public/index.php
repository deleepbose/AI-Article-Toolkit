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

/**
 * Compute the application's base path so the app works under any URL prefix.
 *
 * Examples:
 *   PHP built-in server, -t public  ->  SCRIPT_NAME = /index.php           -> basePath = /
 *   Apache at /ai-article-toolkit/  ->  SCRIPT_NAME = /ai-article-toolkit/public/index.php
 *                                                                          -> basePath = /ai-article-toolkit/
 *   Apache at /ai-article-toolkit/public/ (no root rewrite)
 *                                   ->  SCRIPT_NAME = /ai-article-toolkit/public/index.php
 *                                                                          -> basePath = /ai-article-toolkit/public/
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath = preg_replace('#/index\.php$#', '', $scriptName) ?? '';

// If the user is hitting the cleaner URL (root .htaccess in play), strip trailing /public too.
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
if (str_ends_with($basePath, '/public') && !str_contains($requestPath, '/public/')) {
    $basePath = substr($basePath, 0, -strlen('/public'));
}

if ($basePath === '' || $basePath === false) {
    $basePath = '/';
} else {
    $basePath = '/' . trim($basePath, '/') . '/';
}

// Strip the base path off the request so route matching stays simple.
$path = $requestPath;
$trimmedBase = rtrim($basePath, '/');
if ($trimmedBase !== '' && str_starts_with($path, $trimmedBase)) {
    $path = substr($path, strlen($trimmedBase));
}
if ($path === '' || $path === false) {
    $path = '/';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
