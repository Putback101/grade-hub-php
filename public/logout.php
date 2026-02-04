<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

// Handle both GET and POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    Auth::logout();
    // Redirect to login page
    header('Location: /grade-hub-php/public/login.php', true, 302);
    exit;
}

// If not GET or POST, redirect to dashboard
header('Location: /grade-hub-php/public/dashboard.php', true, 302);
exit;
?>
