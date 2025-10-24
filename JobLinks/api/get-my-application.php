<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

 $applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($applicationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

 $stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.description as job_description, j.requirements as job_requirements,
           j.location as job_location, j.type as job_type, j.salary_min, j.salary_max,
           c.name as company_name, c.location as company_location,
           u.name as applicant_name, u.email as applicant_email, u.phone as applicant_phone
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN companies c ON j.company_id = c.id
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ? AND a.user_id = ?
");
 $stmt->execute([$applicationId, $_SESSION['user_id']]);
 $application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    echo json_encode(['success' => false, 'message' => 'Application not found']);
    exit;
}

 $html = '
    <div class="application-detail">
        <div class="detail-section">
            <h4>Application Information</h4>
            <p><strong>Applied:</strong> ' . date('M j, Y, g:i A', strtotime($application['applied_at'])) . '</p>
            <p><strong>Status:</strong> <span class="status-badge status-' . $application['status'] . '">' . ucfirst($application['status']) . '</span></p>
        </div>
        
        <div class="detail-section">
            <h4>Applicant Information</h4>
            <p><strong>Name:</strong> ' . $application['applicant_name'] . '</p>
            <p><strong>Email:</strong> ' . $application['applicant_email'] . '</p>
            <p><strong>Phone:</strong> ' . ($application['applicant_phone'] ?: 'Not provided') . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Job Information</h4>
            <p><strong>Position:</strong> ' . $application['job_title'] . '</p>
            <p><strong>Company:</strong> ' . $application['company_name'] . '</p>
            <p><strong>Location:</strong> ' . $application['job_location'] . '</p>
            <p><strong>Type:</strong> ' . ucfirst($application['job_type']) . '</p>
            <p><strong>Salary:</strong> ' . formatSalary($application['salary_min'], $application['salary_max']) . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Cover Letter</h4>
            <p>' . nl2br($application['cover_letter']) . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Resume</h4>
            <p><a href="../' . $application['resume_url'] . '" target="_blank" class="btn btn-sm">View Resume</a></p>
        </div>
    </div>
';

echo json_encode(['success' => true, 'html' => $html]);
?>