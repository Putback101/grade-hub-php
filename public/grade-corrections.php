<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireRole(['registrar', 'admin', 'faculty']);
$user = Auth::getCurrentUser();
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <!-- Correction Status Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="h2 mb-0" id="pendingCount">0</h3>
                            <p class="text-muted small mb-0">Pending</p>
                        </div>
                        <i class="fas fa-clock" style="color: #fd7e14; font-size: 2rem; opacity: 0.6;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="h2 mb-0" id="approvedCount">0</h3>
                            <p class="text-muted small mb-0">Approved</p>
                        </div>
                        <i class="fas fa-check-circle" style="color: #198754; font-size: 2rem; opacity: 0.6;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Corrections Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Correction Requests</h5>
            <div class="d-flex gap-2">
                <?php if ($user['role'] === 'faculty'): ?>
                <button class="btn btn-sm btn-success" onclick="openNewRequestModal()">
                    <i class="fas fa-plus me-1"></i> New Request
                </button>
                <?php endif; ?>
                <?php if ($user['role'] !== 'faculty'): ?>
                <button onclick="exportCorrections()" class="btn btn-sm btn-light border">
                    <i class="fas fa-download me-1"></i> Export
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light" style="background-color: #f8f9fa;">
                    <tr>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Requested By</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Student</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Subject</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Original</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Requested</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Reason</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Date</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Status</th>
                    </tr>
                </thead>
                <tbody id="correctionsTable">
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-file-alt text-muted" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-3">No correction requests</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Correction Request Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1" aria-labelledby="newRequestLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newRequestLabel">New Correction Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newRequestForm">
                    <div class="mb-3">
                        <label class="form-label">Grade Entry ID</label>
                        <input type="text" class="form-control" id="gradeEntryId" placeholder="Enter grade entry ID" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Original Grade</label>
                        <input type="number" class="form-control" id="originalGrade" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Requested Grade</label>
                        <input type="number" class="form-control" id="requestedGrade" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" id="requestReason" rows="3" placeholder="Provide a brief reason" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitNewRequest()">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCorrections();
});

function loadCorrections() {
    fetch('/grade-hub-php/public/api/corrections.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCorrections(data.data);
                updateCorrectionStats(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateCorrectionStats(corrections) {
    const pendingCount = corrections.filter(c => c.status === 'pending').length;
    const approvedCount = corrections.filter(c => c.status === 'approved').length;
    
    document.getElementById('pendingCount').textContent = pendingCount;
    document.getElementById('approvedCount').textContent = approvedCount;
}

function displayCorrections(corrections) {
    const table = document.getElementById('correctionsTable');

    if (!corrections || corrections.length === 0) {
        table.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i class="fas fa-file-alt text-muted" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    <p class="text-muted mt-3">No correction requests</p>
                </td>
            </tr>
        `;
        return;
    }

    const html = corrections.map(correction => {
        const statusBadgeClass = {
            'pending': 'badge bg-warning text-dark',
            'approved': 'badge bg-success',
            'rejected': 'badge bg-danger'
        }[correction.status] || 'badge bg-secondary';

        return `
            <tr>
                <td class="align-middle text-muted small">${correction.requester_name || correction.requested_by || '-'}</td>
                <td class="align-middle fw-semibold">${correction.student_name}</td>
                <td class="align-middle text-muted">${correction.subject_code}</td>
                <td class="align-middle text-muted">${correction.original_grade}</td>
                <td class="align-middle fw-semibold text-primary">${correction.requested_grade}</td>
                <td class="align-middle text-muted">${correction.reason || '-'}</td>
                <td class="align-middle text-muted small">${new Date(correction.created_at).toLocaleDateString()}</td>
                <td class="align-middle"><span class="${statusBadgeClass}">${correction.status.charAt(0).toUpperCase() + correction.status.slice(1)}</span></td>
            </tr>
        `;
    }).join('');

    table.innerHTML = html;
}

function approveCorrection(correctionId) {
    const remarks = prompt('Enter remarks (optional):');
    if (remarks !== null) {
        fetch('/grade-hub-php/public/api/corrections.php?action=approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: correctionId, remarks })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Correction approved');
                loadCorrections();
            }
        });
    }
}

function rejectCorrection(correctionId) {
    const remarks = prompt('Enter remarks:');
    if (remarks !== null) {
        fetch('/grade-hub-php/public/api/corrections.php?action=reject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: correctionId, remarks })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Correction rejected');
                loadCorrections();
            }
        });
    }
}

function exportCorrections() {
    const link = document.createElement('a');
    link.href = '/grade-hub-php/public/api/export.php?action=grade-corrections&format=csv';
    link.download = 'grade-corrections-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function openNewRequestModal() {
    const modalEl = document.getElementById('newRequestModal');
    const modal = new bootstrap.Modal(modalEl);
    document.getElementById('newRequestForm').reset();
    modal.show();
}

function submitNewRequest() {
    const gradeEntryId = document.getElementById('gradeEntryId').value.trim();
    const originalGrade = document.getElementById('originalGrade').value;
    const requestedGrade = document.getElementById('requestedGrade').value;
    const reason = document.getElementById('requestReason').value.trim();

    if (!gradeEntryId || !originalGrade || !requestedGrade || !reason) {
        alert('Please complete all fields.');
        return;
    }

    fetch('/grade-hub-php/public/api/corrections.php?action=request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            grade_entry_id: gradeEntryId,
            original_grade: parseFloat(originalGrade),
            requested_grade: parseFloat(requestedGrade),
            reason: reason
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('Correction request submitted');
            const modalEl = document.getElementById('newRequestModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            loadCorrections();
        } else {
            alert(d.message || 'Failed to submit request');
        }
    })
    .catch(e => {
        console.error(e);
        alert('Failed to submit request');
    });
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Grade Corrections - Grade Hub';
include '../views/base.php';
?>
