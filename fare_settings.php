<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Fare Settings — Admin';
include 'includes/sidebar.php';

$db = getDB();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $types = ['bike','mini','sedan'];
    foreach ($types as $type) {
        $base    = (float)($_POST["base_{$type}"] ?? 0);
        $per_km  = (float)($_POST["perkm_{$type}"] ?? 0);
        $min     = (float)($_POST["min_{$type}"] ?? 0);
        $night   = (float)($_POST["night_{$type}"] ?? 0);

        if ($base > 0 && $per_km > 0 && $min > 0) {
            $db->prepare("
                UPDATE fare_settings
                SET base_fare=?, per_km_rate=?, minimum_fare=?, night_surcharge_percent=?
                WHERE vehicle_type=?
            ")->execute([$base, $per_km, $min, $night, $type]);
        }
    }
    $flash = 'Fare settings updated successfully!';
}

$stmt = $db->query("SELECT * FROM fare_settings ORDER BY FIELD(vehicle_type,'bike','mini','sedan')");
$fares = [];
while ($row = $stmt->fetch()) {
    $fares[$row['vehicle_type']] = $row;
}
?>
<div class="container-fluid py-4 px-4" style="max-width:900px">
  <div class="page-header">
    <h2>💰 Fare Settings</h2>
    <p>Configure pricing for each vehicle type. Changes apply to all new bookings immediately.</p>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-success"><?php echo $flash; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="row g-4">
      <?php
      $icons = ['bike'=>'🏍️','mini'=>'🚗','sedan'=>'🚙'];
      foreach (['bike','mini','sedan'] as $type):
          $f = $fares[$type] ?? ['base_fare'=>50,'per_km_rate'=>10,'minimum_fare'=>70,'night_surcharge_percent'=>20];
      ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-header">
            <?php echo $icons[$type]; ?> <?php echo ucfirst($type); ?>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Base Fare (₹)</label>
              <input type="number" name="base_<?php echo $type; ?>" class="form-control"
                     value="<?php echo $f['base_fare']; ?>" step="0.5" min="1" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Per KM Rate (₹)</label>
              <input type="number" name="perkm_<?php echo $type; ?>" class="form-control"
                     value="<?php echo $f['per_km_rate']; ?>" step="0.5" min="1" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Minimum Fare (₹)</label>
              <input type="number" name="min_<?php echo $type; ?>" class="form-control"
                     value="<?php echo $f['minimum_fare']; ?>" step="0.5" min="1" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Night Surcharge (%)</label>
              <input type="number" name="night_<?php echo $type; ?>" class="form-control"
                     value="<?php echo $f['night_surcharge_percent']; ?>" step="1" min="0" max="100" required>
              <small style="color:#9E9E9E">Applied 10 PM – 6 AM</small>
            </div>
            <div style="background:rgba(0,200,83,0.06);border:1px solid rgba(0,200,83,0.2);border-radius:8px;padding:12px;font-size:0.85rem">
              <div>Example 5km trip: <strong class="text-primary">
                ₹<?php echo number_format($f['base_fare'] + 5 * $f['per_km_rate'], 2); ?>
              </strong></div>
              <div style="color:#9E9E9E">Min: ₹<?php echo $f['minimum_fare']; ?> · Night: +<?php echo $f['night_surcharge_percent']; ?>%</div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-4 text-center">
      <button type="submit" class="btn btn-primary btn-lg px-5">💾 Save All Fare Settings</button>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
