<?php
// includes/notifications.php
// Role-aware notifications with per-member payment updates.

function notif_bootstrap(mysqli $conn) {
    // Create base table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Ensure optional columns (for dedupe / cross-linking)
    ensure_column($conn, 'notifications', 'source_type', "VARCHAR(50) DEFAULT NULL");
    ensure_column($conn, 'notifications', 'source_id',   "INT DEFAULT NULL");

    // Unique guard to avoid duplicating same logical notif
    ensure_unique_index($conn, 'notifications', 'uniq_user_source', ['user_id','source_type','source_id']);
}

function ensure_column(mysqli $conn, string $table, string $column, string $definition) {
    $db = mysqli_fetch_row(mysqli_query($conn, "SELECT DATABASE()"))[0];
    $q  = sprintf(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='%s' AND COLUMN_NAME='%s'",
        mysqli_real_escape_string($conn, $db),
        mysqli_real_escape_string($conn, $table),
        mysqli_real_escape_string($conn, $column)
    );
    $exists = (int)mysqli_fetch_row(mysqli_query($conn, $q))[0] > 0;
    if (!$exists) {
        @mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_unique_index(mysqli $conn, string $table, string $indexName, array $cols) {
    $db = mysqli_fetch_row(mysqli_query($conn, "SELECT DATABASE()"))[0];
    $q = sprintf(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='%s' AND INDEX_NAME='%s'",
        mysqli_real_escape_string($conn, $db),
        mysqli_real_escape_string($conn, $table),
        mysqli_real_escape_string($conn, $indexName)
    );
    $exists = (int)mysqli_fetch_row(mysqli_query($conn, $q))[0] > 0;
    if (!$exists) {
        $colsSql = implode('`,`', $cols);
        @mysqli_query($conn, "ALTER TABLE `$table` ADD UNIQUE KEY `$indexName` (`$colsSql`)");
    }
}

function add_notification(mysqli $conn, int $user_id, string $type, string $title, string $message, ?string $source_type=null, ?int $source_id=null) {
    notif_bootstrap($conn);
    $user_id = (int)$user_id;
    $type    = mysqli_real_escape_string($conn, $type);
    $title   = mysqli_real_escape_string($conn, $title);
    $message = mysqli_real_escape_string($conn, $message);

    if ($source_type !== null && $source_id !== null) {
        $source_type = mysqli_real_escape_string($conn, $source_type);
        $source_id   = (int)$source_id;
        @mysqli_query($conn, "
            INSERT IGNORE INTO notifications (user_id, type, title, message, source_type, source_id)
            VALUES ($user_id, '$type', '$title', '$message', '$source_type', $source_id)
        ");
    } else {
        @mysqli_query($conn, "
            INSERT INTO notifications (user_id, type, title, message)
            VALUES ($user_id, '$type', '$title', '$message')
        ");
    }
}

/* ======================
   MEMBER-SIDE SEEDING
   ====================== */
function seed_member_on_login(mysqli $conn, int $uid): void {
    notif_bootstrap($conn);

    // 1) Welcome (only once per member)
    add_notification(
        $conn, $uid, 'system',
        'Welcome to Remer Fitness Gym!',
        'Explore classes, track progress, and check today’s promos.',
        'welcome', $uid
    );

    // 2) Live promos → personal promo notifs
    $promoRes = mysqli_query($conn, "
        SELECT promo_id, promo_name, discount, end_date
        FROM promo
        WHERE end_date >= CURDATE()
        ORDER BY end_date ASC
        LIMIT 10
    ");
    if ($promoRes) {
        while ($p = mysqli_fetch_assoc($promoRes)) {
            $pid  = (int)$p['promo_id'];
            $ends = date('M j, Y', strtotime($p['end_date']));
            add_notification(
                $conn, $uid, 'promo',
                "Sale: {$p['promo_name']} — {$p['discount']}% OFF",
                "Ends on $ends. Visit the Shop to apply it.",
                'promo', $pid
            );
        }
    }

    // 3) Upcoming classes → personal class notifs
    $classRes = mysqli_query($conn, "
        SELECT id, class_name, start_date, end_date, start_time, end_time
        FROM class
        WHERE status='Upcoming' AND start_date >= CURDATE()
        ORDER BY start_date ASC, start_time ASC
        LIMIT 10
    ");
    if ($classRes) {
        while ($c = mysqli_fetch_assoc($classRes)) {
            $cid  = (int)$c['id'];
            $when = '';
            if (!empty($c['start_date'])) {
                $d1 = date('M j, Y', strtotime($c['start_date']));
                if (!empty($c['end_date'])) {
                    $d2 = date('M j, Y', strtotime($c['end_date']));
                    $when = ($d1 === $d2) ? $d1 : "$d1 - $d2";
                } else $when = $d1;
            }
            $time = (!empty($c['start_time']) && !empty($c['end_time']))
                ? date("g:i A", strtotime($c['start_time'])) . " - " . date("g:i A", strtotime($c['end_time']))
                : '';
            $sub  = trim(($when ? "Schedule: $when" : '') . ($time ? " | $time" : ''), " |");

            add_notification(
                $conn, $uid, 'class_booking',
                "New Class Available: ".$c['class_name'],
                $sub ?: 'Spots may be limited. Book early.',
                'class', $cid
            );
        }
    }
}

/* =========================================
   PER-MEMBER PAYMENT NOTIFICATIONS (KEY)
   - call when a member submits a payment
   - call when admin Approves/Rejects
   ========================================= */
function notify_member_payment_status(mysqli $conn, int $transaction_id): void {
    notif_bootstrap($conn);
    $tid = (int)$transaction_id;

    $q = mysqli_query($conn, "
        SELECT t.transaction_id, t.user_id, t.total_amount, t.payment_method,
               t.reference_number, t.status, t.transaction_type, t.date
        FROM transactions t
        WHERE t.transaction_id = $tid
        LIMIT 1
    ");
    if (!$q || !($t = mysqli_fetch_assoc($q))) return;

    $uid  = (int)$t['user_id'];
    $amt  = number_format((float)$t['total_amount'], 2);
    $ref  = $t['reference_number'] ?: '—';
    $type = ucfirst($t['transaction_type'] ?? 'payment');

    switch ($t['status']) {
        case 'pending_verification':
            add_notification(
                $conn, $uid, 'payment',
                "$type Payment Submitted (Ref: $ref)",
                "We received your payment (₱$amt). It’s pending verification.",
                'payment', $tid
            );
            break;

        case 'completed':
            add_notification(
                $conn, $uid, 'payment',
                "$type Payment Approved (Ref: $ref)",
                "Your payment of ₱$amt is approved. Thank you!",
                'payment', $tid
            );
            // Optional: membership activation reminder
            add_notification(
                $conn, $uid, 'membership',
                "Membership Updated",
                "Your membership or purchase is now active. Enjoy your workouts!",
                'member_status', $uid
            );
            break;

        case 'rejected':
            add_notification(
                $conn, $uid, 'payment',
                "$type Payment Rejected (Ref: $ref)",
                "We couldn’t verify the payment (₱$amt). Please re-upload a clear proof or contact support.",
                'payment', $tid
            );
            break;
    }
}

/* ======================
   ADMIN-SIDE SEEDING
   ====================== */
function seed_admin_on_login(mysqli $conn, int $admin_uid): void {
    notif_bootstrap($conn);

    // Pending payments (for admin only)
    $payRes = mysqli_query($conn, "
        SELECT transaction_id, reference_number, total_amount, date
        FROM transactions
        WHERE status='pending_verification'
        ORDER BY date DESC
        LIMIT 20
    ");
    if ($payRes) {
        while ($t = mysqli_fetch_assoc($payRes)) {
            $tid = (int)$t['transaction_id'];
            $ref = $t['reference_number'] ?: '—';
            $amt = number_format((float)$t['total_amount'], 2);
            add_notification(
                $conn, $admin_uid, 'payment',
                "Payment Pending: Ref $ref",
                "₱$amt awaiting verification.",
                'payment', $tid
            );
        }
    }

    // Inventory: low stock / discontinued
    $stockRes = mysqli_query($conn, "
        SELECT product_id, product_name, quantity, status
        FROM product
        WHERE (quantity <= 5 AND (status IS NULL OR status != 'Discontinued'))
           OR status = 'Discontinued'
        ORDER BY status='Discontinued' DESC, quantity ASC
        LIMIT 20
    ");
    if ($stockRes) {
        while ($p = mysqli_fetch_assoc($stockRes)) {
            $pid  = (int)$p['product_id'];
            $name = $p['product_name'] ?? 'Item';
            $qty  = (int)$p['quantity'];
            $stat = $p['status'] ?: 'Available';
            $title = ($stat === 'Discontinued') ? "Discontinued: $name" : "Low Stock: $name";
            $msg   = ($stat === 'Discontinued') ? "Marked discontinued." : "Only $qty left.";
            add_notification($conn, $admin_uid, 'inventory', $title, $msg, 'stock', $pid);
        }
    }

    // Classes needing instructor
    $classNeed = mysqli_query($conn, "
        SELECT id, class_name
        FROM class
        WHERE (instructor_id IS NULL) OR status='Pending'
        ORDER BY id DESC
        LIMIT 20
    ");
    if ($classNeed) {
        while ($c = mysqli_fetch_assoc($classNeed)) {
            add_notification(
                $conn, $admin_uid, 'system',
                "Instructor Needed: ".$c['class_name'],
                "Assign an instructor to publish the schedule.",
                'class_need', (int)$c['id']
            );
        }
    }
}
