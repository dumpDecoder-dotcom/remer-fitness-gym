<?php
/**
 * Body JSON: { "id": number }
 * Marks ONE visible notification as read for the current user.
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated']); exit; }

$payload = json_decode(file_get_contents('php://input'), true);
$nid = isset($payload['id']) ? (int)$payload['id'] : 0;
if ($nid <= 0) { echo json_encode(['error'=>'Invalid id']); exit; }

$user_id = (int)$_SESSION['user_id'];
$role    = trim($_SESSION['role'] ?? '');

$sql = "
  UPDATE notifications
  SET is_read = 1
  WHERE id = ?
    AND (
      (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
      OR LOWER(TRIM(target_user_type)) = LOWER(?)
      OR LOWER(TRIM(target_user_type)) = 'all'
    )
  LIMIT 1
";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'Prepare failed']); exit; }
$stmt->bind_param('iis', $nid, $user_id, $role);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok]);
