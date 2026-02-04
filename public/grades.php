<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireAuth();
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <?php if ($user['role'] === 'student'): ?>
    <div class="card text-white mb-4" style="background: linear-gradient(135deg, #1f2a4a 0%, #2c3a63 100%); border-radius: 16px;">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;background: rgba(255,255,255,0.15);">
                <i class="fas fa-user-graduate" style="font-size: 1.5rem;"></i>
            </div>
            <div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user['name'] ?? 'Student'); ?></h4>
                <div class="text-white-50 small">Student ID: <?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" id="studentSummary">
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">GPA</div>
                            <div class="h3 mb-0" id="studentGpa">0.00</div>
                            <div class="text-muted small">Current semester</div>
                        </div>
                        <i class="fas fa-award text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Units</div>
                            <div class="h3 mb-0" id="studentUnits">0</div>
                            <div class="text-muted small">Enrolled units</div>
                        </div>
                        <i class="fas fa-book-open text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Subjects</div>
                            <div class="h3 mb-0" id="studentSubjectsWithFinal">0</div>
                            <div class="text-muted small">With final grades</div>
                        </div>
                        <i class="fas fa-list-check text-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Status</div>
                            <div class="h5 mb-1" id="studentStatus">Regular</div>
                            <div class="text-muted small">Academic standing</div>
                        </div>
                        <span id="studentStatusDot" class="rounded-circle" style="width:10px;height:10px;background:#22c55e;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Students</p>
                    <h3 class="h2 mb-0" id="totalStudents">0</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Total Entries</p>
                    <h3 class="h2 mb-0" id="totalEntries">0</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Approved</p>
                    <h3 class="h2 mb-0 text-success" id="approvedCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Pending</p>
                    <h3 class="h2 mb-0 text-warning" id="pendingCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Avg. Grade</p>
                    <h3 class="h2 mb-0" id="avgGrade">0.00</h3>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'admin'): ?>
    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Grade Distribution</h5>
                    <canvas id="gradesDistChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Pass/Fail Ratio</h5>
                    <div class="d-flex justify-content-center">
                        <canvas id="passFailChartSmall" style="max-width:260px; max-height:260px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grades Table with Filters -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><?php echo $user['role'] === 'student' ? 'Grade Report' : 'All Student Grades'; ?></h5>
                <button onclick="exportGrades()" class="btn btn-sm btn-light border">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
            <div class="row g-3 align-items-center mb-3">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input id="searchGrades" type="text" class="form-control border-0 bg-light" placeholder="Search by student name or subject...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select id="subjectFilter" class="form-select border-0 bg-light">
                        <option value="">All Subjects</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select id="statusFilter" class="form-select border-0 bg-light">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="pending_approval">Pending Approval</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Final</th>
                            <th>Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTable">
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-book-open text-muted" style="font-size:2.5rem; opacity:0.3;"></i>
                                <p class="text-muted mt-3"><?php echo $user['role'] === 'student' ? 'No grades available yet' : 'No grades found'; ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let gradesDistChart = null;
let passFailChartSmall = null;
const showCharts = "<?php echo htmlspecialchars($user['role']); ?>" === 'admin';
const isStudent = "<?php echo htmlspecialchars($user['role']); ?>" === 'student';

document.addEventListener('DOMContentLoaded', function() {
    loadGradesData();
    loadSubjectsFilter();

    document.getElementById('searchGrades').addEventListener('keyup', debounce(loadGradesData, 300));
    document.getElementById('statusFilter').addEventListener('change', loadGradesData);
    document.getElementById('subjectFilter').addEventListener('change', loadGradesData);
});

function loadGradesData() {
    const search = document.getElementById('searchGrades').value;
    const status = document.getElementById('statusFilter').value;
    const subject = document.getElementById('subjectFilter').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (subject) params.append('subject', subject);

    fetch('/grade-hub-php/public/api/grades.php?action=list&' + params.toString())
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                renderGradesTable(d.data);
                if (isStudent) {
                    updateStudentStatsFromGrades(d.data);
                }
            }
        }).catch(e => console.error(e));

    // load stats & charts
    fetch('/grade-hub-php/public/api/dashboard.php?action=dashboard')
        .then(r => r.json())
        .then(d => {
            if (!d.success) return;
            if (isStudent) {
                updateStudentSummary(d.data);
            } else {
                updateGradeStats(d.data);
            }
        })
        .catch(e => console.error(e));

    if (showCharts) {
        fetch('/grade-hub-php/public/api/dashboard.php?action=charts')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    renderGradesDist(d.data.gradeDistribution);
                    renderPassFailSmall(d.data);
                }
            }).catch(e => console.error(e));
    }
}

