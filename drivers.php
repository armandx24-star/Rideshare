<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Manage Drivers — Admin';
include 'includes/sidebar.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action   = $_POST['action'] ?? '';
    $driverId = (int)($_POST['driver_id'] ?? 0);

    $allowed = ['approve','reject','activate','deactivate','delete'];
    if (!in_array($action, $allowed) || !$driverId) {
        echo json_encode(['success' => false]); exit();
    }

    switch ($action) {
        case 'approve':
            $db->prepare("UPDATE drivers SET status='approved' WHERE id=?")->execute([$driverId]);
            break;
        case 'reject':
            $db->prepare("UPDATE drivers SET status='rejected', online_status=0 WHERE id=?")->execute([$driverId]);
            break;
        case 'activate':
            $db->prepare("UPDATE drivers SET status='approved' WHERE id=?")->execute([$driverId]);
            break;
        case 'deactivate':
            $db->prepare("UPDATE drivers SET status='rejected', online_status=0 WHERE id=?")->execute([$driverId]);
            break;
        case 'delete':
            $db->prepare("DELETE FROM drivers WHERE id=?")->execute([$driverId]);
            break;
    }
    echo json_encode(['success' => true]); exit();
}

$search  = sanitize($_GET['search'] ?? '');
$filter  = $_GET['status'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where  = "1=1";
$params = [];
if ($search) {
    $where .= " AND (d.name LIKE ? OR d.email LIKE ? OR d.phone LIKE ? OR d.vehicle_number LIKE ?)";
    $like = "%$search%";
    $params = [$like,$like,$like,$like];
}
if ($filter && in_array($filter, ['pending','approved','rejected'])) {
    $where .= " AND d.status=?";
    $params[] = $filter;
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM drivers d WHERE $where");
$totalStmt->execute($params);
$totalRows = $totalStmt->fetchColumn();
$pages = ceil($totalRows / $perPage);

$params[] = $perPage; $params[] = $offset;
$stmt = $db->prepare("
    SELECT d.*,
           (SELECT COUNT(*) FROM rides r WHERE r.driver_id=d.id AND r.status='completed') AS completed_rides,
           (SELECT COALESCE(SUM(fare),0) FROM rides r WHERE r.driver_id=d.id AND r.status='completed') AS total_earned
    FROM drivers d WHERE $where
    ORDER BY FIELD(d.status,'pending','approved','rejected'), d.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$drivers = $stmt->fetchAll();
?>
<div class="container-fluid py-4 px-4">
  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h2>🚗 Manage Drivers</h2>
      <p><?php echo $totalRows; ?> drivers found</p>
    </div>
    <form method="GET" class="d-flex gap-2 flex-wrap">
      <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="width:200px">
      <select name="status" class="form-select" style="width:150px">
        <option value="">All Status</option>
        <?php foreach (['pending','approved','rejected'] as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $filter===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="drivers.php" class="btn btn-secondary">Clear</a>
    </form>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table-dark-custom w-100">
        <thead>
          <tr><th>#</th><th>Driver</th><th>Vehicle</th><th>License</th><th>Online</th><th>Rides</th><th>Earned</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody id="driversTable">
          <?php if (empty($drivers)): ?>
            <tr><td colspan="9" class="text-center py-4" style="color:#9E9E9E">No drivers found.</td></tr>
          <?php else: foreach ($drivers as $d): ?>
          <tr id="row-<?php echo $d['id']; ?>">
            <td><?php echo $d['id']; ?></td>
            <td>
              <strong><?php echo htmlspecialchars($d['name']); ?></strong><br>
              <small style="color:#9E9E9E"><?php echo htmlspecialchars($d['email']); ?> · <?php echo htmlspecialchars($d['phone']); ?></small>
            </td>
            <td><?php echo ucfirst($d['vehicle_type']); ?> · <?php echo htmlspecialchars($d['vehicle_number']); ?></td>
            <td><?php echo htmlspecialchars($d['license_number']); ?></td>
            <td>
              <span class="badge <?php echo $d['online_status']?'badge-online':'badge-offline'; ?>">
                <?php echo $d['online_status']?'Online':'Offline'; ?>
              </span>
            </td>
            <td><?php echo $d['completed_rides']; ?></td>
            <td class="text-primary"><?php echo formatCurrency($d['total_earned']); ?></td>
            <td>
              <?php
              $sc = ['pending'=>'badge-pending-driver','approved'=>'badge-approved','rejected'=>'badge-rejected'];
              echo '<span class="badge ' . ($sc[$d['status']] ?? '') . '">' . ucfirst($d['status']) . '</span>';
              ?>
            </td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <?php if ($d['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-primary" onclick="driverAction('approve', <?php echo $d['id']; ?>)">✓ Approve</button>
                  <button class="btn btn-sm btn-danger"  onclick="driverAction('reject',  <?php echo $d['id']; ?>)">✕ Reject</button>
                <?php elseif ($d['status'] === 'approved'): ?>
                  <button class="btn btn-sm btn-danger" onclick="driverAction('deactivate', <?php echo $d['id']; ?>)">Deactivate</button>
                <?php else: ?>
                  <button class="btn btn-sm btn-primary" onclick="driverAction('activate', <?php echo $d['id']; ?>)">Activate</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-secondary" onclick="if(confirm('Delete driver?')) driverAction('delete', <?php echo $d['id']; ?>)">🗑</button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-body pt-0 d-flex justify-content-center gap-2">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $filter; ?>"
           class="btn btn-sm <?php echo $i===$page?'btn-primary':'btn-secondary'; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function driverAction(action, driverId) {
    const data = new FormData();
    data.append('action', action);
    data.append('driver_id', driverId);

    fetch('drivers.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (action === 'delete') {
                document.getElementById('row-' + driverId)?.remove();
                showToast('Driver deleted', 'info');
            } else {
                showToast('Action applied: ' + action, 'success');
                setTimeout(() => location.reload(), 800);
            }
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>