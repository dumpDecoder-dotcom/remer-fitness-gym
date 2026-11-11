<?php
session_start();
include 'dbconnect.php';

if (isset($_GET['id']) && isset($_GET['class_id'])) {
    $user_id = intval($_GET['id']); 
    $class_id = intval($_GET['class_id']);
    
    // Check if already registered
$check_sql = "SELECT 1 FROM member_class_registration WHERE class_id = $class_id AND member_id = $user_id LIMIT 1";
$check_result = mysqli_query($conn, $check_sql);

if (!$check_result) {
    $_SESSION['error'] = "Database error: " . mysqli_error($conn);
    header("Location: ../member_bookclass.php");
    exit;
}

if (mysqli_num_rows($check_result) > 0) {
    $_SESSION['error'] = "You are already registered to this class!";
    header("Location: ../member_bookclass.php");
    exit;
}

    
    // Get member info
    $member_sql = "SELECT fname, lname, email FROM user WHERE user_id = $user_id";
    $member_result = mysqli_query($conn, $member_sql);
    if (!$member_result || mysqli_num_rows($member_result) == 0) {
        $_SESSION['error'] = "Member not found.";
        header("Location: ../member_bookclass.php");
        exit;
    }
    $member = mysqli_fetch_assoc($member_result);
    $member_name = $member['fname'] . ' ' . $member['lname'];
    
    // Get class info
    $class_sql = "SELECT c.*, u.fname as coach_fname, u.lname as coach_lname, u.user_id as coach_id
              FROM class c
              LEFT JOIN user u ON c.instructor_id = u.user_id
              WHERE c.id = $class_id";

    $class_result = mysqli_query($conn, $class_sql);
    if (!$class_result || mysqli_num_rows($class_result) == 0) {
        $_SESSION['error'] = "Class not found.";
        header("Location: ../member_bookclass.php");
        exit;
    }
    $class = mysqli_fetch_assoc($class_result);

    $class_name = $class['class_name'];
    $class_date = date('M d, Y', strtotime($class['start_date']));
    if (!empty($class['end_date']) && $class['end_date'] != $class['start_date']) {
        $class_date .= ' - ' . date('M d, Y', strtotime($class['end_date']));
    }
    $class_time = date('h:i A', strtotime($class['start_time']));
    $coach_name = (!empty($class['coach_fname']) ? $class['coach_fname'] . ' ' . $class['coach_lname'] : 'Unassigned');
    $coach_id = $class['coach_id'] ?? null;
    $class_type = $class['class_type'] ?? 'Fitness Class';
    
    // Check class capacity
    $count_sql = "SELECT COUNT(*) as total FROM member_class_registration WHERE class_id = $class_id";
    $count_result = mysqli_query($conn, $count_sql);
    $count = mysqli_fetch_assoc($count_result);
    $total_students = isset($count['total']) ? (int)$count['total'] : 0;

    $capacity = $class['capacity'] ?? 20;
    if ($total_students >= $capacity) {
        $_SESSION['error'] = "This class is already full!";
        header("Location: ../member_bookclass.php");
        exit;
    }
    
    // Register to class
    $sql = "INSERT INTO member_class_registration (class_id, member_id, registration_date, status) 
            VALUES ($class_id, $user_id, NOW(), 'confirmed')";
    
    if (mysqli_query($conn, $sql)) {
        $registration_id = mysqli_insert_id($conn);
        
        // 🔔 CREATE NOTIFICATIONS

        // 1. Notification for Member
        $member_title = "✅ Class Booking Confirmed!";
        $member_message = "You've successfully joined \"$class_name\" ($class_type) with Coach $coach_name on $class_date at $class_time. See you there! 💪";
        $member_message_sql = mysqli_real_escape_string($conn, $member_message);
        $notif_member = "INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, is_read, is_sent, created_at)
                         VALUES ($user_id, 'class_booking', '$member_title', '$member_message_sql', NULL, $registration_id, 0, 0, NOW())";
        mysqli_query($conn, $notif_member);
        
        // 2. Notification for Coach/Instructor
        if (!empty($coach_id)) {
            $coach_title = "📅 New Student Joined Your Class";
            $coach_message = "$member_name just joined your \"$class_name\" class scheduled for $class_date at $class_time. Total students: " . ($total_students + 1) . "/$capacity";
            $coach_message_sql = mysqli_real_escape_string($conn, $coach_message);
            $notif_coach = "INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, is_read, is_sent, created_at)
                            VALUES ($coach_id, 'class_booking', '$coach_title', '$coach_message_sql', NULL, $registration_id, 0, 0, NOW())";
            mysqli_query($conn, $notif_coach);
        }
        
        // 3. Notification for All Staff
        $staff_title = "📊 Class Registration Alert";
        $staff_message = "$member_name joined \"$class_name\" (Coach: $coach_name) on $class_date";
        $staff_message_sql = mysqli_real_escape_string($conn, $staff_message);
        $notif_staff = "INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, is_read, is_sent, created_at)
                       SELECT user_id, 'class_booking', '$staff_title', '$staff_message_sql', 'Staff', $registration_id, 0, 0, NOW()
                       FROM user WHERE role = 'Staff' AND status = 'Active'";
        mysqli_query($conn, $notif_staff);
        
        // 4. Notification for Admin
        $admin_title = "📋 New Class Booking";
        $admin_message = "$member_name booked \"$class_name\" with Coach $coach_name. Class on $class_date at $class_time.";
        $admin_message_sql = mysqli_real_escape_string($conn, $admin_message);
        $notif_admin = "INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, is_read, is_sent, created_at)
                       SELECT user_id, 'class_booking', '$admin_title', '$admin_message_sql', 'Admin', $registration_id, 0, 0, NOW()
                       FROM user WHERE role = 'Admin' AND status = 'Active'";
        mysqli_query($conn, $notif_admin);
        
        $_SESSION['success'] = "Successfully registered to the class!";
    } else {
        $_SESSION['error'] = "Error joining class: " . mysqli_error($conn);
    }

} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../member_bookclass.php");
exit;
?>
