<?php
require_once 'Actions/dbconnect.php';
require_once 'notification.php';

$notif = new Notification($conn);

// 1. Check memberships expiring in 3 days
$expiring = $conn->query("
    SELECT id, CONCAT(firstname, ' ', lastname) as name 
    FROM members 
    WHERE membership_end = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    AND id NOT IN (
        SELECT user_id FROM notifications 
        WHERE type = 'membership_expiry' 
        AND DATE(created_at) = CURDATE()
    )
");

while ($member = $expiring->fetch_assoc()) {
    $notif->create(
        $member['id'],                      // user_id
        'membership_expiry',                // type
        'Membership Expiring Soon',         // title
        'Hi ' . $member['name'] . ', your membership will expire in 3 days. Renew now!', // message
        'member'                            // target_user_type
    );
}

// 2. Check payment due tomorrow
$payments_due = $conn->query("
    SELECT member_id, amount 
    FROM payments 
    WHERE due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    AND status = 'pending'
");

while ($payment = $payments_due->fetch_assoc()) {
    $notif->create(
        $payment['member_id'],
        'payment_due',
        'Payment Due Tomorrow',
        'Reminder: Your payment of ₱' . number_format($payment['amount']) . ' is due tomorrow.',
        'member'
    );
}

// 3. Send class reminders (2 hours before)
$upcoming_classes = $conn->query("
    SELECT b.member_id, c.class_name, c.schedule_time 
    FROM bookings b
    JOIN classes c ON b.class_id = c.id
    WHERE DATE(c.schedule_date) = CURDATE()
    AND c.schedule_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
    AND b.status = 'confirmed'
");

while ($class = $upcoming_classes->fetch_assoc()) {
    $notif->create(
        $class['member_id'],
        'class_reminder',
        'Class Starting Soon',
        'Reminder: Your ' . $class['class_name'] . ' class starts at ' . date('g:i A', strtotime($class['schedule_time'])),
        'member'
    );
}

echo "✅ Automated notifications sent successfully!";