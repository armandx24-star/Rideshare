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

$drvStmt = $db->prepare("SELECT status, online_status FROM drivers WHERE id=?");
$drvStmt->execute([$driverId]);
$drv = $drvStmt->fetch();
if ($drv['status'] !== 'approved' || !$drv['online_status']) {
    echo json_encode(['success' => false, 'message' => 'You must be online and approved.']);
    exit();
}

$active = getDriverActiveRide($driverId);
if ($active) {
    echo json_encode(['success' => false, 'message' => 'You already have an active ride.']);
    exit();
}

// Verify the ride belongs to this driver and is still pending
$stmt = $db->prepare("SELECT * FROM rides WHERE id=? AND driver_id=? AND status='pending'");
$stmt->execute([$rideId, $driverId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['success' => false, 'message' => 'Ride no longer available.']);
    exit();
}

$db->prepare("UPDATE rides SET status='accepted' WHERE id=?")->execute([$rideId]);
echo json_encode([
    'success'    => true,
    'message'    => 'Ride accepted! Head to pickup location.',
    'pickup_lat' => $ride['pickup_lat'],
    'pickup_lng' => $ride['pickup_lng'],
]);
