<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        $sql = "INSERT INTO attendance (user_id) VALUES ($user_id)";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Check-in recorded.";
        } else {
            $_SESSION['error'] = "Error during check-in.";
        }
    }
    header("Location: ../staff_attendance.php");
    exit;
?>