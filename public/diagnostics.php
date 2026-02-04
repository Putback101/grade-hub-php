<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../app/middleware/Auth.php';

// This page helps diagnose activity logs display issues
Auth::requireRole(['admin']);

$user = Auth::getCurrentUser();
$conn = Database::getInstance()->getConnection();

// Check database
$dbResult = $conn->query('SELECT COUNT(*) as count FROM activity_logs');
$dbCount = $dbResult->fetch_assoc()['count'];
?>

<?php ob_start(); ?>
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Activity Logs Diagnostic</h1>
        <p class="text-muted">Checking if activity logs are working correctly</p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">✓ Server-Side Checks</h6>
                </div>
                <div class="card-body">
                    <p><strong>Current User:</strong> <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['role']); ?>)</p>
                    <p><strong>User ID:</strong> <code><?php echo htmlspecialchars($user['id']); ?></code></p>
                    <p><strong>Database Records:</strong> <span class="badge bg-success"><?php echo $dbCount; ?> records</span></p>
                    <p><strong>API Endpoint:</strong> <code>/api/dashboard.php?action=recent</code></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">📊 Client-Side Test</h6>
                </div>
                <div class="card-body">
                    <p><strong>Test the API directly:</strong></p>
                    <button class="btn btn-primary btn-sm" onclick="testAPI()">Test API Endpoint</button>
                    <div id="apiResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">🔧 Troubleshooting Steps</h6>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Check the browser console (F12 → Console tab) for JavaScript errors</li>
                        <li>Click "Test API Endpoint" button above to verify the API is returning data</li>
                        <li>If the API test shows data, the issue is in JavaScript. Check console for errors.</li>
                        <li>If the API test shows no data, the issue is in the backend. Contact support.</li>
                        <li>Try hard-refreshing the page (Ctrl+Shift+R) to clear cache</li>
                        <li>If still not working, check if you're logged in as admin (top right corner)</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Testing...';
    
    fetch('/grade-hub-php/public/api/dashboard.php?action=recent')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            if (data.success && data.data && data.data.length > 0) {
                resultDiv.innerHTML = `
                    <div class="alert alert-success" role="alert">
                        <h6 class="alert-heading">✓ API Working!</h6>
                        <p>Found <strong>${data.data.length}</strong> activity logs</p>
                        <p><strong>Sample:</strong><br>
                        ${data.data[0].user_name} - ${data.data[0].action} (${data.data[0].created_at})</p>
                        <p><small>If you still don't see data on the activity logs page, there may be a JavaScript error. Check the browser console (F12).</small></p>
                    </div>
                `;
            } else if (data.success && (!data.data || data.data.length === 0)) {
                resultDiv.innerHTML = `
                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading">ℹ No Data</h6>
                        <p>The API returned successfully but with 0 records. This might be normal if no one has logged in recently.</p>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <h6 class="alert-heading">✗ API Error</h6>
                        <p>${data.error || 'Unknown error'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            resultDiv.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading">✗ Network Error</h6>
                    <p>${error.message}</p>
                </div>
            `;
        });
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Activity Logs Diagnostic - Grade Hub';
include '../views/base.php';
?>
