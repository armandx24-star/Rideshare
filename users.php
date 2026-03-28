<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Manage Users — Admin';
include 'includes/sidebar.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['delete_user'];
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
    header('Location: users.php?msg=deleted'); exit();
}

$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = "1=1";
$params = [];
if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params = [$like,$like,$like];
}

$total = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
$total->execute($params);
$totalRows = $total->fetchColumn();
$pages = ceil($totalRows / $perPage);

$params[] = $perPage; $params[] = $offset;
$stmt = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM rides WHERE user_id=u.id) AS ride_count FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<div class="container-fluid py-4 px-4">
  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h2>👤 Manage Users</h2>
      <p><?php echo $totalRows; ?> total users registered</p>
    </div>
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="search" class="form-control" placeholder="Search name, email, phone..."
             value="<?php echo htmlspecialchars($search); ?>" style="width:250px">
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($search): ?><a href="users.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>

  <?php if ($_GET['msg'] ?? '' === 'deleted'): ?>
    <div class="alert alert-success">User deleted successfully.</div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body p-0">
      <table class="table-dark-custom w-100">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Rides</th><th>Joined</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="7" class="text-center py-4" style="color:#9E9E9E">No users found.</td></tr>
          <?php else: foreach ($users as $u): ?>
          <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['phone']); ?></td>
            <td><?php echo $u['ride_count']; ?></td>
            <td><small><?php echo date('d M Y', strtotime($u['created_at'])); ?></small></td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete this user? All their rides will also be deleted.')">
                <input type="hidden" name="delete_user" value="<?php echo $u['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-body pt-0 d-flex justify-content-center gap-2">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
           class="btn btn-sm <?php echo $i===$page?'btn-primary':'btn-secondary'; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
