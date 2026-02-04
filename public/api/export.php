<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ApiResponse.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

Auth::requireAuth();

$action = $_GET['action'] ?? '';
$user = Auth::getCurrentUser();
$format = $_GET['format'] ?? 'csv'; // csv, json, or pdf-html

if ($action === 'grades') {
    $conn = Database::getInstance()->getConnection();
    
    // Get all grades based on role
    if ($user['role'] === 'student') {
        $result = $conn->query(
            "SELECT u.name as student_name, s.code as subject_code, s.name as subject_name,
                    ge.prelim_grade, ge.midterm_grade, ge.final_grade, ge.computed_grade, ge.status
             FROM grade_entries ge
             JOIN enrollments e ON ge.enrollment_id = e.id
             JOIN users u ON e.student_id = u.id
             JOIN subjects s ON e.subject_id = s.id
             WHERE e.student_id = '{$user['id']}'
             ORDER BY s.code"
        );
    } else {
        $result = $conn->query(
            "SELECT u.name as student_name, s.code as subject_code, s.name as subject_name,
                    ge.prelim_grade, ge.midterm_grade, ge.final_grade, ge.computed_grade, ge.status
             FROM grade_entries ge
             JOIN enrollments e ON ge.enrollment_id = e.id
             JOIN users u ON e.student_id = u.id
             JOIN subjects s ON e.subject_id = s.id
             ORDER BY s.code, u.name"
        );
    }

    $grades = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
    }

    if ($format === 'csv') {
        exportCSV('grades', $grades, [
            'student_name' => 'Student Name',
            'subject_code' => 'Subject Code',
            'subject_name' => 'Subject Name',
            'prelim_grade' => 'Prelim Grade',
            'midterm_grade' => 'Midterm Grade',
            'final_grade' => 'Final Grade',
            'computed_grade' => 'Computed Grade',
            'status' => 'Status'
        ]);
    } else {
        ApiResponse::success($grades);
    }
}

if ($action === 'activity-logs') {
    $conn = Database::getInstance()->getConnection();
    
    $result = $conn->query(
        "SELECT al.id, u.name, al.action, al.description, al.created_at
         FROM activity_logs al
         JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC
         LIMIT 1000"
    );

    $logs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    if ($format === 'csv') {
        exportCSV('activity-logs', $logs, [
            'name' => 'User',
            'action' => 'Action',
            'description' => 'Description',
            'created_at' => 'Timestamp'
        ]);
    } else {
        ApiResponse::success($logs);
    }
}

if ($action === 'grade-corrections') {
    $conn = Database::getInstance()->getConnection();
    
    $result = $conn->query(
        "SELECT gc.id, u.name as student_name, s.code as subject_code, s.name as subject_name,
                gc.old_grade, gc.new_grade, gc.reason, gc.status, gc.created_at
         FROM grade_corrections gc
         JOIN users u ON gc.student_id = u.id
         JOIN subjects s ON gc.subject_id = s.id
         ORDER BY gc.created_at DESC"
    );

    $corrections = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $corrections[] = $row;
        }
    }

    if ($format === 'csv') {
        exportCSV('grade-corrections', $corrections, [
            'student_name' => 'Student',
            'subject_code' => 'Subject Code',
            'subject_name' => 'Subject Name',
            'old_grade' => 'Old Grade',
            'new_grade' => 'New Grade',
            'reason' => 'Reason',
            'status' => 'Status',
            'created_at' => 'Created At'
        ]);
    } else {
        ApiResponse::success($corrections);
    }
}

if ($action === 'reports') {
    $conn = Database::getInstance()->getConnection();
    
    // Get grade distribution data
    $result = $conn->query(
        "SELECT 
            CASE 
                WHEN ge.computed_grade >= 90 THEN 'A (90-100)'
                WHEN ge.computed_grade >= 80 THEN 'B (80-89)'
                WHEN ge.computed_grade >= 70 THEN 'C (70-79)'
                WHEN ge.computed_grade >= 60 THEN 'D (60-69)'
                ELSE 'F (Below 60)'
            END as grade_range,
            COUNT(*) as count
         FROM grade_entries ge
         WHERE ge.status = 'approved'
         GROUP BY grade_range
         ORDER BY ge.computed_grade DESC"
    );

    $distribution = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $distribution[] = $row;
        }
    }

    if ($format === 'csv') {
        exportCSV('grade-distribution', $distribution, [
            'grade_range' => 'Grade Range',
            'count' => 'Count'
        ]);
    } else {
        ApiResponse::success($distribution);
    }
}

function exportCSV($filename, $data, $headers) {
    // Send headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '_' . date('Y-m-d_His') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create file pointer
    $output = fopen('php://output', 'w');

    // Write BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");

    // Write header row
    fputcsv($output, array_values($headers));

    // Write data rows
    foreach ($data as $row) {
        $csvRow = [];
        foreach ($headers as $key => $label) {
            $csvRow[] = $row[$key] ?? '';
        }
        fputcsv($output, $csvRow);
    }

    fclose($output);
    exit;
}

ApiResponse::error('Invalid action');
?>
