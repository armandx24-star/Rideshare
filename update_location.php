<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('driver');
header('Content-Type: application/json');

$input    = json_decode(file_get_contents('php://input'), true);
$lat      = (float)($input['lat'] ?? 0);
$lng      = (float)($input['lng'] ?? 0);
$driverId = $_SESSION['driver_id'];

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates.']);
    exit();
}

$db = getDB();
$db->prepare("UPDATE drivers SET lat=?, lng=?, location_updated_at=NOW() WHERE id=?")
   ->execute([$lat, $lng, $driverId]);

echo json_encode(['success' => true]);
