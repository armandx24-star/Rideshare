<?php

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();

$error = '';
$defaultRole = $_GET['role'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role       = $_POST['role'] ?? 'user';
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();

        if ($role === 'admin') {
            // Admin login by username only
            $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$identifier]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: ' . BASE_URL . '/admin/');
                exit();
            } else {
                $error = 'Invalid admin credentials.';
            }

        } elseif ($role === 'driver') {
            $stmt = $db->prepare("SELECT * FROM drivers WHERE email = ? OR phone = ?");
            $stmt->execute([$identifier, $identifier]);
            $driver = $stmt->fetch();
            if ($driver && password_verify($password, $driver['password'])) {
                if ($driver['status'] === 'rejected') {
                    $error = 'Your driver account has been rejected. Please contact support.';
                } elseif ($driver['status'] === 'pending') {
                    $error = 'Your driver account is pending admin approval.';
                } else {
                    $_SESSION['driver_id']   = $driver['id'];
                    $_SESSION['driver_name'] = $driver['name'];
                    header('Location: ' . BASE_URL . '/driver/');
                    exit();
                }
            } else {
                $error = 'Invalid email/phone or password.';
            }

        } else {
            
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: ' . BASE_URL . '/user/');
                exit();
            } else {
                $error = 'Invalid email/phone or password.';
            }
        }
    }
}

$pageTitle = 'Login — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">🚗 RideShare</div>
    <div class="auth-tagline">Welcome back! Sign in to continue.</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php echo renderFlash(); ?>

    <form method="POST" action="">
      <!-- Role Selection -->
      <div class="mb-4">
        <label class="form-label">Login As</label>
        <div class="d-flex gap-2">
          <?php foreach (['user'=>'👤 User','driver'=>'🚗 Driver','admin'=>'🛠 Admin'] as $r => $label): ?>
            <label class="flex-fill" style="cursor:pointer">
              <input type="radio" name="role" value="<?php echo $r; ?>"
                     <?php echo ($defaultRole===$r)?'checked':''; ?>
                     onchange="this.closest('form').querySelectorAll('.role-label').forEach(el=>el.classList.remove('selected')); this.nextElementSibling.classList.add('selected')"
                     class="d-none">
              <div class="role-label fare-card py-2 px-1 text-center <?php echo ($defaultRole===$r)?'selected':''; ?>" style="font-size:0.8rem;font-weight:600;padding:10px!important">
                <?php echo $label; ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Email or Phone Number</label>
        <input type="text" name="identifier" class="form-control"
               placeholder="Enter email or phone" value="<?php echo htmlspecialchars($_POST['identifier']??''); ?>" required>
      </div>

      <div class="mb-4">
        <label class="form-label">Password</label>
        <div style="position:relative">
          <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
          <span onclick="togglePwd('loginPassword')" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9E9E9E;font-size:1rem">👁</span>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Sign In</button>

      <p class="text-center" style="color:#9E9E9E;font-size:0.9rem">
        Don't have an account? <a href="register.php">Register here</a>
      </p>
      <p class="text-center" style="font-size:0.8rem;color:#666;margin-top:12px">
        Demo: user@demo.com / password &nbsp;|&nbsp; driver@demo.com / password &nbsp;|&nbsp; admin / admin123
      </p>
    </form>
  </div>
</div>

<script>
function togglePwd(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
document.querySelectorAll('input[name=role]').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.role-label').forEach(el => el.classList.remove('selected'));
        r.nextElementSibling.classList.add('selected');
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
