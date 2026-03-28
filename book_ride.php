<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
        exit();
    }

    $userId         = (int) $_SESSION['user_id'];
    $pickupLocation = sanitize($input['pickup_location'] ?? '');
    $dropLocation   = sanitize($input['drop_location']   ?? '');
    $pickupLat      = (float)($input['pickup_lat'] ?? 0);
    $pickupLng      = (float)($input['pickup_lng'] ?? 0);
    $dropLat        = (float)($input['drop_lat']   ?? 0);
    $dropLng        = (float)($input['drop_lng']   ?? 0);
    $distance       = (float)($input['distance']   ?? 0);
    $vehicleType    = $input['vehicle_type'] ?? 'mini';
    $paymentMethod  = $input['payment_method'] ?? 'cash';
    if (!in_array($paymentMethod, ['cash', 'upi', 'online'])) {
        $paymentMethod = 'cash';
    }

    if (!$pickupLocation || !$dropLocation || !$pickupLat || !$pickupLng || !$dropLat || !$dropLng) {
        echo json_encode(['success' => false, 'message' => 'Please fill all location fields.']);
        exit();
    }

    $validTypes = ['bike', 'mini', 'sedan'];
    if (!in_array($vehicleType, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid vehicle type.']);
        exit();
    }

    if ($distance <= 0) {
        $distance = haversineDistance($pickupLat, $pickupLng, $dropLat, $dropLng);
    }

    $db = getDB();

    $userCheck = $db->prepare("SELECT id FROM users WHERE id = ?");
    $userCheck->execute([$userId]);
    if (!$userCheck->fetch()) {
        session_destroy();
        echo json_encode(['success' => false, 'message' => 'Your session is out of date. Please log out and log in again.', 'redirect' => true]);
        exit();
    }

    // Prevent double booking
    $existing = getUserActiveRide($userId);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You already have an active ride.']);
        exit();
    }

    $fare = calculateFare($distance, $vehicleType);

    // Create ride
    $stmt = $db->prepare("
        INSERT INTO rides
            (user_id, pickup_location, drop_location,
             pickup_lat, pickup_lng, drop_lat, drop_lng,
             distance, vehicle_type, fare, status, payment_method)
        VALUES (?,?,?,?,?,?,?,?,?,?,'pending',?)
    ");
    $stmt->execute([
        $userId, $pickupLocation, $dropLocation,
        $pickupLat, $pickupLng, $dropLat, $dropLng,
        $distance, $vehicleType, $fare, $paymentMethod
    ]);
    $rideId = $db->lastInsertId();

    $driverId = assignDriver((int)$rideId);
    if ($driverId) {
        $db->prepare("UPDATE rides SET driver_id=? WHERE id=?")->execute([$driverId, $rideId]);
        echo json_encode(['success' => true, 'message' => 'Driver found!', 'ride_id' => $rideId, 'driver_assigned' => true]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Searching for driver...', 'ride_id' => $rideId, 'driver_assigned' => false]);
    }

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
