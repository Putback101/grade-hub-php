<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/models/User.php';

$seeds = require __DIR__ . '/seeders.php';
$users = $seeds['users'] ?? [];

$u = new User();

foreach ($users as $user) {
    $email = $user['email'];
    $existing = $u->getByEmail($email);
    if ($existing) {
        echo "Skipping existing user: {$email}\n";
        continue;
    }

    $payload = [
        'email' => $email,
        'name' => $user['name'],
        'role' => $user['role'] ?? 'student',
        'password' => $user['password'],
        'department' => $user['department'] ?? null,
        'student_id' => $user['student_id'] ?? null
    ];

    $res = $u->register($payload);
    if ($res['success']) {
        echo "Inserted user: {$email} (id: {$res['user_id']})\n";
    } else {
        echo "Failed to insert {$email}: " . ($res['error'] ?? 'unknown') . "\n";
    }
}

echo "Done.\n";
