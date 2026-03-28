<?php
require_once __DIR__ . '/config.php';
?>
<footer style="background:rgba(13,13,13,0.95);border-top:1px solid rgba(255,255,255,0.06);padding:32px 0;margin-top:60px">
  <div class="container text-center">
    <p style="color:#9E9E9E;font-size:0.85rem;margin:0">
      &copy; <?php echo date('Y'); ?> <strong style="color:#00C853">RideShare</strong> — Built with PHP &amp; MySQL. Powered by OpenStreetMap.
    </p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/libs/leaflet.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
<?php if (isset($includeMap) && $includeMap): ?>
<script src="<?php echo BASE_URL; ?>/assets/js/map.js"></script>
<?php
endif; ?>
<?php
if (!empty($pageScripts))
  echo $pageScripts;
?>
</body>
</html>

