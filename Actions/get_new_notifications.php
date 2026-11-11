<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'notifications'=>[]]); exit; }

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');

$EXCLUDE_FOR_ADMIN = " AND LOWER(type) NOT IN ('login','logout','user_login','user_logout') ";
$maybeExclude = (strcasecmp($user_role, 'Admin') === 0) ? $EXCLUDE_FOR_ADMIN : "";

$sql = "
    SELECT id, type, title, message, is_read, created_at
    FROM notifications
    WHERE (
            (user_id = ? AND (target_user_type IS NULL OR target_user_type = ''))
            OR target_user_type = ?
            OR target_user_type = 'all'
          )
      AND (is_sent = 0 OR is_sent IS NULL)
      $maybeExclude
    ORDER BY created_at DESC, id DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $user_role);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

/* Mark sent */
if (!empty($rows)) {
    $ids = implode(',', array_map('intval', array_column($rows, 'id')));
    $conn->query("UPDATE notifications SET is_sent=1 WHERE id IN ($ids)");
}

echo json_encode(['success'=>true,'notifications'=>$rows]);
