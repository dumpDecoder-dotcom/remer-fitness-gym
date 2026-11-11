<?php
session_start();
include 'dbconnect.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); 

    $check = "SELECT * FROM user WHERE user_id = $user_id AND role = 'Staff'";
    $checkResult = mysqli_query($conn, $check);

    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        // ✅ Active kapag inactivate ulit
        $activate = "UPDATE user SET status = 'Active' WHERE user_id = $user_id";

        if (mysqli_query($conn, $activate)) {
            $_SESSION['success'] = "Staff Account has been activated.";
        } else {
            $_SESSION['error'] = "Failed to activate staff. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Staff not found or already active.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../staff-list.php");
exit;
?>
