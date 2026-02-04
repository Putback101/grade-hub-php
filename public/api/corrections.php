<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ApiResponse.php';
require_once __DIR__ . '/../../app/models/GradeCorrection.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = Auth::getCurrentUser();

if ($method === 'GET' && $action === 'list') {
    Auth::requireRole(['registrar', 'admin', 'faculty']);
    $filters = ['status' => $_GET['status'] ?? null];

    if ($user['role'] === 'faculty') {
        $filters['requested_by'] = $user['id'];
    }
    
    $gradeCorrection = new GradeCorrection();
    $corrections = $gradeCorrection->getAll($filters);
    ApiResponse::success($corrections);
}

if ($method === 'POST' && $action === 'request') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['grade_entry_id'])) {
        ApiResponse::error('Grade entry ID is required', 400);
    }

    $gradeCorrection = new GradeCorrection();
    $result = $gradeCorrection->create([
        'grade_entry_id' => $data['grade_entry_id'],
        'requested_by' => $user['id'],
        'original_grade' => $data['original_grade'],
        'requested_grade' => $data['requested_grade'],
        'reason' => $data['reason']
    ]);

    if ($result['success']) {
        ActivityLog::log($user['id'], 'REQUEST_CORRECTION', 'Grade correction requested: ' . $data['grade_entry_id']);
        ApiResponse::success(['id' => $result['id']], 'Correction request submitted');
    } else {
        ApiResponse::error('Failed to submit correction request');
    }
}

if ($method === 'POST' && $action === 'approve') {
    Auth::requireRole('registrar');
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        ApiResponse::error('Correction ID is required', 400);
    }

    $gradeCorrection = new GradeCorrection();
    if ($gradeCorrection->approve($data['id'], $user['id'], $data['remarks'] ?? '')) {
        ActivityLog::log($user['id'], 'APPROVE_CORRECTION', 'Grade correction approved: ' . $data['id']);
        ApiResponse::success(null, 'Correction approved');
    } else {
        ApiResponse::error('Failed to approve correction');
    }
}

if ($method === 'POST' && $action === 'reject') {
    Auth::requireRole('registrar');
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        ApiResponse::error('Correction ID is required', 400);
    }

    $gradeCorrection = new GradeCorrection();
    if ($gradeCorrection->reject($data['id'], $user['id'], $data['remarks'] ?? '')) {
        ActivityLog::log($user['id'], 'REJECT_CORRECTION', 'Grade correction rejected: ' . $data['id']);
        ApiResponse::success(null, 'Correction rejected');
    } else {
        ApiResponse::error('Failed to reject correction');
    }
}

ApiResponse::error('Invalid action', 400);
?>
