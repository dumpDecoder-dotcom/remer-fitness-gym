<?php
// Body JSON: { "id": number }
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$nid = isset($payload['id']) ? (int)$payload['id'] : 0;
if ($nid <= 0) { echo json_encode(['error'=>'Invalid id']); exit; }

$user_id = (int)$_SESSION['user_id'];

$sql = "
  UPDATE notifications
  SET is_read = 1
  WHERE id = ?
    AND (
      (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
      OR LOWER(TRIM(target_user_type)) = 'member'
      OR LOWER(TRIM(target_user_type)) = 'all'
    )
";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'Prepare failed']); exit; }
$stmt->bind_param('ii', $nid, $user_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok]);
