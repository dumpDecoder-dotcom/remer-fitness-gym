<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// Flash
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Manila time & today
date_default_timezone_set("Asia/Manila");
$today = date("Y-m-d");

// --- Fetch all Members with their latest attendance *for today*
$sql = "
  SELECT
    u.user_id, u.fname, u.lname,
    a.in_time, a.out_time
  FROM `user` u
  LEFT JOIN (
      SELECT atd.*
      FROM attendance atd
      JOIN (
          SELECT user_id, MAX(id) AS max_id
          FROM attendance
          WHERE `date` = ?
          GROUP BY user_id
      ) latest ON latest.max_id = atd.id
  ) a ON a.user_id = u.user_id
  WHERE u.role = 'Member'
  ORDER BY u.lname, u.fname
";

$members = [];
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param('s', $today);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $members[] = $r;
  $stmt->close();
}

// --- Split into Checked-in vs Not checked-in (for today)
$checkedIn    = [];
$notCheckedIn = [];

foreach ($members as $m) {
  if (!empty($m['in_time'])) $checkedIn[] = $m;
  else $notCheckedIn[] = $m;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff | Attendance Monitoring</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    .section-card { background:#fff; border-radius:1rem; padding:1rem 1rem .5rem; }
    .section-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .section-title { margin:0; font-weight:800; }
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
          <li class="nav-item"><a class="nav-link" href="staff_class.php">Class</a></li>
          <li class="nav-item"><a class="nav-link active" href="#">Attendance</a></li>
          <li class="nav-item"><a class="nav-link" href="staff_schedule.php">Schedule</a></li>
          <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
          <li class="nav-item"><a class="nav-link" href="staff_payment_verification.php">Tsansaction</a></li>
          <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- CONTENT -->
  <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">

    <!-- Back button (not in header) -->
    <div class="w-100">
      <a href="#" onclick="event.preventDefault();window.history.back();"
         class="btn btn-light rounded-circle fw-bold shadow text-nowrap"
         style="width:40px;height:40px;">
        <i class="ri-arrow-go-back-line"></i>
      </a>
    </div>

    <!-- Alerts -->
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

    <!-- ===== CHECKED IN TODAY ===== -->
    <div class="section-card">
      <div class="section-head">
        <h2 class="section-title display-6 lh-1 m-0">
          Checked In Today (<?= htmlspecialchars(date('M d, Y', strtotime($today))) ?>)
          <span class="badge bg-success ms-2"><?= count($checkedIn) ?></span>
        </h2>
      </div>

      <div class="table-responsive mt-3">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Check In</th>
              <th>Check Out</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($checkedIn)): ?>
            <?php foreach ($checkedIn as $row): ?>
              <?php
                $uid   = (int)$row['user_id'];
                $name  = htmlspecialchars($row['fname'].' '.$row['lname']);
                $in    = $row['in_time']  ? date("h:i A", strtotime($row['in_time']))   : '—';
                $out   = $row['out_time'] ? date("h:i A", strtotime($row['out_time']))  : '—';
                $canCO = $row['in_time'] && !$row['out_time']; // can Check Out
              ?>
              <tr>
                <td><?= $name ?></td>
                <td><?= $in ?></td>
                <td><?= $out ?></td>
                <td class="text-center">
                  <?php if ($canCO): ?>
                    <a href="Actions/action_checkout.php?user_id=<?= $uid ?>" class="btn btn-warning btn-sm">Check Out</a>
                  <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>Done</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-muted">No one has checked in yet today.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ===== NOT CHECKED IN TODAY ===== -->
    <div class="section-card">
      <div class="section-head">
        <h2 class="section-title display-6 lh-1 m-0">
          Not Checked In Today
          <span class="badge bg-secondary ms-2"><?= count($notCheckedIn) ?></span>
        </h2>
      </div>

      <div class="table-responsive mt-3">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Check In</th>
              <th>Check Out</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($notCheckedIn)): ?>
            <?php foreach ($notCheckedIn as $row): ?>
              <?php
                $uid  = (int)$row['user_id'];
                $name = htmlspecialchars($row['fname'].' '.$row['lname']);
              ?>
              <tr>
                <td><?= $name ?></td>
                <td>—</td>
                <td>—</td>
                <td class="text-center">
                  <a href="Actions/action_checkin.php?user_id=<?= $uid ?>" class="btn btn-success btn-sm">Check In</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-muted">All members have already checked in today.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
