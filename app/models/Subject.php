<?php

class Subject {
    private $conn;
    private $table = 'subjects';

    public $id;
    public $code;
    public $name;
    public $units;
    public $semester;
    public $academic_year;
    public $faculty_id;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Get all subjects
    public function getAll($filters = []) {
        $query = "SELECT s.*, u.name as faculty_name 
                 FROM {$this->table} s 
                 LEFT JOIN users u ON s.faculty_id = u.id 
                 WHERE 1=1";

        if (isset($filters['academic_year'])) {
            $query .= " AND s.academic_year = '{$filters['academic_year']}'";
        }
        if (isset($filters['semester'])) {
            $query .= " AND s.semester = '{$filters['semester']}'";
        }

        $query .= " ORDER BY s.code";
        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Get subject by ID
    public function getById($id) {
        $stmt = $this->conn->prepare(
            "SELECT s.*, u.name as faculty_name 
             FROM {$this->table} s 
             LEFT JOIN users u ON s.faculty_id = u.id 
             WHERE s.id = ?"
        );
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Create subject
    public function create($data) {
        $this->id = $this->generateUUID();
        $this->code = $data['code'];
        $this->name = $data['name'];
        $this->units = $data['units'];
        $this->semester = $data['semester'];
        $this->academic_year = $data['academic_year'];
        $this->faculty_id = $data['faculty_id'] ?? null;

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} 
            (id, code, name, units, semester, academic_year, faculty_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $stmt->bind_param(
            'sssisss',
            $this->id,
            $this->code,
            $this->name,
            $this->units,
            $this->semester,
            $this->academic_year,
            $this->faculty_id
        );

        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }

    // Get subjects by faculty
    public function getByFaculty($faculty_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} 
             WHERE faculty_id = ? 
             ORDER BY academic_year DESC, semester DESC, code"
        );
        $stmt->bind_param('s', $faculty_id);
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
