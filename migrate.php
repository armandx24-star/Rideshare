<?php
require_once 'includes/config.php';

$results = [];
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $sqls = [
        "ALTER TABLE rides ADD COLUMN IF NOT EXISTS payment_method ENUM('cash','upi') DEFAULT NULL",
        "ALTER TABLE rides ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','confirmed') NOT NULL DEFAULT 'pending'",
        "ALTER TABLE rides MODIFY COLUMN status ENUM('pending','accepted','ongoing','payment_pending','completed','cancelled') NOT NULL DEFAULT 'pending'",
        "ALTER TABLE drivers ADD COLUMN IF NOT EXISTS upi_id VARCHAR(100) DEFAULT NULL",
    ];

    foreach ($sqls as $sql) {
        $pdo->exec($sql);
        $results[] = ['ok', $sql];
    }
    $success = true;
} catch (Exception $e) {
    $results[] = ['err', $e->getMessage()];
    $success = false;
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Migration</title>
<style>body{background:#0f0f0f;color:#f5f5f5;font-family:monospace;padding:30px}
.ok{color:#00C853}.err{color:#FF4757}</style></head>
<body>
<h3>🛠 DB Migration</h3>
<?php foreach ($results as [$s,$m]): ?>
<div class="<?=$s?>">✓ <?=htmlspecialchars($m)?></div>
<?php endforeach; ?>
<?php if ($success): ?>
<p style="color:#00C853;margin-top:16px;font-size:1.2rem">✅ Migration complete! Delete this file now.</p>
<p><a href="<?=BASE_URL?>/user/" style="color:#00bfff">→ User Dashboard</a> | 
   <a href="<?=BASE_URL?>/driver/" style="color:#00bfff">→ Driver Dashboard</a> |
   <a href="<?=BASE_URL?>/admin/" style="color:#00bfff">→ Admin</a></p>
<?php else: ?>
<p style="color:#FF4757;margin-top:16px">❌ Migration failed.</p>
<?php endif; ?>
</body></html>
