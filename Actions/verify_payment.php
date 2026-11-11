<?php
session_start();
require_once __DIR__ . '/dbconnect.php';

// Admins only
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'Admin')) {
    header("Location: ../login.php");
    exit;
}

// Validate input
if (!isset($_POST['transaction_id'], $_POST['action'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../payment-verification.php");
    exit;
}
$transaction_id = (int)$_POST['transaction_id'];
$action = $_POST['action'];
if ($transaction_id <= 0 || !in_array($action, ['approve','reject'], true)) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../payment-verification.php");
    exit;
}

// Throw mysqli exceptions so we can catch/rollback
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn->begin_transaction();

    // Fetch and lock the transaction row to avoid double-processing
    $tx_sql = "SELECT * FROM transactions WHERE transaction_id = ? FOR UPDATE";
    $tx_stmt = $conn->prepare($tx_sql);
    $tx_stmt->bind_param('i', $transaction_id);
    $tx_stmt->execute();
    $tx_res = $tx_stmt->get_result();
    $t = $tx_res ? $tx_res->fetch_assoc() : null;

    if (!$t) {
        throw new RuntimeException("Transaction not found.");
    }

    $user_id   = (int)$t['user_id'];
    $type      = (string)($t['transaction_type'] ?? '');
    // Safe amount fallback (NO SQL reference to a missing column)
    if (array_key_exists('total_amount', $t)) {
        $amount = (float)$t['total_amount'];
    } elseif (array_key_exists('amount', $t)) {
        $amount = (float)$t['amount'];
    } else {
        $amount = 0.00;
    }

    if ($action === 'approve') {
        // Only activate membership AFTER admin approval
        if ($type === 'membership') {
            // Backtick `user` to avoid reserved-word issues
            $update_member_sql = "
                UPDATE `user`
                   SET membership_type = COALESCE(membership_type, 'monthly'),
                       membership_start = CURDATE(),
                       membership_end   = DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
                       status           = 'Active'
                 WHERE user_id = ?
            ";
            $um = $conn->prepare($update_member_sql);
            $um->bind_param('i', $user_id);
            $um->execute();
        }

        // Mark transaction as completed
        $upd_tx_sql = "UPDATE transactions SET status = 'completed' WHERE transaction_id = ?";
        $ut = $conn->prepare($upd_tx_sql);
        $ut->bind_param('i', $transaction_id);
        $ut->execute();

        // ----- Notifications -----
        $helper_path = __DIR__ . '/../includes/notifications.php';
        $helper_used = false;
        if (is_file($helper_path)) {
            require_once $helper_path;
            if (function_exists('notify_member_payment_status')) {
                notify_member_payment_status($conn, $transaction_id);
                $helper_used = true;
            }
        }
        if (!$helper_used) {
            $notif_title   = "✅ Payment Approved";
            $notif_message = ($type === 'membership')
                ? "Your payment for your membership has been verified and approved. Your membership is now ACTIVE! 💪"
                : "Your payment has been verified and approved. Thank you!";
            $ins_sql = "
                INSERT INTO notifications
                    (user_id, type, title, message, related_id, is_read, is_sent, target_user_type, created_at)
                VALUES
                    (?, 'payment', ?, ?, ?, 0, 0, 'Member', NOW())
            ";
            $ins = $conn->prepare($ins_sql);
            $ins->bind_param('issi', $user_id, $notif_title, $notif_message, $transaction_id);
            $ins->execute();
        }

        $_SESSION['success'] = ($type === 'membership')
            ? "Membership renewed and payment approved."
            : "Payment for items approved successfully.";

    } else { // reject
        // Reject the transaction
        $rej_tx_sql = "UPDATE transactions SET status = 'rejected' WHERE transaction_id = ?";
        $rt = $conn->prepare($rej_tx_sql);
        $rt->bind_param('i', $transaction_id);
        $rt->execute();

        // ----- Notifications -----
        $helper_path = __DIR__ . '/../includes/notifications.php';
        $helper_used = false;
        if (is_file($helper_path)) {
            require_once $helper_path;
            if (function_exists('notify_member_payment_status')) {
                notify_member_payment_status($conn, $transaction_id);
                $helper_used = true;
            }
        }
        if (!$helper_used) {
            $notif_title   = "❌ Payment Rejected";
            $amt_fmt       = '₱' . number_format($amount, 2);
            $notif_message = "Your payment of $amt_fmt for your membership has been rejected. Please contact the gym staff for assistance.";
            $ins_sql = "
                INSERT INTO notifications
                    (user_id, type, title, message, related_id, is_read, is_sent, target_user_type, created_at)
                VALUES
                    (?, 'payment', ?, ?, ?, 0, 0, 'Member', NOW())
            ";
            $ins = $conn->prepare($ins_sql);
            $ins->bind_param('issi', $user_id, $notif_title, $notif_message, $transaction_id);
            $ins->execute();
        }

        $_SESSION['error'] = "Payment rejected successfully.";
    }

    $conn->commit();

} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignore) {}
    error_log("[PAYMENT_ACTION] TxID={$transaction_id} Error: " . $e->getMessage());
    $_SESSION['error'] = "Something went wrong while processing the payment.";
}

// Redirect back
header("Location: ../payment-verification.php");
exit;
