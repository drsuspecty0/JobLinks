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
    SELECT c.*, u.name as owner_name,
           (SELECT COUNT(*) FROM jobs WHERE company_id = c.id) as job_count
    FROM companies c
    LEFT JOIN users u ON c.user_id = u.id
";
 $params = [$perPage, ($page - 1) * $perPage];

// Apply filters
if (isset($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $query .= " AND (c.name LIKE ? " . "%$search%") : "")";
    $params[] = $search;
}

if (isset($_GET['industry'])) {
    $query .= " AND c.industry = ?";
    $params[] = $_GET['industry'];
}

 $query .= " ORDER BY c.created_at DESC";
 $params = array_merge($params, [$perPage, ($page - 1) * $perPage]);

// Get paginated results
 $result = paginate($query, $page, $perPage);
 $companies = $result['items'];
 $pagination = $result['pagination'];

echo json_encode(['success' => true, 'companies' => $companies, 'pagination' => $pagination]);
?>