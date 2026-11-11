<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['count'=>0]); exit; }

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? ($_SESSION['user_type'] ?? '');

$EXCLUDE_FOR_ADMIN = " AND LOWER(type) NOT IN ('login','logout','user_login','user_logout') ";
$maybeExclude = (strcasecmp($user_role, 'Admin') === 0) ? $EXCLUDE_FOR_ADMIN : "";

$sql = "
    SELECT COUNT(*) AS c
    FROM notifications
    WHERE (
            (user_id = ? AND (target_user_type IS NULL OR target_user_type = ''))
            OR target_user_type = ?
            OR target_user_type = 'all'
          )
      AND is_read = 0
      $maybeExclude
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $user_role);
$stmt->execute();
$c = (int)$stmt->get_result()->fetch_assoc()['c'];

echo json_encode(['count'=>$c]);
