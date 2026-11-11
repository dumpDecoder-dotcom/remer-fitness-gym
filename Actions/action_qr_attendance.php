<?php
session_start();
include 'dbconnect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['error'] = "Unauthorized access. Please log in as staff or admin.";
    header("Location: ../login.php");
    exit;
}

$role = $_SESSION['role']; 
if ($role !== "Admin" && $role !== "Staff") {
    $_SESSION['error'] = "Only authorized staff or administrators can scan member QR codes.";
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['user_id'])) {
    $_SESSION['error'] = "Invalid QR code.";
    header("Location: " . ($role === "Admin" ? "../dashboard.php" : "../staff_class.php"));
    exit;
}

$member_id = intval($_GET['user_id']);
date_default_timezone_set("Asia/Manila");

$member_query = "SELECT CONCAT(fname, ' ', lname) AS fullname, fname, lname, status, role FROM user WHERE user_id = $member_id";
$member_result = mysqli_query($conn, $member_query);

if (!$member_result || mysqli_num_rows($member_result) === 0) {
    $_SESSION['error'] = "Member not found.";
    header("Location: " . ($role === "Admin" ? "../dashboard.php" : "../staff_class.php"));
    exit;
}

$member = mysqli_fetch_assoc($member_result);
if ($member['role'] !== "Member") {
    $_SESSION['error'] = "QR code does not belong to a valid member account.";
    header("Location: " . ($role === "Admin" ? "../dashboard.php" : "../staff_class.php"));
    exit;
}

$member_status = $member['status']; 
if (strtolower($member_status) !== "active") {
    $_SESSION['error'] = "{$member['fullname']} is currently marked as '{$member_status}'.";
} 
else {
    $_SESSION['success'] = "{$member['fullname']} is an Active Member!";
}

$check_query = "SELECT id FROM attendance 
                WHERE user_id = $member_id 
                AND DATE(in_time) = CURDATE() 
                AND out_time IS NULL 
                ORDER BY id DESC LIMIT 1";
$check_result = mysqli_query($conn, $check_query);

$is_checkout = false;

if ($check_result && mysqli_num_rows($check_result) > 0) {
    // CHECK-OUT
    $update_query = "UPDATE attendance 
                    SET out_time = NOW() 
                    WHERE user_id = $member_id 
                    AND DATE(in_time) = CURDATE() 
                    AND out_time IS NULL";
    if (mysqli_query($conn, $update_query)) {
        $is_checkout = true;
        
        // 🔔 NOTIFY ADMINS ABOUT CHECK-OUT
        notifyAttendance($conn, $member['fname'], $member['lname'], 'checked out');
    } 
    else {
        $_SESSION['error'] = "Error during check-out.";
    }
} else {
    // CHECK-IN
    $insert_query = "INSERT INTO attendance (user_id, in_time) VALUES ($member_id, NOW())";
    if (mysqli_query($conn, $insert_query)) {
        // 🔔 NOTIFY ADMINS ABOUT CHECK-IN
        notifyAttendance($conn, $member['fname'], $member['lname'], 'checked in');
    } else {
        $_SESSION['error'] = "Error during check-in.";
    }
}

header("Location: " . ($role === "Admin" ? "../dashboard.php" : "../staff_class.php"));
exit;

/**
 * 🔔 Notify Admins about Member Attendance
 */
function notifyAttendance($conn, $fname, $lname, $action) {
    $full_name = $fname . ' ' . $lname;
    $time = date('h:i A');
    
    $title = "Member Attendance";
    $message = "$full_name $action at $time";
    
    $query = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, created_at)
              SELECT 
                  user_id,
                  'class_schedule',
                  ?,
                  ?,
                  'admin',
                  0,
                  NOW()
              FROM user 
              WHERE role = 'Admin' AND status = 'Active'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $title, $message);
    $stmt->execute();
}
?>