<?php

require_once __DIR__ . '/db.php';


function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 2);
}


function calculateFare(float $distance, string $vehicleType): float {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM fare_settings WHERE vehicle_type = ?");
    $stmt->execute([$vehicleType]);
    $settings = $stmt->fetch();

    if (!$settings) {
        return 0.0;
    }

    $fare = $settings['base_fare'] + ($distance * $settings['per_km_rate']);

    
    if ($fare < $settings['minimum_fare']) {
        $fare = $settings['minimum_fare'];
    }

    $hour = (int) date('H');
    if ($hour >= 22 || $hour < 6) {
        $fare += $fare * ($settings['night_surcharge_percent'] / 100);
    }

    return round($fare, 2);
}


function getAllFares(float $distance): array {
    $types = ['bike', 'mini', 'sedan'];
    $fares = [];
    foreach ($types as $type) {
        $fares[$type] = calculateFare($distance, $type);
    }
    return $fares;
}

function assignDriver(int $rideId): ?int {
    $db = getDB();

    $rideStmt = $db->prepare("SELECT vehicle_type FROM rides WHERE id = ?");
    $rideStmt->execute([$rideId]);
    $ride = $rideStmt->fetch();
    $vehicleType = $ride['vehicle_type'] ?? null;

    $stmt = $db->prepare("
        SELECT d.id FROM drivers d
        WHERE d.online_status = 1
          AND d.status = 'approved'
          " . ($vehicleType ? "AND d.vehicle_type = :vtype" : "") . "
          AND d.id NOT IN (
              SELECT driver_id FROM driver_rejections WHERE ride_id = :ride_id
          )
          AND d.id NOT IN (
              SELECT driver_id FROM rides
              WHERE status IN ('accepted','ongoing')
              AND driver_id IS NOT NULL
          )
        ORDER BY RAND()
        LIMIT 1
    ");

    $params = [':ride_id' => $rideId];
    if ($vehicleType) {
        $params[':vtype'] = $vehicleType;
    }
    $stmt->execute($params);
    $driver = $stmt->fetch();

    if ($driver) {
        return (int) $driver['id'];
    }
    return null;
}


function estimateTime(float $distanceKm): int {
    return (int) ceil(($distanceKm / 30) * 60);
}


function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function formatCurrency(mixed $amount): string {
    return '₹' . number_format((float)($amount ?? 0), 2);
}


function statusBadge(string $status): string {
    $classes = [
        'pending'         => 'badge-pending',
        'accepted'        => 'badge-accepted',
        'ongoing'         => 'badge-ongoing',
        'completed'       => 'badge-completed',
        'cancelled'       => 'badge-cancelled',
        'payment_pending' => 'bg-info text-dark',
    ];
    $labels = [
        'payment_pending' => 'Payment Pending',
    ];
    $cls   = $classes[$status] ?? 'badge-secondary';
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}


function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}


function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';

    $types = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];
    $cls = $types[$flash['type']] ?? 'alert-info';
    return '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
         . htmlspecialchars($flash['message'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}


function getUserActiveRide(int $userId): ?array {
    $db = getDB();
    // Include 'completed' rides from last 24h so the rating form is shown
    $stmt = $db->prepare("
        SELECT r.*, d.name AS driver_name, d.phone AS driver_phone,
               d.vehicle_number, d.vehicle_type, d.upi_id AS driver_upi_id
        FROM rides r
        LEFT JOIN drivers d ON r.driver_id = d.id
        WHERE r.user_id = ?
          AND (
            r.status IN ('pending','accepted','ongoing','payment_pending')
            OR (r.status = 'completed' AND r.completed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
          )
        ORDER BY r.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function getDriverActiveRide(int $driverId): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, u.name AS user_name, u.phone AS user_phone
        FROM rides r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.driver_id = ?
          AND r.status IN ('accepted','ongoing','payment_pending')
        ORDER BY r.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$driverId]);
    return $stmt->fetch() ?: null;
}


function getPendingRideForDriver(int $driverId): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, u.name AS user_name, u.phone AS user_phone
        FROM rides r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = 'pending'
          AND r.driver_id = ?
          AND r.driver_id NOT IN (
              SELECT driver_id FROM driver_rejections WHERE ride_id = r.id
          )
        LIMIT 1
    ");
    $stmt->execute([$driverId]);
    return $stmt->fetch() ?: null;
}
