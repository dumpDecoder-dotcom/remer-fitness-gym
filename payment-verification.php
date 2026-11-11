<?php
session_start();
include 'Actions/dbconnect.php';

// Admin gate
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: login.php");
    exit;
}

$page_title = "Payment Verification | Remer Fitness Gym";

// Alerts
$success_message = $_SESSION['success'] ?? null;
$error_message   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

/* --------------------------
   STATUS FILTER (show only selected)
   ?status_filter=completed|rejected|pending|all
---------------------------*/
$valid_filters = [
  'all'       => null,
  'completed' => "t.status = 'completed'",
  'rejected'  => "t.status = 'rejected'",
  'pending'   => "t.status = 'pending_verification'",
];
$selected_filter_key = $_GET['status_filter'] ?? 'all';
if (!array_key_exists($selected_filter_key, $valid_filters)) {
    $selected_filter_key = 'all';
}
$filter_expr = $valid_filters[$selected_filter_key];

function where_clause(string $typeClause, ?string $statusFilter): string {
    return "WHERE {$typeClause}" . ($statusFilter ? " AND {$statusFilter}" : "");
}

// MEMBERSHIP query
$q_membership = "
    SELECT 
        t.transaction_id,
        u.fname, u.lname,
        t.total_amount,
        t.payment_method,
        t.reference_number,
        t.proof_of_payment,
        t.status,
        t.transaction_type,
        t.date
    FROM transactions t
    JOIN user u ON t.user_id = u.user_id
    " . where_clause("t.transaction_type = 'membership'", $filter_expr) . "
    ORDER BY t.date DESC
";
$res_membership = mysqli_query($conn, $q_membership);

// ITEMS query
$q_item = "
    SELECT 
        t.transaction_id,
        u.fname, u.lname,
        t.total_amount,
        t.payment_method,
        t.reference_number,
        t.proof_of_payment,
        t.status,
        t.transaction_type,
        t.date
    FROM transactions t
    JOIN user u ON t.user_id = u.user_id
    " . where_clause("t.transaction_type <> 'membership'", $filter_expr) . "
    ORDER BY t.date DESC
