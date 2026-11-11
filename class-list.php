<?php
session_start();
include 'Actions/dbconnect.php';

// 🗑️ Handle Class Deletion (must be before other POST actions)
if (isset($_POST['delete_class_id'])) {
    $delete_id = intval($_POST['delete_class_id']);

    // Delete related records first (only if table exists)
    mysqli_query($conn, "DELETE FROM class_assignment_requests WHERE class_id = $delete_id");
    // mysqli_query($conn, "DELETE FROM class_participants WHERE class_id = $delete_id"); // ❌ Remove if table doesn't exist

    // Delete main class record
    if (mysqli_query($conn, "DELETE FROM class WHERE id = $delete_id")) {
        $_SESSION['success'] = "Class deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete class.";
    }

    header("Location: class-list.php");
    exit;
}


// 🕒 Auto-update class status based on current date & time
date_default_timezone_set('Asia/Manila'); // ensure correct timezone
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

$update_query = "
    UPDATE class
    SET status = CASE
        -- Future date
        WHEN '$current_date' < start_date THEN 'Upcoming'

        -- Same date but time not yet started
        WHEN '$current_date' = start_date AND '$current_time' < start_time THEN 'Waiting'

        -- Same date and within time range
        WHEN '$current_date' = start_date AND '$current_time' BETWEEN start_time AND end_time THEN 'Ongoing'

        -- Multi-day ongoing (after start date but before end date)
        WHEN '$current_date' > start_date AND '$current_date' < end_date THEN 'Ongoing'

        -- Last day but within time
        WHEN '$current_date' = end_date AND '$current_time' <= end_time THEN 'Ongoing'

        -- After end date or end time
        WHEN '$current_date' > end_date OR ('$current_date' = end_date AND '$current_time' > end_time) THEN 'Completed'

        ELSE status
    END
";

mysqli_query($conn, $update_query);



// 🔴 AUTO-RESET DECLINED CLASSES TO PENDING
$check_declined = "
    SELECT DISTINCT class_id 
    FROM class_assignment_requests 
    WHERE status = 'Declined'
";
$declined_result = mysqli_query($conn, $check_declined);

if (mysqli_num_rows($declined_result) > 0) {
    $declined_ids = [];
    while ($row = mysqli_fetch_assoc($declined_result)) {
        $declined_ids[] = $row['class_id'];
    }
    
    if (!empty($declined_ids)) {
        $ids_string = implode(',', $declined_ids);
        
        $reset_classes = "
            UPDATE class 
            SET instructor_id = NULL, 
                status = 'Pending' 
            WHERE id IN ($ids_string)
        ";
        mysqli_query($conn, $reset_classes);
        
        $cleanup = "
            DELETE FROM class_assignment_requests 
            WHERE status = 'Declined'
        ";
        mysqli_query($conn, $cleanup);
    }
}

