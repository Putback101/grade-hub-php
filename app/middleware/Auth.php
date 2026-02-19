<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../../includes/Database.php';

class Auth {
    private const REMEMBER_COOKIE = 'gradehub_remember';
    private static $hasRememberTokenTable = null;

    public static function login($email, $password, $rememberMe = false) {
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

        session_regenerate_id(true);
        $user->updateLastLogin($userData['id']);
        self::storeUserInSession($userData);
        self::createSessionRecord($userData['id']);

        if ($rememberMe) {
            self::issueRememberToken($userData['id']);
        } else {
            self::clearRememberCookie();
        }

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
        self::revokeRememberTokenFromCookie();
        self::clearRememberCookie();

        if (isset($_SESSION['user_id'])) {
            ActivityLog::log($_SESSION['user_id'], 'LOGOUT', 'User logged out');
            self::destroySessionRecord();
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
            }
            session_destroy();
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'No user logged in'];
    }

    public static function isAuthenticated() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
            return self::validateSessionRecord();
        }

        return self::attemptRememberLogin();
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

    private static function storeUserInSession($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['name'] = $userData['name'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['department'] = $userData['department'] ?? null;
        $_SESSION['student_id'] = $userData['student_id'] ?? null;
        $_SESSION['login_time'] = time();
        $_SESSION['session_id'] = session_id();
    }

    private static function issueRememberToken($userId) {
        if (!self::rememberTokensAvailable()) {
            self::clearRememberCookie();
            return;
        }

        $conn = Database::getInstance()->getConnection();

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator);
        $expiresAt = date('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME);

        $stmt = $conn->prepare(
            "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
             VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ssss', $userId, $selector, $tokenHash, $expiresAt);
        $stmt->execute();

        self::setRememberCookie($selector, $validator);
    }

    private static function setRememberCookie($selector, $validator) {
        $value = $selector . ':' . $validator;
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => time() + REMEMBER_ME_LIFETIME,
            'path' => '/grade-hub-php/public/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    private static function clearRememberCookie() {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/grade-hub-php/public/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    private static function parseRememberCookie() {
        if (empty($_COOKIE[self::REMEMBER_COOKIE])) {
            return null;
        }

        $parts = explode(':', $_COOKIE[self::REMEMBER_COOKIE], 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $validator] = $parts;
        if ($selector === '' || $validator === '') {
            return null;
        }

        return ['selector' => $selector, 'validator' => $validator];
    }

    private static function revokeRememberTokenFromCookie() {
        if (!self::rememberTokensAvailable()) {
            self::clearRememberCookie();
            return;
        }

        $cookieData = self::parseRememberCookie();
        if (!$cookieData) {
            return;
        }

        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare("UPDATE remember_tokens SET revoked = 1 WHERE selector = ?");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $cookieData['selector']);
        $stmt->execute();
    }

    private static function attemptRememberLogin() {
        if (!self::rememberTokensAvailable()) {
            self::clearRememberCookie();
            return false;
        }

        $cookieData = self::parseRememberCookie();
        if (!$cookieData) {
            return false;
        }

        $conn = Database::getInstance()->getConnection();
        $selector = $cookieData['selector'];
        $validator = $cookieData['validator'];

        $stmt = $conn->prepare(
            "SELECT rt.id AS token_row_id, rt.user_id, rt.token_hash, rt.expires_at, u.*
             FROM remember_tokens rt
             INNER JOIN users u ON u.id = rt.user_id
             WHERE rt.selector = ? AND rt.revoked = 0 AND u.is_active = 1
             LIMIT 1"
        );
        if (!$stmt) {
            self::clearRememberCookie();
            return false;
        }

        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();

        if (!$record) {
            self::clearRememberCookie();
            return false;
        }

        if (strtotime($record['expires_at']) < time()) {
            $expireStmt = $conn->prepare("UPDATE remember_tokens SET revoked = 1 WHERE id = ?");
            $expireStmt->bind_param('i', $record['token_row_id']);
            $expireStmt->execute();
            self::clearRememberCookie();
            return false;
        }

        $incomingHash = hash('sha256', $validator);
        if (!hash_equals($record['token_hash'], $incomingHash)) {
            $revokeStmt = $conn->prepare("UPDATE remember_tokens SET revoked = 1 WHERE id = ?");
            $revokeStmt->bind_param('i', $record['token_row_id']);
            $revokeStmt->execute();
            self::clearRememberCookie();
            return false;
        }

        session_regenerate_id(true);
        self::storeUserInSession($record);
        self::createSessionRecord($record['user_id']);

        $user = new User();
        $user->updateLastLogin($record['user_id']);
        ActivityLog::log($record['user_id'], 'LOGIN_REMEMBERED', 'User logged in via remember-me');

        $revokeStmt = $conn->prepare("UPDATE remember_tokens SET revoked = 1, last_used_at = NOW() WHERE id = ?");
        $revokeStmt->bind_param('i', $record['token_row_id']);
        $revokeStmt->execute();
        self::issueRememberToken($record['user_id']);

        return true;
    }

    private static function rememberTokensAvailable() {
        if (self::$hasRememberTokenTable !== null) {
            return self::$hasRememberTokenTable;
        }

        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1"
        );
        if (!$stmt) {
            self::$hasRememberTokenTable = false;
            return false;
        }

        $table = 'remember_tokens';
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $result = $stmt->get_result();
        self::$hasRememberTokenTable = ($result && $result->num_rows > 0);

        return self::$hasRememberTokenTable;
    }
}
?>
