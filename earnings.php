<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('driver');
$driverId = $_SESSION['driver_id'];
$db       = getDB();

/* ─── earnings summary ──────────────────────────────────────────────────── */
$summaryStmt = $db->prepare("
    SELECT
        COALESCE(SUM(fare), 0)                                                 AS total,
        COALESCE(SUM(CASE WHEN DATE(COALESCE(completed_at, created_at)) = CURDATE()
                          THEN fare ELSE 0 END), 0)                            AS today,
        COALESCE(SUM(CASE WHEN YEARWEEK(COALESCE(completed_at,created_at),1) = YEARWEEK(NOW(),1)
                          THEN fare ELSE 0 END), 0)                            AS this_week,
        COUNT(*)                                                               AS total_rides,
        COUNT(CASE WHEN DATE(COALESCE(completed_at,created_at)) = CURDATE() THEN 1 END) AS today_rides
    FROM rides
    WHERE driver_id = ?
      AND (status = 'completed' OR payment_status = 'confirmed')
");
$summaryStmt->execute([$driverId]);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

/* ─── avg rating ─────────────────────────────────────────────────────────── */
$ratingStmt = $db->prepare("
    SELECT ROUND(AVG(rt.user_to_driver), 1)
    FROM ratings rt
    JOIN rides r ON rt.ride_id = r.id
    WHERE r.driver_id = ? AND rt.user_to_driver IS NOT NULL
");
$ratingStmt->execute([$driverId]);
$avgRating = (float) $ratingStmt->fetchColumn();

/* ─── 7-day chart ─────────────────────────────────────────────────────────── */
$chartStmt = $db->prepare("
    SELECT DATE(COALESCE(completed_at, created_at)) AS day,
           SUM(fare)   AS earnings,
           COUNT(*)    AS rides
    FROM rides
    WHERE driver_id = ?
      AND (status = 'completed' OR payment_status = 'confirmed')
      AND COALESCE(completed_at, created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(COALESCE(completed_at, created_at))
    ORDER BY day ASC
");
$chartStmt->execute([$driverId]);
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels   = [];
$chartEarnings = [];
$chartRides    = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D d', strtotime($d));
    $found = array_values(array_filter($chartData, fn($r) => $r['day'] === $d));
    $chartEarnings[] = $found ? (float)$found[0]['earnings'] : 0;
    $chartRides[]    = $found ? (int)$found[0]['rides']    : 0;
}

/* ─── recent rides for table ─────────────────────────────────────────────── */
$recentStmt = $db->prepare("
    SELECT r.*, u.name AS user_name, rt.user_to_driver AS rating
    FROM rides r
    LEFT JOIN users   u  ON r.user_id  = u.id
    LEFT JOIN ratings rt ON r.id       = rt.ride_id
    WHERE r.driver_id = ?
      AND (r.status = 'completed' OR r.payment_status = 'confirmed')
    ORDER BY COALESCE(r.completed_at, r.created_at) DESC
    LIMIT 20
");
$recentStmt->execute([$driverId]);
$recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Earnings — ' . APP_NAME;
include '../includes/header.php';
?>

<div class="container py-4">
  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h2>💰 Earnings</h2>
      <p>Your complete earnings summary and ride history</p>
    </div>
    <a href="index.php" class="btn btn-secondary">← Dashboard</a>
  </div>

  <!-- ── Summary Cards ─────────────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card stat-green">
        <div class="stat-icon">💵</div>
        <div class="stat-value"><?= formatCurrency($summary['today']) ?></div>
        <div class="stat-label">Today</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-blue">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?= formatCurrency($summary['this_week']) ?></div>
        <div class="stat-label">This Week</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-yellow">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?= formatCurrency($summary['total']) ?></div>
        <div class="stat-label">Total Earnings</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-red">
        <div class="stat-icon">🚗</div>
        <div class="stat-value"><?= (int)$summary['total_rides'] ?></div>
        <div class="stat-label">Total Rides</div>
      </div>
    </div>
  </div>

  <!-- ── Secondary Stats ───────────────────────────────────────────────── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card stat-green" style="padding:16px">
        <div class="stat-label">Today's Rides</div>
        <div class="stat-value" style="font-size:1.6rem"><?= (int)$summary['today_rides'] ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-blue" style="padding:16px">
        <div class="stat-label">Avg Rating</div>
        <div class="stat-value" style="font-size:1.6rem">
          <?= $avgRating > 0 ? '<span style="color:#F7C948">★</span> ' . number_format($avgRating, 1) : '—' ?>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-yellow" style="padding:16px">
        <div class="stat-label">Per-Ride Avg</div>
        <div class="stat-value" style="font-size:1.6rem">
          <?= $summary['total_rides'] > 0
              ? formatCurrency(round($summary['total'] / $summary['total_rides'], 2))
              : '₹0' ?>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card stat-red" style="padding:16px">
        <div class="stat-label">Weekly Rides</div>
        <div class="stat-value" style="font-size:1.6rem"><?= array_sum($chartRides) ?></div>
      </div>
    </div>
  </div>

  <!-- ── 7-Day Chart ─────────────────────────────────────────────────────── -->
  <div class="card mb-4">
    <div class="card-header">📈 Last 7 Days — Earnings &amp; Rides</div>
    <div class="card-body">
      <?php if (array_sum($chartEarnings) == 0): ?>
        <div class="text-center py-4" style="color:#9E9E9E">
          <div style="font-size:2.5rem">📊</div>
          <p class="mt-2">Complete rides to see your earnings chart here.</p>
        </div>
      <?php else: ?>
        <canvas id="earningsChart" height="90"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Ride History Table ─────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">🕐 Completed Ride History</div>
    <?php if (empty($recent)): ?>
      <div class="card-body text-center py-5" style="color:#9E9E9E">
        <div style="font-size:3rem">🚗</div>
        <p class="mt-2">No completed rides yet. Accept and complete a ride to see earnings here.</p>
      </div>
    <?php else: ?>
    <div class="card-body p-0" style="overflow-x:auto">
      <table class="table-dark-custom w-100">
        <thead>
          <tr>
            <th>#</th>
            <th>Date</th>
            <th>Passenger</th>
            <th>Route</th>
            <th>Dist</th>
            <th>Fare</th>
            <th>Payment</th>
            <th>Rating</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td><small style="color:#9E9E9E">#<?= $r['id'] ?></small></td>
            <td style="white-space:nowrap">
              <small><?= date('d M, h:i A', strtotime($r['completed_at'] ?: $r['created_at'])) ?></small>
            </td>
            <td><?= htmlspecialchars($r['user_name'] ?? '—') ?></td>
            <td style="min-width:160px;max-width:220px">
              <div style="font-size:0.8rem">
                📍 <?= htmlspecialchars(mb_strimwidth($r['pickup_location'], 0, 28, '…')) ?><br>
                🏁 <?= htmlspecialchars(mb_strimwidth($r['drop_location'],   0, 28, '…')) ?>
              </div>
            </td>
            <td style="white-space:nowrap"><?= $r['distance'] ?> km</td>
            <td class="text-primary fw-bold" style="white-space:nowrap"><?= formatCurrency($r['fare']) ?></td>
            <td>
              <?php
              $pm = $r['payment_method'] ?? '';
              $pm_icons  = ['cash'=>'💵 Cash','upi'=>'📱 UPI','online'=>'💳 Online'];
              $pm_colors = ['cash'=>'#81C784','upi'=>'#64B5F6','online'=>'#CE93D8'];
              if ($pm && isset($pm_icons[$pm])) {
                  echo '<span style="color:'.$pm_colors[$pm].';font-size:0.8rem;font-weight:600">'.$pm_icons[$pm].'</span>';
              } else { echo '<span style="color:#666">—</span>'; }
              ?>
            </td>
            <td>
              <?php if ($r['rating']): ?>
                <span style="color:#F7C948"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5-(int)$r['rating']) ?></span>
              <?php else: ?>
                <span style="color:#444">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (array_sum($chartEarnings) > 0): ?>
<script>
const ctx = document.getElementById('earningsChart');
if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Earnings (₹)',
                    data: <?= json_encode($chartEarnings) ?>,
                    backgroundColor: 'rgba(0,200,83,0.22)',
                    borderColor: '#00C853',
                    borderWidth: 2,
                    borderRadius: 6,
                    yAxisID: 'y',
                },
                {
                    label: 'Rides',
                    data: <?= json_encode($chartRides) ?>,
                    backgroundColor: 'rgba(33,150,243,0.18)',
                    borderColor: '#64B5F6',
                    borderWidth: 2,
                    borderRadius: 6,
                    type: 'line',
                    yAxisID: 'y1',
                    tension: 0.3,
                    pointRadius: 5,
                    pointBackgroundColor: '#64B5F6',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: '#f5f5f5', font: { size: 13 } } },
                tooltip: {
                    callbacks: {
                        label: c => c.dataset.label.startsWith('Earnings')
                            ? '₹' + parseFloat(c.raw).toFixed(2)
                            : c.raw + ' rides'
                    }
                }
            },
            scales: {
                x:  { ticks: { color: '#9E9E9E' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y:  { position: 'left',  ticks: { color: '#9E9E9E', callback: v => '₹'+v }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
                y1: { position: 'right', ticks: { color: '#64B5F6' }, grid: { drawOnChartArea: false }, beginAtZero: true }
            }
        }
    });
} else if (!typeof Chart !== 'undefined') {
    document.getElementById('earningsChart').parentElement.innerHTML =
        '<p style="color:#9E9E9E;text-align:center">Chart.js could not load (CDN blocked on local network). Data shows correctly in the cards above.</p>';
}
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
