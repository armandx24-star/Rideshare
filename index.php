<?php

require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfLoggedIn();
$pageTitle = 'RideShare — Book Rides Instantly';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="badge mb-3" style="background:rgba(0,200,83,0.15);color:#00C853;border:1px solid rgba(0,200,83,0.3);padding:8px 16px;border-radius:20px;font-size:0.8rem;font-weight:600;">
          🚀 Fast &amp; Reliable Rides
        </div>
        <h1>Your City,<br><span>Your Ride.</span></h1>
        <p class="mt-4 mb-5">Book a ride in seconds. Choose from Bike, Mini, or Sedan. Safe drivers, transparent pricing, zero hidden fees.</p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="register.php" class="btn btn-primary btn-lg">🚗 Book a Ride</a>
          <a href="register.php?tab=driver" class="btn btn-outline-primary btn-lg">Become a Driver</a>
        </div>
        <div class="mt-5 d-flex gap-4 flex-wrap" style="color:#9E9E9E;font-size:0.9rem">
          <span>✅ No OTP Required</span>
          <span>✅ Real-time Tracking</span>
          <span>✅ Safe &amp; Secure</span>
        </div>
      </div>
      <div class="col-lg-6 text-center d-none d-lg-block">
        <div class="hero-car">🚕</div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="py-5" style="background:rgba(255,255,255,0.02);border-top:1px solid rgba(255,255,255,0.06);border-bottom:1px solid rgba(255,255,255,0.06);">
  <div class="container">
    <div class="row text-center g-4">
      <?php
      $stats = [
          ['5K+', 'Happy Riders', '😊'],
          ['500+', 'Active Drivers', '🚗'],
          ['20K+', 'Rides Completed', '✅'],
          ['4.8★', 'Average Rating', '⭐'],
      ];
      foreach ($stats as $s) {
          echo "<div class='col-6 col-md-3'>
            <div class='stat-value' style='font-size:2rem;font-weight:800;color:#00C853'>{$s[2]} {$s[0]}</div>
            <div style='color:#9E9E9E;font-size:0.85rem;margin-top:4px'>{$s[1]}</div>
          </div>";
      }
      ?>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="py-5 mt-4">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:2rem;font-weight:800">Why Choose <span style="color:#00C853">RideShare?</span></h2>
      <p style="color:#9E9E9E">Everything you need for a great ride experience</p>
    </div>
    <div class="row g-4">
      <?php
      $features = [
          ['🗺️','Live Map Tracking','Watch your ride in real-time on an interactive map powered by OpenStreetMap.'],
          ['💰','Transparent Fares','See exact fare before you book. No surge pricing surprises ever.'],
          ['⚡','Instant Booking','Book in under 30 seconds. Get matched with a nearby driver immediately.'],
          ['🛡️','Safe &amp; Secure','All drivers are verified and approved by our admin team before going live.'],
          ['🚴','Vehicle Choice','Choose from Bike, Mini Car, or Sedan based on your needs and budget.'],
          ['📊','Ride History','Track all your rides with full details, fare breakdown, and ratings.'],
      ];
      foreach ($features as $f) {
          echo "<div class='col-md-6 col-lg-4'>
            <div class='feature-card h-100'>
              <div class='icon'>{$f[0]}</div>
              <h4>{$f[1]}</h4>
              <p>{$f[2]}</p>
            </div>
          </div>";
      }
      ?>
    </div>
  </div>
</section>

<!-- Vehicle Types -->
<section class="py-5" style="background:rgba(255,255,255,0.02)">
  <div class="container">
    <div class="text-center mb-5">
      <h2 style="font-size:2rem;font-weight:800">Choose Your <span style="color:#00C853">Ride Type</span></h2>
    </div>
    <div class="row g-4 justify-content-center">
      <?php
      $vehicles = [
          ['🏍️','Bike','₹30 base + ₹7/km','Fastest for short trips'],
          ['🚗','Mini','₹50 base + ₹10/km','Comfortable 4-seater'],
          ['🚙','Sedan','₹80 base + ₹14/km','Premium experience'],
      ];
      foreach ($vehicles as $v) {
          echo "<div class='col-md-4'>
            <div class='feature-card h-100'>
              <div class='icon'>{$v[0]}</div>
              <h4>{$v[1]}</h4>
              <p style='color:#00C853;font-weight:700;font-size:1.1rem'>{$v[2]}</p>
              <p>{$v[3]}</p>
            </div>
          </div>";
      }
      ?>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="py-5">
  <div class="container text-center">
    <div class="card" style="padding:60px;background:linear-gradient(135deg,#0D1F0D,#1A1A2E)">
      <h2 style="font-size:2.5rem;font-weight:800">Ready to <span style="color:#00C853">Ride?</span></h2>
      <p style="color:#9E9E9E;margin:16px auto;max-width:500px">Join thousands of satisfied riders. Create your account and book your first ride in minutes.</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
        <a href="register.php" class="btn btn-primary btn-lg">Create Account</a>
        <a href="login.php"    class="btn btn-secondary btn-lg">Login</a>
      </div>
      <p class="mt-4" style="color:#9E9E9E;font-size:0.85rem">
        Are you a driver? <a href="register.php?tab=driver" style="color:#00C853">Register as Driver →</a>
      </p>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