// 🟢 Handle Quick Instructor Assignment
if (isset($_POST['assign_instructor'])) {
    $class_id = intval($_POST['class_id']);
    $instructor_id = intval($_POST['instructor_id']);

    $class_query = "SELECT class_name FROM class WHERE id = $class_id";
    $class_result = mysqli_query($conn, $class_query);
    $class_data = mysqli_fetch_assoc($class_result);
    $class_name = $class_data['class_name'];

    $instructor_query = "SELECT fname, lname FROM user WHERE user_id = $instructor_id";
    $instructor_result = mysqli_query($conn, $instructor_query);
    $instructor_data = mysqli_fetch_assoc($instructor_result);
    $instructor_name = $instructor_data['fname'] . ' ' . $instructor_data['lname'];


// Fetch class schedule
$class_schedule_query = "SELECT start_date, end_date FROM class WHERE id = $class_id";
$class_schedule_result = mysqli_query($conn, $class_schedule_query);
$class_schedule = mysqli_fetch_assoc($class_schedule_result);

$today = date('Y-m-d');
$start_date = $class_schedule['start_date'];
$end_date = $class_schedule['end_date'];

// Determine status based on date
if ($today < $start_date) {
    $status = 'Upcoming';
} elseif ($today >= $start_date && $today <= $end_date) {
    $status = 'Ongoing';
} else {
    $status = 'Completed';
}

// Update class with correct status
$update_class = "
    UPDATE class 
    SET instructor_id = $instructor_id, status = '$status' 
    WHERE id = $class_id
";
    
    if (mysqli_query($conn, $update_class)) {
        $check = "SELECT * FROM class_assignment_requests 
                  WHERE class_id = $class_id AND instructor_id = $instructor_id";
        $check_result = mysqli_query($conn, $check);
        
        if (mysqli_num_rows($check_result) == 0) {
            $insert = "INSERT INTO class_assignment_requests (class_id, instructor_id, status, created_at) 
                      VALUES ($class_id, $instructor_id, 'Pending', NOW())";
            mysqli_query($conn, $insert);
        } else {
            $update = "UPDATE class_assignment_requests 
                      SET status = 'Pending', created_at = NOW() 
                      WHERE class_id = $class_id AND instructor_id = $instructor_id";
            mysqli_query($conn, $update);
        }
        
        $_SESSION['success'] = "Instructor '$instructor_name' assigned to '$class_name'. Waiting for approval.";
    } else {
        $_SESSION['error'] = "Failed to assign instructor.";
    }
    
    header("Location: class-list.php");
    exit;
}

