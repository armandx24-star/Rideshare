<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Manage Rides — Admin';
include 'includes/sidebar.php';

$db = getDB();

$status = $_GET['status'] ?? '';
$type   = $_GET['type']   ?? '';
$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = "1=1";
$params = [];
if ($status && in_array($status, ['pending','accepted','ongoing','completed','cancelled'])) {
    $where .= " AND r.status=?"; $params[] = $status;
}
if ($type && in_array($type, ['bike','mini','sedan'])) {
    $where .= " AND r.vehicle_type=?"; $params[] = $type;
}
if ($search) {
    $where .= " AND (u.name LIKE ? OR d.name LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like;
}

$totalStmt = $db->prepare("
    SELECT COUNT(*) FROM rides r
    LEFT JOIN users u ON r.user_id=u.id
    LEFT JOIN drivers d ON r.driver_id=d.id
    WHERE $where
");
$totalStmt->execute($params);
$totalRows = $totalStmt->fetchColumn();
$pages = ceil($totalRows / $perPage);

$params[] = $perPage; $params[] = $offset;
$stmt = $db->prepare("
    SELECT r.*, u.name AS user_name, d.name AS driver_name
    FROM rides r
    LEFT JOIN users u ON r.user_id=u.id
    LEFT JOIN drivers d ON r.driver_id=d.id
    WHERE $where
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$rides = $stmt->fetchAll();
?>
<div class="container-fluid py-4 px-4">
  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h2>🗺️ All Rides</h2>
      <p><?php echo $totalRows; ?> rides found</p>
    </div>
  </div>

  <form method="GET" class="d-flex gap-2 flex-wrap mb-4">
    <input type="text" name="search" class="form-control" placeholder="Search user/driver..." value="<?php echo htmlspecialchars($search); ?>" style="width:200px">
    <select name="status" class="form-select" style="width:160px">
      <option value="">All Statuses</option>
      <?php foreach (['pending','accepted','ongoing','completed','cancelled'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="type" class="form-select" style="width:140px">
      <option value="">All Types</option>
      <?php foreach (['bike','mini','sedan'] as $t): ?>
        <option value="<?php echo $t; ?>" <?php echo $type===$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="rides.php" class="btn btn-secondary">Clear</a>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <table class="table-dark-custom w-100">
        <thead>
          <tr><th>#</th><th>User</th><th>Driver</th><th>Type</th><th>Route</th><th>Distance</th><th>Fare</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php if (empty($rides)): ?>
            <tr><td colspan="9" class="text-center py-4" style="color:#9E9E9E">No rides found.</td></tr>
          <?php else: foreach ($rides as $r): ?>
          <tr>
            <td><small>#<?php echo $r['id']; ?></small></td>
            <td><?php echo htmlspecialchars($r['user_name'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($r['driver_name'] ?? '—'); ?></td>
            <td><?php echo ucfirst($r['vehicle_type']); ?></td>
            <td style="max-width:200px">
              <small>
                📍 <?php echo htmlspecialchars(substr($r['pickup_location'],0,25)); ?><br>
                🏁 <?php echo htmlspecialchars(substr($r['drop_location'],0,25)); ?>
              </small>
            </td>
            <td><?php echo $r['distance']; ?> km</td>
            <td class="text-primary fw-bold"><?php echo formatCurrency($r['fare']); ?></td>
            <td><?php echo statusBadge($r['status']); ?></td>
            <td><small><?php echo date('d M Y, h:i A', strtotime($r['created_at'])); ?></small></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-body pt-0 d-flex justify-content-center gap-2">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>"
           class="btn btn-sm <?php echo $i===$page?'btn-primary':'btn-secondary'; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>