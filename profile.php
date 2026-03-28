<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('user');
$userId = $_SESSION['user_id'];
$db = getDB();

// Load current user
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'profile';

  if ($action === 'profile') {
    $name = sanitize($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($phone)) {
      $error = 'All fields are required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Invalid email address.';
    }
    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
      $error = 'Phone must be 10 digits.';
    }
    else {
      $chk = $db->prepare("SELECT id FROM users WHERE (email=? OR phone=?) AND id!=?");
      $chk->execute([$email, $phone, $userId]);
      if ($chk->fetch()) {
        $error = 'Email or phone already in use by another account.';
      }
      else {
        $db->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?")
          ->execute([$name, $email, $phone, $userId]);
        $_SESSION['user_name'] = $name;
        $success = 'Profile updated successfully!';
        // Reload
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
      }
    }
  }
  elseif ($action === 'password') {
    $oldPwd = $_POST['old_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($oldPwd, $user['password'])) {
      $error = 'Current password is incorrect.';
    }
    elseif (strlen($newPwd) < 6) {
      $error = 'New password must be at least 6 characters.';
    }
    elseif ($newPwd !== $confirm) {
      $error = 'Passwords do not match.';
    }
    else {
      $hashed = password_hash($newPwd, PASSWORD_DEFAULT);
      $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $userId]);
      $success = 'Password changed successfully!';
    }
  }
}

// Stats — count all rides and aggregate by status
$statsStmt = $db->prepare("
    SELECT
        COUNT(*)                                                 AS total_rides,
        SUM(CASE WHEN status = 'completed'  THEN 1 ELSE 0 END)  AS completed,
        SUM(CASE WHEN status = 'cancelled'  THEN 1 ELSE 0 END)  AS cancelled,
        COALESCE(
          SUM(CASE WHEN status = 'completed' THEN CAST(fare AS DECIMAL(10,2)) ELSE 0 END),
          0
        )                                                        AS total_spent
    FROM rides
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
// Ensure keys exist even if no rows
$stats['total_rides'] = (int)($stats['total_rides'] ?? 0);
$stats['completed']   = (int)($stats['completed']   ?? 0);
$stats['cancelled']   = (int)($stats['cancelled']   ?? 0);
$stats['total_spent'] = (float)($stats['total_spent'] ?? 0);


$pageTitle = 'My Profile — ' . APP_NAME;
include '../includes/header.php';
?>
<div class="container py-4" style="max-width:800px">
  <div class="page-header">
    <h2>👤 My Profile</h2>
    <p>Manage your account details</p>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <?php
$items = [
  ['🚗', 'Total Rides', $stats['total_rides'], 'stat-green'],
  ['✅', 'Completed', $stats['completed'], 'stat-blue'],
  ['✕', 'Cancelled', $stats['cancelled'], 'stat-red'],
  ['💰', 'Total Spent', formatCurrency($stats['total_spent']), 'stat-yellow'],
];
foreach ($items as $item) {
  echo "<div class='col-6 col-md-3'>
            <div class='stat-card {$item[3]}'>
              <div class='stat-icon'>{$item[0]}</div>
              <div class='stat-value'>{$item[2]}</div>
              <div class='stat-label'>{$item[1]}</div>
            </div>
          </div>";
}
?>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php
endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php
endif; ?>

  <div class="row g-4">
    <!-- Edit Profile -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">✏️ Edit Profile</div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="profile">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control"
                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-4">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-control"
                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" maxlength="10" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header">🔒 Change Password</div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="password">
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="old_password" class="form-control" placeholder="Current password" required>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
            </div>
            <div class="mb-4">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password"
                required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mt-4">
    <a href="../logout.php" class="btn btn-danger">Logout</a>
  </div>
</div>
<?php include '../includes/footer.php'; ?>