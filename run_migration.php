<?php
/**
 * Safe DB migration — adds columns/tables only if missing.
 * Run once: http://localhost/arman-new/run_migration.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$log = [];

function safeExec(PDO $db, string $sql, string &$log): void {
    try {
        $db->exec($sql);
        $log .= "✅ OK: " . substr(trim($sql), 0, 80) . "<br>";
    } catch (PDOException $e) {
        $log .= "⚠️ Skipped (already exists or irrelevant): " . $e->getMessage() . "<br>";
    }
}

function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function tableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

ob_start();
echo "<h2>RideShare DB Migration</h2>";

// ── rides table ─────────────────────────────────────────────────────────────
$ridesColumns = [
    'vehicle_type'       => "ALTER TABLE rides ADD COLUMN vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini' AFTER distance",
    'cancellation_reason'=> "ALTER TABLE rides ADD COLUMN cancellation_reason VARCHAR(255) DEFAULT NULL AFTER status",
    'started_at'         => "ALTER TABLE rides ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL AFTER cancellation_reason",
    'completed_at'       => "ALTER TABLE rides ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER started_at",
    'payment_method'     => "ALTER TABLE rides ADD COLUMN payment_method ENUM('cash','upi','online') DEFAULT NULL",
    'payment_status'     => "ALTER TABLE rides ADD COLUMN payment_status ENUM('pending','confirmed') NOT NULL DEFAULT 'pending'",
];
foreach ($ridesColumns as $col => $sql) {
    if (!columnExists($db, 'rides', $col)) {
        safeExec($db, $sql, $log);
    } else {
        $log .= "⏭️ rides.{$col} already exists<br>";
    }
}

// Modify status ENUM to include payment_pending
safeExec($db, "ALTER TABLE rides MODIFY COLUMN status ENUM('pending','accepted','ongoing','payment_pending','completed','cancelled') NOT NULL DEFAULT 'pending'", $log);

// Modify payment_method ENUM to include 'online' (in case it was added as cash/upi only)
safeExec($db, "ALTER TABLE rides MODIFY COLUMN payment_method ENUM('cash','upi','online') DEFAULT NULL", $log);

// ── drivers table ────────────────────────────────────────────────────────────
$driverColumns = [
    'vehicle_type'   => "ALTER TABLE drivers ADD COLUMN vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini'",
    'vehicle_number' => "ALTER TABLE drivers ADD COLUMN vehicle_number VARCHAR(50) NOT NULL DEFAULT ''",
    'license_number' => "ALTER TABLE drivers ADD COLUMN license_number VARCHAR(50) NOT NULL DEFAULT ''",
    'status'         => "ALTER TABLE drivers ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
    'online_status'  => "ALTER TABLE drivers ADD COLUMN online_status TINYINT(1) NOT NULL DEFAULT 0",
    'upi_id'         => "ALTER TABLE drivers ADD COLUMN upi_id VARCHAR(100) DEFAULT NULL",
];
foreach ($driverColumns as $col => $sql) {
    if (!columnExists($db, 'drivers', $col)) {
        safeExec($db, $sql, $log);
    } else {
        $log .= "⏭️ drivers.{$col} already exists<br>";
    }
}

// ── fare_settings table ───────────────────────────────────────────────────────
if (!tableExists($db, 'fare_settings')) {
    safeExec($db, "CREATE TABLE fare_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_type ENUM('bike','mini','sedan') NOT NULL,
        base_fare DECIMAL(10,2) NOT NULL DEFAULT 50.00,
        per_km_rate DECIMAL(10,2) NOT NULL DEFAULT 10.00,
        minimum_fare DECIMAL(10,2) NOT NULL DEFAULT 70.00,
        night_surcharge_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_vehicle (vehicle_type)
    ) ENGINE=InnoDB", $log);

    safeExec($db, "INSERT IGNORE INTO fare_settings (vehicle_type, base_fare, per_km_rate, minimum_fare, night_surcharge_percent) VALUES
        ('bike',30.00,7.00,50.00,20.00),
        ('mini',50.00,10.00,70.00,20.00),
        ('sedan',80.00,14.00,100.00,20.00)", $log);
} else {
    $log .= "⏭️ fare_settings already exists<br>";
}

// ── ratings table ─────────────────────────────────────────────────────────────
if (!tableExists($db, 'ratings')) {
    safeExec($db, "CREATE TABLE ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ride_id INT NOT NULL UNIQUE,
        user_to_driver TINYINT(1) DEFAULT NULL,
        driver_to_user TINYINT(1) DEFAULT NULL,
        user_comment VARCHAR(255) DEFAULT NULL,
        driver_comment VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE
    ) ENGINE=InnoDB", $log);
} else {
    $log .= "⏭️ ratings already exists<br>";
}

// ── driver_rejections table ───────────────────────────────────────────────────
if (!tableExists($db, 'driver_rejections')) {
    safeExec($db, "CREATE TABLE driver_rejections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ride_id INT NOT NULL,
        driver_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_rejection (ride_id, driver_id)
    ) ENGINE=InnoDB", $log);
} else {
    $log .= "⏭️ driver_rejections already exists<br>";
}

echo $log;
echo "<hr><p style='color:green;font-weight:bold'>Migration complete! <a href='/arman-new/user/'>Go to Dashboard →</a></p>";
echo "<p style='color:#aaa;font-size:0.85em'>You can delete this file after running: <code>run_migration.php</code></p>";
ob_end_flush();
