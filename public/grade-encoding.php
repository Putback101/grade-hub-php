<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';
require_once __DIR__ . '/../app/models/Subject.php';

Auth::requireRole(['faculty', 'admin']);
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <!-- Subject & Filters Section -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label fw-semibold mb-2">Select Subject</label>
                    <select id="subjectSelect" class="form-select form-select-lg">
                        <option value="">Choose a subject to encode grades...</option>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary flex-grow-1" id="resetBtn"><i class="fas fa-redo me-2"></i>Reset</button>
                        <button class="btn btn-success flex-grow-1" id="saveBtn" disabled><i class="fas fa-save me-2"></i>Save Grades</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="fas fa-graduation-cap text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="text-muted mt-4 fs-5">Select a subject to view and encode grades</p>
        </div>
    </div>

    <!-- Enrollments Table -->
    <div id="enrollmentsContainer" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-bottom">
                <h5 class="mb-0">Student Grades for <span id="selectedSubjectName" class="fw-bold"></span></h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Final</th>
                            <th>Computed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="enrollmentsTable">
                        <tr><td colspan="7" class="text-center py-4 text-muted">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentSubjectId = null;
let gradesModified = {};

document.addEventListener('DOMContentLoaded', function() {
    loadFacultySubjects();
    document.getElementById('subjectSelect').addEventListener('change', loadEnrollments);
    document.getElementById('saveBtn').addEventListener('click', saveGrades);
    document.getElementById('resetBtn').addEventListener('click', resetForm);
});

function loadFacultySubjects() {
    fetch('/grade-hub-php/public/api/subjects.php?action=faculty')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('subjectSelect');
                data.data.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = subject.id;
                    option.textContent = `${subject.code} - ${subject.name}`;
                    select.appendChild(option);
                });
            }
        });
}

function loadEnrollments() {
    const subjectId = document.getElementById('subjectSelect').value;
    const subjectOption = document.getElementById('subjectSelect').options[document.getElementById('subjectSelect').selectedIndex];
    
    if (!subjectId) {
        document.getElementById('enrollmentsContainer').classList.add('d-none');
        document.getElementById('emptyState').classList.remove('d-none');
        document.getElementById('saveBtn').disabled = true;
        currentSubjectId = null;
        gradesModified = {};
        return;
    }

    currentSubjectId = subjectId;
    document.getElementById('selectedSubjectName').textContent = subjectOption.textContent;
    document.getElementById('emptyState').classList.add('d-none');
    document.getElementById('enrollmentsContainer').classList.remove('d-none');
    
    const table = document.getElementById('enrollmentsTable');
    table.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Loading...</td></tr>';

    // Fetch enrollments for this subject
    fetch(`/grade-hub-php/public/api/grades.php?action=enrollments&subject_id=${subjectId}`)
        .then(r => r.json())
        .then(d => {
            if (d.success && Array.isArray(d.data)) {
                displayEnrollments(d.data);
            } else {
                table.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No students enrolled</td></tr>';
            }
        })
        .catch(e => {
            console.error(e);
            table.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading enrollments</td></tr>';
        });
}

function displayEnrollments(enrollments) {
    const table = document.getElementById('enrollmentsTable');
    if (!enrollments || enrollments.length === 0) {
        table.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No students enrolled</td></tr>';
        return;
    }

    const html = enrollments.map(e => `
        <tr>
            <td class="align-middle text-muted">${e.student_id || '-'}</td>
            <td class="align-middle fw-semibold">${e.student_name || '-'}</td>
            <td class="align-middle">
                <input type="number" class="form-control form-control-sm grade-input" data-enrollment-id="${e.id}" data-grade-type="prelim" value="${e.prelim_grade || ''}" min="0" max="100" step="0.01">
            </td>
            <td class="align-middle">
                <input type="number" class="form-control form-control-sm grade-input" data-enrollment-id="${e.id}" data-grade-type="midterm" value="${e.midterm_grade || ''}" min="0" max="100" step="0.01">
            </td>
            <td class="align-middle">
                <input type="number" class="form-control form-control-sm grade-input" data-enrollment-id="${e.id}" data-grade-type="final" value="${e.final_grade || ''}" min="0" max="100" step="0.01">
            </td>
            <td class="align-middle fw-semibold">${e.computed_grade || '-'}</td>
            <td class="align-middle">
                <span class="badge bg-${getStatusColor(e.status)}">${(e.status || 'pending').toUpperCase()}</span>
            </td>
        </tr>
    `).join('');

    table.innerHTML = html;

    // Attach change handlers to grade inputs
    document.querySelectorAll('.grade-input').forEach(input => {
        input.addEventListener('change', function() {
            markAsModified(this.dataset.enrollmentId);
        });
    });
}

function markAsModified(enrollmentId) {
    gradesModified[enrollmentId] = true;
    document.getElementById('saveBtn').disabled = false;
}

function saveGrades() {
    const enrollmentIds = Object.keys(gradesModified);
    if (enrollmentIds.length === 0) {
        alert('No changes to save');
        return;
    }

    const updates = [];
    enrollmentIds.forEach(enrollmentId => {
        const inputs = document.querySelectorAll(`input[data-enrollment-id="${enrollmentId}"]`);
        const prelimInput = document.querySelector(`input[data-enrollment-id="${enrollmentId}"][data-grade-type="prelim"]`);
        const midtermInput = document.querySelector(`input[data-enrollment-id="${enrollmentId}"][data-grade-type="midterm"]`);
        const finalInput = document.querySelector(`input[data-enrollment-id="${enrollmentId}"][data-grade-type="final"]`);

        updates.push({
            enrollment_id: enrollmentId,
            prelim_grade: prelimInput.value || null,
            midterm_grade: midtermInput.value || null,
            final_grade: finalInput.value || null
        });
    });

    fetch('/grade-hub-php/public/api/grades.php?action=encode', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subject_id: currentSubjectId, grades: updates })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('Grades saved successfully');
            gradesModified = {};
            document.getElementById('saveBtn').disabled = true;
            loadEnrollments();
        } else {
            alert('Error: ' + (d.message || 'Failed to save grades'));
        }
    })
    .catch(e => {
        console.error(e);
        alert('Error saving grades');
    });
}

function resetForm() {
    gradesModified = {};
    document.getElementById('saveBtn').disabled = true;
    loadEnrollments();
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'submitted': 'info',
        'approved': 'success',
        'rejected': 'danger'
    };
    return colors[status] || 'secondary';
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Grade Encoding - Grade Hub';
include '../views/base.php';
?>
