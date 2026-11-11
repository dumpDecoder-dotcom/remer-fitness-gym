<?php
/**
 * Member Notifications (always-welcome version)
 * - Ensures table (+ ENUM migration for 'class_joined')
 * - Seeds on login:
 *     • ALWAYS adds a welcome notif (no dedupe)
 *     • Optionally adds promos/classes (same as before)
 * - Endpoints: list / mark_one / mark_all / clear / class_joined / welcome_only
 * - Helper: notify_member_class_joined($conn, $uid, $class_id)
 *
 * AJAX:
 *   GET  ?mode=list                       -> { unread_count, unread[], read[] }
 *   POST ?mode=mark_one    {id}           -> { success:true }
 *   POST ?mode=mark_all                   -> { success:true }
 *   POST ?mode=clear                      -> { success:true }
 *   POST ?mode=class_joined {class_id}    -> { success:true }
 *   POST ?mode=welcome_only               -> { success:true }
 *
 * Server-side:
 *   require_once __DIR__.'/member_notifications.php';
 *   seed_member_notifs_on_login($conn, $user_id, $fname); // call right after login
 */

session_start();
require_once __DIR__ . '/dbconnect.php';
header('Content-Type: application/json');

// ---------- Helpers ----------
function ensure_table(mysqli $conn): void {
    // 1) Table
    $sql = "CREATE TABLE IF NOT EXISTS `member_notifications` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT UNSIGNED NOT NULL,
              `type` ENUM('welcome','promo','class_booking','class_reminder','membership_expiry','payment_due','system')
                     NOT NULL DEFAULT 'system',
              `title` VARCHAR(120) NOT NULL,
              `message` TEXT NOT NULL,
              `is_read` TINYINT(1) NOT NULL DEFAULT 0,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_user_created` (`user_id`,`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);

    // 2) Add enum value 'class_joined' if missing
    $q = $conn->query("SELECT COLUMN_TYPE
                       FROM information_schema.COLUMNS
                       WHERE TABLE_SCHEMA = DATABASE()
                         AND TABLE_NAME = 'member_notifications'
                         AND COLUMN_NAME = 'type'
                       LIMIT 1");
    if ($q && ($row = $q->fetch_assoc())) {
        $colType = $row['COLUMN_TYPE'] ?? '';
        if (stripos($colType, "'class_joined'") === false) {
            if (preg_match("/^enum\\((.*)\\)$/i", $colType, $m)) {
                $vals = $m[1]; // "'welcome','promo',..."
                $vals .= ",'class_joined'";
                $alter = "ALTER TABLE `member_notifications`
                          MODIFY `type` ENUM($vals)
                          NOT NULL DEFAULT 'system'";
                $conn->query($alter);
            }
        }
    }
}

/**
 * Insert a notification with optional time-window de-duplication.
 * $dedupe_hours == 0  => always insert (no dedupe)
 */
function add_notif(mysqli $conn, int $uid, string $type, string $title, string $message, int $dedupe_hours = 12): void {
    if ($dedupe_hours > 0) {
        $q = $conn->prepare(
            "SELECT id FROM member_notifications
             WHERE user_id=? AND type=? AND title=?
               AND created_at >= (NOW() - INTERVAL ? HOUR)
             LIMIT 1"
        );
        $q->bind_param('issi', $uid, $type, $title, $dedupe_hours);
        $q->execute();
        $exists = $q->get_result()->fetch_assoc();
        $q->close();
        if ($exists) return;
    }

    $ins = $conn->prepare("INSERT INTO member_notifications (user_id, type, title, message) VALUES (?,?,?,?)");
    $ins->bind_param('isss', $uid, $type, $title, $message);
    $ins->execute();
    $ins->close();
}

function fmtDayMonTime($ts): string {
    if (!$ts) return 'soon';
    $dt = date_create($ts);
    return $dt ? $dt->format('M d, Y g:i A') : $ts; // 12-hour format
}

