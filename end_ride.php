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

$stmt = $db->prepare("SELECT * FROM rides WHERE id=? AND driver_id=? AND status='ongoing'");
$stmt->execute([$rideId, $driverId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['success' => false, 'message' => 'Ride not found or not ongoing.']);
    exit();
}

$db->prepare("UPDATE rides SET status='payment_pending' WHERE id=?")
   ->execute([$rideId]);

echo json_encode([
    'success' => true,
    'message' => 'Ride ended! Please collect payment from passenger.',
    'fare'    => $ride['fare'],
]);
