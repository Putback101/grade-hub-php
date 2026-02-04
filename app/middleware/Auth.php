<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../../includes/Database.php';

class Auth {
    public static function login($email, $password) {
        $user = new User();
        $userData = $user->getByEmail($email);

        if (!$userData) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        if (!User::verifyPassword($password, $userData['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        if (!$userData['is_active']) {
            return ['success' => false, 'error' => 'Account is inactive'];
        }

        // Update last login
        $user->updateLastLogin($userData['id']);

        // Store in session
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['name'] = $userData['name'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['department'] = $userData['department'] ?? null;
        $_SESSION['student_id'] = $userData['student_id'] ?? null;
        $_SESSION['login_time'] = time();
        $_SESSION['session_id'] = session_id();

        self::createSessionRecord($userData['id']);

        // Log activity
        ActivityLog::log($userData['id'], 'LOGIN', 'User logged in');

        return [
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'email' => $userData['email'],
                'name' => $userData['name'],
                'role' => $userData['role']
            ]
        ];
    }

    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            ActivityLog::log($_SESSION['user_id'], 'LOGOUT', 'User logged out');
            self::destroySessionRecord();
            session_destroy();
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'No user logged in'];
    }

    public static function isAuthenticated() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['email'])) {
            return false;
        }

        return self::validateSessionRecord();
    }

    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'name' => $_SESSION['name'],
            'role' => $_SESSION['role'],
            'department' => $_SESSION['department'] ?? null,
            'student_id' => $_SESSION['student_id'] ?? null
        ];
    }

    public static function hasRole($role) {
        if (!self::isAuthenticated()) {
            return false;
        }

        if (is_array($role)) {
            return in_array($_SESSION['role'], $role);
        }

        return $_SESSION['role'] === $role;
    }

    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireAuth();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($_SESSION['role'], $roles)) {
            header('HTTP/1.1 403 Forbidden');
            echo "Access denied";
            exit;
        }
    }

    private static function createSessionRecord($userId) {
        $conn = Database::getInstance()->getConnection();
        $sessionId = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);

        $stmt = $conn->prepare(
            "REPLACE INTO sessions (id, user_id, ip_address, user_agent, created_at, expires_at)
             VALUES (?, ?, ?, ?, NOW(), ?)"
        );
        $stmt->bind_param('sssss', $sessionId, $userId, $ip, $ua, $expiresAt);
        $stmt->execute();
    }

    private static function validateSessionRecord() {
        $conn = Database::getInstance()->getConnection();
        $sessionId = session_id();

        $stmt = $conn->prepare("SELECT user_id, expires_at FROM sessions WHERE id = ?");
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if (!$result) {
            return false;
        }

        if (strtotime($result['expires_at']) < time()) {
            self::destroySessionRecord();
            return false;
        }

        // Sliding expiration
        $newExpires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $update = $conn->prepare("UPDATE sessions SET expires_at = ? WHERE id = ?");
        $update->bind_param('ss', $newExpires, $sessionId);
        $update->execute();

        return true;
    }

    private static function destroySessionRecord() {
        $conn = Database::getInstance()->getConnection();
        $sessionId = session_id();
        if (!$sessionId) {
            return;
        }
        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
    }
}
?>
