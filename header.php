<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
$currentRole = isset($_SESSION['user_id']) ? 'user' : (isset($_SESSION['driver_id']) ? 'driver' : (isset($_SESSION['admin_id']) ? 'admin' : null));
$userName = $_SESSION['user_name'] ?? $_SESSION['driver_name'] ?? $_SESSION['admin_username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle ?? APP_NAME; ?></title>
<meta name="description" content="RideShare - Book rides instantly. Fast, safe, and affordable.">
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Leaflet CSS (local — CDN may be blocked on WAMP) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/libs/leaflet.css">
<!-- Custom CSS -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">Ride<span>Share</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <?php if (!$currentRole): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link btn btn-primary ms-2" href="<?php echo BASE_URL; ?>/register.php" style="color:#fff!important;padding:8px 20px">Sign Up</a></li>
        <?php elseif ($currentRole === 'user'): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/user/">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/user/history.php">History</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/user/profile.php">Profile</a></li>
          <li class="nav-item"><span class="nav-link text-muted">Hi, <?php echo htmlspecialchars($userName); ?></span></li>
          <li class="nav-item"><a class="nav-link btn btn-danger ms-2" href="<?php echo BASE_URL; ?>/logout.php" style="color:#fff!important;padding:8px 16px">Logout</a></li>
        <?php elseif ($currentRole === 'driver'): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/driver/">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/driver/earnings.php">Earnings</a></li>
          <li class="nav-item"><a class="nav-link btn btn-danger ms-2" href="<?php echo BASE_URL; ?>/logout.php" style="color:#fff!important;padding:8px 16px">Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
