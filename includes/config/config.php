<?php
// Application settings
define('APP_NAME', 'Music Store');
define('APP_URL', 'http://localhost/Music-Store');
define('APP_VERSION', '1.0.0');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'music_store_session');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../../assets/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination settings
define('ITEMS_PER_PAGE', 12);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/error.log');

// Create required directories
$directories = [
    UPLOAD_DIR,
    __DIR__ . '/../../logs',
    __DIR__ . '/../../assets/uploads/products',
    __DIR__ . '/../../assets/uploads/users'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params(SESSION_LIFETIME);
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
} 