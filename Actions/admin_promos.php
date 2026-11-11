<?php
// admin_promos.php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// ---- Gate: Admin only ----
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'Admin')) {
  header("Location: login.php"); exit;
}

// ---- Flash helpers ----
function flash($key, $val=null) {
  if ($val !== null) { $_SESSION[$key] = $val; return; }
  if (isset($_SESSION[$key])) { $v = $_SESSION[$key]; unset($_SESSION[$key]); return $v; }
  return null;
}

// ---- Ensure promos table exists ----
$ensureSQL = "
CREATE TABLE IF NOT EXISTS promos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  category ENUM('membership','product','class') NOT NULL DEFAULT 'membership',
  badge ENUM('none','hot','expires','new','special') NOT NULL DEFAULT 'none',
  badge_text VARCHAR(50) DEFAULT NULL,        -- optional override like '🎓 STUDENT PROMO'
  discount_label VARCHAR(120) DEFAULT NULL,   -- free text like '50% OFF' or 'BUY 10 GET 2'
  original_price DECIMAL(10,2) DEFAULT NULL,
  new_price DECIMAL(10,2) DEFAULT NULL,
  starts_at DATE DEFAULT NULL,
  expires_at DATE DEFAULT NULL,
  cta_text VARCHAR(60) DEFAULT 'Claim Now',
  cta_link VARCHAR(300) DEFAULT 'login.php',
  image_url VARCHAR(500) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  display_order INT NOT NULL DEFAULT 100,     -- lower = earlier
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (category),
  INDEX (is_active),
  INDEX (expires_at),
  INDEX (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($ensureSQL);

// ---- Handle actions (create / toggle / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $title          = trim($_POST['title'] ?? '');
    $category       = $_POST['category'] ?? 'membership';
    $badge          = $_POST['badge'] ?? 'none';
    $badge_text     = trim($_POST['badge_text'] ?? '');
    $discount_label = trim($_POST['discount_label'] ?? '');
    $orig           = $_POST['original_price'] !== '' ? floatval($_POST['original_price']) : null;
    $newp           = $_POST['new_price'] !== '' ? floatval($_POST['new_price']) : null;
    $starts_at      = $_POST['starts_at'] ?: null;
    $expires_at     = $_POST['expires_at'] ?: null;
    $cta_text       = trim($_POST['cta_text'] ?? 'Claim Now');
    $cta_link       = trim($_POST['cta_link'] ?? 'login.php');
    $image_url      = trim($_POST['image_url'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $display_order  = is_numeric($_POST['display_order'] ?? '') ? intval($_POST['display_order']) : 100;
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
      flash('error', 'Title is required.');
      header("Location: admin_promos.php"); exit;
    }

    $sql = "INSERT INTO promos
      (title, category, badge, badge_text, discount_label, original_price, new_price,
       starts_at, expires_at, cta_text, cta_link, image_url, description, display_order, is_active)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param(
        'sssssssssisssii',
        $title, $category, $badge, $badge_text, $discount_label,
        $orig, $newp, $starts_at, $expires_at, $cta_text, $cta_link,
        $image_url, $description, $display_order, $is_active
      );
      if ($stmt->execute()) {
        flash('success', 'Promo created successfully.');
      } else {
        flash('error', 'Failed to create promo.');
      }
      $stmt->close();
    }
    header("Location: admin_promos.php"); exit;
  }

  if ($action === 'toggle' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $to = (int)($_POST['to'] ?? 1);
    if ($id > 0) {
      if ($stmt = $conn->prepare("UPDATE promos SET is_active=? WHERE id=?")) {
        $stmt->bind_param('ii', $to, $id);
        $ok = $stmt->execute();
        $stmt->close();
        flash($ok ? 'success' : 'error', $ok ? 'Promo status updated.' : 'Failed to update status.');
      }
    }
    header("Location: admin_promos.php"); exit;
  }

  if ($action === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    if ($id > 0) {
      if ($stmt = $conn->prepare("DELETE FROM promos WHERE id=?")) {
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        flash($ok ? 'success' : 'error', $ok ? 'Promo deleted.' : 'Failed to delete promo.');
      }
    }
    header("Location: admin_promos.php"); exit;
  }
}

// ---- Filters (optional) ----
$filter_category = $_GET['cat'] ?? 'all';
$filter_status   = $_GET['status'] ?? 'all';
$where = [];
$params = []; $types = '';

