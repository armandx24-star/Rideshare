<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireRole('driver');
header('Content-Type: application/json');

$driverId = $_SESSION['driver_id'];
$db = getDB();

$stmt = $db->prepare("SELECT online_status, status FROM drivers WHERE id=?");
$stmt->execute([$driverId]);
$driver = $stmt->fetch();

if ($driver['status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Account not approved.']);
    exit();
}

$newStatus = $driver['online_status'] ? 0 : 1;
$db->prepare("UPDATE drivers SET online_status=? WHERE id=?")->execute([$newStatus, $driverId]);
echo json_encode(['success' => true, 'online_status' => $newStatus]);
