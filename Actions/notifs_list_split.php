<?php
/**
 * Unified list for ALL roles. Returns:
 * {
 *   unread_count: int,
 *   unread: [rows...],
 *   read:   [rows...]
 * }
 *
 * Visibility rule:
 *  - Personal: (user_id = me AND (target_user_type IS NULL OR ''))
 *  - Role-targeted: target_user_type IN (my role, 'all')
 *  - If Admin: excludes login/logout types (as requested)
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated']); exit; }

$user_id = (int)($_SESSION['user_id']);
$role    = trim($_SESSION['role'] ?? '');

$excludeForAdmin = (strcasecmp($role,'Admin')===0)
  ? " AND LOWER(type) NOT IN ('login','logout','user_login','user_logout') "
  : "";

$sql = "
  SELECT id, user_id, type, title, message, is_read, created_at, target_user_type
  FROM notifications
  WHERE
    (
      (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
      OR LOWER(TRIM(target_user_type)) = LOWER(?)
      OR LOWER(TRIM(target_user_type)) = 'all'
    )
  $excludeForAdmin
  ORDER BY created_at DESC, id DESC
  LIMIT 300
";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'Prepare failed']); exit; }
$stmt->bind_param('is', $user_id, $role);
$stmt->execute();
$res = $stmt->get_result();

$unread = [];
$read   = [];
while ($row = $res->fetch_assoc()) {
  if ((int)$row['is_read'] === 1) $read[] = $row;
  else                            $unread[] = $row;
}
$stmt->close();

echo json_encode([
  'unread_count' => count($unread),
  'unread'       => $unread,
  'read'         => $read
]);
