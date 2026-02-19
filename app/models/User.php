<?php

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $email;
    public $password_hash;
    public $name;
    public $role;
    public $department;
    public $student_id;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $last_login;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Register a new user
    public function register($data) {
        $this->email = $data['email'];
        $this->name = $data['name'];
        $this->role = $data['role'];
        $this->password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $this->id = $this->generateUUID();
        $this->is_active = true;
        $this->department = $data['department'] ?? null;
        $this->student_id = $data['student_id'] ?? null;

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} 
            (id, email, password_hash, name, role, department, student_id, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $stmt->bind_param(
            'sssssssi',
            $this->id,
            $this->email,
            $this->password_hash,
            $this->name,
            $this->role,
            $this->department,
            $this->student_id,
            $this->is_active
        );

        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $this->id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }

    // Get user by email
    public function getByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get user by ID
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? AND is_active = TRUE");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update last login
    public function updateLastLogin($id) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param('s', $id);
        return $stmt->execute();
    }

    public function createPasswordResetToken($email) {
        $user = $this->getByEmail($email);
        if (!$user || !$user['is_active']) {
            return ['success' => true];
        }

        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator);
        $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_LIFETIME);

        $stmt = $this->conn->prepare(
            "INSERT INTO password_reset_tokens (user_id, selector, token_hash, expires_at)
             VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $stmt->bind_param('ssss', $user['id'], $selector, $tokenHash, $expiresAt);
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => $stmt->error];
        }

        return [
            'success' => true,
            'email' => $user['email'],
            'name' => $user['name'],
            'token' => $selector . ':' . $validator
        ];
    }

    public function resetPasswordByToken($token, $newPassword) {
        $parts = explode(':', $token, 2);
        if (count($parts) !== 2) {
            return ['success' => false, 'error' => 'Invalid reset token'];
        }

        [$selector, $validator] = $parts;
        if ($selector === '' || $validator === '') {
            return ['success' => false, 'error' => 'Invalid reset token'];
        }

        $stmt = $this->conn->prepare(
            "SELECT prt.id, prt.user_id, prt.token_hash, prt.expires_at, prt.used_at, u.is_active
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.selector = ?
             ORDER BY prt.created_at DESC
             LIMIT 1"
        );
        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        if (!$record) {
            return ['success' => false, 'error' => 'Invalid or expired reset link'];
        }

        if ($record['used_at'] !== null) {
            return ['success' => false, 'error' => 'Reset link has already been used'];
        }

        if (!$record['is_active']) {
            return ['success' => false, 'error' => 'Account is inactive'];
        }

        if (strtotime($record['expires_at']) < time()) {
            return ['success' => false, 'error' => 'Reset link has expired'];
        }

        $incomingHash = hash('sha256', $validator);
        if (!hash_equals($record['token_hash'], $incomingHash)) {
            return ['success' => false, 'error' => 'Invalid or expired reset link'];
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $this->conn->begin_transaction();
        try {
            $updateUser = $this->conn->prepare("UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $updateUser->bind_param('ss', $passwordHash, $record['user_id']);
            $updateUser->execute();

            $useToken = $this->conn->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?");
            $useToken->bind_param('i', $record['id']);
            $useToken->execute();

            $invalidateResets = $this->conn->prepare(
                "UPDATE password_reset_tokens
                 SET used_at = COALESCE(used_at, NOW())
                 WHERE user_id = ? AND used_at IS NULL"
            );
            $invalidateResets->bind_param('s', $record['user_id']);
            $invalidateResets->execute();

            $this->revokeRememberTokens($record['user_id']);

            $this->conn->commit();
            return ['success' => true, 'user_id' => $record['user_id']];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => 'Unable to reset password'];
        }
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        if (!self::verifyPassword($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateUser = $this->conn->prepare("UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        if (!$updateUser) {
            return ['success' => false, 'error' => 'Unable to update password: ' . $this->conn->error];
        }

        $updateUser->bind_param('ss', $newHash, $userId);
        if (!$updateUser->execute()) {
            return ['success' => false, 'error' => 'Unable to update password: ' . $updateUser->error];
        }

        // Best-effort token revocation; password update should still succeed even if token table is unavailable.
        $this->revokeRememberTokens($userId);

        return ['success' => true];
    }

    private function revokeRememberTokens($userId) {
        if (!$this->tableExists('remember_tokens')) {
            return;
        }

        $stmt = $this->conn->prepare("UPDATE remember_tokens SET revoked = 1 WHERE user_id = ?");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $userId);
        $stmt->execute();
    }

    private function tableExists($tableName) {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $tableName);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    // Get all users by role
    public function getByRole($role) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE role = ? AND is_active = TRUE ORDER BY name");
        $stmt->bind_param('s', $role);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Verify password
    public static function verifyPassword($inputPassword, $hash) {
        return password_verify($inputPassword, $hash);
    }

    // Generate UUID v4
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
