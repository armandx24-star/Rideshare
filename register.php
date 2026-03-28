<?php

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();

$error   = '';
$success = '';
$activeTab = $_GET['tab'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['reg_type'] ?? 'user';
    $db   = getDB();

    if ($type === 'user') {
        $name     = sanitize($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($name)||empty($email)||empty($phone)||empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = 'Phone must be 10 digits.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email=? OR phone=?");
            $stmt->execute([$email, $phone]);
            if ($stmt->fetch()) {
                $error = 'Email or phone already registered.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)");
                $stmt->execute([$name, $email, $phone, $hashed]);
                setFlash('success', 'Account created! Please login.');
                header('Location: login.php?role=user');
                exit();
            }
        }
        $activeTab = 'user';

    } elseif ($type === 'driver') {
        $name          = sanitize($_POST['name'] ?? '');
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $phone         = trim($_POST['phone'] ?? '');
        $password      = $_POST['password'] ?? '';
        $confirm       = $_POST['confirm_password'] ?? '';
        $vehicle_type  = $_POST['vehicle_type'] ?? '';
        $vehicle_number= strtoupper(sanitize($_POST['vehicle_number'] ?? ''));
        $license_number= strtoupper(sanitize($_POST['license_number'] ?? ''));

        $validTypes = ['bike','mini','sedan'];

        if (empty($name)||empty($email)||empty($phone)||empty($password)||empty($vehicle_type)||empty($vehicle_number)||empty($license_number)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = 'Phone must be 10 digits.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (!in_array($vehicle_type, $validTypes)) {
            $error = 'Invalid vehicle type.';
        } else {
            $stmt = $db->prepare("SELECT id FROM drivers WHERE email=? OR phone=?");
            $stmt->execute([$email, $phone]);
            if ($stmt->fetch()) {
                $error = 'Email or phone already registered as driver.';
            } else {
                $docPath = null;
                if (!empty($_FILES['document']['name'])) {
                    $uploadDir = UPLOAD_DIR . 'documents/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext      = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                    $filename = 'doc_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    $allowed  = ['jpg','jpeg','png','pdf'];
                    if (!in_array(strtolower($ext), $allowed)) {
                        $error = 'Document must be JPG, PNG, or PDF.';
                        goto renderPage;
                    }
                    if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadDir . $filename)) {
                        $docPath = 'documents/' . $filename;
                    }
                }

                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO drivers (name,email,phone,password,vehicle_type,vehicle_number,license_number,document_path) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$name,$email,$phone,$hashed,$vehicle_type,$vehicle_number,$license_number,$docPath]);
                setFlash('success', 'Driver account created! Please wait for admin approval, then login.');
                header('Location: login.php?role=driver');
                exit();
            }
        }
        $activeTab = 'driver';
    }
}

renderPage:
$pageTitle = 'Register — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper" style="padding:40px 24px">
  <div class="auth-card" style="max-width:520px">
    <div class="auth-logo">🚗 RideShare</div>
    <div class="auth-tagline">Create your account to get started.</div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="regTabs">
      <li class="nav-item">
        <a class="nav-link <?php echo $activeTab==='user'?'active':''; ?>" href="#userTab" data-bs-toggle="tab">👤 User</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $activeTab==='driver'?'active':''; ?>" href="#driverTab" data-bs-toggle="tab">🚗 Driver</a>
      </li>
    </ul>

    <div class="tab-content" id="regTabContent">

      <div class="tab-pane fade <?php echo $activeTab==='user'?'show active':''; ?>" id="userTab">
        <form method="POST" action="">
          <input type="hidden" name="reg_type" value="user">
          <div class="mb-3 mt-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="John Doe"
                   value="<?php echo htmlspecialchars($_POST['name']??''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="john@email.com"
                   value="<?php echo htmlspecialchars($_POST['email']??''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Phone Number (10 digits)</label>
            <input type="tel" name="phone" class="form-control" placeholder="9876543210" maxlength="10"
                   value="<?php echo htmlspecialchars($_POST['phone']??''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-lg">Create Account</button>
        </form>
      </div>

      <div class="tab-pane fade <?php echo $activeTab==='driver'?'show active':''; ?>" id="driverTab">
        <form method="POST" action="" enctype="multipart/form-data">
          <input type="hidden" name="reg_type" value="driver">
          <div class="mb-3 mt-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="Driver Name" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="col-6">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-control" placeholder="9876543210" maxlength="10" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Vehicle Type</label>
            <select name="vehicle_type" class="form-select" required>
              <option value="">-- Select Vehicle --</option>
              <option value="bike">🏍️ Bike</option>
              <option value="mini">🚗 Mini Car</option>
              <option value="sedan">🚙 Sedan</option>
            </select>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label">Vehicle Number</label>
              <input type="text" name="vehicle_number" class="form-control" placeholder="MH01AB1234" required>
            </div>
            <div class="col-6">
              <label class="form-label">License Number</label>
              <input type="text" name="license_number" class="form-control" placeholder="DL1234567890" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Upload Document <small class="text-muted-light">(Optional, JPG/PNG/PDF)</small></label>
            <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
          </div>
          <div class="row g-3 mb-4">
            <div class="col-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required>
            </div>
            <div class="col-6">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat" required>
            </div>
          </div>
          <div class="alert alert-info" style="font-size:0.85rem">
            ℹ️ Your account will require <strong>admin approval</strong> before you can accept rides.
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-lg">Register as Driver</button>
        </form>
      </div>

    </div>

    <p class="text-center mt-3" style="color:#9E9E9E;font-size:0.9rem">
      Already have an account? <a href="login.php">Login here</a>
    </p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
