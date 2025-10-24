<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
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
 $status = isset($data['status']) ? sanitize($data['status']) : '';

// Validate data
if ($applicationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

// Validate status
 $validStatuses = ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Update application status
    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $applicationId]);
    
    echo json_encode(['success' => true, 'message' => 'Application status updated successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>