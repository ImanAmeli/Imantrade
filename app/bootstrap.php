<?php
/**
 * Application bootstrap: autoloader, config, session, database.
 */

// --- PSR-4-ish autoloader for the App\ namespace ---
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/' . $rel . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/Core/helpers.php';

$cfg = config();

if (!empty($cfg['app']['timezone'])) {
    date_default_timezone_set($cfg['app']['timezone']);
}

if (!empty($cfg['app']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

// --- Session (hardened) ---
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_name('rms_session');
    session_start();
}

// --- Database ---
App\Core\Database::init($cfg['db']);
