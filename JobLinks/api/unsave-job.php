<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to update your saved jobs']);
    exit;
}

// Get JSON data
 $data = json_decode(file_get_contents('php://input'), true);
 $jobId = isset($data['job_id']) ? (int)$data['job_id'] : 0;

// Validate data
if ($jobId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job']);
    exit;
}

try {
    // Remove saved job
    $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$_SESSION['user_id'], $jobId]);
    
    // Get updated saved jobs count
    $savedJobsCount = getSavedJobsCount();
    
    echo json_encode(['success' => true, 'message' => 'Job removed from saved jobs', 'saved_jobs_count' => $savedJobsCount]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>