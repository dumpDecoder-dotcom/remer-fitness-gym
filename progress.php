<?php
// progress.php
session_start();
include 'Actions/dbconnect.php';

// Ensure PH time math is correct
date_default_timezone_set('Asia/Manila');

// ---- Preset remark options (standardized) ----
$REMARK_PRESETS = [
    "Good job",
    "You're on track",
    "Great form today",
    "Completed target",
    "Missed target—recover next time"
];

// ---- Handle update (prepared statement) ----
if (isset($_POST['update_remarks'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $remarks = trim($_POST['remarks'] ?? '');

    if ($id > 0) {
        if ($stmt = $conn->prepare("UPDATE progress SET remarks = ? WHERE id = ?")) {
            $stmt->bind_param('si', $remarks, $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Remarks updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update remarks.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Failed to prepare update statement.";
        }
    } else {
        $_SESSION['error'] = "Invalid record ID.";
    }

    header("Location: progress.php");
    exit;
}

// ---- Alerts ----
$success_message = $_SESSION['success'] ?? null;
$error_message   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ---- Sorting control ----
$sort = $_GET['sort'] ?? 'newest';
$ORDER_MAP = [
    'newest'   => "p.date_logged DESC",
    'oldest'   => "p.date_logged ASC",
    'name_az'  => "u.lname ASC, u.fname ASC, p.date_logged DESC",
    'name_za'  => "u.lname DESC, u.fname DESC, p.date_logged DESC",
    'category' => "p.category ASC, p.date_logged DESC",
];
$order_expr = $ORDER_MAP[$sort] ?? $ORDER_MAP['newest'];

// ---- Date range filter: today | week | month | all ----
$date_range = $_GET['date_range'] ?? 'all';
$startDT = null;
$endDT   = null;

$today = new DateTime('today');
switch ($date_range) {
    case 'today':
        $startDT = (clone $today)->setTime(0,0,0);
        $endDT   = (clone $today)->setTime(23,59,59);
        break;
    case 'week':
        // Monday–Sunday of this week
        $monday  = (clone $today)->modify('monday this week')->setTime(0,0,0);
        $sunday  = (clone $monday)->modify('sunday this week')->setTime(23,59,59);
        $startDT = $monday; $endDT = $sunday;
        break;
    case 'month':
        $first   = (clone $today)->modify('first day of this month')->setTime(0,0,0);
        $last    = (clone $today)->modify('last day of this month')->setTime(23,59,59);
        $startDT = $first;  $endDT = $last;
        break;
    default:
        $date_range = 'all';
}

// Build WHERE fragment for date range (used by both queries)
$date_where = '';
if ($startDT && $endDT) {
    $start_str = $conn->real_escape_string($startDT->format('Y-m-d H:i:s'));
    $end_str   = $conn->real_escape_string($endDT->format('Y-m-d H:i:s'));
    $date_where = " AND p.date_logged BETWEEN '$start_str' AND '$end_str' ";
}

// ---- Queries (Members only) ----
// With remarks: not null/empty after trim
$sql_with = "
    SELECT p.*, CONCAT(u.fname, ' ', u.lname) AS fullname
    FROM progress p
    INNER JOIN user u ON p.user_id = u.user_id
    WHERE u.role = 'Member'
      AND p.remarks IS NOT NULL
      AND TRIM(p.remarks) <> ''
      $date_where
    ORDER BY $order_expr
";
$res_with = mysqli_query($conn, $sql_with);

// No remarks
$sql_none = "
    SELECT p.*, CONCAT(u.fname, ' ', u.lname) AS fullname
    FROM progress p
    INNER JOIN user u ON p.user_id = u.user_id
    WHERE u.role = 'Member'
      AND (p.remarks IS NULL OR TRIM(p.remarks) = '')
      $date_where
    ORDER BY $order_expr
";
$res_none = mysqli_query($conn, $sql_none);

function safe_count($res) { return $res ? mysqli_num_rows($res) : 0; }
?>
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
         style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
        <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
         style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff | Progress Monitoring</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>
        .section-card { background:#fff; border:none; border-radius:1rem; padding:1rem; }
        .section-header { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
        .scroll-wrap { max-height: 58vh; overflow:auto; }
        thead th { position: sticky; top: 0; background: #f8f9fa; z-index: 1; }
        .controls-bar { display:flex; align-items:center; gap:.5rem; }
        .controls-bar .form-select { width: 200px; }
        .controls-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .badge-filter { font-weight:600; }
        .subtle { color:#6c757d; }
    </style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">
<nav class="navbar navbar-expand-lg bg-transparent">
    <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
        <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
            <ul class="navbar-nav ms-2">
                <?php if (($_SESSION['role'] ?? '') === "Admin") { ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
                    <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Progress</a></li>
                    <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
                <?php } elseif (($_SESSION['role'] ?? '') === "Staff") { ?>
                    <li class="nav-item"><a class="nav-link" href="staff_class.php">Class</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff_attendance.php">Attendance</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff_schedule.php">Schedule</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Progress</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff_payment_verification.php">Tsansaction</a></li>
                <?php } ?>
                <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
    <div class="w-100">
        <a href="" onclick="window.history.back();return false;"
           class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;">
            <i class="ri-arrow-go-back-line"></i>
        </a>
    </div>

    <!-- Page title + (moved) filters BELOW the header -->
    <div class="w-100 d-flex flex-column gap-2">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="lh-1 m-0 display-6 fw-bold text-white">Member Progress</h2>
        </div>

        <!-- Controls bar (moved down here) -->
        <form class="controls-row" method="get" action="">
            <!-- Left: badges for current filters -->
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-dark badge-filter">
                    Range: <?= htmlspecialchars(strtoupper($date_range)) ?>
                    <?php if ($startDT && $endDT): ?>
                        (<?= $startDT->format('M d') ?>–<?= $endDT->format('M d') ?>)
                    <?php endif; ?>
                </span>
                <span class="badge bg-light text-dark badge-filter">Sort: <?= htmlspecialchars($sort) ?></span>
            </div>

            <!-- Right: the actual dropdowns -->
            <div class="controls-bar">
                <label class="form-label m-0 subtle" for="date_range">Range:</label>
                <select id="date_range" name="date_range" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all"   <?= $date_range==='all'?'selected':''; ?>>All</option>
                    <option value="today" <?= $date_range==='today'?'selected':''; ?>>Today</option>
                    <option value="week"  <?= $date_range==='week'?'selected':''; ?>>This week</option>
                    <option value="month" <?= $date_range==='month'?'selected':''; ?>>This month</option>
                </select>

                <label class="form-label m-0 subtle" for="sort">Sort:</label>
                <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="newest"   <?= $sort==='newest'?'selected':''; ?>>Newest first</option>
                    <option value="oldest"   <?= $sort==='oldest'?'selected':''; ?>>Oldest first</option>
                    <option value="name_az"  <?= $sort==='name_az'?'selected':''; ?>>Name A–Z</option>
                    <option value="name_za"  <?= $sort==='name_za'?'selected':''; ?>>Name Z–A</option>
                    <option value="category" <?= $sort==='category'?'selected':''; ?>>Category</option>
                </select>
            </div>
        </form>
    </div>

    <div class="w-100 d-flex flex-column gap-3">

        <!-- ✅ WITH REMARKS -->
        <div class="section-card">
            <div class="section-header">
                <h4 class="m-0">Members with Remarks</h4>
                <span class="badge bg-primary"><?= safe_count($res_with) ?> records</span>
            </div>
            <div class="scroll-wrap mt-2">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member Name</th>
                            <th>Category</th>
                            <th>Exercise</th>
                            <th>Sets</th>
                            <th>Reps</th>
                            <th>Remarks</th>
                            <th>Date Logged</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($res_with && mysqli_num_rows($res_with) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res_with)): ?>
                            <?php
                              $id = (int)$row['id'];
                              $modalId = 'remarksModal_with_' . $id;
                            ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['exercise']) ?></td>
                                <td><?= htmlspecialchars($row['sets']) ?></td>
                                <td><?= htmlspecialchars($row['reps']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['remarks'])) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($row['date_logged'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#<?= $modalId ?>">Edit</button>
                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="progress.php">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Remarks for <?= htmlspecialchars($row['fullname']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $id ?>">

                                                <div class="mb-2 d-flex flex-wrap gap-2">
                                                    <?php foreach ($REMARK_PRESETS as $opt): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            onclick="addRemark('remarks-<?= $modalId ?>', '<?= htmlspecialchars($opt, ENT_QUOTES) ?>')">
                                                            <?= htmlspecialchars($opt) ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="input-group mb-2">
                                                    <select id="remarkSelect-<?= $modalId ?>" class="form-select">
                                                        <option value="" selected disabled>Select a standard remark…</option>
                                                        <?php foreach ($REMARK_PRESETS as $opt): ?>
                                                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                            onclick="
                                                            (function(){
                                                              const s=document.getElementById('remarkSelect-<?= $modalId ?>');
                                                              if(s && s.value){ addRemark('remarks-<?= $modalId ?>', s.value); }
                                                            })();">
                                                        Add
                                                    </button>
                                                </div>

                                                <textarea id="remarks-<?= $modalId ?>" name="remarks" class="form-control" rows="4"
                                                          placeholder="Enter remarks…"><?= htmlspecialchars($row['remarks']) ?></textarea>
                                                <div class="form-text">Tip: Presets append; separated by “ | ”.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_remarks" class="btn btn-success">Save</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted">No records with remarks for this range.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ❗ WITHOUT REMARKS -->
        <div class="section-card">
            <div class="section-header">
                <h4 class="m-0">Members without Remarks</h4>
                <span class="badge bg-secondary"><?= safe_count($res_none) ?> records</span>
            </div>
            <div class="scroll-wrap mt-2">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member Name</th>
                            <th>Category</th>
                            <th>Exercise</th>
                            <th>Sets</th>
                            <th>Reps</th>
                            <th>Remarks</th>
                            <th>Date Logged</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($res_none && mysqli_num_rows($res_none) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res_none)): ?>
                            <?php
                              $id = (int)$row['id'];
                              $modalId = 'remarksModal_none_' . $id;
                            ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['exercise']) ?></td>
                                <td><?= htmlspecialchars($row['sets']) ?></td>
                                <td><?= htmlspecialchars($row['reps']) ?></td>
                                <td><span class="text-muted">—</span></td>
                                <td><?= date('M d, Y h:i A', strtotime($row['date_logged'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#<?= $modalId ?>">Add</button>
                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="progress.php">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Add Remarks for <?= htmlspecialchars($row['fullname']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $id ?>">

                                                <div class="mb-2 d-flex flex-wrap gap-2">
                                                    <?php foreach ($REMARK_PRESETS as $opt): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            onclick="addRemark('remarks-<?= $modalId ?>', '<?= htmlspecialchars($opt, ENT_QUOTES) ?>')">
                                                            <?= htmlspecialchars($opt) ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="input-group mb-2">
                                                    <select id="remarkSelect-<?= $modalId ?>" class="form-select">
                                                        <option value="" selected disabled>Select a standard remark…</option>
                                                        <?php foreach ($REMARK_PRESETS as $opt): ?>
                                                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                            onclick="
                                                            (function(){
                                                              const s=document.getElementById('remarkSelect-<?= $modalId ?>');
                                                              if(s && s.value){ addRemark('remarks-<?= $modalId ?>', s.value); }
                                                            })();">
                                                        Add
                                                    </button>
                                                </div>

                                                <textarea id="remarks-<?= $modalId ?>" name="remarks" class="form-control" rows="4"
                                                          placeholder="Enter remarks…"></textarea>
                                                <div class="form-text">Tip: Presets append; separated by “ | ”.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_remarks" class="btn btn-success">Save</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center text-muted">No empty-remark entries for this range.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Append a standardized remark into the textarea identified by elementId.
// Prevent duplicates (case-insensitive). Join using ' | '.
function addRemark(elementId, text) {
    const ta = document.getElementById(elementId);
    if (!ta) return;

    const sep = ' | ';
    const current = ta.value.trim();
    const parts = current ? current.split(sep).map(s => s.trim()).filter(Boolean) : [];

    const exists = parts.some(p => p.toLowerCase() === String(text).toLowerCase());
    if (!exists) parts.push(text);

    ta.value = parts.join(sep);
    ta.dispatchEvent(new Event('input'));
}
</script>
<script src="script.js"></script>
</body>
</html>
