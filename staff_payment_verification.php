<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

/* ---------------- Flash ---------------- */
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

/* ---------------- Status filter ----------------
   ?status = all | pending_verification | completed | rejected
-------------------------------------------------*/
$filter_status = $_GET['status'] ?? 'all';
$allowed = ['all','pending_verification','completed','rejected'];
if (!in_array($filter_status, $allowed, true)) {
  $filter_status = 'all';
}

/* ---------------- Fetch item transactions (prepared) ---------------- */
$rows = [];
$sql = "
  SELECT 
      t.transaction_id,
      u.fname, u.lname,
      t.total_amount,
      t.payment_method,
      t.proof_of_payment,
      t.status,
      t.date
  FROM transactions t
  JOIN user u ON t.user_id = u.user_id
  WHERE t.transaction_type = 'item'
";
$params = [];
$types  = '';

if ($filter_status !== 'all') {
  $sql .= " AND t.status = ? ";
  $params[] = $filter_status;
  $types   .= 's';
}

$sql .= "
  ORDER BY 
    CASE 
      WHEN t.status = 'pending_verification' THEN 1
      WHEN t.status = 'completed' THEN 2
      WHEN t.status = 'rejected' THEN 3
      ELSE 4
    END,
    t.date DESC
";

if ($stmt = $conn->prepare($sql)) {
  if ($types) { $stmt->bind_param($types, ...$params); }
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  $stmt->close();
}

/* ---------------- Split into sections ---------------- */
$pending  = [];
$approved = [];
$declined = [];

foreach ($rows as $r) {
  switch ($r['status']) {
    case 'pending_verification': $pending[]  = $r; break;
    case 'completed':            $approved[] = $r; break;
    case 'rejected':             $declined[] = $r; break;
    default: break;
  }
}

/* ---------------- Renderer ---------------- */
function render_section(string $title, string $status_key, array $data) {
  $badgeClass = [
    'pending_verification' => 'status-pending',
    'completed'            => 'status-completed',
    'rejected'             => 'status-rejected'
  ][$status_key] ?? 'status-pending';
  ?>
  <div class="card section-card shadow-sm mb-3">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between">
        <h5 class="m-0 fw-bold"><?= htmlspecialchars($title) ?></h5>
        <span class="badge bg-dark-subtle text-dark-emphasis"><?= count($data) ?> record(s)</span>
      </div>

      <div class="table-container mt-3">
        <table class="table table-hover align-middle text-center">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Amount</th>
              <th>Type</th>
              <th>Payment Method</th>
              <th>Proof</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($data)): ?>
            <?php foreach ($data as $row): ?>
              <tr>
                <td>#<?= (int)$row['transaction_id'] ?></td>
                <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                <td>₱<?= number_format((float)$row['total_amount'], 2) ?></td>
                <td><span class="type-badge">Item</span></td>
                <td><?= htmlspecialchars(ucfirst($row['payment_method'])) ?></td>
                <td>
                  <?php if (!empty($row['proof_of_payment'])): ?>
                    <a href="<?= htmlspecialchars($row['proof_of_payment']) ?>" target="_blank" rel="noopener">
                      <img src="<?= htmlspecialchars($row['proof_of_payment']) ?>" alt="Proof">
                    </a>
                  <?php else: ?>
                    <span class="text-muted">No proof</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-badge <?= $badgeClass ?>">
                    <?= htmlspecialchars(ucfirst(str_replace('_',' ', $row['status']))) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars(date('M d, Y', strtotime($row['date']))) ?></td>
                <td>
                  <?php if ($row['status'] === 'pending_verification'): ?>
                    <form method="POST" action="Actions/verify_payment_cashier.php" class="d-flex justify-content-center gap-2">
                      <input type="hidden" name="transaction_id" value="<?= (int)$row['transaction_id'] ?>">
                      <button name="action" value="approve" class="btn btn-success btn-sm" title="Approve">
                        <i class="ri-check-line"></i>
                      </button>
                      <button name="action" value="reject" class="btn btn-danger btn-sm" title="Reject">
                        <i class="ri-close-line"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <small class="text-muted">—</small>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No records.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cashier | Payment Verification (Items)</title>
<link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
:root { --text-clr: rgba(255,255,255,0.6); --hover-clr: #ffffff; }
body { background:#2b2b2b; color:#f8f9fa; }

/* Navbar */
.navbar { background-color: transparent !important; }
.navbar .container { border-bottom: 1px solid var(--text-clr); }
.nav-link { color: var(--text-clr) !important; font-weight: 500; transition: color .3s; }
.nav-link:hover { color: var(--hover-clr) !important; }
.logo-img { height: 50px; }

/* Cards & table */
.section-card { border: none; border-radius: 1rem; }
.table-container{
  overflow-x: auto; overflow-y: auto; max-height: 420px;
  white-space: nowrap; border-radius: 10px;
}
table { min-width: 1000px; }
thead th { position: sticky; top: 0; background:#212529; color:#fff; z-index:2; }
table img { border-radius: 8px; max-width: 80px; height:auto; cursor:pointer; }
.status-badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.status-pending { background:#ffeeba; color:#856404; }
.status-completed { background:#d4edda; color:#155724; }
.status-rejected { background:#f8d7da; color:#721c24; }
.type-badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; background:#6f42c1; color:#fff; }

/* Filter (moved below header) */
.toolbar { display:flex; justify-content:flex-end; gap:.5rem; margin-top:.5rem; }
.filter-select { width: 200px; border-radius: 10px; padding:6px; }
</style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

<!-- Navbar (no filter here anymore) -->
<nav class="navbar navbar-expand-lg bg-transparent">
  <div class="container px-md-4 px-lg-5 py-1">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="staff_class.php">Class</a></li>
        <li class="nav-item"><a class="nav-link" href="staff_attendance.php">Attendance</a></li>
        <li class="nav-item"><a class="nav-link" href="staff_schedule.php">Schedule</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Transaction</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Content -->
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="fw-bold m-0">Payment Verification (Items)</h3>
    <a href="#" onclick="event.preventDefault();history.back();" class="btn btn-light btn-sm">
      <i class="ri-arrow-go-back-line"></i> Back
    </a>
  </div>

  <!-- 🔽 SORT/FILTER BAR (lowered; not in the header) -->
  <form method="GET" class="toolbar">
    <select name="status" class="form-select form-select-sm filter-select" onchange="this.form.submit()">
      <option value="all" <?= $filter_status==='all'?'selected':''; ?>>Show All</option>
      <option value="pending_verification" <?= $filter_status==='pending_verification'?'selected':''; ?>>Pending</option>
      <option value="completed" <?= $filter_status==='completed'?'selected':''; ?>>Approved</option>
      <option value="rejected" <?= $filter_status==='rejected'?'selected':''; ?>>Declined</option>
    </select>
  </form>

  <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3">
      <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3">
      <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php
    if ($filter_status === 'all' || $filter_status === 'pending_verification') {
      render_section('Pending', 'pending_verification', $pending);
    }
    if ($filter_status === 'all' || $filter_status === 'completed') {
      render_section('Approved', 'completed', $approved);
    }
    if ($filter_status === 'all' || $filter_status === 'rejected') {
      render_section('Declined', 'rejected', $declined);
    }
    if ($filter_status !== 'all' && empty($pending) && empty($approved) && empty($declined)) {
      echo '<div class="card section-card"><div class="card-body text-center text-muted">No records for this status.</div></div>';
    }
  ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
