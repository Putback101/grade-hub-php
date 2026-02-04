<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/ApiResponse.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';
require_once __DIR__ . '/../../app/models/GradeEntry.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

// Check authentication for API - return JSON error instead of redirecting
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    ApiResponse::error('Unauthorized');
    exit;
}

$action = $_GET['action'] ?? '';
$user = Auth::getCurrentUser();

if ($action === 'recent') {
    if ($user['role'] === 'faculty') {
        $activities = ActivityLog::getUserActivitiesFiltered($user['id'], 20, ['LOGIN', 'LOGOUT']);
    } else {
        $activities = ActivityLog::getRecent(20);
    }
    ApiResponse::success($activities);
}

if ($action === 'dashboard') {
    $conn = Database::getInstance()->getConnection();

    // Get stats based on user role
    $stats = [];

    if ($user['role'] === 'student') {
        // Student stats
        $result = $conn->query(
            "SELECT COUNT(*) as total FROM enrollments WHERE student_id = '{$user['id']}'"
        );
        $stats['totalSubjects'] = $result->fetch_assoc()['total'];
        $stats['mySubjects'] = $stats['totalSubjects'];
        $stats['enrolledSubjects'] = $stats['totalSubjects'];

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             WHERE e.student_id = '{$user['id']}' AND ge.status = 'approved'"
        );
        $stats['gradesApproved'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             WHERE e.student_id = '{$user['id']}' AND ge.status = 'pending_approval'"
        );
        $stats['gradesPending'] = $result->fetch_assoc()['total'];

        // Dashboard 5-card stats
        $stats['totalStudents'] = 1;

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             WHERE e.student_id = '{$user['id']}'"
        );
        $stats['gradesEncoded'] = $result->fetch_assoc()['total'];
        $stats['totalGrades'] = $stats['gradesEncoded'];

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             WHERE e.student_id = '{$user['id']}' AND ge.status IN ('submitted','pending_approval')"
        );
        $stats['pendingApprovals'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             WHERE e.student_id = '{$user['id']}' AND ge.status = 'approved'"
        );
        $stats['gradesApproved'] = $result->fetch_assoc()['total'];
        $stats['approvedGrades'] = $stats['gradesApproved'];
        $stats['approvedGrades'] = $stats['gradesApproved'];

        $result = $conn->query(
            "SELECT AVG(ge.computed_grade) as avg_grade FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             WHERE e.student_id = '{$user['id']}' AND ge.status = 'approved' AND ge.computed_grade IS NOT NULL"
        );
        $avg = $result->fetch_assoc()['avg_grade'] ?? null;
        $stats['currentGwa'] = $avg !== null ? round($avg, 2) : null;
    } else if ($user['role'] === 'faculty') {
        // Faculty stats
        $result = $conn->query(
            "SELECT COUNT(*) as total FROM subjects WHERE faculty_id = '{$user['id']}'"
        );
        $stats['totalSubjects'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             JOIN subjects s ON e.subject_id = s.id 
             WHERE s.faculty_id = '{$user['id']}' AND ge.status = 'draft'"
        );
        $stats['gradesDraft'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             JOIN subjects s ON e.subject_id = s.id 
             WHERE s.faculty_id = '{$user['id']}' AND ge.status = 'submitted'"
        );
        $stats['gradesSubmitted'] = $result->fetch_assoc()['total'];

        // Dashboard 5-card stats
        $result = $conn->query(
            "SELECT COUNT(DISTINCT e.student_id) as total
             FROM enrollments e 
             JOIN subjects s ON e.subject_id = s.id 
             WHERE s.faculty_id = '{$user['id']}'"
        );
        $stats['totalStudents'] = $result->fetch_assoc()['total'];
        $stats['myStudents'] = $stats['totalStudents'];

        $result = $conn->query(
            "SELECT COUNT(*) as total 
             FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             JOIN subjects s ON e.subject_id = s.id 
             WHERE s.faculty_id = '{$user['id']}'"
        );
        $stats['gradesEncoded'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total 
             FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             JOIN subjects s ON e.subject_id = s.id 
             WHERE s.faculty_id = '{$user['id']}' AND ge.status IN ('submitted','pending_approval')"
        );
        $stats['pendingApprovals'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total 
             FROM grade_entries ge 
             JOIN enrollments e ON ge.enrollment_id = e.id 
             JOIN subjects s ON e.subject_id = s.id 
             WHERE s.faculty_id = '{$user['id']}' AND ge.status = 'approved'"
        );
        $stats['gradesApproved'] = $result->fetch_assoc()['total'];

        $result = $conn->query(
            "SELECT COUNT(*) as total 
             FROM grade_corrections gc
             JOIN grade_entries ge ON gc.grade_entry_id = ge.id
             JOIN enrollments e ON ge.enrollment_id = e.id
             JOIN subjects s ON e.subject_id = s.id
             WHERE s.faculty_id = '{$user['id']}' AND gc.status = 'pending'"
        );
        $stats['correctionRequests'] = $result->fetch_assoc()['total'];
        $stats['myCorrections'] = $stats['correctionRequests'];
    } else if ($user['role'] === 'registrar') {
        // Registrar stats
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
        $stats['totalStudents'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status = 'submitted'");
        $stats['gradesForApproval'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_corrections WHERE status = 'pending'");
        $stats['correctionRequests'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status = 'approved'");
        $stats['gradesApproved'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries");
        $stats['gradesEncoded'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status IN ('submitted','pending_approval')");
        $stats['pendingApprovals'] = $result->fetch_assoc()['total'];
    } else if ($user['role'] === 'admin') {
        // Admin stats
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        $stats['totalUsers'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status = 'approved'");
        $stats['totalGradesApproved'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM subjects");
        $stats['totalSubjects'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_corrections WHERE status = 'pending'");
        $stats['pendingCorrections'] = $result->fetch_assoc()['total'];

        // Dashboard 5-card stats
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
        $stats['totalStudents'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries");
        $stats['gradesEncoded'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status IN ('submitted','pending_approval')");
        $stats['pendingApprovals'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status = 'approved'");
        $stats['gradesApproved'] = $result->fetch_assoc()['total'];

        $result = $conn->query("SELECT COUNT(*) as total FROM grade_corrections WHERE status = 'pending'");
        $stats['correctionRequests'] = $result->fetch_assoc()['total'];
    }

    ApiResponse::success($stats);
}

if ($action === 'charts') {
    $conn = Database::getInstance()->getConnection();

    // Grade distribution (approved grades) by bucket
    $gradeDistQuery = "SELECT 
        SUM(CASE WHEN computed_grade >= 90 THEN 1 ELSE 0 END) AS A,
        SUM(CASE WHEN computed_grade >= 80 AND computed_grade < 90 THEN 1 ELSE 0 END) AS B,
        SUM(CASE WHEN computed_grade >= 70 AND computed_grade < 80 THEN 1 ELSE 0 END) AS C,
        SUM(CASE WHEN computed_grade >= 60 AND computed_grade < 70 THEN 1 ELSE 0 END) AS D,
        SUM(CASE WHEN computed_grade < 60 THEN 1 ELSE 0 END) AS F
        FROM grade_entries
        WHERE status = 'approved'
    ";
    $res = $conn->query($gradeDistQuery);
    $dist = $res ? $res->fetch_assoc() : ['A'=>0,'B'=>0,'C'=>0,'D'=>0,'F'=>0];

    // Submissions over last 7 days
    $subQuery = "SELECT DATE(submitted_at) as d, COUNT(*) as cnt
        FROM grade_entries
        WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(submitted_at)
        ORDER BY DATE(submitted_at) ASC";
    $res2 = $conn->query($subQuery);
    $subs = [];
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            $subs[] = $row;
        }
    }

    ApiResponse::success([
        'gradeDistribution' => $dist,
        'submissions' => $subs
    ]);
}

if ($action === 'reports') {
    Auth::requireRole(['registrar', 'admin']);
    $conn = Database::getInstance()->getConnection();

    $reports = [];
    
    // Total students
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $reports['totalStudents'] = $result->fetch_assoc()['total'] ?? 0;

    // Total subjects
    $result = $conn->query("SELECT COUNT(*) as total FROM subjects");
    $reports['totalSubjects'] = $result->fetch_assoc()['total'] ?? 0;

    // Total grades (approved)
    $result = $conn->query("SELECT COUNT(*) as total FROM grade_entries WHERE status = 'approved'");
    $reports['totalGradesApproved'] = $result->fetch_assoc()['total'] ?? 0;

    // Pass/Fail counts (grade >= 60 is pass)
    $result = $conn->query(
        "SELECT 
            SUM(CASE WHEN computed_grade >= 60 AND status = 'approved' THEN 1 ELSE 0 END) as pass_count,
            SUM(CASE WHEN computed_grade < 60 AND status = 'approved' THEN 1 ELSE 0 END) as fail_count
         FROM grade_entries"
    );
    $passFailData = $result->fetch_assoc();
    $reports['passCount'] = $passFailData['pass_count'] ?? 0;
    $reports['failCount'] = $passFailData['fail_count'] ?? 0;

    // Average grade
    $result = $conn->query(
        "SELECT AVG(computed_grade) as avg_grade FROM grade_entries WHERE status = 'approved' AND computed_grade IS NOT NULL"
    );
    $avgData = $result->fetch_assoc();
    $reports['averageGrade'] = round($avgData['avg_grade'] ?? 0, 2);

    // Subjects with their pass rates
    $result = $conn->query(
        "SELECT 
            s.id, s.code, s.name,
            COUNT(ge.id) as total_grades,
            SUM(CASE WHEN ge.computed_grade >= 60 AND ge.status = 'approved' THEN 1 ELSE 0 END) as pass_count,
            AVG(CASE WHEN ge.status = 'approved' THEN ge.computed_grade ELSE NULL END) as avg_grade
         FROM subjects s
         LEFT JOIN enrollments e ON s.id = e.subject_id
         LEFT JOIN grade_entries ge ON e.id = ge.enrollment_id
         GROUP BY s.id, s.code, s.name
         ORDER BY s.code ASC"
    );
    
    $subjectPerformance = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $total = $row['total_grades'] ?? 0;
            $passRate = $total > 0 ? round(($row['pass_count'] / $total) * 100, 1) : 0;
            $subjectPerformance[] = [
                'code' => $row['code'],
                'name' => $row['name'],
                'total_grades' => $total,
                'pass_count' => $row['pass_count'] ?? 0,
                'pass_rate' => $passRate,
                'average_grade' => round($row['avg_grade'] ?? 0, 2)
            ];
        }
    }
    $reports['subjectPerformance'] = $subjectPerformance;

    // Grade distribution
    $result = $conn->query(
        "SELECT 
            SUM(CASE WHEN computed_grade >= 90 THEN 1 ELSE 0 END) AS A,
            SUM(CASE WHEN computed_grade >= 80 AND computed_grade < 90 THEN 1 ELSE 0 END) AS B,
            SUM(CASE WHEN computed_grade >= 70 AND computed_grade < 80 THEN 1 ELSE 0 END) AS C,
            SUM(CASE WHEN computed_grade >= 60 AND computed_grade < 70 THEN 1 ELSE 0 END) AS D,
            SUM(CASE WHEN computed_grade < 60 THEN 1 ELSE 0 END) AS F
         FROM grade_entries
         WHERE status = 'approved'"
    );
    $dist = $result->fetch_assoc();
    $reports['gradeDistribution'] = $dist;

    ApiResponse::success($reports);
}

ApiResponse::error('Invalid action', 400);
?>
