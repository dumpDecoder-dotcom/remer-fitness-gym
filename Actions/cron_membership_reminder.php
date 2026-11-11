<?php
session_start();
include 'dbconnect.php';
require 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila');

/**
 * 🔔 MEMBERSHIP EXPIRY CHECKER WITH NOTIFICATIONS
 * Run this daily via cron job or task scheduler
 * Checks: 7 days, 3 days, expiry day, and expired memberships
 */

// ==========================================
// 1. CHECK EXPIRING IN 7 DAYS
// ==========================================
$seven_days = date('Y-m-d', strtotime('+7 days'));
$query_7 = "SELECT user_id, fname, lname, email, membership_expiry 
            FROM user 
            WHERE role = 'Member' 
            AND status = 'Active'
            AND membership_expiry = '$seven_days'";
$result_7 = mysqli_query($conn, $query_7);

if ($result_7 && mysqli_num_rows($result_7) > 0) {
    while ($member = mysqli_fetch_assoc($result_7)) {
        $full_name = $member['fname'] . ' ' . $member['lname'];
        $expiry_date = date('M d, Y', strtotime($member['membership_expiry']));
        
        // 📧 Send Email
        sendExpiryEmail($member, 7);
        
        // 🔔 In-app Notification for Member
        $notif_member = "INSERT INTO notifications (user_id, type, title, message, is_read, is_sent, created_at)
                        VALUES ({$member['user_id']}, 'membership', '⚠️ Membership Expiring Soon', 
                        'Your membership will expire in 7 days (on $expiry_date). Renew now to continue enjoying our gym facilities! 💪', 
                        0, 0, NOW())";
        mysqli_query($conn, $notif_member);
        
        // 🔔 Notification for Admin
        $notif_admin = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, is_sent, created_at)
                       SELECT user_id, 'membership', '⏰ Member Expiring in 7 Days', 
                       '$full_name membership expires on $expiry_date', 
                       'Admin', 0, 0, NOW()
                       FROM user WHERE role = 'Admin' AND status = 'Active'";
        mysqli_query($conn, $notif_admin);
    }
}

// ==========================================
// 2. CHECK EXPIRING IN 3 DAYS (URGENT)
// ==========================================
$three_days = date('Y-m-d', strtotime('+3 days'));
$query_3 = "SELECT user_id, fname, lname, email, membership_expiry 
            FROM user 
            WHERE role = 'Member' 
            AND status = 'Active'
            AND membership_expiry = '$three_days'";
$result_3 = mysqli_query($conn, $query_3);

if ($result_3 && mysqli_num_rows($result_3) > 0) {
    while ($member = mysqli_fetch_assoc($result_3)) {
        $full_name = $member['fname'] . ' ' . $member['lname'];
        $expiry_date = date('M d, Y', strtotime($member['membership_expiry']));
        
        // 📧 Send Urgent Email
        sendExpiryEmail($member, 3);
        
        // 🔔 Urgent In-app Notification for Member
        $notif_member = "INSERT INTO notifications (user_id, type, title, message, is_read, is_sent, created_at)
                        VALUES ({$member['user_id']}, 'membership', '🚨 URGENT: Membership Expiring in 3 Days!', 
                        'Your membership expires in just 3 days! Renew now to avoid interruption. Visit our payment page or contact us today.', 
                        0, 0, NOW())";
        mysqli_query($conn, $notif_member);
        
        // 🔔 Urgent Notification for Admin
        $notif_admin = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, is_sent, created_at)
                       SELECT user_id, 'membership', '🚨 URGENT: Member Expiring in 3 Days', 
                       '$full_name membership expires in 3 days. Consider reaching out.', 
                       'Admin', 0, 0, NOW()
                       FROM user WHERE role = 'Admin' AND status = 'Active'";
        mysqli_query($conn, $notif_admin);
    }
}

// ==========================================
// 3. CHECK EXPIRED TODAY (AUTO-DEACTIVATE)
// ==========================================
$today = date('Y-m-d');
$query_expired = "SELECT user_id, fname, lname, email, membership_expiry 
                  FROM user 
                  WHERE role = 'Member' 
                  AND status = 'Active'
                  AND membership_expiry = '$today'";
$result_expired = mysqli_query($conn, $query_expired);

