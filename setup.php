<?php
require_once 'includes/config.php';

$log   = [];
$error = null;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    $log[] = ['ok', 'Database "' . DB_NAME . '" selected'];

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach (['driver_rejections','ratings','rides','fare_settings','admin','drivers','users'] as $tbl) {
        $pdo->exec("DROP TABLE IF EXISTS `$tbl`");
        $log[] = ['ok', "Dropped table: $tbl"];
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        profile_pic VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: users'];

    $pdo->exec("CREATE TABLE drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini',
        vehicle_number VARCHAR(50) NOT NULL,
        license_number VARCHAR(50) NOT NULL,
        profile_pic VARCHAR(255) DEFAULT NULL,
        document_path VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        online_status TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: drivers'];

    // ── Create admin ──────────────────────────────────────────
    $pdo->exec("CREATE TABLE admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: admin'];

    // ── Create fare_settings ──────────────────────────────────
    $pdo->exec("CREATE TABLE fare_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_type ENUM('bike','mini','sedan') NOT NULL,
        base_fare DECIMAL(10,2) NOT NULL DEFAULT 50.00,
        per_km_rate DECIMAL(10,2) NOT NULL DEFAULT 10.00,
        minimum_fare DECIMAL(10,2) NOT NULL DEFAULT 70.00,
        night_surcharge_percent DECIMAL(5,2) NOT NULL DEFAULT 20.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_vehicle (vehicle_type)
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: fare_settings'];

    // ── Create rides ──────────────────────────────────────────
    $pdo->exec("CREATE TABLE rides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        driver_id INT DEFAULT NULL,
        pickup_location VARCHAR(255) NOT NULL,
        drop_location VARCHAR(255) NOT NULL,
        pickup_lat DECIMAL(10,8) NOT NULL DEFAULT 0,
        pickup_lng DECIMAL(11,8) NOT NULL DEFAULT 0,
        drop_lat DECIMAL(10,8) NOT NULL DEFAULT 0,
        drop_lng DECIMAL(11,8) NOT NULL DEFAULT 0,
        distance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        vehicle_type ENUM('bike','mini','sedan') NOT NULL DEFAULT 'mini',
        fare DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('pending','accepted','ongoing','payment_pending','completed','cancelled') NOT NULL DEFAULT 'pending',
        payment_method ENUM('cash','upi') DEFAULT NULL,
        payment_status ENUM('pending','confirmed') NOT NULL DEFAULT 'pending',
        cancellation_reason VARCHAR(255) DEFAULT NULL,
        started_at TIMESTAMP NULL DEFAULT NULL,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: rides'];

    // ── Create ratings ────────────────────────────────────────
    $pdo->exec("CREATE TABLE ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ride_id INT NOT NULL UNIQUE,
        user_to_driver TINYINT(1) DEFAULT NULL,
        driver_to_user TINYINT(1) DEFAULT NULL,
        user_comment VARCHAR(255) DEFAULT NULL,
        driver_comment VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: ratings'];

    // ── Create driver_rejections ──────────────────────────────
    $pdo->exec("CREATE TABLE driver_rejections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ride_id INT NOT NULL,
        driver_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_rejection (ride_id, driver_id)
    ) ENGINE=InnoDB");
    $log[] = ['ok', 'Created table: driver_rejections'];

    $pdo->exec("INSERT INTO admin (username, password) VALUES
        ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");
    $log[] = ['ok', 'Admin account created (admin / admin123)'];

    $pdo->exec("INSERT INTO fare_settings (vehicle_type, base_fare, per_km_rate, minimum_fare, night_surcharge_percent) VALUES
        ('bike',  30.00, 7.00,  50.00, 20.00),
        ('mini',  50.00, 10.00, 70.00, 20.00),
        ('sedan', 80.00, 14.00, 100.00, 20.00)");
    $log[] = ['ok', 'Fare settings seeded'];

    $pdo->exec("INSERT INTO users (name, email, phone, password) VALUES
        ('Demo User', 'user@demo.com', '9876543210',
         '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");
    $log[] = ['ok', 'Demo user created (user@demo.com / password)'];

    $pdo->exec("INSERT INTO drivers (name, email, phone, password, vehicle_type, vehicle_number, license_number, status, online_status) VALUES
        ('Demo Driver', 'driver@demo.com', '9123456780',
         '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
         'mini', 'MH01AB1234', 'DL1234567890', 'approved', 0)");
    $log[] = ['ok', 'Demo driver created (driver@demo.com / password)'];

    $success = true;

} catch (Exception $e) {
    $error   = $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RideShare Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:#0f0f0f;color:#f5f5f5;font-family:'Segoe UI',sans-serif}
  .card{background:#1a1a1a;border:1px solid rgba(255,255,255,0.08);border-radius:12px}
  .ok{color:#00C853}.skip{color:#9E9E9E}.box-ok{background:rgba(0,200,83,0.12);border:1px solid rgba(0,200,83,0.4);color:#00C853;border-radius:10px;padding:16px}
  .box-err{background:rgba(255,71,87,0.12);border:1px solid rgba(255,71,87,0.4);color:#FF4757;border-radius:10px;padding:16px}
  .btn-primary{background:#00C853;border:none}.btn-primary:hover{background:#00a846}
  code{color:#F7C948}
</style>
</head>
<body>
<div class="container py-5" style="max-width:680px">
  <h3 class="mb-1">🛠 RideShare — Database Setup</h3>
  <p style="color:#9E9E9E" class="mb-4">Drops &amp; recreates all tables with correct schema</p>

  <?php if (!$success): ?>
    <div class="box-err mb-4">
      <strong>❌ Error</strong><br><code><?php echo htmlspecialchars($error ?? 'Unknown'); ?></code>
      <hr style="border-color:rgba(255,71,87,0.3)">
      Check <code>includes/config.php</code> — make sure <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> are correct.
    </div>
  <?php else: ?>
    <div class="box-ok mb-4">
      <strong>✅ Database fully rebuilt!</strong> All tables created with correct schema + demo data seeded.
    </div>

    <div class="card p-4 mb-4">
      <h6 class="mb-3">📋 Setup Log</h6>
      <div style="max-height:260px;overflow-y:auto">
        <?php foreach ($log as [$s,$m]): ?>
        <div class="<?php echo $s; ?> mb-1" style="font-size:0.82rem">✓ <?php echo htmlspecialchars($m); ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card p-4 mb-4">
      <h6>🔑 Login Credentials</h6>
      <table class="table table-dark table-sm mt-2 mb-0">
        <tr><th>Role</th><th>Email / Username</th><th>Password</th></tr>
        <tr><td>👤 User</td><td>user@demo.com</td><td>password</td></tr>
        <tr><td>🚗 Driver</td><td>driver@demo.com</td><td>password</td></tr>
        <tr><td>🛠 Admin</td><td>admin</td><td>admin123</td></tr>
      </table>
    </div>

    <div class="d-flex gap-3">
      <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary btn-lg flex-fill">✓ Register</a>
      <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-light btn-lg flex-fill">Login</a>
      <a href="<?php echo BASE_URL; ?>/admin/login.php" class="btn btn-outline-light btn-lg flex-fill">Admin</a>
    </div>

    <p class="text-center mt-3" style="font-size:0.78rem;color:#666">
      ⚠️ Delete <code>setup.php</code> from your project once done.
    </p>
  <?php endif; ?>
</div>
</body>
</html>
