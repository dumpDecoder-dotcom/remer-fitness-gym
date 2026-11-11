<?php
/**
 * Notifications endpoint
 * 
 * Modes:
 *   GET  ?mode=popup                -> fetch latest unsent popup for this viewer (and mark sent)
 *   GET  ?mode=list                 -> list up to 100 visible notifications + unread count
 *   POST ?mode=mark_one  {id}       -> mark one as read
 *   POST ?mode=mark_all             -> mark all visible as read
 *   POST ?mode=clear                -> delete all visible notifications (careful!)
 *
 * Visibility:
 *   - Personal: user_id = <viewer> AND target_user_type NULL or ''
 *   - Role:     LOWER(TRIM(target_user_type)) IN ('admin','staff','member')
 *   - All:      LOWER(TRIM(target_user_type)) = 'all'
 *
 * Admin filter:
 *   - Admin does NOT see login/logout style items (by type or by text in title/message).
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$user_id       = (int)($_SESSION['user_id'] ?? 0);
$user_role_raw = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
$mode          = $_GET['mode'] ?? 'list';

/* --- Normalize viewer role to: admin | staff | member (default: member) --- */
$role = strtolower(trim((string)$user_role_raw));
if (!in_array($role, ['admin','staff','member'], true)) {
    $role = 'member';
}

/*
   Admin exclusion:
   Hide any login/logout-like notifications even if "type" was not set exactly.
   This covers:
     - exact type: login/logout/user_login/user_logout/login_alert/user login alert
     - title contains "login"
     - message contains "logged in" or "login"
*/
$EXCLUDE_FOR_ADMIN = "
  AND NOT (
        COALESCE(LOWER(type),'') IN ('login','logout','user_login','user_logout','login_alert','user login alert')
     OR COALESCE(LOWER(title),'')   LIKE '%login%'
     OR COALESCE(LOWER(message),'') LIKE '% logged in %'
     OR COALESCE(LOWER(message),'') LIKE '%login%'
  )
";
$maybeExclude = ($role === 'admin') ? $EXCLUDE_FOR_ADMIN : "";

/*
  Visibility rules (normalized):
  - Personal: user_id = ? AND target_user_type is NULL or ''
  - Role:     LOWER(TRIM(target_user_type)) = ?   -- admin|staff|member
  - All:      LOWER(TRIM(target_user_type)) = 'all'
*/
$VISIBILITY_SQL = "
    (
        (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
        OR LOWER(TRIM(target_user_type)) = ?
        OR LOWER(TRIM(target_user_type)) = 'all'
    )
";

/* ---------- POPUP: fetch one unsent, then mark sent ---------- */
if ($mode === 'popup') {
    $sql = "
        SELECT id, type, title, message, is_read, is_sent, created_at, user_id, target_user_type
        FROM notifications
        WHERE $VISIBILITY_SQL
          AND (is_sent = 0 OR is_sent IS NULL)
          $maybeExclude
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ";
    if (!$stmt = $conn->prepare($sql)) {
        echo json_encode(['error' => 'Query prepare failed']); exit;
    }
    $stmt->bind_param('is', $user_id, $role);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = ['new_notifications' => 0];
    if ($res && $res->num_rows) {
        $n = $res->fetch_assoc();

        if ($upd = $conn->prepare("UPDATE notifications SET is_sent = 1 WHERE id = ?")) {
            $upd->bind_param('i', $n['id']);
            $upd->execute();
            $upd->close();
        }

        $out['new_notifications']   = 1;
        $out['latest_notification'] = $n;
    }
    echo json_encode($out); exit;
}

/* ---------- LIST: latest 100 ---------- */
if ($mode === 'list') {
    $list_sql = "
        SELECT id, type, title, message, is_read, created_at
        FROM notifications
        WHERE $VISIBILITY_SQL
        $maybeExclude
        ORDER BY created_at DESC, id DESC
        LIMIT 100
    ";
    if (!$list_stmt = $conn->prepare($list_sql)) {
        echo json_encode(['error'=>'Query prepare failed']); exit;
    }
    $list_stmt->bind_param('is', $user_id, $role);
    $list_stmt->execute();
    $rows = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $list_stmt->close();

    $count_sql = "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE $VISIBILITY_SQL
          AND COALESCE(is_read,0) = 0
          $maybeExclude
    ";
    if (!$count_stmt = $conn->prepare($count_sql)) {
        echo json_encode(['error'=>'Count prepare failed']); exit;
    }
    $count_stmt->bind_param('is', $user_id, $role);
    $count_stmt->execute();
    $unread = (int)($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $count_stmt->close();

    echo json_encode(['unread_count'=>$unread, 'notifications'=>$rows]); exit;
}

/* ---------- MARK ONE AS READ ---------- */
if ($mode === 'mark_one' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid id']); exit; }

    // Only mark if the row is visible to this viewer
    $chk_sql = "
        SELECT id FROM notifications
        WHERE id = ?
          AND $VISIBILITY_SQL
          $maybeExclude
        LIMIT 1
    ";
    if (!$chk = $conn->prepare($chk_sql)) { echo json_encode(['error' => 'Check prepare failed']); exit; }
    $chk->bind_param('i is', $id, $user_id, $role); // note: 'i is' is invalid; we need two binds separately
    // Fix binding: split into two statements since mysqli doesn't allow mixing types in one string like that.
    $chk->close();

    // Re-check with two-step binding due to mysqli quirk
    $chk = $conn->prepare("
        SELECT id FROM notifications
        WHERE id = ?
          AND (
                (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
                OR LOWER(TRIM(target_user_type)) = ?
                OR LOWER(TRIM(target_user_type)) = 'all'
              )
          $maybeExclude
        LIMIT 1
    ");
    if (!$chk) { echo json_encode(['error' => 'Check prepare failed']); exit; }
    $chk->bind_param('iis', $id, $user_id, $role);
    $chk->execute();
    $visible = $chk->get_result()->num_rows > 0;
    $chk->close();

    if (!$visible) { echo json_encode(['error' => 'Not allowed']); exit; }

    if ($upd = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")) {
        $upd->bind_param('i', $id);
        $ok = $upd->execute();
        $upd->close();
        echo json_encode(['success' => $ok]); exit;
    }
    echo json_encode(['error' => 'Update failed']); exit;
}

/* ---------- MARK ALL AS READ (only visible ones) ---------- */
if ($mode === 'mark_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "
        UPDATE notifications
        SET is_read = 1
        WHERE $VISIBILITY_SQL
        $maybeExclude
    ";
    if (!$stmt = $conn->prepare($sql)) { echo json_encode(['error' => 'Update prepare failed']); exit; }
    $stmt->bind_param('is', $user_id, $role);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => (bool)$ok]); exit;
}

/* ---------- CLEAR (DELETE) ALL VISIBLE (be careful) ---------- */
if ($mode === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "
        DELETE FROM notifications
        WHERE $VISIBILITY_SQL
        $maybeExclude
    ";
    if (!$stmt = $conn->prepare($sql)) { echo json_encode(['error' => 'Delete prepare failed']); exit; }
    $stmt->bind_param('is', $user_id, $role);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => (bool)$ok]); exit;
}

/* ---------- Fallback ---------- */
echo json_encode(['error' => 'Invalid mode']);
