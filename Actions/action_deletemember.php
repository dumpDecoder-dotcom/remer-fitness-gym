<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_GET['id'])) {
        $user_id = intval($_GET['id']); 

        // Check if the member exists and is currently Active
        $check = "SELECT * FROM user WHERE user_id = $user_id AND role = 'Member' AND status = 'Active'";
        $checkResult = mysqli_query($conn, $check);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            // Soft delete (set to Inactive)
            $softDelete = "UPDATE user SET status = 'Inactive' WHERE user_id = $user_id";

            if (mysqli_query($conn, $softDelete)) {
                $_SESSION['error'] = "Member account is set to Inactive.";
            } else {
                $_SESSION['error'] = "Failed to update member status. Please try again.";
            }
        } else {
            $_SESSION['error'] = "Member not found or already inactive.";
        }
    } else {
        $_SESSION['error'] = "Invalid request.";
    }

    header("Location: ../member-list.php");
    exit;
?>
