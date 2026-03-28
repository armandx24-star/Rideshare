<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('user');
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$rideId = (int)($input['ride_id'] ?? 0);
$reason = sanitize($input['reason'] ?? '');
$userId = $_SESSION['user_id'];

if (!$rideId) {
    echo json_encode(['success' => false, 'message' => 'Invalid ride.']);
    exit();
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM rides WHERE id=? AND user_id=?");
$stmt->execute([$rideId, $userId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['success' => false, 'message' => 'Ride not found.']);
    exit();
}
if (!in_array($ride['status'], ['pending'])) {
    echo json_encode(['success' => false, 'message' => 'Only pending rides can be cancelled.']);
    exit();
}

$stmt2 = $db->prepare("UPDATE rides SET status='cancelled', cancellation_reason=? WHERE id=?");
$stmt2->execute([$reason, $rideId]);

echo json_encode(['success' => true, 'message' => 'Ride cancelled.']);
