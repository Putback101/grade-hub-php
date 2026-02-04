<?php
// Simple redirect to login or dashboard based on auth status
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ./dashboard');
} else {
    header('Location: ./login');
}
exit;
?>
