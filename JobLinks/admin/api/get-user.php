<?php
require_once '../../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

 $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

 $stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as application_count,
           (SELECT COUNT(*) FROM saved_jobs WHERE user_id = u.id) as saved_jobs_count
    FROM users u
    WHERE u.id = ?
");
 $stmt->execute([$userId]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

 $html = '
    <div class="user-detail">
        <div class="detail-section">
            <h4>User Information</h4>
            <p><strong>Name:</strong> ' . $user['name'] . '</p>
            <p><strong>Email:</strong> ' . $user['email'] . '</p>
            <p><strong>Phone:</strong> ' . ($user['phone'] ?: 'Not provided') . '</p>
            <p><strong>Location:</strong> ' . ($user['location'] ?: 'Not provided') . '</p>
            <p><strong>Role:</strong> <span class="role-badge role-' . $user['role'] . '">' . ucfirst($user['role']) . '</span></p>
        </div>
        
        <div class="detail-section">
            <h4>Profile</h4>
            <p><strong>Bio:</strong> ' . ($user['bio'] ?: 'Not provided') . '</p>
            <p><strong>LinkedIn:</strong> ' . ($user['linkedin_url'] ? '<a href="' . $user['linkedin_url'] . '" target="_blank">' . $user['linkedin_url'] . '</a>' : 'Not provided') . '</p>
            <p><strong>GitHub:</strong> ' . ($user['github_url'] ? '<a href="' . $user['github_url'] . '" target="_blank">' . $user['github_url'] . '</a>' : 'Not provided') . '</p>
        </div>
        
        <div class="detail-section">
            <h4>Statistics</h4>
            <p><strong>Applications:</strong> ' . $user['application_count'] . '</p>
            <p><strong>Saved Jobs:</strong> ' . $user['saved_jobs_count'] . '</p>
            <p><strong>Member Since:</strong> ' . date('M j, Y', strtotime($user['created_at'])) . '</p>
        </div>
    </div>
';

echo json_encode(['success' => true, 'html' => $html]);
?>