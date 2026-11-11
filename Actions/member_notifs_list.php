<?php
// Returns: { unread_count:int, unread:[...], read:[...] }
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
  SELECT id, user_id, type, title, message, is_read, created_at, target_user_type
  FROM notifications
  WHERE
    (
      (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
      OR LOWER(TRIM(target_user_type)) = 'member'
      OR LOWER(TRIM(target_user_type)) = 'all'
    )
  ORDER BY created_at DESC, id DESC
  LIMIT 200
";

$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'Prepare failed']); exit; }
$stmt->bind_param('i', $user_id);
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
