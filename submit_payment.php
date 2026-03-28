<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('user');
header('Content-Type: application/json');

$input         = json_decode(file_get_contents('php://input'), true);
$rideId        = (int)($input['ride_id'] ?? 0);
$paymentMethod = $input['payment_method'] ?? '';
$userId        = $_SESSION['user_id'];

if (!$rideId || !in_array($paymentMethod, ['cash', 'upi'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM rides WHERE id=? AND user_id=? AND status='payment_pending'");
$stmt->execute([$rideId, $userId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['success' => false, 'message' => 'Ride not found or not in payment state.']);
    exit();
}

$db->prepare("UPDATE rides SET payment_method=? WHERE id=?")
   ->execute([$paymentMethod, $rideId]);

$driverUpi = null;
if ($paymentMethod === 'upi' && $ride['driver_id']) {
    $driverStmt = $db->prepare("SELECT upi_id FROM drivers WHERE id=?");
    $driverStmt->execute([$ride['driver_id']]);
    $driverUpi = $driverStmt->fetchColumn();
}

echo json_encode([
    'success'    => true,
    'message'    => 'Payment method submitted.',
    'driver_upi' => $driverUpi,
    'fare'       => $ride['fare'],
]);
