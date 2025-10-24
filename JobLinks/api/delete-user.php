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
 $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;

// Validate data
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent deletion of admin users
 $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
 $stmt->execute([$userId]);
 $userRole = $stmt->fetchColumn();

if ($userRole === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete user's applications
    $stmt = $pdo->prepare("DELETE FROM applications WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Delete user's saved jobs
    $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Delete user's company if they're an employer
    $stmt = $pdo->prepare("DELETE FROM companies WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    
} catch (PDOException $e) {
    // Rollback transaction
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>