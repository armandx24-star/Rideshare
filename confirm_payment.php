<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('driver');
header('Content-Type: application/json');

$input     = json_decode(file_get_contents('php://input'), true);
$rideId    = (int)($input['ride_id'] ?? 0);
$confirmed = (bool)($input['confirmed'] ?? false);
$driverId  = $_SESSION['driver_id'];

if (!$rideId) {
    echo json_encode(['success' => false, 'message' => 'Invalid ride.']);
    exit();
}

$db = getDB();

// Verify ride belongs to driver and is in payment_pending state
$stmt = $db->prepare("SELECT * FROM rides WHERE id=? AND driver_id=? AND status='payment_pending'");
$stmt->execute([$rideId, $driverId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['success' => false, 'message' => 'Ride not found.']);
    exit();
}

if ($confirmed) {
    $db->prepare("UPDATE rides SET status='completed', payment_status='confirmed', completed_at=NOW() WHERE id=?")
       ->execute([$rideId]);
    echo json_encode([
        'success' => true,
        'message' => 'Payment confirmed! Ride completed. Fare: ' . formatCurrency($ride['fare']),
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Waiting for payment...',
    ]);
}
