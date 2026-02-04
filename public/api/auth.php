<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ApiResponse.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);

    $errors = [];
    if (empty($data['email'])) $errors['email'] = 'Email is required';
    if (empty($data['password'])) $errors['password'] = 'Password is required';
    if (empty($data['name'])) $errors['name'] = 'Name is required';
    if (empty($data['role'])) $errors['role'] = 'Role is required';

    if (!empty($errors)) {
        ApiResponse::error('Validation failed', 400, $errors);
    }

    $user = new User();
    $result = $user->register($data);

    if ($result['success']) {
        ActivityLog::log($result['user_id'], 'REGISTER', 'New user registered');
        ApiResponse::success(['user_id' => $result['user_id']], 'User registered successfully');
    } else {
        ApiResponse::error('Registration failed: ' . $result['error']);
    }
}

if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['email']) || empty($data['password'])) {
        ApiResponse::error('Email and password are required', 400);
    }

    $result = Auth::login($data['email'], $data['password']);

    if ($result['success']) {
        ApiResponse::success($result['user'], 'Login successful');
    } else {
        ApiResponse::error($result['error']);
    }
}

if ($method === 'POST' && $action === 'logout') {
    Auth::requireAuth();
    $result = Auth::logout();
    ApiResponse::success(null, 'Logout successful');
}

if ($method === 'GET' && $action === 'profile') {
    Auth::requireAuth();
    $user = Auth::getCurrentUser();
    ApiResponse::success($user);
}

ApiResponse::error('Invalid action', 400);
?>
