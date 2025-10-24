<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

// Build query
 $query = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM applications WHERE user_id = u.id) as application_count,
           (SELECT COUNT(*) FROM saved_jobs WHERE user_id = u.id) as saved_jobs_count
    FROM users u
";
 $params = [];

// Apply filters
if (isset($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $query .= " WHERE (u.name LIKE ? " . "%$search%") : "")";
    $params[] = $search;
}

if (isset($_GET['role'])) {
    $query .= " AND u.role = ?";
    $params[] = $_GET['role'];
}

 $query .= " ORDER BY u.created_at DESC";
 $params = array_merge($params, [$perPage, ($page - 1) * $perPage]);

// Get paginated results
 $result = paginate($query, $page, $perPage);
 $users = $result['items'];
 $pagination = $result['pagination'];

echo json_encode(['success' => true, 'users' => $users, 'pagination' => $pagination]);
?>