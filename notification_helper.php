<?php
// Actions/notification_helper.php
// Simple helper functions to send notifications easily

/**
 * Send notification to a specific user
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id Recipient user ID
 * @param string $type Notification type (membership_expiry, payment_due, etc.)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $target_user_type User type (member, staff, admin, cashier)
 * @return bool Success status
 */
function sendNotification($conn, $user_id, $type, $title, $message, $target_user_type = 'member') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, target_user_type, type, title, message, is_read, is_popup_shown, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
        ");
        $stmt->bind_param("issss", $user_id, $target_user_type, $type, $title, $message);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Send Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification to all users of a specific type
 * 
 * @param mysqli $conn Database connection
 * @param string $user_type Target user type (member, staff, admin, cashier)
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @return int Number of notifications sent
 */
function sendNotificationToUserType($conn, $user_type, $type, $title, $message) {
    try {
        // Get all users of the specified type
        $sql = "SELECT user_id FROM user WHERE role = ? AND status = 'Active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $count = 0;
        while ($user = $result->fetch_assoc()) {
            if (sendNotification($conn, $user['user_id'], $type, $title, $message, strtolower($user_type))) {
                $count++;
            }
        }
        
        return $count;
    } catch (Exception $e) {
        error_log("Send Notification to User Type Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Send notification to all active members
 * 
 * @param mysqli $conn Database connection
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @return int Number of notifications sent
 */
function sendNotificationToAllMembers($conn, $type, $title, $message) {
    return sendNotificationToUserType($conn, 'Member', $type, $title, $message);
}

/**
 * Send notification to all active staff
 * 
 * @param mysqli $conn Database connection
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @return int Number of notifications sent
 */
function sendNotificationToAllStaff($conn, $type, $title, $message) {
    return sendNotificationToUserType($conn, 'Staff', $type, $title, $message);
}

/**
 * Send notification to all admins
 * 
 * @param mysqli $conn Database connection
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @return int Number of notifications sent
 */
function sendNotificationToAllAdmins($conn, $type, $title, $message) {
    return sendNotificationToUserType($conn, 'Admin', $type, $title, $message);
}

/**
 * Send notification to everyone (all users)
 * 
 * @param mysqli $conn Database connection
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @return int Number of notifications sent
 */
function sendNotificationToAll($conn, $type, $title, $message) {
    try {
        // Get all active users
        $sql = "SELECT user_id, role FROM user WHERE status = 'Active'";
        $result = $conn->query($sql);
        
        $count = 0;
        while ($user = $result->fetch_assoc()) {
            if (sendNotification($conn, $user['user_id'], $type, $title, $message, strtolower($user['role']))) {
                $count++;
            }
        }
        
        return $count;
    } catch (Exception $e) {
        error_log("Send Notification to All Error: " . $e->getMessage());
        return 0;
    }
}

// ==========================================
// QUICK NOTIFICATION TEMPLATES
// ==========================================

/**
 * Send class booking confirmation notification
 */
function notifyClassBooking($conn, $user_id, $class_name, $class_date, $class_time) {
    $title = "Class Booking Confirmed! 🎉";
    $message = "You have successfully booked '{$class_name}' on {$class_date} at {$class_time}. See you there!";
    return sendNotification($conn, $user_id, 'class_booking', $title, $message, 'member');
}

/**
 * Send payment approval notification
 */
function notifyPaymentApproved($conn, $user_id, $membership_type, $expiry_date) {
    $title = "Payment Approved ✅";
    $message = "Great news! Your {$membership_type} payment has been approved. Your membership is now active until {$expiry_date}.";
    return sendNotification($conn, $user_id, 'renewal_confirmation', $title, $message, 'member');
}

/**
 * Send payment rejection notification
 */
function notifyPaymentRejected($conn, $user_id, $reason) {
    $title = "Payment Rejected ❌";
    $message = "We're sorry, but your payment has been rejected. Reason: {$reason}. Please review and submit your payment again.";
    return sendNotification($conn, $user_id, 'payment_due', $title, $message, 'member');
}

/**
 * Send order confirmation notification
 */
function notifyOrderConfirmed($conn, $user_id, $order_id, $total_amount) {
    $title = "Order Confirmed 🛒";
    $message = "Your order #{$order_id} (₱{$total_amount}) has been confirmed! We'll notify you when it's ready for pickup.";
    return sendNotification($conn, $user_id, 'product', $title, $message, 'member');
}

/**
 * Send order ready notification
 */
function notifyOrderReady($conn, $user_id, $order_id) {
    $title = "Order Ready for Pickup! 📦";
    $message = "Your order #{$order_id} is ready! Please come to the counter to pick it up.";
    return sendNotification($conn, $user_id, 'product', $title, $message, 'member');
}

/**
 * Send class cancellation notification
 */
function notifyClassCancelled($conn, $user_id, $class_name, $class_date) {
    $title = "Class Cancelled ⚠️";
    $message = "We regret to inform you that '{$class_name}' scheduled on {$class_date} has been cancelled. We apologize for any inconvenience.";
    return sendNotification($conn, $user_id, 'class_cancelled', $title, $message, 'member');
}

/**
 * Send membership expiry warning (3 days before)
 */
function notifyMembershipExpiring($conn, $user_id, $expiry_date, $member_name) {
    $title = "Membership Expiring Soon ⏰";
    $message = "Hi {$member_name}! Your membership expires on {$expiry_date}. Renew now to continue your fitness journey!";
    return sendNotification($conn, $user_id, 'membership_expiry', $title, $message, 'member');
}

/**
 * Send class reminder (2 hours before)
 */
function notifyClassReminder($conn, $user_id, $class_name, $class_time) {
    $title = "Class Reminder 🔔";
    $message = "Your class '{$class_name}' starts at {$class_time}. See you soon!";
    return sendNotification($conn, $user_id, 'class_reminder', $title, $message, 'member');
}

/**
 * Send promotional notification
 */
function notifyPromo($conn, $user_id, $promo_title, $promo_message) {
    return sendNotification($conn, $user_id, 'promo', $promo_title, $promo_message, 'member');
}

/**
 * Send announcement to all members
 */
function notifyAnnouncement($conn, $announcement_title, $announcement_message) {
    return sendNotificationToAllMembers($conn, 'announcement', $announcement_title, $announcement_message);
}

// ==========================================
// USAGE EXAMPLES
// ==========================================

/*
// Example 1: Class booking confirmation
include 'notification_helper.php';
notifyClassBooking($conn, $user_id, "Yoga Class", "January 15, 2025", "10:00 AM");

// Example 2: Payment approved
notifyPaymentApproved($conn, $user_id, "Monthly Membership", "February 15, 2025");

// Example 3: Custom notification
sendNotification($conn, $user_id, 'promo', 'Special Offer! 🎁', 'Get 20% off today only!', 'member');

// Example 4: Send to all members
sendNotificationToAllMembers($conn, 'announcement', 'Gym Closure Notice', 'The gym will be closed on January 1st for maintenance.');

// Example 5: Order confirmation
notifyOrderConfirmed($conn, $user_id, "ORD-12345", "1,250.00");
*/
?>