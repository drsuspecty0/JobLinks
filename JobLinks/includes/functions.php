<?php
// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Display message
function showMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Get message
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Pagination
function paginate($query, $page = 1, $perPage = 10) {
    global $pdo;
    
    $offset = ($page - 1) * $perPage;
    
    // Get total items
    $countQuery = str_replace('SELECT *', 'SELECT COUNT(*)', $query);
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute();
    $totalItems = $stmt->fetchColumn();
    
    // Get items for current page
    $query .= " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $totalPages = ceil($totalItems / $perPage);
    
    return [
        'items' => $items,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page - 1,
            'next_page' => $page + 1
        ]
    ];
}

// Send email (simplified version)
function sendEmail($to, $subject, $body) {
    // In a real application, you would use a library like PHPMailer
    // For demo purposes, we'll just log the email
    $log = "To: $to\nSubject: $subject\nBody: $body\n\n";
    file_put_contents('email_log.txt', $log, FILE_APPEND);
    return true;
}

// Generate slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Get unique slug
function getUniqueSlug($table, $column, $text, $id = null) {
    global $pdo;
    
    $slug = generateSlug($text);
    $originalSlug = $slug;
    $counter = 1;
    
    while (true) {
        $query = "SELECT COUNT(*) FROM $table WHERE $column = ?";
        $params = [$slug];
        
        if ($id) {
            $query .= " AND id != ?";
            $params[] = $id;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        if ($stmt->fetchColumn() == 0) {
            return $slug;
        }
        
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
}

// Time ago function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}

// Get initials from name
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper($word[0]);
    }
    return substr($initials, 0, 2);
}

// Get category icon
function getCategoryIcon($category) {
    $icons = [
        'Technology' => 'laptop-code',
        'Marketing' => 'bullhorn',
        'Healthcare' => 'heartbeat',
        'Design' => 'palette',
        'Finance' => 'chart-line',
        'Education' => 'graduation-cap'
    ];
    return isset($icons[$category]) ? $icons[$category] : 'briefcase';
}
?>