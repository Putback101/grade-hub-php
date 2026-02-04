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
