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
    echo json_encode(['success' => false, 'message' => 'Please login to apply for jobs']);
    exit;
}

// Validate CSRF token
if (!verifyCSRFToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get form data
 $jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
 $coverLetter = isset($_POST['cover_letter']) ? sanitize($_POST['cover_letter']) : '';

// Validate data
if ($jobId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job']);
    exit;
}

if (empty($coverLetter)) {
    echo json_encode(['success' => false, 'message' => 'Cover letter is required']);
    exit;
}

// Handle resume upload
 $resumeUrl = '';
if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['resume']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload PDF or DOC files only.']);
        exit;
    }
    
    if ($_FILES['resume']['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
        exit;
    }
    
    $uploadDir = '../assets/resumes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = 'resume_' . $_SESSION['user_id'] . '_' . time() . '_' . basename($_FILES['resume']['name']);
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadPath)) {
        $resumeUrl = 'assets/resumes/' . $fileName;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload resume. Please try again.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Please upload your resume']);
    exit;
}

try {
    // Check if job exists and is active
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found or expired']);
        exit;
    }
    
    // Check if already applied
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$_SESSION['user_id'], $jobId]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
        exit;
    }
    
    // Create application
    $stmt = $pdo->prepare("
        INSERT INTO applications (job_id, user_id, cover_letter, resume_url) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$jobId, $_SESSION['user_id'], $coverLetter, $resumeUrl]);
    
    // Send notification email to employer
    $stmt = $pdo->prepare("
        SELECT u.email 
        FROM users u 
        JOIN companies c ON u.id = c.user_id 
        WHERE c.id = ?
    ");
    $stmt->execute([$job['company_id']]);
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employer) {
        $subject = "New Application for {$job['title']}";
        $body = "You have received a new application for the position: {$job['title']}\n\nPlease log in to your account to view the application details.\n\nBest regards,\nThe JobLinks Team";
        
        sendEmail($employer['email'], $subject, $body);
    }
    
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>