// 🔍 GET FILTER VALUES
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$filter_instructor = isset($_GET['instructor']) ? $_GET['instructor'] : '';
$filter_month = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : '';
$filter_participants = isset($_GET['participants']) ? mysqli_real_escape_string($conn, $_GET['participants']) : '';
$filter_date_range = isset($_GET['date_range']) ? mysqli_real_escape_string($conn, $_GET['date_range']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class List | Admin</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

    <!-- ✅ NAVBAR -->
   <nav class="navbar navbar-expand-lg bg-transparent">
  <div class="container px-md-4 px-lg-5 py-1" style="border-bottom:1px solid var(--text-clr);">
    <a class="navbar-brand" href="#">
      <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="class-list.php">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
    </div>
  </div>
</nav>

    <!-- ✅ MAIN CONTENT -->
    <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-bold m-0 text-white">Class List</h2>
            <a href="add-class.php" class="btn btn-primary px-4 fw-semibold">+ Add Class</a>
        </div>

        <!-- ✅ Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 🔍 ADVANCED FILTERS -->
        <div class="bg-white rounded-4 shadow-sm p-4">
            <form method="GET" action="" class="row g-3">
                <!-- Search Box -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="ri-search-line"></i> Search Class
                    </label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by class name..." 
                           value="<?= htmlspecialchars($search); ?>">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="ri-bookmark-line"></i> Status
                    </label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Upcoming" <?= $filter_status == 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="Ongoing" <?= $filter_status == 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="Completed" <?= $filter_status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>

                <!-- Instructor Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="ri-user-line"></i> Instructor
                    </label>
                    <select name="instructor" class="form-select">
                        <option value="">All Instructors</option>
                        <option value="0" <?= $filter_instructor === '0' ? 'selected' : ''; ?>>No Instructor</option>
                        <?php
                            $inst_query = "SELECT user_id, fname, lname FROM user WHERE role = 'Staff' ORDER BY fname";
                            $inst_result = mysqli_query($conn, $inst_query);
                            while ($inst = mysqli_fetch_assoc($inst_result)):
                        ?>
                            <option value="<?= $inst['user_id']; ?>" 
                                <?= $filter_instructor == $inst['user_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($inst['fname'] . ' ' . $inst['lname']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="ri-calendar-line"></i> Date Range
                    </label>
                    <select name="date_range" class="form-select">
                        <option value="">All Dates</option>
                        <option value="today" <?= $filter_date_range == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?= $filter_date_range == 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?= $filter_date_range == 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="upcoming" <?= $filter_date_range == 'upcoming' ? 'selected' : ''; ?>>All Upcoming</option>
                    </select>
                </div>

                <!-- Month Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="ri-calendar-2-line"></i> Specific Month
                    </label>
                    <input type="month" name="month" class="form-control" 
                           value="<?= htmlspecialchars($filter_month); ?>">
                </div>

                <!-- Participants Filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="ri-group-line"></i> Class Size
                    </label>
                    <select name="participants" class="form-select">
                        <option value="">All Sizes</option>
                        <option value="small" <?= $filter_participants == 'small' ? 'selected' : ''; ?>>Small (1-10)</option>
                        <option value="medium" <?= $filter_participants == 'medium' ? 'selected' : ''; ?>>Medium (11-20)</option>
                        <option value="large" <?= $filter_participants == 'large' ? 'selected' : ''; ?>>Large (21+)</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ri-filter-line"></i> Apply Filters
                    </button>
                    <a href="class-list.php" class="btn btn-outline-secondary px-4">
                        <i class="ri-refresh-line"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- ✅ Class Table -->
        <div class="table-responsive bg-white rounded-4 shadow-sm p-3">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Class Name</th>
                        <th scope="col">Instructor</th>
                        <th scope="col">Date Range</th>
                        <th scope="col">Time</th>
                        <th scope="col">Participants</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 🔍 BUILD QUERY WITH FILTERS
                    $query = "
                        SELECT c.*, u.fname, u.lname
                        FROM class c
                        LEFT JOIN user u ON c.instructor_id = u.user_id
                        WHERE 1=1
                    ";

                    // Search filter
                    if (!empty($search)) {
                        $query .= " AND c.class_name LIKE '%$search%'";
                    }

                    // Status filter
                    if (!empty($filter_status)) {
                        if ($filter_status == 'Pending') {
                            $query .= " AND (c.status = 'Pending' OR c.instructor_id IS NULL)";
                        } else {
                            $query .= " AND c.status = '$filter_status'";
                        }
                    }

                    // Instructor filter
                    if (!empty($filter_instructor) || $filter_instructor === '0') {
                        if ($filter_instructor == '0') {
                            $query .= " AND c.instructor_id IS NULL";
                        } else {
                            $query .= " AND c.instructor_id = " . intval($filter_instructor);
                        }
                    }

                    // Date range filter
                    if (!empty($filter_date_range)) {
                        $today = date('Y-m-d');
                        switch ($filter_date_range) {
                            case 'today':
                                $query .= " AND '$today' BETWEEN c.start_date AND c.end_date";
                                break;
                            case 'week':
                                $week_end = date('Y-m-d', strtotime('+7 days'));
                                $query .= " AND c.start_date BETWEEN '$today' AND '$week_end'";
                                break;
                            case 'month':
                                $month_end = date('Y-m-d', strtotime('+30 days'));
                                $query .= " AND c.start_date BETWEEN '$today' AND '$month_end'";
                                break;
                            case 'upcoming':
                                $query .= " AND c.start_date >= '$today'";
                                break;
                        }
                    }

                    // Specific month filter
                    if (!empty($filter_month)) {
                        $query .= " AND DATE_FORMAT(c.start_date, '%Y-%m') = '$filter_month'";
                    }

                    // Participants filter
                    if (!empty($filter_participants)) {
                        switch ($filter_participants) {
                            case 'small':
                                $query .= " AND c.participant_limit BETWEEN 1 AND 10";
                                break;
                            case 'medium':
                                $query .= " AND c.participant_limit BETWEEN 11 AND 20";
                                break;
                            case 'large':
                                $query .= " AND c.participant_limit >= 21";
                                break;
                        }
                    }

                    $query .= " ORDER BY c.start_date DESC";
                    
                    $result = mysqli_query($conn, $query);
                    $count = 1;

                    if (mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $count++; ?></td>
                                <td><?= htmlspecialchars($row['class_name']); ?></td>
                                
                                <td>
                                    <?php 
                                        if (!empty($row['instructor_id']) && !empty($row['fname'])) {
                                            echo htmlspecialchars($row['fname'] . ' ' . $row['lname']);
                                        } else {
                                            echo '<span class="badge bg-secondary">No Instructor</span>';
                                        }
                                    ?>
                                </td>

                                <td>
                                    <?php 
                                        if (!empty($row['start_date']) && $row['start_date'] !== '0000-00-00' && 
                                            !empty($row['end_date']) && $row['end_date'] !== '0000-00-00') {
                                            echo date("F j, Y", strtotime($row['start_date'])) . 
                                                 ' - ' . date("F j, Y", strtotime($row['end_date']));
                                        } else {
                                            echo '<span class="text-muted">No date set</span>';
                                        }
                                    ?>
                                </td>

                                <td>
                                    <?= date("g:i A", strtotime($row['start_time'])) . ' - ' . date("g:i A", strtotime($row['end_time'])); ?>
                                </td>

                                <td><?= htmlspecialchars($row['participant_limit']); ?></td>

                                <td>
                                    <?php 
                                        if (empty($row['instructor_id'])) {
                                            echo '<span class="badge bg-secondary px-3 py-2">Pending</span>';
                                        } else {
                                            $status_class = match($row['status']) {
                                            'Completed' => 'bg-secondary text-white', // gray
                                            'Ongoing' => 'bg-success text-white', // green
                                            'Waiting' => 'bg-warning text-dark', // orange
                                            'Upcoming' => 'bg-transparent border border-secondary text-secondary', // transparent
                                            'Pending' => 'bg-info text-white',
                                            default => 'bg-light text-dark'
                                        };

                                            echo '<span class="badge ' . $status_class . ' px-3 py-2">' 
                                                 . htmlspecialchars($row['status']) . '</span>';
                                        }
                                    ?>
                                </td>

                                    <td class="d-flex gap-2">
                                    <?php if ($row['status'] === 'Completed'): ?>
                                        <button class="btn btn-sm btn-outline-secondary disabled">
                                            <i class="ri-user-settings-line"></i> Change
                                        </button>
                                    <?php elseif (empty($row['instructor_id'])): ?>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal<?= $row['id']; ?>">
                                            <i class="ri-user-add-line"></i> Assign
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#assignModal<?= $row['id']; ?>">
                                            <i class="ri-user-settings-line"></i> Change
                                        </button>
                                    <?php endif; ?>

                                    <!-- 🗑️ Delete button -->
                                    <?php if ($row['status'] === 'Completed'): ?>
                                        <button class="btn btn-sm btn-secondary disabled">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirmDelete(event, '<?= htmlspecialchars($row['class_name']); ?>')">
                                            <input type="hidden" name="delete_class_id" value="<?= $row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="assignModal<?= $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign Instructor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="class_id" value="<?= $row['id']; ?>">
                                                
                                                <label class="form-label fw-bold">Class: <?= htmlspecialchars($row['class_name']); ?></label>
                                                
                                                <label class="form-label mt-3">Select Instructor</label>
                                                <select name="instructor_id" class="form-select" required>
                                                    <option value="">-- Choose Instructor --</option>
                                                    <?php
                                                        $instructor_query = "SELECT user_id, fname, lname FROM user WHERE role = 'Staff' ORDER BY fname";
                                                        $instructor_result = mysqli_query($conn, $instructor_query);
                                                        while ($instructor = mysqli_fetch_assoc($instructor_result)):
                                                    ?>
                                                        <option value="<?= $instructor['user_id']; ?>" 
                                                            <?= ($row['instructor_id'] == $instructor['user_id']) ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($instructor['fname'] . ' ' . $instructor['lname']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="assign_instructor" class="btn btn-primary">
                                                    <i class="ri-check-line"></i> Assign
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="ri-inbox-line" style="font-size: 3rem;"></i>
                                <p class="mb-0 mt-2">No classes found with current filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(event, className) {
            event.preventDefault();
            if (confirm(`Are you sure you want to permanently delete "${className}"? This action cannot be undone.`)) {
                event.target.submit();
            }
        }
        </script>

</body>
</html>