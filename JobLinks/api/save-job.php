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
    echo json_encode(['success' => false, 'message' => 'Please login to save jobs', 'redirect' => 'auth/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'])]);
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
    // Check if job exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE id = ? AND is_active = 1");
    $stmt->execute([$jobId]);
    
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
    
    // Check if already saved
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$_SESSION['user_id'], $jobId]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Job already saved']);
        exit;
    }
    
    // Save job
    $stmt = $pdo->prepare("
        INSERT INTO saved_jobs (user_id, job_id) 
        VALUES (?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $jobId]);
    
    // Get updated saved jobs count
    $savedJobsCount = getSavedJobsCount();
    
    echo json_encode(['success' => true, 'message' => 'Job saved successfully', 'saved_jobs_count' => $savedJobsCount]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>