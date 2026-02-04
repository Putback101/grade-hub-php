<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireRole(['registrar', 'admin']);
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <!-- Filters and Export Section -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-3">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select id="semesterFilter" class="form-select border-0 bg-light">
                        <option value="">All Semesters</option>
                        <option value="1st" selected>1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                    </select>
                </div>
                <div class="col-12 col-sm-3">
                    <label class="form-label small fw-semibold">Academic Year</label>
                    <select id="yearFilter" class="form-select border-0 bg-light">
                        <option value="2024-2025" selected>2024-2025</option>
                        <option value="2023-2024">2023-2024</option>
                        <option value="2022-2023">2022-2023</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 d-flex gap-2 justify-content-sm-end">
                    <button onclick="printReport()" class="btn btn-light border flex-grow-1 flex-sm-grow-0">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button onclick="exportPDF()" class="btn btn-light border flex-grow-1 flex-sm-grow-0">
                        <i class="fas fa-file-pdf me-1"></i> Export PDF
                    </button>
                    <button onclick="exportExcel()" class="btn btn-light border flex-grow-1 flex-sm-grow-0">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Total Students</p>
                            <h3 class="h2 mb-0" id="totalStudentsReport"><span class="spinner-border spinner-border-sm"></span></h3>
                        </div>
                        <i class="fas fa-users" style="color: #20c997; font-size: 1.5rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Pass Rate</p>
                            <h3 class="h2 mb-0" id="passRateReport"><span class="spinner-border spinner-border-sm"></span></h3>
                        </div>
                        <i class="fas fa-check-circle" style="color: #198754; font-size: 1.5rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Average Grade</p>
                            <h3 class="h2 mb-0" id="avgGradeReport"><span class="spinner-border spinner-border-sm"></span></h3>
                        </div>
                        <i class="fas fa-chart-line" style="color: #6c757d; font-size: 1.5rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Subjects</p>
                            <h3 class="h2 mb-0" id="subjectCountReport"><span class="spinner-border spinner-border-sm"></span></h3>
                        </div>
                        <i class="fas fa-book" style="color: #fd7e14; font-size: 1.5rem; opacity: 0.7;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Grade Distribution Chart -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Grade Distribution</h5>
                    <canvas id="gradeDistributionChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Pass/Fail Summary Chart -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-4">Pass/Fail Summary</h5>
                    <div class="d-flex justify-content-center">
                        <canvas id="passFailChart" style="max-height: 300px; max-width: 300px;"></canvas>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <div class="text-center">
                            <i class="fas fa-circle" style="color: #198754; font-size: 0.8rem;"></i>
                            <span class="text-muted small ms-1">Passed</span>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-circle" style="color: #dc3545; font-size: 0.8rem;"></i>
                            <span class="text-muted small ms-1">Failed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subject Performance Summary Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Subject Performance Summary</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light" style="background-color: #f8f9fa;">
                        <tr>
                            <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Subject Code</th>
                            <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Subject Name</th>
                            <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Students</th>
                            <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Average Grade</th>
                            <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Performance</th>
                        </tr>
                    </thead>
                    <tbody id="subjectPerformanceTable">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Loading subject performance data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let gradeDistributionChart = null;
let passFailChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadReportData();
    
    document.getElementById('semesterFilter').addEventListener('change', loadReportData);
    document.getElementById('yearFilter').addEventListener('change', loadReportData);
});

function loadReportData() {
    const semester = document.getElementById('semesterFilter').value;
    const year = document.getElementById('yearFilter').value;
    
    // Load report statistics and charts
    fetch('/grade-hub-php/public/api/dashboard.php?action=reports')
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                updateReportStats(d.data);
                renderGradeDistributionChart(d.data.gradeDistribution);
                renderPassFailChart(d.data);
                loadSubjectPerformance(d.data.subjectPerformance);
            }
        })
        .catch(e => console.error(e));
}

