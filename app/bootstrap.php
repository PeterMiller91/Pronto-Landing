<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);
define('PUBLIC_DIR', dirname(__DIR__) . '/public');
define('UPLOAD_DIR', PUBLIC_DIR . '/uploads');

$GLOBALS['config'] = require __DIR__ . '/config.php';

date_default_timezone_set('Europe/Berlin');
mb_internal_encoding('UTF-8');

require __DIR__ . '/helpers.php';
require __DIR__ . '/icons.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/data.php';
require __DIR__ . '/status.php';
require __DIR__ . '/images.php';
require __DIR__ . '/menu_data.php';
require __DIR__ . '/migrate.php';

// Datenbank automatisch auf den neuesten Stand bringen (einmalig, danach nur ein kurzer Check)
try {
    if (db_is_installed()) {
        migrate();
    }
} catch (Throwable $ex) {
    error_log('Pronto Pronto Migration: ' . $ex->getMessage());
}

// Grundlegende Sicherheits-Header (zusätzlich zur .htaccess)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
