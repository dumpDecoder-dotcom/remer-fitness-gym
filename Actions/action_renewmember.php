<?php
session_start();
include 'dbconnect.php';

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); 

    // Check if the user exists and is an inactive member
    $check = "
        SELECT * 
        FROM user 
        WHERE user_id = $user_id 
          AND role = 'Member' 
          AND status = 'Inactive'
    ";
    $checkResult = mysqli_query($conn, $check);

    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        // Set timezone and calculate new expiry date
        date_default_timezone_set('Asia/Manila');
        $new_expiry = date('Y-m-d', strtotime('+30 days')); // 30 days extension
        $renewal_date = date('Y-m-d');

        // Update member status and expiry
        $renew = "
            UPDATE user 
            SET 
                status = 'Active', 
                member_expiry = '$new_expiry',
                renewal_date = '$renewal_date'
            WHERE user_id = $user_id
        ";

        if (mysqli_query($conn, $renew)) {
            $_SESSION['success'] = "✅ Member account has been successfully reactivated until $new_expiry.";
        } else {
            $_SESSION['error'] = "❌ Failed to reactivate member. Please try again.";
        }
    } else {
        $_SESSION['error'] = "⚠️ Member not found or already active.";
    }
} else {
    $_SESSION['error'] = "❌ Invalid request.";
}

// Redirect back to member list
header("Location: ../member-list.php");
exit;
?>
