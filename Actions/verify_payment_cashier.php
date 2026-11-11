<?php
session_start();
include 'dbconnect.php';

// Ensure only Staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'], $_POST['action'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $action = $_POST['action'];

    // Determine new status
    $new_status = ($action === 'approve') ? 'completed' : 'rejected';

    // Update transaction status
    $update_query = "
        UPDATE transactions 
        SET status = '$new_status'
        WHERE transaction_id = $transaction_id
    ";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success'] = ($action === 'approve')
            ? 'Payment approved successfully.'
            : 'Payment has been rejected.';
    } else {
        $_SESSION['error'] = 'Failed to update payment status. Please try again.';
    }
}

header("Location: ../staff_payment_verification.php");
exit;
?>
