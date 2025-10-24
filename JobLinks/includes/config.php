<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'joblinks');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost/joblinks');
define('SITE_NAME', 'JobLinks');

// Start session
session_start();

// Connect to database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include functions
require_once 'functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

// Check if user is employer
function isEmployer() {
    return isLoggedIn() && $_SESSION['user_role'] === 'employer';
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format salary
function formatSalary($min, $max, $currency = 'USD') {
    if ($min && $max) {
        return $currency . ' ' . number_format($min) . ' - ' . number_format($max);
    } elseif ($min) {
        return $currency . ' ' . number_format($min) . '+';
    }
    return 'Competitive';
}

// Get application count
function getApplicationCount() {
    if (!isLoggedIn() || !isEmployer()) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.company_id IN (SELECT id FROM companies WHERE user_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// Get saved jobs count
function getSavedJobsCount() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// Check dark mode preference
function isDarkMode() {
    if (isset($_COOKIE['dark_mode'])) {
        return $_COOKIE['dark_mode'] === 'true';
    }
    return false;
}
?>