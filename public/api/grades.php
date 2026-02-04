<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ApiResponse.php';
require_once __DIR__ . '/../../includes/Mailer.php';
require_once __DIR__ . '/../../app/models/GradeEntry.php';
require_once __DIR__ . '/../../app/models/Enrollment.php';
require_once __DIR__ . '/../../app/models/GradeCorrection.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = Auth::getCurrentUser();

if ($method === 'GET' && $action === 'list') {
    $filters = [
        'status' => $_GET['status'] ?? null,
        'student_id' => $_GET['student_id'] ?? null,
        'subject_id' => $_GET['subject_id'] ?? null
    ];

    // Students can only see their own grades
    if ($user['role'] === 'student') {
        $filters['student_id'] = $user['id'];
    }

    $gradeEntry = new GradeEntry();
    $grades = $gradeEntry->getAll($filters);
    ApiResponse::success($grades);
}

if ($method === 'GET' && $action === 'pending') {
    Auth::requireRole('registrar');
    $gradeEntry = new GradeEntry();
    $pending = $gradeEntry->getPendingApprovals();
    ApiResponse::success($pending);
}

if ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        ApiResponse::error('Grade ID is required', 400);
    }

    $gradeEntry = new GradeEntry();
    $grade = $gradeEntry->getById($id);

    if (!$grade) {
        ApiResponse::error('Grade not found', 404);
    }

    // Authorization check
    if ($user['role'] === 'student' && $grade['student_id'] !== $user['id']) {
        ApiResponse::error('Unauthorized', 403);
    }

    ApiResponse::success($grade);
}

if ($method === 'POST' && $action === 'update') {
    Auth::requireRole(['faculty', 'admin']);
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        ApiResponse::error('Grade ID is required', 400);
    }

    $gradeEntry = new GradeEntry();
    if ($gradeEntry->updateGrades($data['id'], $data)) {
        ActivityLog::log($user['id'], 'UPDATE_GRADE', 'Grade entry updated: ' . $data['id']);
        ApiResponse::success(null, 'Grade updated successfully');
    } else {
        ApiResponse::error('Failed to update grade');
    }
}

if ($method === 'POST' && $action === 'submit') {
    Auth::requireRole(['faculty', 'admin']);
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        ApiResponse::error('Grade ID is required', 400);
    }

    $gradeEntry = new GradeEntry();
    if ($gradeEntry->submit($data['id'])) {
        ActivityLog::log($user['id'], 'SUBMIT_GRADE', 'Grade submitted: ' . $data['id']);
        ApiResponse::success(null, 'Grade submitted successfully');
    } else {
        ApiResponse::error('Failed to submit grade');
    }
}

if ($method === 'POST' && $action === 'approve') {
    Auth::requireRole('registrar');
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        ApiResponse::error('Grade ID is required', 400);
    }

    $gradeEntry = new GradeEntry();
    if ($gradeEntry->approve($data['id'], $user['id'])) {
        // Get grade details for email notification
        $grade = $gradeEntry->getById($data['id']);
        if ($grade) {
            $conn = Database::getInstance()->getConnection();
            $studentResult = $conn->query("SELECT email, name FROM users WHERE id = '{$grade['student_id']}'");
            $student = $studentResult->fetch_assoc();
            
            if ($student && !empty($student['email'])) {
                Mailer::sendGradeApprovalNotification(
                    $student['email'],
                    $student['name'],
                    $grade['subject_code'] ?? '',
                    $grade['subject_name'] ?? '',
                    $grade['computed_grade'] ?? ''
                );
            }
        }
        
        ActivityLog::log($user['id'], 'APPROVE_GRADE', 'Grade approved: ' . $data['id']);
        ApiResponse::success(null, 'Grade approved successfully');
    } else {
        ApiResponse::error('Failed to approve grade');
    }
}

if ($method === 'POST' && $action === 'reject') {
    Auth::requireRole('registrar');
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        ApiResponse::error('Grade ID is required', 400);
    }

    $gradeEntry = new GradeEntry();
    if ($gradeEntry->reject($data['id'])) {
        ActivityLog::log($user['id'], 'REJECT_GRADE', 'Grade rejected: ' . $data['id']);
        ApiResponse::success(null, 'Grade rejected');
    } else {
        ApiResponse::error('Failed to reject grade');
    }
}

ApiResponse::error('Invalid action', 400);
?>
