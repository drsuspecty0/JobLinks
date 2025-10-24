<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
 $data = json_decode(file_get_contents('php://input'), true);
 $applicationId = isset($data['application_id']) ? (int)$data['application_id'] : 0;

// Validate data
if ($applicationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

try {
    // Delete application
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$applicationId, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Application withdrawn successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>