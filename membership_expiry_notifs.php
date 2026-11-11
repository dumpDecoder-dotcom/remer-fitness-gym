<?php
require_once __DIR__ . '/../Actions/dbconnect.php';
require_once __DIR__ . '/../includes/member_notifications.php';

$today = new DateTimeImmutable('today');

$sql = "
  SELECT user_id, fname, lname, status, membership_end
  FROM user
  WHERE membership_end IS NOT NULL
";
if (!$res = $conn->query($sql)) {
    echo "DB error\n"; 
    exit;
}

while ($row = $res->fetch_assoc()) {
    $uid = (int)$row['user_id'];
    $end = new DateTimeImmutable($row['membership_end']);
    $days = (int)$end->diff($today)->format('%r%a'); // negative if in future

    // Expiring soon: -7, -3, 0 (today)
    if ($days === -7 || $days === -3 || $days === 0) {
        if ($days < 0) {
            $days_left = abs($days);
            $title = "Membership expiring in {$days_left} day" . ($days_left===1?'':'s');
            $msg   = "Hi! Your membership will expire on " . $end->format('M d, Y') . ". Renew now to avoid interruption.";
            add_notif($conn, $uid, 'membership_expiry', $title, $msg, 24);
        } else {
            $title = "Membership expires today";
            $msg   = "Your membership expires today (" . $end->format('M d, Y') . "). You can renew anytime.";
            add_notif($conn, $uid, 'membership_expiry', $title, $msg, 24);
        }
    }

    // Already expired (> 0)
    if ($days > 0) {
        $title = "Membership expired";
        $msg   = "Your membership expired on " . $end->format('M d, Y') . ". Renew to continue gym access.";
        add_notif($conn, $uid, 'membership_expiry', $title, $msg, 24);
    }
}

echo "OK\n";
