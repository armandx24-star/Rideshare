<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('driver');
header('Content-Type: application/json');

$input    = json_decode(file_get_contents('php://input'), true);
$rideId   = (int)($input['ride_id'] ?? 0);
$driverId = $_SESSION['driver_id'];
$db = getDB();

if (!$rideId) {
    echo json_encode(['success' => false, 'message' => 'Invalid ride.']);
    exit();
}

$db->prepare("
    INSERT IGNORE INTO driver_rejections (ride_id, driver_id) VALUES (?,?)
")->execute([$rideId, $driverId]);

$db->prepare("UPDATE rides SET driver_id=NULL WHERE id=? AND status='pending'")->execute([$rideId]);

$newDriver = assignDriver($rideId);
if ($newDriver) {
    $db->prepare("UPDATE rides SET driver_id=? WHERE id=?")->execute([$newDriver, $rideId]);
} else {
    $db->prepare("UPDATE rides SET driver_id=NULL WHERE id=?")->execute([$rideId]);
}

echo json_encode(['success' => true, 'message' => 'Ride rejected.']);
