<?php

class GradeEntry {
    private $conn;
    private $table = 'grade_entries';

    public $id;
    public $enrollment_id;
    public $prelim_grade;
    public $midterm_grade;
    public $final_grade;
    public $computed_grade;
    public $status;
    public $remarks;
    public $encoded_by;
    public $submitted_at;
    public $approved_at;
    public $approved_by;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    // Get all grade entries
    public function getAll($filters = []) {
        $query = "SELECT ge.*, 
                         e.student_id, e.subject_id,
                         u.name as student_name, u.email as student_email,
                         s.code as subject_code, s.name as subject_name,
                         f.name as faculty_name
                  FROM {$this->table} ge
                  JOIN enrollments e ON ge.enrollment_id = e.id
                  JOIN users u ON e.student_id = u.id
                  JOIN subjects s ON e.subject_id = s.id
                  LEFT JOIN users f ON u.id = f.id
                  WHERE 1=1";

        if (isset($filters['status'])) {
            $status = $this->conn->real_escape_string($filters['status']);
            $query .= " AND ge.status = '{$status}'";
        }
        if (isset($filters['student_id'])) {
            $student_id = $this->conn->real_escape_string($filters['student_id']);
            $query .= " AND e.student_id = '{$student_id}'";
        }
        if (isset($filters['subject_id'])) {
            $subject_id = $this->conn->real_escape_string($filters['subject_id']);
            $query .= " AND e.subject_id = '{$subject_id}'";
        }

        $query .= " ORDER BY ge.created_at DESC";
        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Get grade entry by ID
    public function getById($id) {
        $stmt = $this->conn->prepare(
            "SELECT ge.*, 
                    e.student_id, e.subject_id,
                    u.name as student_name,
                    s.code as subject_code, s.name as subject_name
             FROM {$this->table} ge
             JOIN enrollments e ON ge.enrollment_id = e.id
             JOIN users u ON e.student_id = u.id
             JOIN subjects s ON e.subject_id = s.id
             WHERE ge.id = ?"
        );
        $stmt->bind_param('s', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get grade entry by enrollment ID
    public function getByEnrollmentId($enrollmentId) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE enrollment_id = ? LIMIT 1"
        );
        $stmt->bind_param('s', $enrollmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get enrollment rows with grade data for one subject
    public function getSubjectEnrollmentGrades($subjectId) {
        $stmt = $this->conn->prepare(
            "SELECT e.id,
                    u.student_id,
                    u.name AS student_name,
                    ge.id AS grade_entry_id,
                    ge.prelim_grade,
                    ge.midterm_grade,
                    ge.final_grade,
                    ge.computed_grade,
                    COALESCE(ge.status, 'draft') AS status
             FROM enrollments e
             INNER JOIN users u ON u.id = e.student_id
             LEFT JOIN {$this->table} ge ON ge.enrollment_id = e.id
             WHERE e.subject_id = ?
             ORDER BY u.name ASC"
        );
        $stmt->bind_param('s', $subjectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Create or update grades by enrollment record
    public function upsertByEnrollment($enrollmentId, $data, $encodedBy) {
        $existing = $this->getByEnrollmentId($enrollmentId);
        if (!$existing) {
            $create = $this->create([
                'enrollment_id' => $enrollmentId,
                'encoded_by' => $encodedBy
            ]);
            if (!$create['success']) {
                return false;
            }
            $gradeEntryId = $create['id'];
        } else {
            $gradeEntryId = $existing['id'];
        }

        return $this->updateGrades($gradeEntryId, $data);
    }

    // Create grade entry
    public function create($data) {
        $this->id = $this->generateUUID();
        $this->enrollment_id = $data['enrollment_id'];
        $this->encoded_by = $data['encoded_by'];
        $this->status = 'draft';

        $stmt = $this->conn->prepare(
            "INSERT INTO {$this->table} 
            (id, enrollment_id, encoded_by, status) 
            VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param('ssss', $this->id, $this->enrollment_id, $this->encoded_by, $this->status);

        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }

    // Update grades
    public function updateGrades($id, $data) {
        $this->id = $id;
        $this->prelim_grade = $data['prelim_grade'] ?? null;
        $this->midterm_grade = $data['midterm_grade'] ?? null;
        $this->final_grade = $data['final_grade'] ?? null;
        $this->remarks = $data['remarks'] ?? null;

        // Calculate computed grade (average of provided grades)
        $grades = array_filter([
            $this->prelim_grade,
            $this->midterm_grade,
            $this->final_grade
        ]);
        $this->computed_grade = count($grades) > 0 ? array_sum($grades) / count($grades) : null;

        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} 
             SET prelim_grade = ?, midterm_grade = ?, final_grade = ?, 
                 computed_grade = ?, remarks = ?, updated_at = NOW()
             WHERE id = ?"
        );

        $stmt->bind_param(
            'ddddss',
            $this->prelim_grade,
            $this->midterm_grade,
            $this->final_grade,
            $this->computed_grade,
            $this->remarks,
            $this->id
        );

        return $stmt->execute();
    }

    // Submit grades
    public function submit($id) {
        $status = 'submitted';
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} 
             SET status = ?, submitted_at = NOW(), updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('ss', $status, $id);
        return $stmt->execute();
    }

    // Approve grades
    public function approve($id, $approved_by) {
        $status = 'approved';
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} 
             SET status = ?, approved_at = NOW(), approved_by = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('sss', $status, $approved_by, $id);
        return $stmt->execute();
    }

    // Reject grades
    public function reject($id) {
        $status = 'rejected';
        $stmt = $this->conn->prepare(
            "UPDATE {$this->table} 
             SET status = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('ss', $status, $id);
        return $stmt->execute();
    }

    // Get pending approvals
    public function getPendingApprovals($limit = 10) {
        $status = 'pending_approval';
        $stmt = $this->conn->prepare(
            "SELECT ge.*, 
                    e.student_id,
                    u.name as student_name,
                    s.code as subject_code
             FROM {$this->table} ge
             JOIN enrollments e ON ge.enrollment_id = e.id
             JOIN users u ON e.student_id = u.id
             JOIN subjects s ON e.subject_id = s.id
             WHERE ge.status = ?
             ORDER BY ge.submitted_at DESC
             LIMIT ?"
        );
        $stmt->bind_param('si', $status, $limit);
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
