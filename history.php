<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireRole('user');
$userId = $_SESSION['user_id'];
$db     = getDB();

// ── AJAX mode: return table partial ──────────────────────────────────────────
$isAjax  = !empty($_GET['ajax']);
$status  = trim($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$allowed = ['pending','accepted','ongoing','completed','cancelled'];
$where   = 'r.user_id = ?';
$params  = [$userId];
if ($status && in_array($status, $allowed)) {
    $where   .= ' AND r.status = ?';
    $params[] = $status;
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM rides r WHERE $where");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$queryParams   = $params;
$queryParams[] = $perPage;
$queryParams[] = $offset;

$stmt = $db->prepare("
    SELECT  r.*,
            d.name            AS driver_name,
            d.vehicle_number  AS driver_vehicle_no,
            d.phone           AS driver_phone,
            d.vehicle_type    AS driver_vehicle_type,
            rt.user_to_driver AS user_rating
    FROM rides r
    LEFT JOIN drivers d  ON r.driver_id  = d.id
    LEFT JOIN ratings rt ON r.id         = rt.ride_id
    WHERE $where
    ORDER BY r.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($queryParams);
$rides = $stmt->fetchAll();

/* ── helpers ───────────────────────────────────────────────────────────────── */
function paymentBadge(?string $m): string {
    if (!$m) return '<span style="color:#666">—</span>';
    $icons  = ['cash'=>'💵','upi'=>'📱','online'=>'💳'];
    $labels = ['cash'=>'Cash','upi'=>'UPI','online'=>'Online'];
    $colors = ['cash'=>'#81C784','upi'=>'#64B5F6','online'=>'#CE93D8'];
    $ico   = $icons[$m]  ?? '💳';
    $lbl   = $labels[$m] ?? ucfirst($m);
    $clr   = $colors[$m] ?? '#9E9E9E';
    return "<span class=\"pay-badge\" style=\"color:{$clr};border-color:{$clr}\">{$ico} {$lbl}</span>";
}

function starDisplay(int $n): string {
    return '<span class="stars-display">' . str_repeat('★', $n) . str_repeat('☆', 5 - $n) . '</span>';
}

/* ── build table rows HTML ─────────────────────────────────────────────────── */
ob_start(); ?>
<?php if (empty($rides)): ?>
  <tr><td colspan="9" class="text-center py-5" style="color:#9E9E9E">
    <div style="font-size:3rem">🚗</div>
    <p class="mt-2">No rides found for this filter.</p>
  </td></tr>
<?php else: foreach ($rides as $r): ?>
  <tr>
    <td><small style="color:#9E9E9E">#<?= $r['id'] ?></small></td>

    <td class="route-cell">
      <div class="route-line" title="<?= htmlspecialchars($r['pickup_location']) ?>">
        <span class="route-pin pickup-pin">📍</span>
        <span><?= htmlspecialchars(mb_strimwidth($r['pickup_location'], 0, 35, '…')) ?></span>
      </div>
      <div class="route-line" title="<?= htmlspecialchars($r['drop_location']) ?>">
        <span class="route-pin drop-pin">🏁</span>
        <span><?= htmlspecialchars(mb_strimwidth($r['drop_location'], 0, 35, '…')) ?></span>
      </div>
    </td>

    <td><?= ucfirst($r['vehicle_type']) ?></td>
    <td style="white-space:nowrap"><?= $r['distance'] ?> km</td>
    <td class="text-primary fw-bold" style="white-space:nowrap"><?= formatCurrency($r['fare']) ?></td>
    <td><?= statusBadge($r['status']) ?></td>

    <td>
      <?php if ($r['driver_name']): ?>
        <div style="font-size:0.82rem;font-weight:600"><?= htmlspecialchars($r['driver_name']) ?></div>
        <div style="font-size:0.75rem;color:#9E9E9E"><?= htmlspecialchars($r['driver_vehicle_no'] ?? '') ?></div>
        <div style="font-size:0.75rem;color:#9E9E9E">📞 <?= htmlspecialchars($r['driver_phone'] ?? '') ?></div>
      <?php else: ?>
        <span style="color:#666">—</span>
      <?php endif; ?>
    </td>

    <td><?= paymentBadge($r['payment_method']) ?></td>

    <td class="rating-cell" id="rating-cell-<?= $r['id'] ?>">
      <?php if ($r['user_rating']): ?>
        <?= starDisplay((int)$r['user_rating']) ?>
      <?php elseif ($r['status'] === 'completed'): ?>
        <button class="btn-rate-now" onclick="openRatingModal(<?= $r['id'] ?>)">⭐ Rate</button>
      <?php else: ?>
        <span style="color:#444">—</span>
      <?php endif; ?>
    </td>

    <td style="white-space:nowrap"><small><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
  </tr>
<?php endforeach; endif; ?>
<?php
$tableHtml = ob_get_clean();

// AJAX: send table rows + pagination JSON
if ($isAjax) {
    header('Content-Type: application/json');
    ob_start(); ?>
    <tr>
      <th>#</th><th>Route</th><th>Type</th><th>Dist</th>
      <th>Fare</th><th>Status</th><th>Driver</th>
      <th>Payment</th><th>Rating</th><th>Date</th>
    </tr>
    <?php
    $headerHtml = ob_get_clean();
    echo json_encode([
        'rows'  => $tableHtml,
        'total' => $total,
        'pages' => $pages,
        'page'  => $page,
    ]);
    exit;
}

// ── Full page ────────────────────────────────────────────────────────────────
$pageTitle = 'Ride History — ' . APP_NAME;
include '../includes/header.php';
?>

<!-- Rating Modal -->
<div id="ratingModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:18px;max-width:380px;width:92%;padding:32px;text-align:center">
    <div style="font-size:2.5rem;margin-bottom:8px">⭐</div>
    <h4 style="margin-bottom:4px">Rate Your Driver</h4>
    <p style="color:#9E9E9E;font-size:0.85rem;margin-bottom:20px">How was your experience?</p>
    <div class="modal-stars" id="modalStars">
      <span class="mstar" data-val="1">★</span>
      <span class="mstar" data-val="2">★</span>
      <span class="mstar" data-val="3">★</span>
      <span class="mstar" data-val="4">★</span>
      <span class="mstar" data-val="5">★</span>
    </div>
    <input type="hidden" id="modalRating" value="0">
    <input type="hidden" id="modalRideId" value="0">
    <textarea id="modalComment" class="form-control mt-3" placeholder="Comment (optional)" rows="2"></textarea>
    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-secondary flex-fill" onclick="closeRatingModal()">Cancel</button>
      <button class="btn btn-primary flex-fill" onclick="submitRating()">Submit</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="histToast" style="display:none;position:fixed;bottom:24px;right:24px;background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);color:#fff;padding:12px 20px;border-radius:10px;z-index:10000;font-size:0.9rem"></div>

<div class="container py-4">
  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h2>🕐 Ride History</h2>
      <p>All your past and active rides</p>
    </div>
    <a href="index.php" class="btn btn-primary">+ Book New Ride</a>
  </div>

  <!-- Filter Tabs -->
  <div class="filter-tabs d-flex gap-2 flex-wrap mb-4">
    <?php
    $filterList = ['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted',
                   'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    foreach ($filterList as $s => $label) {
        $active = ($status === $s) ? 'active' : '';
        echo "<button class=\"filter-btn {$active}\" data-status=\"{$s}\">{$label}</button>";
    }
    ?>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div style="overflow-x:auto">
        <table class="table-dark-custom w-100" id="historyTable">
          <thead>
            <tr>
              <th>#</th><th>Route</th><th>Type</th><th>Dist</th>
              <th>Fare</th><th>Status</th><th>Driver</th>
              <th>Payment</th><th>Rating</th><th>Date</th>
            </tr>
          </thead>
          <tbody id="historyBody">
            <?= $tableHtml ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="card-body pt-0 d-flex justify-content-center gap-2 flex-wrap" id="pagination">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <button class="btn btn-sm page-btn <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>"
                data-page="<?= $i ?>"><?= $i ?></button>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
/* Filter tabs */
.filter-btn {
  background: #2A2A3E; color: #9E9E9E;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px; padding: 7px 18px;
  font-size: 0.85rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.filter-btn:hover { color: #fff; background: #33334a; }
.filter-btn.active {
  background: linear-gradient(135deg,#00C853,#00A040);
  color: #fff; border-color: transparent;
  box-shadow: 0 4px 12px rgba(0,200,83,0.3);
}

/* Route cell */
.route-cell { min-width: 200px; max-width: 260px; }
.route-line { display: flex; align-items: flex-start; gap: 5px; font-size: 0.82rem; line-height: 1.4; word-break: break-word; }
.route-pin  { flex-shrink: 0; font-size: 0.9rem; margin-top: 1px; }

/* Payment badge */
.pay-badge {
  border: 1px solid; border-radius: 20px;
  padding: 3px 10px; font-size: 0.75rem; font-weight: 600;
  white-space: nowrap;
}

/* Rate Now button */
.btn-rate-now {
  background: rgba(247,201,72,0.12);
  border: 1px solid rgba(247,201,72,0.4);
  color: #F7C948; border-radius: 8px;
  padding: 5px 12px; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.btn-rate-now:hover { background: rgba(247,201,72,0.25); }

/* Stars */
.stars-display { color: #F7C948; letter-spacing: 1px; font-size: 1rem; }
.rating-cell   { white-space: nowrap; }

/* Modal stars */
.modal-stars { display: flex; justify-content: center; gap: 10px; }
.mstar {
  font-size: 2.4rem; cursor: pointer;
  color: #333; transition: color 0.15s, transform 0.15s;
}
.mstar.lit  { color: #F7C948; }
.mstar:hover { transform: scale(1.15); }
</style>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let currentStatus = '<?= $status ?>';
let currentPage   = <?= $page ?>;

/* ── Filter / Pagination AJAX ────────────────────────────────────────────── */
function loadRides(status, page) {
  currentStatus = status;
  currentPage   = page;
  const url = `history.php?ajax=1&status=${encodeURIComponent(status)}&page=${page}`;
  fetch(url).then(r => r.json()).then(data => {
    document.getElementById('historyBody').innerHTML = data.rows;
    buildPagination(data.pages, data.page);
    // sync filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.status === status);
    });
  }).catch(() => showHistToast('Failed to load rides.', '#FF4757'));
}

function buildPagination(pages, page) {
  let existing = document.getElementById('pagination');
  if (pages <= 1) { if (existing) existing.innerHTML = ''; return; }
  let html = '';
  for (let i = 1; i <= pages; i++) {
    html += `<button class="btn btn-sm ${i===page?'btn-primary':'btn-secondary'} page-btn"
                     data-page="${i}" onclick="loadRides('${currentStatus}',${i})">${i}</button>`;
  }
  if (!existing) {
    existing = document.createElement('div');
    existing.id = 'pagination';
    existing.className = 'card-body pt-0 d-flex justify-content-center gap-2 flex-wrap';
    document.querySelector('.card').appendChild(existing);
  }
  existing.innerHTML = html;
}

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => loadRides(btn.dataset.status, 1));
});
document.querySelectorAll('.page-btn').forEach(btn => {
  btn.addEventListener('click', () => loadRides(currentStatus, parseInt(btn.dataset.page)));
});

/* ── Rating Modal ─────────────────────────────────────────────────────────── */
let selectedRating = 0;

function openRatingModal(rideId) {
  selectedRating = 0;
  document.getElementById('modalRideId').value  = rideId;
  document.getElementById('modalRating').value  = 0;
  document.getElementById('modalComment').value = '';
  document.querySelectorAll('.mstar').forEach(s => s.classList.remove('lit'));
  document.getElementById('ratingModal').style.display = 'flex';
}

function closeRatingModal() {
  document.getElementById('ratingModal').style.display = 'none';
}

// Star hover & click
document.querySelectorAll('.mstar').forEach(star => {
  star.addEventListener('mouseenter', () => {
    const val = parseInt(star.dataset.val);
    document.querySelectorAll('.mstar').forEach(s => {
      s.classList.toggle('lit', parseInt(s.dataset.val) <= val);
    });
  });
  star.addEventListener('mouseleave', () => {
    document.querySelectorAll('.mstar').forEach(s => {
      s.classList.toggle('lit', parseInt(s.dataset.val) <= selectedRating);
    });
  });
  star.addEventListener('click', () => {
    selectedRating = parseInt(star.dataset.val);
    document.getElementById('modalRating').value = selectedRating;
    document.querySelectorAll('.mstar').forEach(s => {
      s.classList.toggle('lit', parseInt(s.dataset.val) <= selectedRating);
    });
  });
});

function submitRating() {
  const rideId  = parseInt(document.getElementById('modalRideId').value);
  const rating  = parseInt(document.getElementById('modalRating').value);
  const comment = document.getElementById('modalComment').value.trim();

  if (!rating || rating < 1 || rating > 5) {
    showHistToast('Please select a star rating.', '#F7C948');
    return;
  }

  fetch(BASE_URL + '/user/rate_ride.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ride_id: rideId, rating, comment })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      closeRatingModal();
      const cell = document.getElementById('rating-cell-' + rideId);
      if (cell) {
        cell.innerHTML = '<span class="stars-display">'
          + '★'.repeat(rating) + '☆'.repeat(5 - rating) + '</span>';
      }
      showHistToast('Rating submitted! ⭐', '#00C853');
    } else {
      showHistToast(data.message || 'Failed.', '#FF4757');
    }
  })
  .catch(() => showHistToast('Network error.', '#FF4757'));
}

// Close modal on backdrop click
document.getElementById('ratingModal').addEventListener('click', function(e) {
  if (e.target === this) closeRatingModal();
});

/* ── Toast ────────────────────────────────────────────────────────────────── */
function showHistToast(msg, color) {
  const t = document.getElementById('histToast');
  t.textContent = msg;
  t.style.borderColor = color || 'rgba(255,255,255,0.1)';
  t.style.display = 'block';
  setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>
