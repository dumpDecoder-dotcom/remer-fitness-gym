<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_GET['user_id'])) {
        date_default_timezone_set("Asia/Manila");
        $user_id = intval($_GET['user_id']);
        $now = date('Y-m-d H:i:s');

        $sql = "UPDATE attendance 
                SET out_time = '$now' 
                WHERE user_id = $user_id 
                AND date = CURDATE() 
                AND out_time IS NULL 
                ORDER BY id DESC LIMIT 1";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Check-out recorded.";
        } else {
            $_SESSION['error'] = "Error during check-out.";
        }
    }
    header("Location: ../staff_attendance.php");
    exit;
?>