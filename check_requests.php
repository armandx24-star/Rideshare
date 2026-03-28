<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('driver');
header('Content-Type: application/json');

$driverId = $_SESSION['driver_id'];
$db = getDB();

// Get driver vehicle type
$driverStmt = $db->prepare("SELECT vehicle_type, online_status, status FROM drivers WHERE id = ?");
$driverStmt->execute([$driverId]);
$driverRow = $driverStmt->fetch();

if (!$driverRow || !$driverRow['online_status'] || $driverRow['status'] !== 'approved') {
    echo json_encode(['has_request' => false]);
    exit();
}

$stmt = $db->prepare("
    SELECT COUNT(*) FROM rides
    WHERE status = 'pending'
      AND (
          driver_id = :driver_id
          OR (
              driver_id IS NULL
              AND vehicle_type = :vtype
              AND :driver_id NOT IN (
                  SELECT driver_id FROM driver_rejections WHERE ride_id = rides.id
              )
          )
      )
");
$stmt->execute([':driver_id' => $driverId, ':vtype' => $driverRow['vehicle_type']]);
$count = (int)$stmt->fetchColumn();

echo json_encode(['has_request' => $count > 0]);
