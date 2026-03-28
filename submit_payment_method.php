<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('driver');
header('Content-Type: application/json');

$input         = json_decode(file_get_contents('php://input'), true);
$rideId        = (int)($input['ride_id'] ?? 0);
$paymentMethod = $input['payment_method'] ?? '';
$driverId      = $_SESSION['driver_id'];

if (!$rideId || !in_array($paymentMethod, ['cash', 'upi'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM rides WHERE id=? AND driver_id=? AND status='payment_pending'");
$stmt->execute([$rideId, $driverId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['success' => false, 'message' => 'Ride not found or already completed.']);
    exit();
}

$db->prepare("UPDATE rides SET payment_method=? WHERE id=?")
   ->execute([$paymentMethod, $rideId]);

$upiId = null;
if ($paymentMethod === 'upi') {
    $drvStmt = $db->prepare("SELECT upi_id FROM drivers WHERE id=?");
    $drvStmt->execute([$driverId]);
    $upiId = $drvStmt->fetchColumn();
}

echo json_encode([
    'success'  => true,
    'method'   => $paymentMethod,
    'upi_id'   => $upiId,
    'fare'     => $ride['fare'],
    'message'  => 'Payment method set to ' . strtoupper($paymentMethod) . '.',
]);
