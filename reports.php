<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Reports — Admin';
include 'includes/sidebar.php';

$db = getDB();

$dateFrom = $_GET['from'] ?? date('Y-m-01');  
$dateTo   = $_GET['to']   ?? date('Y-m-d');    
$stmt = $db->prepare("
    SELECT
        DATE(created_at) AS day,
        COUNT(*) AS total_rides,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
        SUM(CASE WHEN status='completed' THEN fare ELSE 0 END) AS revenue,
        SUM(CASE WHEN vehicle_type='bike'  AND status='completed' THEN 1 ELSE 0 END) AS bike_rides,
        SUM(CASE WHEN vehicle_type='mini'  AND status='completed' THEN 1 ELSE 0 END) AS mini_rides,
        SUM(CASE WHEN vehicle_type='sedan' AND status='completed' THEN 1 ELSE 0 END) AS sedan_rides
    FROM rides
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY day DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$rows = $stmt->fetchAll();

$totals = [
    'rides'=>0,'completed'=>0,'cancelled'=>0,'revenue'=>0.0,'bike'=>0,'mini'=>0,'sedan'=>0
];
foreach ($rows as $r) {
    $totals['rides']     += $r['total_rides'];
    $totals['completed'] += $r['completed'];
    $totals['cancelled'] += $r['cancelled'];
    $totals['revenue']   += $r['revenue'];
    $totals['bike']      += $r['bike_rides'];
    $totals['mini']      += $r['mini_rides'];
    $totals['sedan']     += $r['sedan_rides'];
}
?>
<div class="container-fluid py-4 px-4">
  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h2>📈 Reports</h2>
      <p>Daily revenue &amp; ride summary</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">🖨 Print Report</button>
  </div>

  <form method="GET" class="d-flex gap-2 align-items-center mb-4 flex-wrap">
    <div>
      <label class="form-label mb-1">From</label>
      <input type="date" name="from" class="form-control" value="<?php echo $dateFrom; ?>">
    </div>
    <div>
      <label class="form-label mb-1">To</label>
      <input type="date" name="to" class="form-control" value="<?php echo $dateTo; ?>">
    </div>
    <div style="margin-top:22px">
      <button type="submit" class="btn btn-primary">Apply</button>
    </div>
    <div style="margin-top:22px">
      <a href="?from=<?php echo date('Y-m-01'); ?>&to=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary">This Month</a>
    </div>
  </form>

  <div class="row g-3 mb-4">
    <?php
    $summaryItems = [
        ['💰', formatCurrency($totals['revenue']), 'Total Revenue', 'stat-green'],
        ['🚗', $totals['rides'],       'Total Rides',     'stat-blue'],
        ['✅', $totals['completed'],   'Completed',       'stat-yellow'],
        ['✕',  $totals['cancelled'],   'Cancelled',       'stat-red'],
    ];
    foreach ($summaryItems as $item) {
        echo "<div class='col-6 col-md-3'>
            <div class='stat-card {$item[3]}'>
              <div class='stat-icon'>{$item[0]}</div>
              <div class='stat-value'>{$item[1]}</div>
              <div class='stat-label'>{$item[2]}</div>
            </div>
          </div>";
    }
    ?>
  </div>

  <div class="row g-3 mb-4">
    <?php
    foreach (['bike'=>'🏍️','mini'=>'🚗','sedan'=>'🚙'] as $t => $icon) {
        echo "<div class='col-4'>
            <div class='card text-center p-3'>
              <div style='font-size:1.8rem'>{$icon}</div>
              <div style='font-size:1.4rem;font-weight:800;color:#00C853'>{$totals[$t]}</div>
              <div style='color:#9E9E9E;font-size:0.8rem'>".ucfirst($t)." rides</div>
            </div>
          </div>";
    }
    ?>
  </div>

  <div class="card">
    <div class="card-header">📅 Daily Breakdown</div>
    <div class="card-body p-0">
      <table class="table-dark-custom w-100">
        <thead>
          <tr><th>Date</th><th>Total Rides</th><th>Completed</th><th>Cancelled</th><th>🏍 Bike</th><th>🚗 Mini</th><th>🚙 Sedan</th><th>Revenue</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center py-4" style="color:#9E9E9E">No data for selected range.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><strong><?php echo date('d M Y (D)', strtotime($r['day'])); ?></strong></td>
            <td><?php echo $r['total_rides']; ?></td>
            <td style="color:#00C853"><?php echo $r['completed']; ?></td>
            <td style="color:#FF4757"><?php echo $r['cancelled']; ?></td>
            <td><?php echo $r['bike_rides']; ?></td>
            <td><?php echo $r['mini_rides']; ?></td>
            <td><?php echo $r['sedan_rides']; ?></td>
            <td class="text-primary fw-bold"><?php echo formatCurrency($r['revenue']); ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
        <tfoot>
          <tr style="background:rgba(0,200,83,0.05);font-weight:700">
            <td>TOTAL</td>
            <td><?php echo $totals['rides']; ?></td>
            <td style="color:#00C853"><?php echo $totals['completed']; ?></td>
            <td style="color:#FF4757"><?php echo $totals['cancelled']; ?></td>
            <td><?php echo $totals['bike']; ?></td>
            <td><?php echo $totals['mini']; ?></td>
            <td><?php echo $totals['sedan']; ?></td>
            <td class="text-primary"><?php echo formatCurrency($totals['revenue']); ?></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>
</div>

<style>
@media print {
  .sidebar, form, button { display: none !important; }
  .main-with-sidebar { margin-left: 0 !important; }
  body { background: #fff !important; color: #000 !important; }
  .card, .stat-card { background: #fff !important; border: 1px solid #ccc !important; color: #000 !important; }
  .table-dark-custom th, .table-dark-custom td { color: #000 !important; border-color: #ccc !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
