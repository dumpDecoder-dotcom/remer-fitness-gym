<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

/* ---------- Flash ---------- */
$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

/* ---------- Time context ---------- */
date_default_timezone_set('Asia/Manila');
$today        = date('Y-m-d');
$current_time = date('H:i:s');

/* ---------- Fetch approved classes for this instructor (with participant counts) ---------- */
$classes = [];
$sql = "
  SELECT
    c.id,
    c.class_name,
    c.start_date, c.end_date,
    c.start_time, c.end_time,
    c.participant_limit,
    IFNULL(rc.enrolled, 0) AS enrolled
  FROM class c
  INNER JOIN class_assignment_requests r
      ON c.id = r.class_id
  LEFT JOIN (
      SELECT class_id, COUNT(*) AS enrolled
      FROM member_class_registration
      GROUP BY class_id
  ) rc ON rc.class_id = c.id
  WHERE r.instructor_id = ?
    AND r.status = 'Approved'
    AND c.end_date >= ?          -- ignore fully finished classes
  ORDER BY c.start_date ASC, c.start_time ASC
";

if ($stmt = $conn->prepare($sql)) {
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $stmt->bind_param('is', $uid, $today);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $classes[] = $row;
  $stmt->close();
}

/* ---------- Classify: Ongoing / Waiting / Upcoming ---------- */
$ongoing = [];
$waiting = [];
$upcoming = [];

foreach ($classes as $c) {
  $sd = $c['start_date'];
  $ed = $c['end_date'];
  $st = $c['start_time'];
  $et = $c['end_time'];

  if ($today < $sd) {
    // Future class
    $c['_label'] = 'Upcoming';
    $c['_badge'] = 'bg-info text-dark';
    $c['_icon']  = 'ri-calendar-line';
    $upcoming[]  = $c;
  } elseif ($today === $sd) {
    if ($current_time < $st) {
      $c['_label'] = 'Waiting';
      $c['_badge'] = 'bg-warning text-dark';
      $c['_icon']  = 'ri-timer-line';
      $waiting[]   = $c;
    } elseif ($current_time >= $st && $current_time <= $et) {
      $c['_label'] = 'Ongoing';
      $c['_badge'] = 'bg-success text-white';
      $c['_icon']  = 'ri-time-line';
      $ongoing[]   = $c;
    } else {
      // same-day but already ended → ignore
      continue;
    }
  } else { // $today > $sd
    if ($today <= $ed) {
      // Multiday still running
      $c['_label'] = 'Ongoing';
      $c['_badge'] = 'bg-success text-white';
      $c['_icon']  = 'ri-time-line';
      $ongoing[]   = $c;
    } else {
      // Finished
      continue;
    }
  }
}

/* ---------- Helper: render a section ---------- */
function render_section(string $title, array $rows) {
  ?>
  <div class="section-card">
    <div class="section-head">
      <h2 class="section-title display-6 lh-1 m-0">
        <?= htmlspecialchars($title) ?>
        <span class="badge bg-secondary ms-2"><?= count($rows) ?></span>
      </h2>
    </div>

    <div class="table-responsive mt-3 section-scroll">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th class="text-start">Class</th>
            <th>Date</th>
            <th>Time</th>
            <th>Participants</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td class="text-start fw-semibold"><?= htmlspecialchars($row['class_name']) ?></td>
              <td>
                <?= date('M j, Y', strtotime($row['start_date'])) ?>
                <?php if ($row['end_date'] && $row['end_date'] !== $row['start_date']): ?>
                  – <?= date('M j, Y', strtotime($row['end_date'])) ?>
                <?php endif; ?>
              </td>
              <td>
                <?= date('g:i A', strtotime($row['start_time'])) ?>
                – <?= date('g:i A', strtotime($row['end_time'])) ?>
              </td>
              <td>👥 <?= (int)$row['enrolled'] ?> / <?= (int)$row['participant_limit'] ?></td>
              <td>
                <span class="badge <?= $row['_badge'] ?>">
                  <i class="<?= $row['_icon'] ?>"></i> <?= $row['_label'] ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-muted text-center py-4">No classes here.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
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
  <title>Staff | Remer Fitness Gym</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    .section-card { background:#fff; border-radius:1rem; padding:1rem 1rem .75rem; margin-bottom:1rem; }
    .section-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .section-title { margin:0; font-weight:800; }
    .section-scroll { max-height: 360px; overflow:auto; scrollbar-width:thin; scrollbar-color:#ccc transparent; }
    thead th { white-space: nowrap; }
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
          <li class="nav-item"><a class="nav-link active" href="#">Class</a></li>
          <li class="nav-item"><a class="nav-link" href="staff_attendance.php">Attendance</a></li>
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
    <!-- Back button under header -->
    <div class="w-100">
      <a href="#" onclick="event.preventDefault();window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width:40px;height:40px;">
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

    <!-- Page title -->
    <div class="rounded-5 bg-white p-3">
      <div class="d-flex align-items-center justify-content-between">
        <h2 class="lh-1 m-0 display-6 fw-bold">Your Scheduled Classes</h2>
        <span class="text-muted">Today: <?= htmlspecialchars(date('M d, Y', strtotime($today))) ?></span>
      </div>
    </div>

    <!-- Sections -->
    <?php
      // Ongoing first, then Waiting, then Upcoming (matches your previous ordering)
      render_section('Ongoing Classes', $ongoing);
      render_section('Waiting Classes', $waiting);
      render_section('Upcoming Classes', $upcoming);

      if (empty($ongoing) && empty($waiting) && empty($upcoming)) {
        echo '<div class="section-card"><p class="text-center text-muted my-3">No active or upcoming classes found.</p></div>';
      }
    ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
