<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $perPage = 10;

// Build query
 $query = "
    SELECT j.*, c.name as company_name
    FROM jobs j
    JOIN companies c ON j.company_id = c.id
    ORDER BY j.created_at DESC
";
 $params = [];

// Apply filters
if (isset($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $query .= " AND (j.title LIKE ? " . "%$search%') : "")";
    $params[] = $search;
}

if (isset($_GET['category'])) {
    $query .= " AND j.category = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['type'])) {
    $query .= " AND j.type = ?";
    $params[] = $_GET['type'];
}

if (isset($_GET['min_price'])) {
    $query .= " AND j.salary_min >= ?";
    $params[] = $_GET['min_price'];
}

if (isset($_GET['max_price'])) {
    $query .= " AND j.salary_max <= ?";
    $params[] = $_GET['max_price'];
}

 $query .= " ORDER BY j.created_at DESC";
 $params = array_merge($params, [$perPage, ($page - 1) * $perPage]);

// Get paginated results
 $result = paginate($query, $page, $perPage);
 $jobs = $result['items'];
 $pagination = $result['pagination'];

echo json_encode(['success' => true, 'jobs' => $jobs, 'pagination' => $pagination]);
?>