if ($result_expired && mysqli_num_rows($result_expired) > 0) {
    while ($member = mysqli_fetch_assoc($result_expired)) {
        $full_name = $member['fname'] . ' ' . $member['lname'];
        
        // 🔴 AUTO-DEACTIVATE MEMBERSHIP
        $deactivate = "UPDATE user SET status = 'Inactive' WHERE user_id = {$member['user_id']}";
        mysqli_query($conn, $deactivate);
        
        // 📧 Send Expiry Email
        sendExpiryEmail($member, 0);
        
        // 🔔 Notification for Member
        $notif_member = "INSERT INTO notifications (user_id, type, title, message, is_read, is_sent, created_at)
                        VALUES ({$member['user_id']}, 'membership', '❌ Membership Expired', 
                        'Your membership has expired. Your account is now inactive. Please renew to continue using gym facilities.', 
                        0, 0, NOW())";
        mysqli_query($conn, $notif_member);
        
        // 🔔 Notification for Admin
        $notif_admin = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, is_sent, created_at)
                       SELECT user_id, 'membership', '❌ Member Expired', 
                       '$full_name membership expired today and account is now inactive.', 
                       'Admin', 0, 0, NOW()
                       FROM user WHERE role = 'Admin' AND status = 'Active'";
        mysqli_query($conn, $notif_admin);
        
        // 🔔 Notification for Staff
        $notif_staff = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, is_sent, created_at)
                       SELECT user_id, 'membership', 'ℹ️ Member Status Changed', 
                       '$full_name membership expired and is now inactive.', 
                       'Staff', 0, 0, NOW()
                       FROM user WHERE role = 'Staff' AND status = 'Active'";
        mysqli_query($conn, $notif_staff);
    }
}

// ==========================================
// 4. EMAIL SENDING FUNCTION
// ==========================================
function sendExpiryEmail($member, $days_before) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = (require __DIR__ . '/mail_config.php')['username']; 
        $mail->Password = (require __DIR__ . '/mail_config.php')['password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Sender and recipient
        $mail->setFrom('fitnessremer@gmail.com', 'Remer Fitness Gym');
        $mail->addAddress($member['email'], $member['fname'] . ' ' . $member['lname']);
        
        // Different messages based on days
        if ($days_before == 7) {
            $subject = 'Membership Expiry Reminder - 7 Days';
            $message = "
                <h2>Hi {$member['fname']},</h2>
                <p>This is a friendly reminder that your membership at <strong>Remer Fitness Gym</strong> 
                will expire on <b>{$member['membership_expiry']}</b> (in 7 days).</p>
                <p>To continue enjoying our services, please renew your membership before the expiry date.</p>
                <br>
                <p>Thank you for staying fit with us! 💪</p>
            ";
        } elseif ($days_before == 3) {
            $subject = '🚨 URGENT: Membership Expiring in 3 Days!';
            $message = "
                <h2>Hi {$member['fname']},</h2>
                <p><strong style='color:red;'>URGENT REMINDER:</strong> Your membership at <strong>Remer Fitness Gym</strong> 
                will expire on <b>{$member['membership_expiry']}</b> (in just 3 days)!</p>
                <p>Please renew your membership immediately to avoid service interruption.</p>
                <p>Contact us or visit our gym to renew today!</p>
                <br>
                <p>Don't miss out on your fitness journey! 💪</p>
            ";
        } else { // Expired
            $subject = '❌ Membership Expired';
            $message = "
                <h2>Hi {$member['fname']},</h2>
                <p>Your membership at <strong>Remer Fitness Gym</strong> has expired as of today ({$member['membership_expiry']}).</p>
                <p>Your account is now <strong style='color:red;'>inactive</strong>. To continue using our facilities, 
                please renew your membership as soon as possible.</p>
                <p>We look forward to seeing you back at the gym soon!</p>
                <br>
                <p>Stay strong! 💪</p>
            ";
        }
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message . "
            <hr>
            <small>This is an automated message. Please do not reply.</small>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        // Silent fail - log error if needed
        error_log("Email failed: " . $mail->ErrorInfo);
    }
}

// Log successful execution (optional)
error_log("[" . date('Y-m-d H:i:s') . "] Membership check completed successfully");

exit;
?>