";
$res_item = mysqli_query($conn, $q_item);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator | Remer Fitness Gym</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    :root { --text-clr: rgba(255,255,255,0.6); --hover-clr:#ffffff; }
    body { background:#2b2b2b; color:#f8f9fa; }
    .navbar { background-color:transparent!important; }
    .navbar .container-narrow { border-bottom:1px solid var(--text-clr); }

    .container-narrow { max-width: 1200px; } /* narrower layout */
    .logo-img { max-height:46px; height:auto; }
    .nav-link { color:var(--text-clr)!important; font-weight:500; transition:color .3s; }
    .nav-link:hover, .nav-link.active { color:var(--hover-clr)!important; }

    .page-wrap { padding: 14px 0 24px; }

    /* Single thin toolbar row (Back on left, filter on right) */
    .thin-toolbar {
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:.75rem;
      padding:.5rem .25rem;
      border-bottom:1px solid #e6e6e6;   /* <- just a line */
      margin: .75rem 0 1rem;             /* slight vertical spacing */
    }
    .thin-toolbar .form-select { width: 220px; }

    .section-card { background:#fff; color:#111; border:none; border-radius:14px; padding:14px; }
    .section-header { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .section-title { margin:0; font-weight:800; font-size:1.1rem; }
    .scroll-wrap { max-height: 52vh; overflow:auto; }
    .table thead.table-dark th { position: sticky; top: 0; z-index: 2; }
    .table { font-size: .92rem; }
    .table td, .table th { white-space: nowrap; }
    table img { border-radius:8px; max-width:70px; height:auto; cursor:pointer; }

    .status-badge{ padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; }
    .status-pending{ background:#ffeeba; color:#856404; }
    .status-completed{ background:#d4edda; color:#155724; }
    .status-rejected{ background:#f8d7da; color:#721c24; }

    .btn-sm { padding: .25rem .5rem; }

    @media (max-width: 1400px){
      .scroll-wrap { max-height: 48vh; }
    }
    @media print {
      @page { size: A4 landscape; margin: 10mm; }
      body { background:#fff; color:#000; }
      .navbar, .btn, .alert, .thin-toolbar { display:none !important; }
      .section-card { box-shadow:none; border:1px solid #ccc; }
      .scroll-wrap { max-height:none; overflow:visible; }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container container-narrow px-3 px-lg-4 py-1">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
    <button class="navbar-toggler text-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
        <li class="nav-item"><a class="nav-link active" href="payment-verification.php">Payments</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container container-narrow page-wrap">

  <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mb-2">
      <?= htmlspecialchars($success_message) ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2 mb-2">
      <?= htmlspecialchars($error_message) ?>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- One-line toolbar: Back (left) + Show status (right) -->
  <div class="thin-toolbar">
    <a href="dashboard.php" class="btn btn-light btn-sm"><i class="ri-arrow-go-back-line"></i> Back</a>

    <form class="d-flex align-items-center gap-2 m-0" method="get" action="">
      <label for="status_filter" class="form-label m-0">Show status:</label>
      <select id="status_filter" name="status_filter" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="all"       <?= $selected_filter_key==='all'?'selected':''; ?>>All</option>
        <option value="completed" <?= $selected_filter_key==='completed'?'selected':''; ?>>Completed</option>
        <option value="rejected"  <?= $selected_filter_key==='rejected'?'selected':''; ?>>Rejected</option>
        <option value="pending"   <?= $selected_filter_key==='pending'?'selected':''; ?>>Pending</option>
      </select>
    </form>
  </div>

  <!-- MEMBERSHIP (TOP) -->
  <div class="section-card shadow-sm mb-3">
    <div class="section-header">
      <h4 class="section-title">Membership Payments<?= $selected_filter_key!=='all' ? ' — '.ucfirst($selected_filter_key):'' ?></h4>
      <span class="badge bg-primary"><?= (int)($res_membership?mysqli_num_rows($res_membership):0) ?> records</span>
    </div>

    <div class="table-responsive scroll-wrap mt-2">
      <table class="table table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th style="min-width:80px;">ID</th>
            <th style="min-width:180px;">Member</th>
            <th style="min-width:110px;">Amount</th>
            <th style="min-width:150px;">Method</th>
            <th style="min-width:150px;">Reference</th>
            <th style="min-width:105px;">Proof</th>
            <th style="min-width:120px;">Status</th>
            <th style="min-width:135px;">Date</th>
            <th style="min-width:120px;">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($res_membership && mysqli_num_rows($res_membership) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($res_membership)): ?>
          <tr>
            <td>#<?= (int)$row['transaction_id'] ?></td>
            <td><?= htmlspecialchars($row['fname'].' '.$row['lname']) ?></td>
            <td>₱<?= number_format((float)$row['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars(ucfirst($row['payment_method'])) ?></td>
            <td><?= htmlspecialchars($row['reference_number'] ?: '—') ?></td>
            <td>
              <?php if (!empty($row['proof_of_payment'])): ?>
                <a href="<?= htmlspecialchars($row['proof_of_payment']) ?>" target="_blank" rel="noopener">
                  <img src="<?= htmlspecialchars($row['proof_of_payment']) ?>" alt="Proof">
                </a>
              <?php else: ?><span class="text-muted">No proof</span><?php endif; ?>
            </td>
            <td>
              <?php
                $s = $row['status'];
                $cls = ($s==='pending_verification') ? 'status-pending'
                     : (($s==='completed') ? 'status-completed' : 'status-rejected');
              ?>
              <span class="status-badge <?= $cls ?>"><?= htmlspecialchars(ucfirst(str_replace('_',' ', $s))) ?></span>
            </td>
            <td><?= htmlspecialchars(date('M d, Y', strtotime($row['date']))) ?></td>
            <td>
              <?php if ($row['status'] === 'pending_verification'): ?>
                <form method="POST" action="Actions/verify_payment.php" class="d-flex gap-1">
                  <input type="hidden" name="transaction_id" value="<?= (int)$row['transaction_id'] ?>">
                  <button name="action" value="approve" class="btn btn-success btn-sm" title="Approve">
                    <i class="ri-check-line"></i>
                  </button>
                  <button name="action" value="reject" class="btn btn-danger btn-sm" title="Reject">
                    <i class="ri-close-line"></i>
                  </button>
                </form>
              <?php else: ?><small class="text-muted">—</small><?php endif; ?>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="9" class="text-muted">No membership transactions<?= $selected_filter_key!=='all'?' for this status':'' ?>.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ITEMS (BOTTOM) -->
  <div class="section-card shadow-sm">
    <div class="section-header">
      <h4 class="section-title">Item Payments<?= $selected_filter_key!=='all' ? ' — '.ucfirst($selected_filter_key):'' ?></h4>
      <span class="badge bg-secondary"><?= (int)($res_item?mysqli_num_rows($res_item):0) ?> records</span>
    </div>

    <div class="table-responsive scroll-wrap mt-2">
      <table class="table table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th style="min-width:80px;">ID</th>
            <th style="min-width:180px;">Member</th>
            <th style="min-width:110px;">Amount</th>
            <th style="min-width:150px;">Method</th>
            <th style="min-width:150px;">Reference</th>
            <th style="min-width:105px;">Proof</th>
            <th style="min-width:120px;">Status</th>
            <th style="min-width:135px;">Date</th>
            <th style="min-width:120px;">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($res_item && mysqli_num_rows($res_item) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($res_item)): ?>
          <tr>
            <td>#<?= (int)$row['transaction_id'] ?></td>
            <td><?= htmlspecialchars($row['fname'].' '.$row['lname']) ?></td>
            <td>₱<?= number_format((float)$row['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars(ucfirst($row['payment_method'])) ?></td>
            <td><?= htmlspecialchars($row['reference_number'] ?: '—') ?></td>
            <td>
              <?php if (!empty($row['proof_of_payment'])): ?>
                <a href="<?= htmlspecialchars($row['proof_of_payment']) ?>" target="_blank" rel="noopener">
                  <img src="<?= htmlspecialchars($row['proof_of_payment']) ?>" alt="Proof">
                </a>
              <?php else: ?><span class="text-muted">No proof</span><?php endif; ?>
            </td>
            <td>
              <?php
                $s = $row['status'];
                $cls = ($s==='pending_verification') ? 'status-pending'
                     : (($s==='completed') ? 'status-completed' : 'status-rejected');
              ?>
              <span class="status-badge <?= $cls ?>"><?= htmlspecialchars(ucfirst(str_replace('_',' ', $s))) ?></span>
            </td>
            <td><?= htmlspecialchars(date('M d, Y', strtotime($row['date']))) ?></td>
            <td>
              <?php if ($row['status'] === 'pending_verification'): ?>
                <form method="POST" action="Actions/verify_payment.php" class="d-flex gap-1">
                  <input type="hidden" name="transaction_id" value="<?= (int)$row['transaction_id'] ?>">
                  <button name="action" value="approve" class="btn btn-success btn-sm" title="Approve">
                    <i class="ri-check-line"></i>
                  </button>
                  <button name="action" value="reject" class="btn btn-danger btn-sm" title="Reject">
                    <i class="ri-close-line"></i>
                  </button>
                </form>
              <?php else: ?><small class="text-muted">—</small><?php endif; ?>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="9" class="text-muted">No item transactions<?= $selected_filter_key!=='all'?' for this status':'' ?>.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
