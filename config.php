<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'grade_hub');
define('DB_PORT', 3306);

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_ME_LIFETIME', 2592000); // 30 days
define('PASSWORD_RESET_LIFETIME', 3600); // 1 hour
define('APP_NAME', 'Grade Hub');
define('APP_URL', 'http://localhost:8000');

// Debug mode
define('DEBUG_MODE', true);

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Session configuration
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/grade-hub-php/public/',
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
