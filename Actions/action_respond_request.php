<?php
session_start();
require_once 'dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Session expired. Please log in again.";
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $class_date = $_POST['class_date'] ?? null; // 🗓️ Added optional date input

    // 🔍 Get related class_id
    $sql = "SELECT class_id FROM class_assignment_requests 
            WHERE request_id = $request_id AND instructor_id = $user_id";
    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        $_SESSION['error'] = "Invalid class request or no permission.";
        header("Location: ../staff_schedule.php");
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    $class_id = (int)$row['class_id'];

    if ($action === 'approve') {
    // ✅ Approve request for current instructor
    $update_request = "UPDATE class_assignment_requests 
                       SET status = 'Approved' 
                       WHERE request_id = $request_id";
    $ok1 = mysqli_query($conn, $update_request);

    // ✅ Update the class record (so it shows under Scheduled Classes)
    $update_class = "
        UPDATE class 
        SET instructor_id = $user_id, 
            status = 'Upcoming',
            class_date = " . ($class_date ? "'" . mysqli_real_escape_string($conn, $class_date) . "'" : "class_date") . ",
            start_date = " . ($class_date ? "'" . mysqli_real_escape_string($conn, $class_date) . "'" : "start_date") . ",
            end_date = " . ($class_date ? "DATE_ADD('" . mysqli_real_escape_string($conn, $class_date) . "', INTERVAL 1 DAY)" : "end_date") . "
        WHERE id = $class_id
    ";
    $ok2 = mysqli_query($conn, $update_class);

    // 🚫 Mark all *other* instructors’ requests for the same class as Reassigned
    $update_others = "
        UPDATE class_assignment_requests
        SET status = 'Reassigned'
        WHERE class_id = $class_id 
        AND request_id != $request_id
    ";
    mysqli_query($conn, $update_others);

    if ($ok1 && $ok2) {
        $_SESSION['success'] = "Class approved successfully for " . ($class_date ?: 'unspecified date') . ".";
    } else {
        $_SESSION['error'] = "Error approving class: " . mysqli_error($conn);
    }

} elseif ($action === 'decline') {
    // ❌ Mark as declined
    $update_request = "UPDATE class_assignment_requests SET status = 'Declined' WHERE request_id = $request_id";
    if (mysqli_query($conn, $update_request)) {
        $_SESSION['success'] = "Class request declined.";
    } else {
        $_SESSION['error'] = "Database error while declining request.";
    }
}

    
}

// 🔁 Stay on the same approval page
header("Location: ../staff_schedule.php");
exit;
?>
