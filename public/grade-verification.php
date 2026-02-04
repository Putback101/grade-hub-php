<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireRole(['registrar', 'admin']);
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <!-- Search and Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-lg-10">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input id="searchFilter" type="text" class="form-control border-0 bg-light" placeholder="Search by student, subject, or faculty...">
                    </div>
                </div>
                <div class="col-12 col-lg-2 text-lg-end">
                    <button class="btn btn-light border" id="filtersBtn"><i class="fas fa-filter me-1"></i> Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Pending Review</p>
                    <h3 class="h2 mb-0" id="pendingReviewCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Approved Today</p>
                    <h3 class="h2 mb-0 text-success" id="approvedTodayCount">0</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1">Rejected</p>
                    <h3 class="h2 mb-0 text-danger" id="rejectedCount">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Grades Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Faculty</th>
                        <th>Prelim</th>
                        <th>Midterm</th>
                        <th>Final</th>
                        <th>Computed</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="gradesTable">
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <i class="fas fa-check-circle text-muted" style="font-size:2.5rem; opacity:0.3;"></i>
                            <p class="text-muted mt-3">No pending grades to review</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadVerificationData();
    document.getElementById('searchFilter').addEventListener('keyup', debounce(loadVerificationData, 250));
    document.getElementById('filtersBtn').addEventListener('click', function(){ alert('Filter options coming soon'); });
});

function loadVerificationData() {
    // Load pending grades
    const search = document.getElementById('searchFilter').value;
    let url = '/grade-hub-php/public/api/grades.php?action=pending';
    if (search) url += '&search=' + encodeURIComponent(search);

    fetch(url)
        .then(res => res.json())
        .then(d => {
            if (d.success) {
                displayPendingGrades(d.data);
                updateVerificationStats(d.data);
            }
        }).catch(e => console.error(e));

    // load overall stats (approved today, rejected) from dashboard endpoint when available
    fetch('/grade-hub-php/public/api/dashboard.php?action=dashboard')
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                // keep values if provided
                document.getElementById('approvedTodayCount').textContent = d.data.approvedToday || 0;
                document.getElementById('rejectedCount').textContent = d.data.rejected || 0;
            }
        }).catch(() => {});
}

function updateVerificationStats(pendingList) {
    document.getElementById('pendingReviewCount').textContent = pendingList.length || 0;
}

function displayPendingGrades(grades) {
    const table = document.getElementById('gradesTable');
    if (!grades || grades.length === 0) {
        table.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-5">
                    <i class="fas fa-check-circle text-muted" style="font-size:2.5rem; opacity:0.3;"></i>
                    <p class="text-muted mt-3">No pending grades to review</p>
                </td>
            </tr>`;
        return;
    }

    const html = grades.map(g => `
        <tr>
            <td class="align-middle fw-semibold">${g.student_name}</td>
            <td class="align-middle text-muted">${g.subject_code} - ${g.subject_name}</td>
            <td class="align-middle text-muted">${g.faculty_name || '-'}</td>
            <td class="align-middle text-muted">${g.prelim_grade || '-'}</td>
            <td class="align-middle text-muted">${g.midterm_grade || '-'}</td>
            <td class="align-middle text-muted">${g.final_grade || '-'}</td>
            <td class="align-middle fw-semibold">${g.computed_grade || '-'}</td>
            <td class="align-middle text-muted">${new Date(g.submitted_at).toLocaleString()}</td>
            <td class="align-middle"><span class="badge bg-warning text-dark">PENDING</span></td>
            <td class="align-middle">
                <button onclick="approveGrade('${g.id}')" class="btn btn-sm btn-success me-1">Approve</button>
                <button onclick="rejectGrade('${g.id}')" class="btn btn-sm btn-danger">Reject</button>
            </td>
        </tr>
    `).join('');

    table.innerHTML = html;
}

function approveGrade(id) {
    if (!confirm('Approve this grade?')) return;
    fetch('/grade-hub-php/public/api/grades.php?action=approve', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
    }).then(r => r.json()).then(d => { if (d.success) { loadVerificationData(); } else alert(d.message || 'Error'); }).catch(e=>console.error(e));
}

function rejectGrade(id) {
    if (!confirm('Reject this grade?')) return;
    fetch('/grade-hub-php/public/api/grades.php?action=reject', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
    }).then(r => r.json()).then(d => { if (d.success) { loadVerificationData(); } else alert(d.message || 'Error'); }).catch(e=>console.error(e));
}

function debounce(fn, wait) { let t; return function(...a){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,a), wait); }; }
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Grade Verification - Grade Hub';
include '../views/base.php';
?>
