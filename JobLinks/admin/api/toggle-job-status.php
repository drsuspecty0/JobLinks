<?php
require_once '../../includes/config.php';

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
 $jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;
 $status = isset($data['status']) ? (int)$data['status'] : 1;

// Validate data
if ($jobId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit;
}

try {
    // Update job status
    $stmt = $pdo->prepare("UPDATE jobs SET is_active = ? WHERE id = ?");
    $stmt->execute([$status, $jobId]);
    
    $statusText = $status ? 'activated' : 'deactivated';
    echo json_encode(['success' => true, 'message' => "Job {$statusText} successfully"]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>