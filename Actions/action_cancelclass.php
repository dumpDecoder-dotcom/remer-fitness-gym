<?php
session_start();
include 'dbconnect.php';

if (isset($_POST['class_id']) && isset($_SESSION['user_id'])) {
    $class_id = intval($_POST['class_id']);
    $user_id = intval($_SESSION['user_id']);

    // Check if user is registered
    $check_sql = "SELECT * FROM member_class_registration WHERE class_id = $class_id AND member_id = $user_id";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        $_SESSION['error'] = "You are not registered for this class.";
        header("Location: ../member_bookclass.php");
        exit;
    }

    // Delete registration
    $delete_sql = "DELETE FROM member_class_registration WHERE class_id = $class_id AND member_id = $user_id";
    if (mysqli_query($conn, $delete_sql)) {
        $_SESSION['success'] = "You have successfully cancelled the class.";

        // Optional: send notification to admin/instructor
        // $registration_id = ...; you can use if needed
        // Send notifications here as in action_bookclass.php

    } else {
        $_SESSION['error'] = "Error cancelling class: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../member_bookclass.php");
exit;
?>
