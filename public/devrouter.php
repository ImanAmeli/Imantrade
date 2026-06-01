<?php
// Dev-only router for `php -S`. Not used in production (Apache uses .htaccess).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = $_SERVER['DOCUMENT_ROOT'];
if ($path !== '/' && is_file($root . $path) && basename($path) !== 'devrouter.php') {
    return false; // serve real asset / install.php directly
}
require $root . '/index.php';
