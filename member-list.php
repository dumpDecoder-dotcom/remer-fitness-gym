<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// Flash
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ----- Fetch all Members with pending count (for Awaiting Approval) -----
$members = [];
$sql = "
  SELECT
    u.user_id, u.fname, u.lname, u.status,
    (
      SELECT COUNT(*) FROM transactions t
      WHERE t.user_id = u.user_id
        AND t.transaction_type = 'membership'
        AND t.status = 'pending_verification'
      ORDER BY t.created_at DESC
      LIMIT 1
    ) AS pending_cnt
  FROM `user` u
  WHERE u.role = 'Member'
  ORDER BY u.lname, u.fname
";
if ($res = mysqli_query($conn, $sql)) {
  while ($r = mysqli_fetch_assoc($res)) $members[] = $r;
  mysqli_free_result($res);
}

// ----- Split into Active vs Inactive/Awaiting -----
$activeMembers   = [];
$inactiveMembers = []; // includes awaiting approval

foreach ($members as $m) {
  $isPending = ((int)$m['pending_cnt'] > 0);
  if ($m['status'] === 'Active') {
    $activeMembers[] = $m;
  } else {
    // Inactive or Awaiting Approval bucket
    $m['_awaiting'] = $isPending;
    $inactiveMembers[] = $m;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Administrator | Remer Fitness Gym</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

  <!-- NAV -->
  <nav class="navbar navbar-expand-lg bg-transparent">
    <div class="container px-md-4 px-lg-5 py-1" style="border-bottom:1px solid var(--text-clr);">
      <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
        <ul class="navbar-nav ms-2">
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
          <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
          <li class="nav-item"><a class="nav-link active" href="#">Members</a></li>
          <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
          <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
          <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
          <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- CONTENT -->
  <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
    <div class="w-100">
      <a href="#" onclick="event.preventDefault();window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width:40px;height:40px;">
        <i class="ri-arrow-go-back-line"></i>
      </a>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
           style="z-index:1055;width:90%;max-width:500px;" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
           style="z-index:1055;width:90%;max-width:500px;" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- ===== ACTIVE MEMBERS ===== -->
    <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100">
      <div class="d-flex align-items-center justify-content-between">
        <h2 class="lh-1 m-0 display-6 fw-bold">
          Active Members
          <span class="badge bg-success ms-2"><?= count($activeMembers) ?></span>
        </h2>
      </div>

      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($activeMembers)): ?>
            <?php foreach ($activeMembers as $m): ?>
              <tr>
                <td><?= htmlspecialchars($m['fname'].' '.$m['lname']) ?></td>
                <td><span class="badge bg-success">Active</span></td>
                <td class="text-center">
                  <a href="Actions/action_deletemember.php?id=<?= (int)$m['user_id'] ?>" class="btn btn-primary btn-sm">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="3" class="text-muted">No active members.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== INACTIVE & AWAITING APPROVAL ===== -->
    <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100">
      <div class="d-flex align-items-center justify-content-between">
        <h2 class="lh-1 m-0 display-6 fw-bold">
          Inactive &amp; Awaiting Approval
          <span class="badge bg-secondary ms-2"><?= count($inactiveMembers) ?></span>
        </h2>
      </div>

      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($inactiveMembers)): ?>
            <?php foreach ($inactiveMembers as $m): ?>
              <?php
                $label = !empty($m['_awaiting']) ? 'Awaiting Approval' : 'Inactive';
                $cls   = !empty($m['_awaiting']) ? 'warning' : 'secondary';
              ?>
              <tr>
                <td><?= htmlspecialchars($m['fname'].' '.$m['lname']) ?></td>
                <td><span class="badge bg-<?= $cls ?>"><?= $label ?></span></td>
                <td class="text-center">
                  <a href="Actions/action_renewmember.php?id=<?= (int)$m['user_id'] ?>" class="btn btn-success btn-sm">Renew</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="3" class="text-muted">No inactive or pending members.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
