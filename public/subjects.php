<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';
require_once __DIR__ . '/../app/models/Subject.php';
require_once __DIR__ . '/../app/models/Enrollment.php';

Auth::requireAuth();
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Available Subjects</h5>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Academic Year</label>
                    <input type="text" id="academicYearFilter" placeholder="2024-2025" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Semester</label>
                    <select id="semesterFilter" class="form-select form-select-sm">
                        <option value="">All Semesters</option>
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Units</th>
                        <th>Semester</th>
                        <th>Year</th>
                        <th>Faculty</th>
                    </tr>
                </thead>
                <tbody id="subjectsTable">
                    <tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSubjects();
    
    document.getElementById('academicYearFilter').addEventListener('change', loadSubjects);
    document.getElementById('semesterFilter').addEventListener('change', loadSubjects);
});

function loadSubjects() {
    const academicYear = document.getElementById('academicYearFilter').value;
    const semester = document.getElementById('semesterFilter').value;

    let url = '/grade-hub-php/public/api/subjects.php?action=list';
    if (academicYear) url += '&academic_year=' + academicYear;
    if (semester) url += '&semester=' + semester;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySubjects(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function displaySubjects(subjects) {
    const table = document.getElementById('subjectsTable');

    if (subjects.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No subjects found</td></tr>';
        return;
    }

    const html = subjects.map(subject => `
        <tr>
            <td class="align-middle fw-semibold">${subject.code}</td>
            <td class="align-middle text-muted">${subject.name}</td>
            <td class="align-middle text-muted">${subject.units}</td>
            <td class="align-middle text-muted">${subject.semester}</td>
            <td class="align-middle text-muted">${subject.academic_year}</td>
            <td class="align-middle text-muted">${subject.faculty_name || '-'}</td>
        </tr>
    `).join('');

    table.innerHTML = html;
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Subjects - Grade Hub';
include '../views/base.php';
?>
