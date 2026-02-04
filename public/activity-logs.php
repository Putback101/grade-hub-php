<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

Auth::requireRole(['admin']);
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <!-- Activity Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="fas fa-wave-square mb-3" style="color: #20c997; font-size: 2rem;"></i>
                    <h6 class="card-title text-muted small mb-2">Total Actions</h6>
                    <div id="totalActions" class="h4 mb-0 fw-bold">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="fas fa-users mb-3" style="color: #6c757d; font-size: 2rem;"></i>
                    <h6 class="card-title text-muted small mb-2">Active Users</h6>
                    <div id="activeUsers" class="h4 mb-0 fw-bold">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                    <i class="fas fa-calendar mb-3" style="color: #198754; font-size: 2rem;"></i>
                    <h6 class="card-title text-muted small mb-2">Today's Actions</h6>
                    <div id="todayActions" class="h4 mb-0 fw-bold">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text border-0 bg-light">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="searchFilter" placeholder="Search by user, action, or details..." class="form-control border-0 bg-light" style="box-shadow: none;">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <select id="actionFilter" class="form-select border-0 bg-light">
                        <option value="">All Actions</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="approve">Approve</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <button onclick="exportLogs()" class="btn btn-light border w-100">
                        <i class="fas fa-download me-2"></i> Export Logs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light" style="background-color: #f8f9fa;">
                    <tr>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Timestamp</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">User</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Action</th>
                        <th style="color: #8898aa; font-weight: 600; font-size: 0.9rem;">Details</th>
                    </tr>
                </thead>
                <tbody id="logsTable">
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="fas fa-wave-square text-muted" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-3">No activity logs found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired - starting to load activity logs');
    loadActivityLogs();
    loadActivityStats();
    
    document.getElementById('searchFilter').addEventListener('keyup', loadActivityLogs);
    document.getElementById('actionFilter').addEventListener('change', loadActivityLogs);
    console.log('Event listeners attached');
});

function loadActivityStats() {
    fetch('/grade-hub-php/public/api/dashboard.php?action=recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activities = data.data || [];
                
                // Total actions
                document.getElementById('totalActions').textContent = activities.length;
                
                // Active users (unique user count)
                const uniqueUsers = new Set(activities.map(a => a.user_id)).size;
                document.getElementById('activeUsers').textContent = uniqueUsers;
                
                // Today's actions (count from today)
                const today = new Date().toDateString();
                const todayCount = activities.filter(a => 
                    new Date(a.created_at).toDateString() === today
                ).length;
                document.getElementById('todayActions').textContent = todayCount;
            }
        })
        .catch(e => console.error(e));
}

function loadActivityLogs() {
    const search = document.getElementById('searchFilter').value;
    const actionFilter = document.getElementById('actionFilter').value;
    
    let url = '/grade-hub-php/public/api/dashboard.php?action=recent';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (actionFilter) url += '&filter=' + actionFilter;
    
    console.log('Fetching from:', url);

    fetch(url)
        .then(response => {
            console.log('Fetch response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Data received:', data);
            if (data.success) {
                console.log('Success - displaying', data.data.length, 'activities');
                displayActivityLogs(data.data);
                loadActivityStats();
            } else {
                console.error('API error:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function displayActivityLogs(activities) {
    const table = document.getElementById('logsTable');
    
    console.log('displayActivityLogs called with', activities?.length || 0, 'activities');

    if (!activities || activities.length === 0) {
        console.warn('No activities to display - showing empty message');
        table.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5">
                    <i class="fas fa-wave-square text-muted" style="font-size: 2.5rem; opacity: 0.3;"></i>
                    <p class="text-muted mt-3">No activity logs found</p>
                </td>
            </tr>
        `;
        return;
    }

    const html = activities.map(log => `
        <tr>
            <td class="align-middle" style="font-size: 0.9rem;">
                <span class="text-muted">${new Date(log.created_at).toLocaleString()}</span>
            </td>
            <td class="align-middle" style="font-size: 0.9rem;">
                <span class="fw-semibold">${log.user_name}</span>
            </td>
            <td class="align-middle" style="font-size: 0.9rem;">
                <span class="text-muted">${log.action}</span>
            </td>
            <td class="align-middle" style="font-size: 0.9rem;">
                <span class="text-muted">${log.details || '-'}</span>
            </td>
        </tr>
    `).join('');

    console.log('Rendering', activities.length, 'rows in table');
    table.innerHTML = html;
}

function exportLogs() {
    const link = document.createElement('a');
    link.href = '/grade-hub-php/public/api/export.php?action=activity-logs&format=csv';
    link.download = 'activity-logs-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Activity Logs - Grade Hub';
include '../views/base.php';
?>
