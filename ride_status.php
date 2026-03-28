<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('user');
header('Content-Type: application/json');

$rideId = (int)($_GET['ride_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$rideId) {
    echo json_encode(['error' => 'Missing ride_id']);
    exit();
}

$db = getDB();
$stmt = $db->prepare("
    SELECT r.*, d.name AS driver_name, d.phone AS driver_phone,
           d.vehicle_number, d.vehicle_type AS driver_vehicle_type,
           d.lat AS driver_lat, d.lng AS driver_lng
    FROM rides r
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$rideId, $userId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo json_encode(['error' => 'Ride not found']);
    exit();
}

echo json_encode([
    'status'           => $ride['status'],
    'driver_name'      => $ride['driver_name'],
    'driver_phone'     => $ride['driver_phone'],
    'vehicle_number'   => $ride['vehicle_number'],
    'vehicle_type'     => $ride['driver_vehicle_type'],
    'fare'             => $ride['fare'],
    'distance'         => $ride['distance'],
    'driver_lat'       => $ride['driver_lat'],
    'driver_lng'       => $ride['driver_lng'],
    'payment_method'   => $ride['payment_method'],
    'payment_status'   => $ride['payment_status'],
]);
