<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

Auth::requireAuth();
$user = Auth::getCurrentUser();
$isAdmin = (($user['role'] ?? '') === 'admin');

$passwordError = '';
$passwordSuccess = '';
$activeTab = $_GET['tab'] ?? 'profile';
if (!in_array($activeTab, ['profile', 'password', 'activity'], true)) {
    $activeTab = 'profile';
}
if (!$isAdmin && $activeTab === 'activity') {
    $activeTab = 'profile';
}
$activityPage = max(1, (int)($_GET['activity_page'] ?? 1));
$activityPerPage = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $activeTab = 'password';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $passwordError = 'All password fields are required.';
    } elseif (strlen($newPassword) < 8) {
        $passwordError = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = 'New password and confirmation do not match.';
    } elseif ($currentPassword === $newPassword) {
        $passwordError = 'New password must be different from current password.';
    } else {
        $userModel = new User();
        $change = $userModel->changePassword($user['id'], $currentPassword, $newPassword);
        if ($change['success']) {
            ActivityLog::log($user['id'], 'PASSWORD_CHANGE', 'User changed password from profile page');
            $passwordSuccess = 'Password updated successfully.';
        } else {
            $passwordError = $change['error'] ?? 'Failed to update password.';
        }
    }
}

$totalActivityPages = 1;
if ($isAdmin) {
    $totalActivityCount = ActivityLog::countAll();
    $totalActivityPages = max(1, (int)ceil($totalActivityCount / $activityPerPage));
    if ($activityPage > $totalActivityPages) {
        $activityPage = $totalActivityPages;
    }
    $activityOffset = ($activityPage - 1) * $activityPerPage;
    $recentActivities = ActivityLog::getRecentPaginated($activityPerPage, $activityOffset);
} else {
    $recentActivities = ActivityLog::getUserActivities($user['id'], 12);
}
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <div class="mx-auto" style="max-width: 1180px;">
    <div class="row g-4">
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="list-group list-group-flush">
                    <a href="./profile?tab=profile" class="list-group-item list-group-item-action <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                    <a href="./profile?tab=password" class="list-group-item list-group-item-action <?php echo $activeTab === 'password' ? 'active' : ''; ?>">
                        <i class="fas fa-key me-2"></i> Update Password
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="./profile?tab=activity" class="list-group-item list-group-item-action <?php echo $activeTab === 'activity' ? 'active' : ''; ?>">
                        <i class="fas fa-clock-rotate-left me-2"></i> Activity Logs
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <?php if ($activeTab === 'profile'): ?>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="d-flex align-items-center justify-content-center rounded-circle"
                             style="width:72px; height:72px; background:#0d6efd; color:#fff; font-size:1.4rem; font-weight:700;">
                            <?php
                            $name = $user['name'] ?? 'User';
                            $initials = implode('', array_map(fn($n) => $n[0], explode(' ', $name)));
                            echo htmlspecialchars(substr($initials, 0, 2));
                            ?>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h3>
                            <div class="text-muted"><?php echo htmlspecialchars(ucfirst($user['role'] ?? '')); ?></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Full Name</label>
                            <div class="form-control bg-light"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Email</label>
                            <div class="form-control bg-light"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted mb-1">Role</label>
                            <div class="form-control bg-light"><?php echo htmlspecialchars(ucfirst($user['role'] ?? '')); ?></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-muted mb-1">Student ID</label>
                            <div class="form-control bg-light"><?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Department</label>
                            <div class="form-control bg-light"><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($activeTab === 'password'): ?>
                    <h4 class="mb-3">Update Password</h4>

                    <?php if ($passwordError): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3" role="alert"><?php echo htmlspecialchars($passwordError); ?></div>
                    <?php endif; ?>
                    <?php if ($passwordSuccess): ?>
                    <div class="alert alert-success py-2 px-3 mb-3" role="alert"><?php echo htmlspecialchars($passwordSuccess); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="d-grid gap-3">
                        <input type="hidden" name="action" value="update_password">
                        <div>
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div>
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" required>
                        </div>
                        <div>
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($activeTab === 'activity' && $isAdmin): ?>
                    <h4 class="mb-3">Activity Logs</h4>
                    <?php if (empty($recentActivities)): ?>
                    <p class="text-muted mb-0">No activity logs yet.</p>
                    <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="border rounded p-3 bg-light">
                            <div class="fw-semibold mb-1"><?php echo htmlspecialchars(str_replace('_', ' ', $activity['action'])); ?></div>
                            <div class="text-muted small mb-1">By: <?php echo htmlspecialchars($activity['user_name'] ?? $user['name']); ?></div>
                            <?php if (!empty($activity['details'])): ?>
                            <div class="text-muted small mb-1"><?php echo htmlspecialchars($activity['details']); ?></div>
                            <?php endif; ?>
                            <div class="text-muted small">
                                <?php echo htmlspecialchars($activity['created_at']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalActivityPages > 1): ?>
                    <div class="d-flex align-items-center gap-2 mt-4 flex-wrap">
                        <?php if ($activityPage > 1): ?>
                            <a href="./profile?tab=activity&activity_page=<?php echo $activityPage - 1; ?>" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: #f1f3f5; border: 0;">
                                <i class="fas fa-chevron-left text-muted"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalActivityPages; $p++): ?>
                            <?php if ($p === $activityPage): ?>
                                <span class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center fw-semibold" style="width: 42px; height: 42px; background: #f8d479; color: #1f2937; border: 0;"><?php echo $p; ?></span>
                            <?php else: ?>
                                <a href="./profile?tab=activity&activity_page=<?php echo $p; ?>" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center fw-semibold text-muted" style="width: 42px; height: 42px; background: #f1f3f5; border: 0;"><?php echo $p; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($activityPage < $totalActivityPages): ?>
                            <a href="./profile?tab=activity&activity_page=<?php echo $activityPage + 1; ?>" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: #f1f3f5; border: 0;">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Profile - Grade Hub';
include '../views/base.php';
?>
