<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_POST['submit'])) {
        if (empty($_POST['instructor_id'])) {
            $_SESSION['error'] = "Please select an instructor first";
            header("Location: ../class-list.php");
            exit;
        }
        $class_id = intval($_POST['class_id']);
        $instructor_id = intval($_POST['instructor_id']);

        $check = "SELECT * FROM class_assignment_requests WHERE class_id = $class_id AND status = 'Pending'";
        $checkResult = mysqli_query($conn, $check);

        if (mysqli_num_rows($checkResult) == 0) {
            $insert = "INSERT INTO class_assignment_requests (class_id, instructor_id) VALUES ($class_id, $instructor_id)";
            if (mysqli_query($conn, $insert)) {
                $_SESSION['success'] = "Request sent to instructor.";
            } else {
                $_SESSION['error'] = "Failed to assign instructor.";
            }
        } else {
            $_SESSION['error'] = "Class already has a pending instructor request.";
        }
    }

    header("Location: ../class-list.php");
    exit;
?>