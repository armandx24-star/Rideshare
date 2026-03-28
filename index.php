<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Dashboard — Admin';
include 'includes/sidebar.php';

$db = getDB();

$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM drivers) AS total_drivers,
        (SELECT COUNT(*) FROM drivers WHERE status='approved') AS approved_drivers,
        (SELECT COUNT(*) FROM drivers WHERE status='pending') AS pending_drivers,
        (SELECT COUNT(*) FROM rides) AS total_rides,
        (SELECT COUNT(*) FROM rides WHERE status='completed') AS completed_rides,
        (SELECT COUNT(*) FROM rides WHERE status='pending') AS pending_rides,
        (SELECT COALESCE(SUM(fare),0) FROM rides WHERE status='completed') AS total_revenue
")->fetch();

$chartData = $db->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS rides, SUM(fare) AS revenue
    FROM rides WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

$chartLabels  = [];
$chartRides   = [];
$chartRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D d', strtotime($d));
    $found = array_values(array_filter($chartData, fn($r) => $r['day'] === $d));
    $chartRides[]   = $found ? $found[0]['rides']   : 0;
    $chartRevenue[] = $found ? $found[0]['revenue']  : 0;
}

$recent = $db->query("
    SELECT r.*, u.name AS user_name, d.name AS driver_name
    FROM rides r
    LEFT JOIN users u ON r.user_id=u.id
    LEFT JOIN drivers d ON r.driver_id=d.id
    ORDER BY r.created_at DESC LIMIT 8
")->fetchAll();
?>
<div class="container-fluid py-4 px-4">
  <div class="page-header">
    <h2>📊 Dashboard</h2>
    <p>Overview of the entire RideShare platform</p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
      <div class="stat-card stat-blue">
        <div class="stat-icon">👤</div>
        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
        <div class="stat-label">Total Users</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card stat-green">
        <div class="stat-icon">🚗</div>
        <div class="stat-value"><?php echo $stats['total_drivers']; ?></div>
        <div class="stat-label">Total Drivers
          <?php if ($stats['pending_drivers']): ?>
            <span class="badge badge-pending ms-1"><?php echo $stats['pending_drivers']; ?> pending</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card stat-yellow">
        <div class="stat-icon">🗺️</div>
        <div class="stat-value"><?php echo $stats['total_rides']; ?></div>
        <div class="stat-label">Total Rides</div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="stat-card stat-red">
        <div class="stat-icon">💰</div>
        <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
        <div class="stat-label">Total Revenue</div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-4">
      <div class="card text-center p-3">
        <div style="font-size:1.6rem;font-weight:800;color:#00C853"><?php echo $stats['completed_rides']; ?></div>
        <div style="font-size:0.8rem;color:#9E9E9E">Completed Rides</div>
      </div>
    </div>
    <div class="col-4">
      <div class="card text-center p-3">
        <div style="font-size:1.6rem;font-weight:800;color:#F7C948"><?php echo $stats['pending_rides']; ?></div>
        <div style="font-size:0.8rem;color:#9E9E9E">Pending Rides</div>
      </div>
    </div>
    <div class="col-4">
      <div class="card text-center p-3">
        <div style="font-size:1.6rem;font-weight:800;color:#2196F3"><?php echo $stats['approved_drivers']; ?></div>
        <div style="font-size:0.8rem;color:#9E9E9E">Active Drivers</div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">📈 Last 7 Days — Rides &amp; Revenue</div>
    <div class="card-body">
      <canvas id="dashChart" height="90"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      🕐 Recent Rides
      <a href="rides.php" class="btn btn-sm btn-secondary ms-auto">View All →</a>
    </div>
    <div class="card-body p-0">
      <table class="table-dark-custom w-100">
        <thead>
          <tr><th>#</th><th>User</th><th>Driver</th><th>Type</th><th>Distance</th><th>Fare</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td><small>#<?php echo $r['id']; ?></small></td>
            <td><?php echo htmlspecialchars($r['user_name'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($r['driver_name'] ?? '—'); ?></td>
            <td><?php echo ucfirst($r['vehicle_type']); ?></td>
            <td><?php echo $r['distance']; ?> km</td>
            <td class="text-primary fw-bold"><?php echo formatCurrency($r['fare']); ?></td>
            <td><?php echo statusBadge($r['status']); ?></td>
            <td><small><?php echo date('d M, h:i A', strtotime($r['created_at'])); ?></small></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('dashChart'), {
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [
            {
                type: 'bar',
                label: 'Rides',
                data: <?php echo json_encode($chartRides); ?>,
                backgroundColor: 'rgba(33,150,243,0.25)',
                borderColor: '#2196F3',
                borderWidth: 2,
                borderRadius: 6,
                yAxisID: 'y'
            },
            {
                type: 'line',
                label: 'Revenue (₹)',
                data: <?php echo json_encode($chartRevenue); ?>,
                borderColor: '#00C853',
                backgroundColor: 'rgba(0,200,83,0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#f5f5f5' } } },
        scales: {
            x:  { ticks: { color: '#9E9E9E' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            y:  { type: 'linear', position: 'left',  ticks: { color: '#2196F3' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
            y1: { type: 'linear', position: 'right', ticks: { color: '#00C853', callback: v => '₹'+v }, grid: { drawOnChartArea: false }, beginAtZero: true }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
