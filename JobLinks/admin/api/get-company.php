<?php
require_once '../../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

 $companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($companyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid company ID']);
    exit;
}

 $stmt = $pdo->prepare("
    SELECT c.*, u.name as owner_name, u.email as owner_email,
           (SELECT COUNT(*) FROM jobs WHERE company_id = c.id) as job_count
    FROM companies c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
 $stmt->execute([$companyId]);
 $company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit;
}

 $html = '
    <div class="company-detail">
        <div class="detail-section">
            <h4>Company Information</h4>
            <p><strong>Name:</strong> ' . $company['name'] . '</p>
            <p><strong>Industry:</strong> ' . $company['industry'] . '</p>
            <p><strong>Size:</strong> ' . $company['size'] . '</p>
            <p><strong>Location:</strong> ' . $company['location'] . '</p>
            <p><strong>Founded:</strong> ' . $company['founded_year'] . '</p>
            <p><strong>Website:</strong> <a href="' . $company['website'] . '" target="_blank">' . $company['website'] . '</a></p>
        </div>
        
        <div class="detail-section">
            <h4>Description</h4>
            <p>' . nl2br($company['description']) . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Owner Information</h4>
            <p><strong>Name:</strong> ' . $company['owner_name'] . '</p>
            <p><strong>Email:</strong> ' . $company['owner_email'] . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Statistics</h4>
            <p><strong>Jobs Posted:</strong> ' . $company['job_count'] . '</p>
            <p><strong>Member Since:</strong> ' . date('M j, Y', strtotime($company['created_at'])) . '</p>
        </div>
    </div>
';

echo json_encode(['success' => true, 'html' => $html]);
?>