function updateGradeStats(data) {
    document.getElementById('totalStudents').textContent = data.totalStudents || 0;
    document.getElementById('totalEntries').textContent = data.totalEntries || 0;
    document.getElementById('approvedCount').textContent = data.gradesApproved || 0;
    document.getElementById('pendingCount').textContent = data.pendingApprovals || 0;
    document.getElementById('avgGrade').textContent = (data.averageGrade || 0).toFixed(2);
}

function updateStudentSummary(data) {
    const gpaEl = document.getElementById('studentGpa');
    const statusEl = document.getElementById('studentStatus');
    const statusDot = document.getElementById('studentStatusDot');
    if (!gpaEl || !statusEl || !statusDot) return;

    const gwa = data.currentGwa === null || data.currentGwa === undefined ? null : parseFloat(data.currentGwa);
    gpaEl.textContent = gwa === null ? '0.00' : gwa.toFixed(2);

    let status = 'Regular';
    let color = '#22c55e';
    if (gwa === null) {
        status = 'N/A';
        color = '#94a3b8';
    } else if (gwa < 75) {
        status = 'Irregular';
        color = '#f97316';
    }
    statusEl.textContent = status;
    statusDot.style.background = color;
}

function updateStudentStatsFromGrades(grades) {
    const subjectsEl = document.getElementById('studentSubjectsWithFinal');
    const unitsEl = document.getElementById('studentUnits');
    if (!subjectsEl || !unitsEl) return;

    const subjectsWithFinal = (grades || []).filter(g => g.status === 'approved' || g.computed_grade !== null).length;
    subjectsEl.textContent = subjectsWithFinal;
    unitsEl.textContent = 0;
}

function renderGradesDist(dist) {
    const ctx = document.getElementById('gradesDistChart');
    if (!ctx) return;
    if (gradesDistChart) gradesDistChart.destroy();
    const labels = ['90-100','80-89','75-79','70-74','Below 70'];
    const data = [(dist['90-100']||0),(dist['80-89']||0),(dist['75-79']||0),(dist['70-74']||0),(dist['below70']||0)];
    gradesDistChart = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Count', data, backgroundColor: '#20c997' }] },
        options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });
}

function renderPassFailSmall(data) {
    const ctx = document.getElementById('passFailChartSmall');
    if (!ctx) return;
    if (passFailChartSmall) passFailChartSmall.destroy();
    const passed = data.passed || 0;
    const failed = data.failed || 0;
    passFailChartSmall = new Chart(ctx, {
        type: 'doughnut',
        data: { labels: ['Passed','Failed'], datasets: [{ data: [passed, failed], backgroundColor: ['#198754','#dc3545'] }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
}

function renderGradesTable(grades) {
    const table = document.getElementById('gradesTable');
    if (!grades || grades.length === 0) {
        table.innerHTML = `\
            <tr>\
                <td colspan="7" class="text-center py-5">\
                    <i class="fas fa-book-open text-muted" style="font-size:2.5rem; opacity:0.3;"></i>\
                    <p class="text-muted mt-3">${isStudent ? 'No grades available yet' : 'No grades found'}</p>\
                </td>\
            </tr>`;
        return;
    }

    const html = grades.map(g => {
        const badge = {
            'draft': 'badge bg-secondary',
            'submitted': 'badge bg-warning text-dark',
            'pending_approval': 'badge bg-primary',
            'approved': 'badge bg-success',
            'rejected': 'badge bg-danger'
        }[g.status] || 'badge bg-secondary';

        return `
            <tr>
                <td class="align-middle fw-semibold">${g.student_name}</td>
                <td class="align-middle text-muted">${g.subject_code} - ${g.subject_name}</td>
                <td class="align-middle text-muted">${g.prelim_grade || '-'}</td>
                <td class="align-middle text-muted">${g.midterm_grade || '-'}</td>
                <td class="align-middle text-muted">${g.final_grade || '-'}</td>
                <td class="align-middle fw-semibold">${g.computed_grade || '-'}</td>
                <td class="align-middle"><span class="${badge}">${(g.status||'').replace('_',' ').toUpperCase()}</span></td>
            </tr>`;
    }).join('');

    table.innerHTML = html;
}

function loadSubjectsFilter() {
    fetch('/grade-hub-php/public/api/subjects.php?action=list')
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const sel = document.getElementById('subjectFilter');
                sel.innerHTML = '<option value="">All Subjects</option>' + d.data.map(s => `<option value="${s.id}">${s.code} - ${s.name}</option>`).join('');
            }
        }).catch(e => console.error(e));
}

function debounce(fn, wait) {
    let t;
    return function(...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this,args), wait); };
}

function exportGrades() {
    const link = document.createElement('a');
    link.href = '/grade-hub-php/public/api/export.php?action=grades&format=csv';
    link.download = 'grades-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Grade Management - Grade Hub';
include '../views/base.php';
?>