function updateReportStats(data) {
    document.getElementById('totalStudentsReport').textContent = data.totalStudents || '0';
    
    // Calculate pass rate from actual data
    const totalApproved = (data.passCount || 0) + (data.failCount || 0);
    const passRate = totalApproved > 0 ? Math.round((data.passCount / totalApproved) * 100) : 0;
    document.getElementById('passRateReport').textContent = passRate + '%';
    
    // Average grade from actual data
    document.getElementById('avgGradeReport').textContent = (data.averageGrade || '0').toFixed(1);
    
    // Subject count from actual data
    document.getElementById('subjectCountReport').textContent = data.totalSubjects || '0';
}

function renderGradeDistributionChart(dist) {
    const ctx = document.getElementById('gradeDistributionChart');
    if (!ctx) return;
    
    // Destroy existing chart
    if (gradeDistributionChart) {
        gradeDistributionChart.destroy();
    }
    
    const labels = ['A (90-100)', 'B (80-89)', 'C (70-79)', 'D (60-69)', 'F (<60)'];
    const data = [
        dist.A || 0,
        dist.B || 0,
        dist.C || 0,
        dist.D || 0,
        dist.F || 0
    ];
    
    gradeDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Count',
                data: data,
                backgroundColor: '#20c997',
                borderColor: '#20c997',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderPassFailChart(data) {
    const ctx = document.getElementById('passFailChart');
    if (!ctx) return;
    
    // Destroy existing chart
    if (passFailChart) {
        passFailChart.destroy();
    }
    
    const passCount = data.passCount || 0;
    const failCount = data.failCount || 0;
    
    passFailChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [passCount, failCount],
                backgroundColor: ['#198754', '#dc3545'],
                borderColor: 'white',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function loadSubjectPerformance(subjects) {
    const table = document.getElementById('subjectPerformanceTable');
    
    if (!subjects || subjects.length === 0) {
        table.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No subject performance data available</td></tr>';
        return;
    }
    
    const html = subjects.map(subject => {
        let performance = 'Good';
        let performanceColor = '#fd7e14';
        
        if (subject.pass_rate >= 90) {
            performance = 'Excellent';
            performanceColor = '#198754';
        } else if (subject.pass_rate >= 75) {
            performance = 'Very Good';
            performanceColor = '#20c997';
        } else if (subject.pass_rate < 50) {
            performance = 'Needs Improvement';
            performanceColor = '#dc3545';
        }
        
        return `
            <tr>
                <td class="align-middle fw-semibold">${subject.code}</td>
                <td class="align-middle text-muted">${subject.name}</td>
                <td class="align-middle">${subject.total_grades}</td>
                <td class="align-middle fw-semibold">${subject.average_grade}</td>
                <td class="align-middle">
                    <span class="badge" style="background-color: ${performanceColor};">${performance}</span>
                </td>
            </tr>
        `;
    }).join('');
    
    table.innerHTML = html;
}

function printReport() {
    window.print();
}

function exportPDF() {
    // For PDF, we'll generate HTML and use browser's print-to-PDF
    const printWindow = window.open('', '', 'height=600,width=800');
    const table = document.querySelector('table');
    const tableHTML = table ? table.outerHTML : '<p>No data to export</p>';
    
    printWindow.document.write(`
        <html><head><title>Grade Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f4f4f4; font-weight: bold; }
            h2 { color: #333; }
        </style>
        </head><body>
        <h2>Grade Report - ${new Date().toLocaleDateString()}</h2>
        ${tableHTML}
        </body></html>
    `);
    printWindow.document.close();
    setTimeout(() => { printWindow.print(); }, 250);
}

function exportExcel() {
    // Export as CSV which can be opened in Excel
    const link = document.createElement('a');
    link.href = '/grade-hub-php/public/api/export.php?action=reports&format=csv';
    link.download = 'grade-report-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Reports & Summary - Grade Hub';
include '../views/base.php';
?>
