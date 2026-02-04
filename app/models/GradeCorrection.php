<?php

class GradeCorrection {
    private $conn;
    private $table = 'grade_corrections';

    public $id;
    public $grade_entry_id;
    public $requested_by;
    public $original_grade;
    public $requested_grade;
    public $reason;
    public $status;
    public $created_at;
    public $reviewed_at;
    public $reviewed_by;
    public $reviewer_remarks;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Get all corrections
    public function getAll($filters = []) {
        $query = "SELECT gc.*, 
                         ge.computed_grade,
                         u.name as requester_name,
                         e.student_id,
                         st.name as student_name,
                         s.code as subject_code
                  FROM {$this->table} gc
                  JOIN grade_entries ge ON gc.grade_entry_id = ge.id
                  JOIN enrollments e ON ge.enrollment_id = e.id
                  JOIN users u ON gc.requested_by = u.id
                  JOIN users st ON e.student_id = st.id
                  JOIN subjects s ON e.subject_id = s.id
                  WHERE 1=1";

        if (isset($filters['status'])) {
            $status = $this->conn->real_escape_string($filters['status']);
            $query .= " AND gc.status = '{$status}'";
        }
        if (isset($filters['requested_by'])) {
            $requested_by = $this->conn->real_escape_string($filters['requested_by']);
            $query .= " AND gc.requested_by = '{$requested_by}'";
        }

        $query .= " ORDER BY gc.created_at DESC";
        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Create correction request
    public function create($data) {
        $this->id = $this->generateUUID();
        $this->grade_entry_id = $data['grade_entry_id'];
        $this->requested_by = $data['requested_by'];
        $this->original_grade = $data['original_grade'];
        $this->requested_grade = $data['requested_grade'];
        $this->reason = $data['reason'];
        $this->status = 'pending';

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} 
            (id, grade_entry_id, requested_by, original_grade, requested_grade, reason, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            'sssddds',
            $this->id,
            $this->grade_entry_id,
            $this->requested_by,
            $this->original_grade,
            $this->requested_grade,
            $this->reason,
            $this->status
        );

        return $stmt->execute() ? ['success' => true, 'id' => $this->id] : ['success' => false];
    }

    // Approve correction
    public function approve($id, $reviewed_by, $remarks = '') {
        $status = 'approved';
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} 
             SET status = ?, reviewed_by = ?, reviewer_remarks = ?, reviewed_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('ssss', $status, $reviewed_by, $remarks, $id);
        return $stmt->execute();
    }

    // Reject correction
    public function reject($id, $reviewed_by, $remarks = '') {
        $status = 'rejected';
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} 
             SET status = ?, reviewed_by = ?, reviewer_remarks = ?, reviewed_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('ssss', $status, $reviewed_by, $remarks, $id);
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
