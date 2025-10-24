<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Get search query
 $query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Search query is required']);
    exit;
}

try {
    // Search jobs
    $stmt = $pdo->prepare("
        SELECT j.id, j.title, j.location, j.type, j.salary_min, j.salary_max, c.name as company 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at >= CURDATE())
        AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?) 
        ORDER BY j.created_at DESC 
        LIMIT 10
    ");
    $searchParam = "%$query%";
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'jobs' => $jobs]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>