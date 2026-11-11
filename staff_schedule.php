<?php 
session_start();
include 'Actions/dbconnect.php';


// 🔍 GET FILTER VALUES FIRST (before any output)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$filter_date_range = isset($_GET['date_range']) ? mysqli_real_escape_string($conn, $_GET['date_range']) : '';
$filter_month = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : '';

// 🔔 Get pending assignment count for notification badge
$pending_count = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Staff') {
    $user_id = intval($_SESSION['user_id']);
    $notif_query = "SELECT COUNT(*) as pending_count 
                    FROM class_assignment_requests 
                    WHERE instructor_id = $user_id AND status = 'Pending'";
    $notif_result = mysqli_query($conn, $notif_query);
    if ($notif_result) {
        $notif_data = mysqli_fetch_assoc($notif_result);
        $pending_count = intval($notif_data['pending_count']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Schedule | Remer Fitness Gym</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

    <!-- ✅ Alert messages (Success / Error) - MOVED INSIDE BODY -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" 
            style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        unset($_SESSION['success']); 
    endif;

    if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" 
            style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        unset($_SESSION['error']); 
    endif; 
    ?>

    <!-- 🧭 Navbar with Notification Badge -->
    <nav class="navbar navbar-expand-lg bg-transparent">
        <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
            <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
                <ul class="navbar-nav ms-2">
                    <li class="nav-item"><a class="nav-link" href="staff_class.php">Class</a></li>
                    <li class="nav-item"><a class="nav-link" href="staff_attendance.php">Attendance</a></li>
                    <li class="nav-item position-relative">
                        <a class="nav-link active" href="staff_schedule.php">
                            Schedule
                            <?php if ($pending_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $pending_count ?>
                                    <span class="visually-hidden">pending assignments</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
                     <li class="nav-item"><a class="nav-link" href="staff_payment_verification.php">Tsansaction</a></li>
                    <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 🧱 Main Content -->
    <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
        <div class="w-100">
            <button onclick="window.history.back()" 
               class="btn btn-light rounded-circle fw-bold shadow text-nowrap" 
               style="width: 40px; height: 40px;">
               <i class="ri-arrow-go-back-line"></i>
            </button>
        </div>

        <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
            <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100">
                <div class="w-100 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h2 class="lh-1 m-0 display-6 fw-bold">Classes Approval</h2>
                    <?php if ($pending_count > 0): ?>
                        <div class="alert alert-warning mb-0 py-2 px-3">
                            <i class="ri-notification-3-fill"></i>
                            You have <strong><?= $pending_count ?></strong> pending assignment(s) to review
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 🔍 FILTERS SECTION -->
                <div class="bg-light rounded-3 p-3 border">
                    <form method="GET" action="staff_schedule.php" class="row g-3">
                        <!-- Search Box -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">
                                <i class="ri-search-line"></i> Search Class
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Search by class name..." 
                                   value="<?= htmlspecialchars($search); ?>">
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">
                                <i class="ri-bookmark-line"></i> Status
                            </label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?= $filter_status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            </select>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">
                                <i class="ri-calendar-line"></i> Date Range
                            </label>
                            <select name="date_range" class="form-select form-select-sm">
                                <option value="">All Dates</option>
                                <option value="today" <?= $filter_date_range == 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?= $filter_date_range == 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="month" <?= $filter_date_range == 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="upcoming" <?= $filter_date_range == 'upcoming' ? 'selected' : ''; ?>>All Upcoming</option>
                            </select>
                        </div>

                        <!-- Month Filter -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">
                                <i class="ri-calendar-2-line"></i> Specific Month
                            </label>
                            <input type="month" name="month" class="form-control form-control-sm" 
                                   value="<?= htmlspecialchars($filter_month); ?>">
                        </div>

                        <!-- Buttons -->
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <i class="ri-filter-line"></i> Apply Filters
                            </button>
                            <a href="staff_schedule.php" class="btn btn-outline-secondary btn-sm px-4">
                                <i class="ri-refresh-line"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- TABLE SECTION -->
                <div class="w-100 flex-grow-1" style="overflow-x: auto;">
                    <?php
                        // 🟢 Function to format date: October 24, 2025
                        function formatDate($date) {
                            if (empty($date) || $date == '0000-00-00') return 'N/A';
                            return date('F j, Y', strtotime($date));
                        }

                        // 🟢 Function to format time: 3:00 PM
                        function formatTime($time) {
    if (empty($time) || $time == '00:00:00') return 'N/A';
    
    // Force conversion to 12-hour format with leading zeros and AM/PM
    $formatted = date('h:i A', strtotime($time));
    return strtoupper($formatted);
}


                        // 🟢 Check if notification is new (within 24 hours)
                        function isNew($created_at) {
                            if (empty($created_at)) return false;
                            $created = strtotime($created_at);
                            $now = time();
                            $diff = $now - $created;
                            return $diff < 86400; // 24 hours in seconds
                        }

                        // Ensure user_id is set
                        if (!isset($_SESSION['user_id'])) {
                            echo '<div class="alert alert-danger">Session expired. Please login again.</div>';
                            exit;
                        }

                        $user_id = intval($_SESSION['user_id']);

                        // 🔍 BUILD QUERY WITH FILTERS
                        $query = "
                            SELECT 
                                r.request_id, 
                                c.class_name, 
                                c.start_date, 
                                c.end_date, 
                                c.start_time, 
                                c.end_time, 
                                r.status AS request_status,
                                r.created_at
                            FROM class_assignment_requests r
                            JOIN class c ON r.class_id = c.id
                            WHERE r.instructor_id = $user_id
                            AND r.status IN ('Pending', 'Approved')
                            AND r.status NOT IN ('Reassigned', 'Declined', 'Cancelled')
                        ";

                        // Search filter
                        if (!empty($search)) {
                            $query .= " AND c.class_name LIKE '%$search%'";
                        }

                        // Status filter
                        if (!empty($filter_status)) {
                            $query .= " AND r.status = '$filter_status'";
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

                        $query .= "
                                ORDER BY 
                                    CASE 
                                        WHEN r.status = 'Pending' THEN 0
                                        WHEN r.status = 'Approved' THEN
                                            CASE
                                                WHEN CONCAT(c.start_date, ' ', c.start_time) <= NOW() 
                                                    AND CONCAT(c.end_date, ' ', c.end_time) >= NOW() THEN 1  -- Ongoing first
                                                WHEN CONCAT(c.start_date, ' ', c.start_time) > NOW() THEN 2   -- Waiting/Upcoming next
                                                WHEN CONCAT(c.end_date, ' ', c.end_time) < NOW() THEN 3       -- Completed last
                                                ELSE 4
                                            END
                                        ELSE 5
                                    END,
                                    c.start_date ASC
                            ";


                        $result = mysqli_query($conn, $query);

                        // Check for query errors
                        if (!$result) {
                            echo '<div class="alert alert-danger">Database error: ' . mysqli_error($conn) . '</div>';
                            exit;
                        }
                    ?>

                    <table class="table table-striped table-hover align-middle" style="max-width: 100%;">
                        <thead class="table-dark">
                            <tr>
                                <th>Class</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status / Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr <?= ($row['request_status'] === 'Pending' && isNew($row['created_at'])) ? 'class="table-warning"' : '' ?>>
                                    <td>
                                        <?= htmlspecialchars($row['class_name']) ?>
                                        <?php if ($row['request_status'] === 'Pending' && isNew($row['created_at'])): ?>
                                            <span class="badge bg-danger ms-2">NEW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($row['start_date']) ?></td>
                                    <td><?= formatDate($row['end_date']) ?></td>
                                    <td><?= formatTime($row['start_time']) ?></td>
                                    <td><?= formatTime($row['end_time']) ?></td>
                                    <td>
                                        <?php if ($row['request_status'] === 'Pending'): ?>
                                            <form action="Actions/action_respond_request.php" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
    <input type="hidden" name="request_id" value="<?= intval($row['request_id']) ?>">

    <!-- 🗓️ Added Date Input for Scheduling -->
    <input type="date" name="class_date" class="form-control form-control-sm" 
           value="<?= htmlspecialchars($row['start_date']) ?>" required>

    <button name="action" value="approve" class="btn btn-success btn-sm">
        <i class="ri-check-line"></i> Approve
    </button>

    <button name="action" value="decline" class="btn btn-danger btn-sm">
        <i class="ri-close-line"></i> Decline
    </button>
</form>

                                        <?php elseif ($row['request_status'] === 'Approved'): ?>
                                        <?php
                                            $today = date('Y-m-d');
                                            $start_date = $row['start_date'];
                                            $end_date = $row['end_date'];

                                           date_default_timezone_set('Asia/Manila'); // make sure timezone is correct
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

$start_date = $row['start_date'];
$end_date = $row['end_date'];
$start_time = $row['start_time'];
$end_time = $row['end_time'];

// Combine date + time for precise comparison
$start_datetime = strtotime("$start_date $start_time");
$end_datetime = strtotime("$end_date $end_time");
$now = strtotime("$current_date $current_time");

if ($now < $start_datetime) {
 
    $class_status = ['Waiting', 'warning', 'ri-timer-line'];
} elseif ($now >= $start_datetime && $now <= $end_datetime) {

    $class_status = ['Ongoing', 'transparent text-dark border border-dark', 'ri-time-line'];
} else {
   
    $class_status = ['Completed', 'success', 'ri-checkbox-circle-line'];
}


                                        ?>
                                        <span class="badge bg-<?= $class_status[1] ?> px-3 py-2">
                                            <i class="<?= $class_status[2] ?>"></i> <?= $class_status[0] ?>
                                        </span>
                                    <?php endif; ?>

                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="ri-inbox-line fs-1"></i>
                                        <p class="mt-2 mb-0">
                                            <?php if (!empty($search) || !empty($filter_status) || !empty($filter_date_range) || !empty($filter_month)): ?>
                                                No classes found with current filters. Try adjusting your filters.
                                            <?php else: ?>
                                                No assigned classes found.
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>  
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLlvGRem5+R9g2FzA8ZGN954O5Q" 
            crossorigin="anonymous"></script>
    <script src="script.js"></script>
</body>
</html>