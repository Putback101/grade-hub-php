<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Subject.php';
require_once __DIR__ . '/../app/models/Enrollment.php';

$seeds = require __DIR__ . '/seeders.php';
$users = $seeds['users'] ?? [];
$subjects = $seeds['subjects'] ?? [];

$u = new User();
$subjectModel = new Subject();
$enrollmentModel = new Enrollment();
$conn = Database::getInstance()->getConnection();

function generateUUIDv4() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

foreach ($users as $user) {
    $email = $user['email'];
    $existing = $u->getByEmail($email);
    if ($existing) {
        echo "Skipping existing user: {$email}\n";
        continue;
    }

    $payload = [
        'email' => $email,
        'name' => $user['name'],
        'role' => $user['role'] ?? 'student',
        'password' => $user['password'],
        'department' => $user['department'] ?? null,
        'student_id' => $user['student_id'] ?? null
    ];

    $res = $u->register($payload);
    if ($res['success']) {
        echo "Inserted user: {$email} (id: {$res['user_id']})\n";
    } else {
        echo "Failed to insert {$email}: " . ($res['error'] ?? 'unknown') . "\n";
    }
}

// Seed subjects (assign faculty round-robin if available)
$facultyIds = [];
$facultyRes = $conn->query("SELECT id FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY name ASC");
if ($facultyRes) {
    while ($row = $facultyRes->fetch_assoc()) {
        $facultyIds[] = $row['id'];
    }
}

$facultyCount = count($facultyIds);
$idx = 0;
foreach ($subjects as $subject) {
    $code = $subject['code'] ?? '';
    if ($code === '') {
        continue;
    }

    $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE code = ? LIMIT 1");
    $checkStmt->bind_param('s', $code);
    $checkStmt->execute();
    $existingSubject = $checkStmt->get_result()->fetch_assoc();

    if ($existingSubject) {
        echo "Skipping existing subject: {$code}\n";
        continue;
    }

    $facultyId = $facultyCount > 0 ? $facultyIds[$idx % $facultyCount] : null;
    $idx++;

    $subjectPayload = [
        'code' => $subject['code'],
        'name' => $subject['name'],
        'units' => (int) ($subject['units'] ?? 3),
        'semester' => $subject['semester'] ?? '1st',
        'academic_year' => $subject['academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
        'faculty_id' => $facultyId
    ];

    $res = $subjectModel->create($subjectPayload);
    if ($res['success']) {
        echo "Inserted subject: {$code} (id: {$res['id']})\n";
    } else {
        echo "Failed to insert subject {$code}: " . ($res['error'] ?? 'unknown') . "\n";
    }
}

// Ensure student enrollments exist for all subjects
$students = [];
$studentRes = $conn->query("SELECT id FROM users WHERE role = 'student' AND is_active = 1");
if ($studentRes) {
    while ($row = $studentRes->fetch_assoc()) {
        $students[] = $row['id'];
    }
}

$subjectIds = [];
$subjectRes = $conn->query("SELECT id FROM subjects");
if ($subjectRes) {
    while ($row = $subjectRes->fetch_assoc()) {
        $subjectIds[] = $row['id'];
    }
}

foreach ($students as $studentId) {
    foreach ($subjectIds as $subjectId) {
        $existsStmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND subject_id = ? LIMIT 1");
        $existsStmt->bind_param('ss', $studentId, $subjectId);
        $existsStmt->execute();
        $exists = $existsStmt->get_result()->fetch_assoc();

        if ($exists) {
            continue;
        }

        if ($enrollmentModel->enroll($studentId, $subjectId)) {
            echo "Enrolled student {$studentId} to subject {$subjectId}\n";
        } else {
            echo "Failed enrollment for student {$studentId} and subject {$subjectId}\n";
        }
    }
}

// Seed example grade entries for existing enrollments
$encoderId = null;
$approverId = null;

$encoderRes = $conn->query("SELECT id FROM users WHERE role = 'faculty' AND is_active = 1 ORDER BY name ASC LIMIT 1");
if ($encoderRes && $encoderRes->num_rows > 0) {
    $encoderId = $encoderRes->fetch_assoc()['id'];
}

$approverRes = $conn->query("SELECT id FROM users WHERE role = 'registrar' AND is_active = 1 ORDER BY name ASC LIMIT 1");
if ($approverRes && $approverRes->num_rows > 0) {
    $approverId = $approverRes->fetch_assoc()['id'];
}

$enrollmentRows = [];
$enrollmentRes = $conn->query("SELECT id FROM enrollments ORDER BY enrolled_at ASC");
if ($enrollmentRes) {
    while ($row = $enrollmentRes->fetch_assoc()) {
        $enrollmentRows[] = $row;
    }
}

$i = 0;
foreach ($enrollmentRows as $enrollment) {
    $enrollmentId = $enrollment['id'];
    $checkStmt = $conn->prepare("SELECT id FROM grade_entries WHERE enrollment_id = ? LIMIT 1");
    $checkStmt->bind_param('s', $enrollmentId);
    $checkStmt->execute();
    $existingGrade = $checkStmt->get_result()->fetch_assoc();

    if ($existingGrade) {
        continue;
    }

    $prelim = mt_rand(75, 95);
    $midterm = mt_rand(75, 95);
    $final = mt_rand(75, 95);
    $computed = round(($prelim + $midterm + $final) / 3, 2);

    // Rotate statuses for realistic sample data.
    $status = 'draft';
    $submittedAt = null;
    $approvedAt = null;
    $approvedBy = null;

    if ($i % 3 === 1) {
        $status = 'submitted';
        $submittedAt = date('Y-m-d H:i:s', time() - mt_rand(3600, 172800));
    } elseif ($i % 3 === 2) {
        $status = 'approved';
        $submittedAt = date('Y-m-d H:i:s', time() - mt_rand(86400, 259200));
        $approvedAt = date('Y-m-d H:i:s', time() - mt_rand(1800, 86400));
        $approvedBy = $approverId;
    }

    $gradeId = generateUUIDv4();
    $insert = $conn->prepare(
        "INSERT INTO grade_entries
         (id, enrollment_id, prelim_grade, midterm_grade, final_grade, computed_grade, status, encoded_by, submitted_at, approved_at, approved_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insert->bind_param(
        'ssddddsssss',
        $gradeId,
        $enrollmentId,
        $prelim,
        $midterm,
        $final,
        $computed,
        $status,
        $encoderId,
        $submittedAt,
        $approvedAt,
        $approvedBy
    );

    if ($insert->execute()) {
        echo "Inserted sample grade entry for enrollment {$enrollmentId}\n";
    } else {
        echo "Failed sample grade entry for enrollment {$enrollmentId}: {$insert->error}\n";
    }

    $i++;
}

echo "Done.\n";
