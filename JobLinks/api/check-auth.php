<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

echo json_encode(['logged_in' => isLoggedIn()]);
?>