if (in_array($filter_category, ['membership','product','class'], true)) {
  $where[] = "category = ?";
  $params[] = $filter_category; $types .= 's';
}
if (in_array($filter_status, ['active','inactive'], true)) {
  $where[] = "is_active = ?";
  $params[] = ($filter_status === 'active') ? 1 : 0; $types .= 'i';
}

$sqlList = "SELECT * FROM promos";
if ($where) $sqlList .= " WHERE ".implode(" AND ", $where);
$sqlList .= " ORDER BY display_order ASC, COALESCE(expires_at, '9999-12-31') ASC, id DESC";

$rows = [];
if ($stmt = $conn->prepare($sqlList)) {
  if ($types) { $stmt->bind_param($types, ...$params); }
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  $stmt->close();
}

$success = flash('success');
$error   = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no"/>
<title>Admin | Promos</title>
<link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
<link rel="stylesheet" href="style.css">
<style>
  :root { --text-clr: rgba(255,255,255,0.6); --hover:#fff; }
  body { background:#2b2b2b; color:#f8f9fa; }
  .navbar{ background:transparent!important; }
  .navbar .container { border-bottom:1px solid var(--text-clr); }
  .nav-link { color:var(--text-clr)!important; }
  .nav-link:hover, .nav-link.active { color:var(--hover)!important; }
  .logo-img { height:50px; }

  .page-wrap { padding-top: 1rem; }
  .card-slab { background:#fff; color:#111; border:none; border-radius:1rem; }
  .sticky-th thead th { position:sticky; top:0; background:#212529; color:#fff; z-index:2; }

  .badge-hot{ background:linear-gradient(135deg,#fa709a 0%,#fee140 100%); }
  .badge-expires{ background:linear-gradient(135deg,#ff9a56 0%,#ff6a00 100%); }
  .badge-new{ background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); }
  .badge-special{ background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); }
  .badge-none{ background:#adb5bd; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container px-md-4 px-lg-5 py-1">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" class="logo-img" alt="Logo"></a>
    <button class="navbar-toggler text-light" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse justify-content-between">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Promos</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
      <a href="dashboard.php" class="btn btn-light btn-sm"><i class="ri-arrow-go-back-line"></i> Back</a>
    </div>
  </div>
</nav>

<div class="container page-wrap">
  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="ri-check-line me-1"></i><?= htmlspecialchars($success) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i><?= htmlspecialchars($error) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <!-- Create Promo -->
  <div class="card card-slab shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="m-0 fw-bold">Create Promo</h4>
      </div>

      <form method="POST" class="row g-3">
        <input type="hidden" name="action" value="create">
        <div class="col-md-6">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required placeholder="New Member Special">
        </div>
        <div class="col-md-3">
          <label class="form-label">Category</label>
          <select class="form-select" name="category">
            <option value="membership">Membership</option>
            <option value="product">Product</option>
            <option value="class">Class</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Badge</label>
          <select class="form-select" name="badge">
            <option value="none">None</option>
            <option value="hot">Hot</option>
            <option value="expires">Expires</option>
            <option value="new">New</option>
            <option value="special">Special</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Badge Text (optional)</label>
          <input type="text" name="badge_text" class="form-control" placeholder="🎓 STUDENT PROMO">
        </div>
        <div class="col-md-4">
          <label class="form-label">Discount Label (optional)</label>
          <input type="text" name="discount_label" class="form-control" placeholder="50% OFF / BUY 10 GET 2">
        </div>
        <div class="col-md-2">
          <label class="form-label">Original Price</label>
          <input type="number" step="0.01" name="original_price" class="form-control" placeholder="1800">
        </div>
        <div class="col-md-2">
          <label class="form-label">New Price</label>
          <input type="number" step="0.01" name="new_price" class="form-control" placeholder="900">
        </div>

        <div class="col-md-3">
          <label class="form-label">Starts At</label>
          <input type="date" name="starts_at" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Expires At</label>
          <input type="date" name="expires_at" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">CTA Text</label>
          <input type="text" name="cta_text" class="form-control" value="Claim Now">
        </div>
        <div class="col-md-3">
          <label class="form-label">CTA Link</label>
          <input type="text" name="cta_link" class="form-control" value="login.php">
        </div>

        <div class="col-md-6">
          <label class="form-label">Image URL</label>
          <input type="url" name="image_url" class="form-control" placeholder="https://...">
        </div>
        <div class="col-md-4">
          <label class="form-label">Display Order</label>
          <input type="number" name="display_order" class="form-control" value="100">
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
            <label class="form-check-label" for="is_active">Active</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Short details that will appear under the title."></textarea>
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary"><i class="ri-add-line"></i> Create Promo</button>
          <a href="admin_promos.php" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Filters -->
  <div class="card card-slab shadow-sm mb-3">
    <div class="card-body">
      <form class="row g-2">
        <div class="col-sm-3">
          <label class="form-label">Category</label>
          <select class="form-select" name="cat" onchange="this.form.submit()">
            <option value="all" <?= $filter_category==='all'?'selected':''; ?>>All</option>
            <option value="membership" <?= $filter_category==='membership'?'selected':''; ?>>Membership</option>
            <option value="product" <?= $filter_category==='product'?'selected':''; ?>>Product</option>
            <option value="class" <?= $filter_category==='class'?'selected':''; ?>>Class</option>
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status" onchange="this.form.submit()">
            <option value="all" <?= $filter_status==='all'?'selected':''; ?>>All</option>
            <option value="active" <?= $filter_status==='active'?'selected':''; ?>>Active</option>
            <option value="inactive" <?= $filter_status==='inactive'?'selected':''; ?>>Inactive</option>
          </select>
        </div>
        <div class="col-sm-6 d-flex align-items-end justify-content-end">
          <span class="badge text-bg-secondary">Total: <?= count($rows) ?></span>
        </div>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="card card-slab shadow-sm">
    <div class="card-body">
      <h5 class="fw-bold">Promos</h5>
      <div class="table-responsive sticky-th mt-3" style="max-height:60vh;">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Category</th>
              <th>Badge</th>
              <th>Label</th>
              <th>Prices</th>
              <th>Window</th>
              <th>Order</th>
              <th>Status</th>
              <th>CTA</th>
              <th>Image</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($rows): foreach ($rows as $r): 
            $badgeMap = [
              'hot'     => 'badge-hot',
              'expires' => 'badge-expires',
              'new'     => 'badge-new',
              'special' => 'badge-special',
              'none'    => 'badge-none'
            ];
            $bcls = $badgeMap[$r['badge']] ?? 'badge-none';
          ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($r['description'] ?? '') ?></div>
              </td>
              <td><span class="badge text-bg-dark"><?= htmlspecialchars(ucfirst($r['category'])) ?></span></td>
              <td>
                <span class="badge <?= $bcls ?>"><?= $r['badge']!=='none' ? strtoupper($r['badge']) : '—' ?></span>
                <?php if ($r['badge_text']): ?><div class="small text-muted"><?= htmlspecialchars($r['badge_text']) ?></div><?php endif; ?>
              </td>
              <td><?= htmlspecialchars($r['discount_label'] ?: '—') ?></td>
              <td>
                <?php
                  $op = $r['original_price']; $np = $r['new_price'];
                  if ($op !== null || $np !== null) {
                    echo '<div>'.($op!==null? '₱'.number_format($op,2):'').($op!==null && $np!==null?' → ':'').($np!==null? '₱'.number_format($np,2):'').'</div>';
                  } else { echo '—'; }
                ?>
              </td>
              <td>
                <div class="small">
                  <?php if ($r['starts_at']): ?>From: <?= htmlspecialchars(date('M d, Y', strtotime($r['starts_at']))) ?><br><?php endif; ?>
                  <?php if ($r['expires_at']): ?>Until: <?= htmlspecialchars(date('M d, Y', strtotime($r['expires_at']))) ?><?php endif; ?>
                  <?php if (!$r['starts_at'] && !$r['expires_at']) echo '—'; ?>
                </div>
              </td>
              <td><?= (int)$r['display_order'] ?></td>
              <td>
                <?php if ($r['is_active']): ?>
                  <span class="badge text-bg-success">Active</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="small fw-semibold"><?= htmlspecialchars($r['cta_text'] ?: 'Claim Now') ?></div>
                <a href="<?= htmlspecialchars($r['cta_link'] ?: '#') ?>" target="_blank" class="small">link</a>
              </td>
              <td>
                <?php if ($r['image_url']): ?>
                  <a href="<?= htmlspecialchars($r['image_url']) ?>" target="_blank">
                    <img src="<?= htmlspecialchars($r['image_url']) ?>" style="max-width:70px; border-radius:8px;">
                  </a>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td class="d-flex flex-column gap-1">
                <form method="POST" class="d-inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="to" value="<?= $r['is_active']?0:1 ?>">
                  <button class="btn btn-sm <?= $r['is_active']?'btn-warning':'btn-success' ?>">
                    <?= $r['is_active']?'Deactivate':'Activate' ?>
                  </button>
                </form>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this promo?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="12" class="text-center text-muted py-4">No promos yet. Create one above.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
