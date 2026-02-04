<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireAuth();
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <!-- Welcome Banner -->
    <div class="card bg-primary text-white mb-4" style="background: linear-gradient(135deg, #2d3b6f 0%, #1f2844 100%);">
        <div class="card-body">
            <h3 class="card-title mb-2">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h3>
            <p class="card-text mb-0">
                <?php 
                $messages = [
                    'admin' => 'Full system access for management and oversight.',
                    'registrar' => 'Manage grade approvals and academic records.',
                    'faculty' => 'Enter and monitor your course grades.',
                    'student' => 'View your academic grades and performance.'
                ];
                echo $messages[$user['role']] ?? 'Welcome to GradeHub.';
                ?>
            </p>
        </div>
    </div>

    <!-- Stats Cards with Icons -->
    <div class="row g-3 mb-4" id="stats">
        <!-- Stats loaded by JS -->
    </div>

    <?php if ($user['role'] === 'student'): ?>
    <div class="row g-3">
        <div class="col-12">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="fas fa-graduation-cap text-primary"></i>
                        <h5 class="mb-0">Academic Summary</h5>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <div class="bg-light rounded-3 p-4 text-center">
                                <div class="h3 mb-1" id="studentGwa">N/A</div>
                                <div class="text-muted small">Current GWA</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="bg-light rounded-3 p-4 text-center">
                                <div class="h3 mb-1" id="studentSubjects">0</div>
                                <div class="text-muted small">Subjects</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="bg-light rounded-3 p-4 text-center">
                                <div class="h3 mb-1" id="studentFinalGrades">0</div>
                                <div class="text-muted small">Final Grades</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="bg-light rounded-3 p-4 text-center">
                                <div class="h3 mb-1" id="studentPending">0</div>
                                <div class="text-muted small">Pending</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted text-center small mt-3">
                        Visit "My Grades" to see detailed grade breakdown for each subject.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($user['role'] === 'faculty'): ?>
    <div class="row g-3">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Recent Activity</h5>
                    <div id="recent-activity" style="max-height: 300px; overflow-y: auto;">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Pending Approvals & Recent Activity -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
                    <i class="fas fa-check-circle text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h5 class="card-title">Pending Approvals</h5>
                    <p id="pendingCount" class="text-muted mb-0">Loading...</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Recent Activity</h5>
                    <div id="recent-activity" style="max-height: 300px; overflow-y: auto;">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const currentRole = "<?php echo htmlspecialchars($user['role']); ?>";
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

function loadDashboardData() {
    // Load dashboard stats
    fetch('/grade-hub-php/public/api/dashboard.php?action=dashboard')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStats(data.data);
                if (currentRole === 'student') {
                    updateStudentSummary(data.data);
                }
            }
        })
        .catch(error => console.error('Error:', error));

    // Load charts
    fetch('/grade-hub-php/public/api/dashboard.php?action=charts')
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                renderGradeDistribution(d.data.gradeDistribution);
                renderSubmissionsChart(d.data.submissions);
            }
        }).catch(e => console.error(e));

    if (currentRole !== 'student') {
        // Load recent activities
        fetch('/grade-hub-php/public/api/dashboard.php?action=recent')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecentActivity(data.data);
                }
            })
            .catch(error => console.error('Error:', error));
    }
}

