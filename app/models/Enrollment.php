<?php

class Enrollment {
    private $conn;
    private $table = 'enrollments';

    public $id;
    public $student_id;
    public $subject_id;
    public $enrolled_at;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Get student enrollments
    public function getStudentEnrollments($student_id) {
        $stmt = $this->conn->prepare(
            "SELECT e.*, s.code, s.name, s.units
             FROM {$this->table} e
             JOIN subjects s ON e.subject_id = s.id
             WHERE e.student_id = ?
             ORDER BY s.code"
        );
        $stmt->bind_param('s', $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get subject enrollments
    public function getSubjectEnrollments($subject_id) {
        $stmt = $this->conn->prepare(
            "SELECT e.*, u.name as student_name, u.email as student_email
             FROM {$this->table} e
             JOIN users u ON e.student_id = u.id
             WHERE e.subject_id = ?
             ORDER BY u.name"
        );
        $stmt->bind_param('s', $subject_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Enroll student
    public function enroll($student_id, $subject_id) {
        $this->id = $this->generateUUID();
        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} (id, student_id, subject_id) 
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sss', $this->id, $student_id, $subject_id);
        return $stmt->execute();
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