/** Create a notif after successful class enrollment. */
function notify_member_class_joined(mysqli $conn, int $uid, int $class_id): bool {
    ensure_table($conn);
    $cls = null;
    if ($st = $conn->prepare("SELECT id, COALESCE(class_name,'Gym Class') AS class_name, start_time
                              FROM classes
                              WHERE id = ? LIMIT 1")) {
        $st->bind_param('i', $class_id);
        $st->execute();
        $rs = $st->get_result();
        $cls = $rs ? $rs->fetch_assoc() : null;
        $st->close();
    }
    if (!$cls) return false;

    $when = fmtDayMonTime($cls['start_time'] ?? null);
    $title = "Joined: " . $cls['class_name'];
    $msg   = "You're confirmed for '{$cls['class_name']}' starting on {$when}. See you there!";
    add_notif($conn, $uid, 'class_joined', $title, $msg, 6); // dedupe 6h
    return true;
}

/** Seed on login — ALWAYS add a welcome message (no dedupe). */
function seed_member_notifs_on_login(mysqli $conn, int $uid, string $first_name = ''): void {
    ensure_table($conn);

    // Always welcome (each login gets a new one)
    $title = trim($first_name) !== '' ? "Welcome back, {$first_name}!" : "Welcome back!";
    $when  = date('M d, Y g:i A');
    add_notif(
        $conn,
        $uid,
        'welcome',
        $title,
        "You're logged in. Have a great workout! 💪 (Logged in: {$when})",
        0 // <-- no dedupe; insert every login
    );

    // (Optional) Promos
    $hasPromos = $conn->query("SELECT 1 FROM information_schema.tables
                               WHERE table_schema = DATABASE()
                                 AND table_name = 'promotions' LIMIT 1");
    if ($hasPromos && $hasPromos->num_rows) {
        $sql = "SELECT id, COALESCE(title,'') AS title, COALESCE(description,'') AS description, expires_at
                FROM promotions
                WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())
                ORDER BY expires_at IS NULL, expires_at ASC
                LIMIT 5";
        if ($rs = $conn->query($sql)) {
            while ($p = $rs->fetch_assoc()) {
                $ttl = 'Promo: ' . ($p['title'] ?: 'Limited-time offer');
                $msg = trim(($p['description'] ?: 'Check out our latest promotion!') .
                        ((isset($p['expires_at']) && $p['expires_at'])
                            ? (" (Ends: ".date('M d, Y', strtotime($p['expires_at'])) . ")")
                            : ''));
                add_notif($conn, $uid, 'promo', $ttl, $msg, 24);
            }
            $rs->close();
        }
    }

    // (Optional) Upcoming classes w/ seats
    $hasClasses = $conn->query("SELECT 1 FROM information_schema.tables
                                WHERE table_schema = DATABASE()
                                  AND table_name = 'classes' LIMIT 1");
    if ($hasClasses && $hasClasses->num_rows) {
        $sql = "SELECT id, COALESCE(class_name,'Gym Class') AS class_name,
                       start_time, COALESCE(seats_available,0) AS seats_available
                FROM classes
                WHERE is_active = 1
                  AND COALESCE(seats_available,0) > 0
                  AND start_time >= NOW()
                ORDER BY start_time ASC
                LIMIT 5";
        if ($rs = $conn->query($sql)) {
            while ($c = $rs->fetch_assoc()) {
                $when = $c['start_time'] ? date('M d, Y g:i A', strtotime($c['start_time'])) : 'soon';
                $ttl  = 'Class Available: ' . $c['class_name'];
                $msg  = "Starts {$when}. Seats available: " . (int)$c['seats_available'] . ". Book now!";
                add_notif($conn, $uid, 'class_booking', $ttl, $msg, 6);
            }
            $rs->close();
        }
    }
}

// ---------- If included (PHP) rather than called via HTTP ----------
if (!isset($_GET['mode']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ready' => true]);
    exit;
}

// ---------- Member-only AJAX endpoints ----------
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'Member')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$uid  = (int)$_SESSION['user_id'];
$mode = $_GET['mode'] ?? $_POST['mode'] ?? 'list';

ensure_table($conn);

switch ($mode) {
    case 'seed':
        $fname = (string)($_SESSION['fname'] ?? '');
        seed_member_notifs_on_login($conn, $uid, $fname);
        echo json_encode(['success' => true]);
        break;

    case 'welcome_only': // optional single welcome via AJAX
        $fname = (string)($_SESSION['fname'] ?? '');
        ensure_table($conn);
        $title = trim($fname) !== '' ? "Welcome back, {$fname}!" : "Welcome back!";
        $when  = date('M d, Y g:i A');
        add_notif($conn, $uid, 'welcome', $title, "You're logged in. Have a great workout! 💪 (Logged in: {$when})", 0);
        echo json_encode(['success' => true]);
        break;

    case 'class_joined':
        $payload  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $class_id = (int)($payload['class_id'] ?? 0);
        if ($class_id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing class_id']); break; }
        $ok = notify_member_class_joined($conn, $uid, $class_id);
        echo json_encode(['success' => (bool)$ok]);
        break;

    case 'list':
        $unread = [];
        $read   = [];
        $st = $conn->prepare("SELECT id, type, title, message, is_read, created_at
                              FROM member_notifications
                              WHERE user_id = ?
                              ORDER BY is_read ASC, created_at DESC
                              LIMIT 100");
        $st->bind_param('i', $uid);
        $st->execute();
        $res = $st->get_result();
        while ($row = $res->fetch_assoc()) {
            if ((int)$row['is_read'] === 1) { $read[] = $row; } else { $unread[] = $row; }
        }
        $st->close();
        echo json_encode([
            'unread_count' => count($unread),
            'unread'       => $unread,
            'read'         => $read
        ]);
        break;

    case 'mark_one':
        $payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success'=>false]); break; }
        $st = $conn->prepare("UPDATE member_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $st->bind_param('ii', $id, $uid);
        $ok = $st->execute();
        $st->close();
        echo json_encode(['success' => (bool)$ok]);
        break;

    case 'mark_all':
        $st = $conn->prepare("UPDATE member_notifications SET is_read = 1 WHERE user_id = ?");
        $st->bind_param('i', $uid);
        $ok = $st->execute();
        $st->close();
        echo json_encode(['success' => (bool)$ok]);
        break;

    case 'clear':
        $st = $conn->prepare("DELETE FROM member_notifications WHERE user_id = ?");
        $st->bind_param('i', $uid);
        $ok = $st->execute();
        $st->close();
        echo json_encode(['success' => (bool)$ok]);
        break;

    default:
        echo json_encode(['error' => 'Invalid mode']);
}
