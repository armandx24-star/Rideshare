<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit();
    }

    $distance = (float)($_GET['distance'] ?? 0);
    if ($distance <= 0) {
        echo json_encode(['error' => 'Invalid distance']);
        exit();
    }

    echo json_encode(getAllFares($distance));
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
