<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ApiResponse.php';
require_once __DIR__ . '/../../app/models/Subject.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = Auth::getCurrentUser();

if ($method === 'GET' && $action === 'list') {
    $filters = [
        'academic_year' => $_GET['academic_year'] ?? null,
        'semester' => $_GET['semester'] ?? null
    ];

    $subject = new Subject();
    $subjects = $subject->getAll($filters);
    ApiResponse::success($subjects);
}

if ($method === 'GET' && $action === 'faculty') {
    Auth::requireRole('faculty');
    
    $subject = new Subject();
    $subjects = $subject->getByFaculty($user['id']);
    ApiResponse::success($subjects);
}

if ($method === 'GET' && $action === 'get') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        ApiResponse::error('Subject ID is required', 400);
    }

    $subject = new Subject();
    $subjectData = $subject->getById($id);

    if (!$subjectData) {
        ApiResponse::error('Subject not found', 404);
    }

    ApiResponse::success($subjectData);
}

if ($method === 'POST' && $action === 'create') {
    Auth::requireRole(['admin', 'registrar']);
    $data = json_decode(file_get_contents('php://input'), true);

    $errors = [];
    if (empty($data['code'])) $errors['code'] = 'Subject code is required';
    if (empty($data['name'])) $errors['name'] = 'Subject name is required';
    if (empty($data['units'])) $errors['units'] = 'Units are required';
    if (empty($data['semester'])) $errors['semester'] = 'Semester is required';
    if (empty($data['academic_year'])) $errors['academic_year'] = 'Academic year is required';

    if (!empty($errors)) {
        ApiResponse::error('Validation failed', 400, $errors);
    }

    $subject = new Subject();
    $result = $subject->create($data);

    if ($result['success']) {
        ApiResponse::success(['id' => $result['id']], 'Subject created successfully');
    } else {
        ApiResponse::error('Failed to create subject');
    }
}

ApiResponse::error('Invalid action', 400);
?>
