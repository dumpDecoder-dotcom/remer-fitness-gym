<?php
// This file should be run by cron job every hour
// Example cron: 0 * * * * php /path/to/run_notifications.php

require_once 'db_connection.php'; // Your database connection
require_once 'notification_generator.php';

$generator = new NotificationGenerator($conn);

// Run all notification checks
$generator->checkMembershipExpiry();
$generator->checkPaymentDue();
$generator->sendClassReminders();

echo "Notifications checked and sent at " . date('Y-m-d H:i:s') . "\n";
