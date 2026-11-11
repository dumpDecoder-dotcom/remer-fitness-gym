<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// Flash
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// --- Position filter (All / Coach / Cashier) ---
$valid_positions = ['all','Coach','Cashier'];
$pos = $_GET['position'] ?? 'all';
if (!in_array($pos, $valid_positions, true)) $pos = 'all';

// Fetch staff (filter by position if selected)
$params = [];
$sql = "
  SELECT u.user_id, u.fname, u.lname, u.status, u.staff_position
  FROM `user` u
  WHERE u.role = 'Staff'
";
if ($pos !== 'all') {
  $sql .= " AND u.staff_position = ? ";
  $params[] = $pos;
}
$sql .= " ORDER BY u.lname, u.fname";

$staff = [];
$stmt = $conn->prepare($sql);
if ($pos !== 'all') {
  $stmt->bind_param('s', $params[0]);
}
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $staff[] = $r;
$stmt->close();

// Split into Active / Inactive
$activeStaff = [];
$inactiveStaff = [];
foreach ($staff as $s) {
  if (strcasecmp($s['status'], 'Active') === 0) $activeStaff[] = $s;
  else $inactiveStaff[] = $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Administrator | Remer Fitness Gym</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    .filter-bar{display:flex;gap:.75rem;align-items:center;justify-content:flex-end;margin-bottom:.75rem}
    .filter-bar .form-select{max-width:220px}
    .section-card{background:#fff;border-radius:1rem;padding:1rem}
    .section-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
    .table thead th{white-space:nowrap}
  </style>
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
          <li class="nav-item"><a class="nav-link active" href="#">Staffs</a></li>
          <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
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

    <!-- Position filter (right side) -->
    <form class="filter-bar" method="get" action="">
      <label for="position" class="m-0">Filter by position:</label>
      <select id="position" name="position" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="all"     <?= $pos==='all'?'selected':''; ?>>All</option>
        <option value="Coach"   <?= $pos==='Coach'?'selected':''; ?>>Coach</option>
        <option value="Cashier" <?= $pos==='Cashier'?'selected':''; ?>>Cashier</option>
      </select>
    </form>

    <!-- ===== ACTIVE STAFF ===== -->
    <div class="section-card">
      <div class="section-head">
        <h2 class="lh-1 m-0 display-6 fw-bold">
          Active Staff<?= $pos!=='all' ? " — $pos" : '' ?>
          <span class="badge bg-success ms-2"><?= count($activeStaff) ?></span>
        </h2>
        <a href="add-staff.php" class="btn btn-primary fw-semibold">Add Staff</a>
      </div>

      <div class="table-responsive mt-3">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($activeStaff)): ?>
            <?php foreach ($activeStaff as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['fname'].' '.$s['lname']) ?></td>
                <td><?= htmlspecialchars($s['staff_position']) ?></td>
                <td><span class="badge bg-success">Active</span></td>
                <td class="text-center">
                  <a href="Actions/action_deletestaff.php?id=<?= (int)$s['user_id'] ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Set this staff as Inactive?');">Deactivate</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-muted">No active staff<?= $pos!=='all' ? " for $pos" : '' ?>.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== INACTIVE STAFF ===== -->
    <div class="section-card">
      <div class="section-head">
        <h2 class="lh-1 m-0 display-6 fw-bold">
          Inactive Staff<?= $pos!=='all' ? " — $pos" : '' ?>
          <span class="badge bg-secondary ms-2"><?= count($inactiveStaff) ?></span>
        </h2>
      </div>

      <div class="table-responsive mt-3">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Status</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($inactiveStaff)): ?>
            <?php foreach ($inactiveStaff as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['fname'].' '.$s['lname']) ?></td>
                <td><?= htmlspecialchars($s['staff_position']) ?></td>
                <td><span class="badge bg-secondary">Inactive</span></td>
                <td class="text-center">
                  <a href="Actions/action_activatestaff.php?id=<?= (int)$s['user_id'] ?>"
                     class="btn btn-success btn-sm"
                     onclick="return confirm('Activate this staff member?');">Activate</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-muted">No inactive staff<?= $pos!=='all' ? " for $pos" : '' ?>.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
