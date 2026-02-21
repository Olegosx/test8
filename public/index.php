<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Определяем базовый URL-префикс приложения.
//
// Логика: вычисляем путь к папке public/ относительно DOCUMENT_ROOT,
// затем срезаем суффикс '/public' — он скрыт rewrite-правилом Apache.
//
// Примеры:
//   Apache, doc root /var/www/html, app в /test8/:
//     __DIR__ = /var/www/html/test8/public → urlPath = /test8/public → BASE_PATH = /test8
//   php -S ... -t public/, doc root /app/public:
//     __DIR__ = /app/public → urlPath = '' → BASE_PATH = ''
$_docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($_docRoot !== '' && str_starts_with(__DIR__, $_docRoot)) {
    $_urlPath  = substr(__DIR__, strlen($_docRoot));       // '' или '/test8/public'
    $_basePath = preg_replace('#/public$#i', '', $_urlPath); // '' или '/test8'
} else {
    $_basePath = '';
}
define('BASE_PATH', rtrim($_basePath, '/'));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app = new App\Core\App();
$app->run();
