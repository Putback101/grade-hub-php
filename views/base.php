<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

// Allow public access to login page
if (basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'register.php') {
    Auth::requireAuth();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'GradeHub'; ?></title>
    <link rel="stylesheet" href="./assets/css/tailwind.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-background text-foreground">
    <div class="d-flex vh-100 overflow-hidden">
        <!-- Sidebar -->
        <?php if (Auth::isAuthenticated()): ?>
        <aside class="bg-dark text-light border-end d-flex flex-column flex-shrink-0 overflow-auto" style="width: 260px; background-color: #1a1f3a;">
            <!-- Sidebar Header -->
            <div class="p-4 border-bottom d-flex align-items-center gap-3" style="border-bottom-color: rgba(255,255,255,0.1);">
                <div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded" style="width:45px; height:45px; background: linear-gradient(135deg, #20c997 0%, #198754 100%);">
                    <i class="fas fa-graduation-cap text-white" style="font-size: 1.3rem;"></i>
                </div>
                <div class="overflow-hidden">
                    <h1 class="fw-bold mb-0 text-white" style="font-size:1rem; letter-spacing: -0.3px;">GradeHub</h1>
                    <p class="text-light opacity-70 mb-0" style="font-size:0.7rem; font-weight: 400;">Assessment System</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow-1 p-3 overflow-y-auto">
                <?php 
                $user = Auth::getCurrentUser();
                $menuItems = [];
                
                if ($user['role'] === 'student') {
                    $menuItems = [
                        ['url' => './dashboard', 'icon' => 'fa-home', 'label' => 'Dashboard'],
                        ['url' => './grades', 'icon' => 'fa-eye', 'label' => 'My Grades'],
                        ['url' => './subjects', 'icon' => 'fa-book', 'label' => 'Subjects'],
                    ];
                } else if ($user['role'] === 'faculty') {
                    $menuItems = [
                        ['url' => './dashboard', 'icon' => 'fa-home', 'label' => 'Dashboard'],
                        ['url' => './grade-encoding', 'icon' => 'fa-edit', 'label' => 'Grade Encoding'],
                        ['url' => './subjects', 'icon' => 'fa-book', 'label' => 'Subjects'],
                        ['url' => './grade-corrections', 'icon' => 'fa-file-alt', 'label' => 'Corrections'],
                    ];
                } else if ($user['role'] === 'registrar') {
                    $menuItems = [
                        ['url' => './dashboard', 'icon' => 'fa-home', 'label' => 'Dashboard'],
                        ['url' => './grade-verification', 'icon' => 'fa-check-circle', 'label' => 'Verification'],
                        ['url' => './grade-corrections', 'icon' => 'fa-file-alt', 'label' => 'Corrections'],
                        ['url' => './reports', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
                    ];
                } else if ($user['role'] === 'admin') {
                    $menuItems = [
                        ['url' => './dashboard', 'icon' => 'fa-home', 'label' => 'Dashboard'],
                        ['url' => './grade-encoding', 'icon' => 'fa-edit', 'label' => 'Grade Encoding'],
                        ['url' => './grade-verification', 'icon' => 'fa-check-circle', 'label' => 'Verification'],
                        ['url' => './grades', 'icon' => 'fa-eye', 'label' => 'My Grades'],
                        ['url' => './grade-corrections', 'icon' => 'fa-file-alt', 'label' => 'Corrections'],
                        ['url' => './reports', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
                        ['url' => './activity-logs', 'icon' => 'fa-users', 'label' => 'Activity Logs'],
                    ];
                }
                
                // Determine current page
                $currentPage = basename($_SERVER['PHP_SELF'], '.php');
                
                foreach ($menuItems as $item):
                    $isActive = (strpos($item['url'], $currentPage) !== false) || 
                               ($currentPage === 'index' && $item['url'] === './dashboard');
                ?>
                <a href="<?php echo $item['url']; ?>" class="nav-link d-flex align-items-center gap-3 px-3 py-2 text-light rounded-3 mb-2 <?php echo $isActive ? 'bg-success' : ''; ?>" style="transition: all 0.2s; font-size: 0.95rem; font-weight: 500; <?php echo !$isActive ? 'color: rgba(255,255,255,0.85);' : ''; ?>">
                    <i class="fas <?php echo $item['icon']; ?>" style="width:18px; text-align: center; font-size: 1.05rem;"></i>
                    <span><?php echo $item['label']; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="d-flex flex-column flex-grow-1 overflow-hidden">
            <!-- Header -->
            <?php if (Auth::isAuthenticated()): ?>
            <header class="border-bottom bg-light px-4 d-flex align-items-center justify-content-between flex-shrink-0" style="height: 64px;">
                <div>
                    <?php
                        $pageMeta = [
                            'dashboard' => ['title' => 'Dashboard', 'subtitle' => 'Overview of system activity and stats'],
                            'grade-encoding' => ['title' => 'Grade Encoding', 'subtitle' => 'Enter and manage student grades'],
                            'grade-verification' => ['title' => 'Grade Verification', 'subtitle' => 'Review and approve submitted grades'],
                            'grades' => ['title' => 'Grade Management', 'subtitle' => 'View and analyze all student grades'],
                            'grade-corrections' => ['title' => 'Grade Corrections', 'subtitle' => 'Request and manage grade corrections'],
                            'reports' => ['title' => 'Reports', 'subtitle' => 'Academic performance insights and summaries'],
                            'activity-logs' => ['title' => 'Activity Logs', 'subtitle' => 'Track recent system actions'],
                            'subjects' => ['title' => 'Subjects', 'subtitle' => 'Browse and manage subject offerings'],
                        ];
                        $meta = $pageMeta[$currentPage] ?? null;
                    ?>
                    <?php if ($meta): ?>
                        <div class="d-flex flex-column">
                            <h1 class="h5 mb-0"><?php echo $meta['title']; ?></h1>
                            <span class="text-muted" style="font-size: 0.8rem;"><?php echo $meta['subtitle']; ?></span>
                        </div>
                    <?php else: ?>
                        <h1 class="h5 mb-0"><?php echo $pageTitle ?? 'GradeHub'; ?></h1>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- User Menu -->
                    <div class="dropdown">
                        <button class="btn btn-light border-0 d-flex align-items-center gap-2" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:32px; height:32px; background-color:#0d6efd; color:white; font-size:0.85rem; font-weight:bold;">
                                <?php 
                                $name = Auth::getCurrentUser()['name'] ?? 'U';
                                $initials = implode('', array_map(fn($n) => $n[0], explode(' ', $name)));
                                echo substr($initials, 0, 2);
                                ?>
                            </div>
                            <div class="d-none d-md-block">
                                <div style="font-size:0.9rem; font-weight:500;"><?php echo Auth::getCurrentUser()['name']; ?></div>
                                <div style="font-size:0.75rem; color:#6c757d;"><?php echo ucfirst(Auth::getCurrentUser()['role']); ?></div>
                            </div>
                        </button>

                        <!-- Dropdown Menu -->
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <form method="POST" action="/grade-hub-php/public/logout.php" style="margin:0;">
                                    <button type="submit" class="dropdown-item" style="border:none; background:none; width:100%; text-align:left; cursor:pointer; padding:0.5rem 1rem;">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            <?php endif; ?>

            <!-- Page Content -->
            <main class="flex-grow-1 overflow-y-auto bg-light">
                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="./assets/js/main.js"></script>
    <script src="./assets/js/api.js"></script>
    <script src="./assets/js/ui.js"></script>
</body>
</html>
