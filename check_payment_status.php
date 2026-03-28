<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('driver');
header('Content-Type: application/json');

$rideId   = (int)($_GET['ride_id'] ?? 0);
$driverId = $_SESSION['driver_id'];

if (!$rideId) {
    echo json_encode(['payment_method' => null]);
    exit();
}

$db = getDB();
$stmt = $db->prepare("SELECT payment_method FROM rides WHERE id=? AND driver_id=? AND status='payment_pending'");
$stmt->execute([$rideId, $driverId]);
$row = $stmt->fetch();

echo json_encode(['payment_method' => $row ? $row['payment_method'] : null]);
