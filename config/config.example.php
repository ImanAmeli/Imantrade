<?php
/**
 * Application configuration.
 * Copy this file to config.php and fill in your real values.
 * config.php is git-ignored so secrets never reach the repo.
 */

return [
    // --- Database (MySQL / MariaDB) ---
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'restaurant_menu',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // --- App ---
    'app' => [
        // Base URL path the app is served from. '' for domain root,
        // '/menu' if installed in a subfolder.
        'base_path' => '',
        // Absolute filesystem path to the public uploads dir.
        'upload_dir' => __DIR__ . '/../public/uploads',
        // Max upload size in bytes (2 MB).
        'max_upload' => 2 * 1024 * 1024,
        // Secret used for CSRF / session hardening. CHANGE THIS.
        'app_key'   => 'change-me-to-a-long-random-string',
        'timezone'  => 'Asia/Tehran',
        'debug'     => false,
    ],
];
