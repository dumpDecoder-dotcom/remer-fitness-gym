<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');
$mode      = $_GET['mode'] ?? 'list';

/* Only exclude login/logout when the current viewer is ADMIN */
$EXCLUDE_FOR_ADMIN = " AND LOWER(type) NOT IN ('login','logout','user_login','user_logout') ";
$maybeExclude = (strcasecmp($user_role, 'Admin') === 0) ? $EXCLUDE_FOR_ADMIN : "";

/* ---------- POPUP ---------- */
if ($mode === 'popup') {
    $sql = "
        SELECT *
        FROM notifications
        WHERE (
                (user_id = ? AND (target_user_type IS NULL OR target_user_type = ''))
                OR target_user_type = ?
                OR target_user_type = 'all'
              )
          AND (is_sent = 0 OR is_sent IS NULL)
          $maybeExclude
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ";
    if (!$stmt = $conn->prepare($sql)) { echo json_encode(['error' => 'Query prepare failed']); exit; }
    $stmt->bind_param('is', $user_id, $user_role);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = ['new_notifications' => 0];
    if ($res && $res->num_rows) {
        $n = $res->fetch_assoc();
        if ($upd = $conn->prepare("UPDATE notifications SET is_sent = 1 WHERE id = ?")) {
            $upd->bind_param('i', $n['id']); $upd->execute();
        }
        $out['new_notifications']   = 1;
        $out['latest_notification'] = $n;
    }
    echo json_encode($out); exit;
}

/* ---------- LIST ---------- */
if ($mode === 'list') {
    $list_sql = "
        SELECT id, type, title, message, is_read, created_at
        FROM notifications
        WHERE (
                (user_id = ? AND (target_user_type IS NULL OR target_user_type = ''))
                OR target_user_type = ?
                OR target_user_type = 'all'
              )
          $maybeExclude
        ORDER BY created_at DESC, id DESC
        LIMIT 100
    ";
    if (!$list_stmt = $conn->prepare($list_sql)) { echo json_encode(['error'=>'Query prepare failed']); exit; }
    $list_stmt->bind_param('is', $user_id, $user_role);
    $list_stmt->execute();
    $rows = $list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $count_sql = "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE (
                (user_id = ? AND (target_user_type IS NULL OR target_user_type = ''))
                OR target_user_type = ?
                OR target_user_type = 'all'
              )
          AND is_read = 0
          $maybeExclude
    ";
    if (!$count_stmt = $conn->prepare($count_sql)) { echo json_encode(['error'=>'Count prepare failed']); exit; }
    $count_stmt->bind_param('is', $user_id, $user_role);
    $count_stmt->execute();
    $unread = (int)$count_stmt->get_result()->fetch_assoc()['total'];

    echo json_encode(['unread_count'=>$unread, 'notifications'=>$rows]); exit;
}

echo json_encode(['error' => 'Invalid mode']);
