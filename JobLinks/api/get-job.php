<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

 $jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit;
}

 $stmt = $pdo->prepare("
    SELECT j.*, c.name as company_name,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    WHERE j.id = ?
");
 $stmt->execute([$jobId]);
 $job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(['success' => false, 'message' => 'Job not found']);
    exit;
}

 $html = '
    <div class="job-detail">
        <div class="detail-section">
            <h4>Job Information</h4>
            <p><strong>Title:</strong> ' . $job['title'] . '</p>
            <p><strong>Company:</strong> ' . $job['company_name'] . '</p>
            <p><strong>Category:</strong> ' . $job['category'] . '</p>
            <p><strong>Type:</strong> ' . ucfirst($job['type']) . '</p>
            <p><strong>Location:</strong> ' . $job['location'] . '</p>
            <p><strong>Salary:</strong> ' . formatSalary($job['salary_min'], $job['salary_max']) . '</p>
            <p><strong>Expires:</strong> ' . ($job['expires_at'] ? date('M j, Y', strtotime($job['expires_at'])) : 'No expiry') . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Description</h4>
            <p>' . nl2br($job['description']) . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Requirements</h4>
            <p>' . nl2br($job['requirements']) . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Benefits</h4>
            <p>' . nl2br($job['benefits']) . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Statistics</h4>
            <p><strong>Applications:</strong> ' . $job['application_count'] . '</p>
            <p><strong>Posted:</strong> ' . date('M j, Y, g:i A', strtotime($job['created_at'])) . '</p>
            <p><strong>Status:</strong> <span class="status-badge status-' . ($job['is_active'] ? 'active' : 'inactive') . '">' . ($job['is_active'] ? 'Active' : 'Inactive') . '</span></p>
        </div>
    </div>
';

echo json_encode(['success' => true, 'html' => $html]);
?>