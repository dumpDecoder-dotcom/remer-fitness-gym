<?php
/**
 * Automated Notification Scheduler
 * Run this file via cron job every hour or every day
 * Cron example: 0 * * * * php /path/to/cron_notifications.php
 */

require_once 'Actions/dbconnect.php';
require_once 'notification.php';

$notif = new Notification($conn);
$log = [];

// ============================================
// 1. CHECK MEMBERSHIP EXPIRY (3 days before)
// ============================================
$expiry_check = $conn->query("
    SELECT m.id, m.user_id, m.expiry_date, u.fname, u.lname 
    FROM memberships m
    JOIN user u ON m.user_id = u.id
    WHERE m.status = 'active' 
    AND m.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    AND NOT EXISTS (
        SELECT 1 FROM notifications n 
        WHERE n.user_id = m.user_id 
        AND n.type = 'membership_expiry' 
        AND DATE(n.created_at) = CURDATE()
    )
");

while ($row = $expiry_check->fetch_assoc()) {
    $days_left = ceil((strtotime($row['expiry_date']) - time()) / 86400);
    $notif->sendMembershipExpiryReminder($row['user_id'], $row['expiry_date'], $days_left);
    $log[] = "Membership expiry reminder sent to {$row['fname']} {$row['lname']}";
}

// ============================================
// 2. CHECK PAYMENT DUE (3 days before)
// ============================================
$payment_check = $conn->query("
    SELECT p.id, p.user_id, p.amount, p.due_date, u.fname, u.lname 
    FROM payments p
    JOIN user u ON p.user_id = u.id
    WHERE p.status = 'pending' 
    AND p.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    AND NOT EXISTS (
        SELECT 1 FROM notifications n 
        WHERE n.user_id = p.user_id 
        AND n.type = 'payment_due' 
        AND n.related_id = p.id
        AND DATE(n.created_at) = CURDATE()
    )
");

while ($row = $payment_check->fetch_assoc()) {
    $notif->sendPaymentDueReminder($row['user_id'], $row['amount'], $row['due_date']);
    $log[] = "Payment reminder sent to {$row['fname']} {$row['lname']}";
}

// ============================================
// 3. CLASS REMINDERS (1 day before)
// ============================================
$class_reminder = $conn->query("
    SELECT cb.id, cb.user_id, cb.class_id, 
           c.class_name, c.start_date, c.start_time, 
           u.fname, u.lname
    FROM class_bookings cb
    JOIN class c ON cb.class_id = c.id
    JOIN user u ON cb.user_id = u.id
    WHERE cb.status = 'confirmed'
    AND c.start_date BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) 
                         AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
    AND NOT EXISTS (
        SELECT 1 FROM notifications n 
        WHERE n.user_id = cb.user_id 
        AND n.type = 'class_reminder' 
        AND n.related_id = cb.id
        AND DATE(n.created_at) = CURDATE()
    )
");

while ($row = $class_reminder->fetch_assoc()) {
    $schedule = $row['start_date'] . ' ' . $row['start_time'];
    $notif->sendClassReminder($row['user_id'], $row['class_name'], $schedule, 'Main Gym Floor');
    $log[] = "Class reminder sent to {$row['fname']} {$row['lname']} for {$row['class_name']}";
}

// ============================================
// 4. CHECK ACTIVE PROMOS (Send on start date)
// ============================================
$promo_check = $conn->query("
    SELECT id, title, description, expiry_date 
    FROM promos 
    WHERE status = 'active' 
    AND start_date = CURDATE()
    AND NOT EXISTS (
        SELECT 1 FROM notifications 
        WHERE type = 'promo' 
        AND related_id = promos.id
    )
");

while ($promo = $promo_check->fetch_assoc()) {
    // Send to all members
    $members = $conn->query("SELECT id FROM user WHERE user_type = 'member'");
    while ($member = $members->fetch_assoc()) {
        $notif->sendPromoNotification(
            $member['id'], 
            $promo['title'], 
            $promo['description'], 
            $promo['expiry_date']
        );
    }
    $log[] = "Promo notification sent for: {$promo['title']}";
}

// ============================================
// 5. AUTO-UPDATE MEMBERSHIP STATUS (Expired)
// ============================================
$expired_memberships = $conn->query("
    UPDATE memberships 
    SET status = 'expired' 
    WHERE status = 'active' 
    AND expiry_date < CURDATE()
");

if ($conn->affected_rows > 0) {
    $log[] = "{$conn->affected_rows} memberships marked as expired";
}

// ============================================
// 6. AUTO-UPDATE CLASS STATUS
// ============================================
// Update to 'Completed'
$conn->query("
    UPDATE class 
    SET status = 'Completed' 
    WHERE status IN ('Upcoming', 'Ongoing') 
    AND end_date < CURDATE()
");

// Update to 'Ongoing'
$conn->query("
    UPDATE class 
    SET status = 'Ongoing' 
    WHERE status = 'Upcoming' 
    AND start_date <= CURDATE() 
    AND end_date >= CURDATE()
");

// ============================================
// LOG RESULTS
// ============================================
$log_message = date('Y-m-d H:i:s') . " - Cron Job Executed\n";
$log_message .= "Total actions: " . count($log) . "\n";
$log_message .= implode("\n", $log) . "\n";
$log_message .= str_repeat("-", 50) . "\n\n";

// Save to log file
file_put_contents('logs/notification_cron.log', $log_message, FILE_APPEND);

// Output (for manual testing)
echo "✅ Notification Cron Job Completed\n";
echo "Actions performed: " . count($log) . "\n";
foreach ($log as $item) {
    echo "- {$item}\n";
}
?>