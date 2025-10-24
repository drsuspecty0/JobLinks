<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
 $data = json_decode(file_get_contents('php://input'), true);
 $email = isset($data['email']) ? sanitize($data['email']) : '';

// Validate email
if (empty($email) || !validateEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetchColumn() > 0) {
        // Update existing subscription
        $stmt = $pdo->prepare("
            UPDATE newsletter 
            SET subscribed = 1, updated_at = NOW() 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);
    } else {
        // Add new subscription
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("
            INSERT INTO newsletter (email, token, subscribed) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$email, $token]);
        
        // Send confirmation email
        $subject = "Confirm Your Subscription to JobLinks Newsletter";
        $body = "Thank you for subscribing to the JobLinks newsletter! You will now receive updates about the latest job opportunities and career advice.\n\nIf you did not subscribe to this newsletter, please ignore this email.\n\nBest regards,\nThe JobLinks Team";
        
        sendEmail($email, $subject, $body);
        
        echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>