<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('user');
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$rideId = (int)($input['ride_id'] ?? 0);
$rating = (int)($input['rating'] ?? 0);
$comment= sanitize($input['comment'] ?? '');
$userId = $_SESSION['user_id'];

if (!$rideId || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating.']);
    exit();
}

$db = getDB();

$stmt = $db->prepare("SELECT id FROM rides WHERE id=? AND user_id=? AND status='completed'");
$stmt->execute([$rideId, $userId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Ride not found or not completed.']);
    exit();
}

$stmt2 = $db->prepare("
    INSERT INTO ratings (ride_id, user_to_driver, user_comment)
    VALUES (?,?,?)
    ON DUPLICATE KEY UPDATE user_to_driver=VALUES(user_to_driver), user_comment=VALUES(user_comment)
");
$stmt2->execute([$rideId, $rating, $comment]);

echo json_encode(['success' => true, 'message' => 'Rating submitted!']);