function displayStats(stats) {
    const statsContainer = document.getElementById('stats');
    statsContainer.innerHTML = '';

    const iconMap = {
        'totalStudents': 'fa-users',
        'totalUsers': 'fa-users',
        'myStudents': 'fa-user-graduate',
        'gradesEncoded': 'fa-file-alt',
        'gradesDraft': 'fa-file-alt',
        'gradesSubmitted': 'fa-check',
        'gradesForApproval': 'fa-clock',
        'pendingApprovals': 'fa-clock',
        'gradesApproved': 'fa-check-circle',
        'approvedGrades': 'fa-check-circle',
        'totalGrades': 'fa-file-alt',
        'totalGradesApproved': 'fa-check-circle',
        'correctionRequests': 'fa-hourglass',
        'pendingCorrections': 'fa-hourglass',
        'totalSubjects': 'fa-book',
        'mySubjects': 'fa-book',
        'enrolledSubjects': 'fa-book-open',
        'currentGwa': 'fa-award',
        'myCorrections': 'fa-rotate',
        'pendingGrades': 'fa-clock',
    };
    
    const colorMap = {
        'totalStudents': '#17a2b8',
        'totalUsers': '#17a2b8',
        'myStudents': '#17a2b8',
        'gradesEncoded': '#0d6efd',
        'gradesDraft': '#0d6efd',
        'gradesSubmitted': '#198754',
        'gradesForApproval': '#fd7e14',
        'pendingApprovals': '#fd7e14',
        'gradesApproved': '#198754',
        'approvedGrades': '#198754',
        'totalGrades': '#0d6efd',
        'totalGradesApproved': '#198754',
        'correctionRequests': '#dc3545',
        'pendingCorrections': '#dc3545',
        'totalSubjects': '#6f42c1',
        'mySubjects': '#6f42c1',
        'enrolledSubjects': '#6f42c1',
        'currentGwa': '#198754',
        'myCorrections': '#dc3545',
    };

    const labels = {
        'totalStudents': 'Total Students',
        'myStudents': 'My Students',
        'gradesEncoded': 'Grades Encoded',
        'pendingApprovals': 'Pending Approvals',
        'gradesApproved': 'Approved Grades',
        'approvedGrades': 'Approved Grades',
        'totalGrades': 'Total Grades',
        'correctionRequests': 'Correction Requests',
        'mySubjects': 'My Subjects',
        'enrolledSubjects': 'Enrolled Subjects',
        'currentGwa': 'Current GWA',
        'myCorrections': 'My Corrections'
    };

    const roleKeys = {
        'admin': ['totalStudents', 'gradesEncoded', 'pendingApprovals', 'gradesApproved', 'correctionRequests'],
        'registrar': ['totalStudents', 'pendingApprovals', 'gradesApproved', 'correctionRequests'],
        'faculty': ['mySubjects', 'myStudents', 'gradesEncoded', 'gradesApproved', 'myCorrections'],
        'student': ['enrolledSubjects', 'totalGrades', 'approvedGrades', 'currentGwa']
    };

    const keys = roleKeys[currentRole] || ['totalStudents', 'gradesEncoded', 'pendingApprovals', 'gradesApproved', 'correctionRequests'];

    keys.forEach(key => {
        let value = stats[key];
        if (key === 'currentGwa') {
            value = value === null || value === undefined ? 'N/A' : value;
        } else {
            value = value || 0;
        }
        const col = document.createElement('div');
        col.className = 'col-6 col-md-3 col-lg-auto flex-grow-1';
        
        const label = labels[key] || key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
        const icon = iconMap[key] || 'fa-circle';
        const color = colorMap[key] || '#6c757d';
        
        col.innerHTML = `
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas ${icon}" style="font-size: 2rem; color: ${color};"></i>
                    </div>
                    <p class="text-muted mb-2" style="font-size: 0.85rem;">${label}</p>
                    <h4 class="mb-0">${value}</h4>
                </div>
            </div>`;
        statsContainer.appendChild(col);
    });
}

function displayRecentActivity(activities) {
    const container = document.getElementById('recent-activity');
    
    if (activities.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">No recent activity</p>';
        return;
    }

    const list = document.createElement('div');
    activities.slice(0, 8).forEach(activity => {
        const item = document.createElement('div');
        item.className = 'd-flex align-items-start gap-2 pb-3 border-bottom';
        item.innerHTML = `
            <i class="fas fa-circle-dot text-muted" style="font-size: 0.5rem; margin-top: 0.5rem;"></i>
            <div style="font-size: 0.9rem;">
                <div class="fw-semibold">${activity.user_name}</div>
                <div class="text-muted small">${activity.action}</div>
                <div class="text-muted" style="font-size: 0.75rem;">${new Date(activity.created_at).toLocaleString()}</div>
            </div>
        `;
        list.appendChild(item);
    });
    container.innerHTML = '';
    container.appendChild(list);
    
    // Update pending approvals count
    fetch('/grade-hub-php/public/api/dashboard.php?action=dashboard')
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const count = d.data.gradesForApproval || d.data.pendingApprovals || 0;
                const el = document.getElementById('pendingCount');
                if (el) el.textContent = count + ' items';
            }
        }).catch(e => console.error(e));
}

function renderGradeDistribution(dist) {
    const labels = ['A (90-100)','B (80-89)','C (70-79)','D (60-69)','F (<60)'];
    const data = [parseInt(dist.A||0), parseInt(dist.B||0), parseInt(dist.C||0), parseInt(dist.D||0), parseInt(dist.F||0)];
    const ctx = document.getElementById('gradeDistChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: ['#0d6efd','#198754','#fd7e14','#ffc107','#dc3545'] }]
        },
        options: { responsive: true }
    });
}

function renderSubmissionsChart(rows) {
    const labels = [];
    const dataPoints = [];
    // build last 7 days labels
    for (let i=6;i>=0;i--) {
        const d = new Date();
        d.setDate(d.getDate()-i);
        labels.push(d.toISOString().slice(0,10));
        dataPoints.push(0);
    }
    rows.forEach(r => {
        const idx = labels.indexOf(r.d);
        if (idx !== -1) dataPoints[idx] = parseInt(r.cnt);
    });
    const ctx = document.getElementById('submissionsChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{ label: 'Submissions', data: dataPoints, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', tension: 0.3 }]
        },
        options: { responsive: true }
    });
}

function updateStudentSummary(data) {
    const gwa = data.currentGwa === null || data.currentGwa === undefined ? 'N/A' : data.currentGwa;
    const subjects = data.enrolledSubjects || 0;
    const finalGrades = data.approvedGrades || 0;
    const pending = data.gradesPending || data.pendingApprovals || 0;

    const gwaEl = document.getElementById('studentGwa');
    const subjEl = document.getElementById('studentSubjects');
    const finalEl = document.getElementById('studentFinalGrades');
    const pendingEl = document.getElementById('studentPending');
    if (gwaEl) gwaEl.textContent = gwa;
    if (subjEl) subjEl.textContent = subjects;
    if (finalEl) finalEl.textContent = finalGrades;
    if (pendingEl) pendingEl.textContent = pending;
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard - Grade Hub';
include '../views/base.php';
?>
