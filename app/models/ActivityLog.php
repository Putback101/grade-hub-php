<?php

class ActivityLog {
    private $conn;
    private $table = 'activity_logs';

    public $id;
    public $user_id;
    public $action;
    public $details;
    public $ip_address;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Log an activity
    public static function log($user_id, $action, $details = '') {
        $log = new self();
        $log->id = $log->generateUUID();
        $log->user_id = $user_id;
        $log->action = $action;
        $log->details = $details;
        $log->ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $log->conn->prepare(
            "INSERT INTO {$log->table} (id, user_id, action, details, ip_address) 
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'sssss',
            $log->id,
            $log->user_id,
            $log->action,
            $log->details,
            $log->ip_address
        );

        return $stmt->execute();
    }

    // Get recent activities
    public static function getRecent($limit = 20) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare(
            "SELECT al.*, u.name as user_name 
             FROM activity_logs al
             JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get user activities
    public static function getUserActivities($user_id, $limit = 50) {
        $conn = Database::getInstance()->getConnection();
        $stmt = $conn->prepare(
            "SELECT al.*, u.name as user_name
             FROM activity_logs al
             JOIN users u ON al.user_id = u.id
             WHERE al.user_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?"
        );
        $stmt->bind_param('si', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function getUserActivitiesFiltered($user_id, $limit = 50, $excludeActions = []) {
        $conn = Database::getInstance()->getConnection();

        $sql = "SELECT al.*, u.name as user_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                WHERE al.user_id = ?";
        $types = 's';
        $params = [$user_id];

        if (!empty($excludeActions)) {
            $placeholders = implode(',', array_fill(0, count($excludeActions), '?'));
            $sql .= " AND al.action NOT IN ($placeholders)";
            $types .= str_repeat('s', count($excludeActions));
            $params = array_merge($params, $excludeActions);
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT ?";
        $types .= 'i';
        $params[] = $limit;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

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
