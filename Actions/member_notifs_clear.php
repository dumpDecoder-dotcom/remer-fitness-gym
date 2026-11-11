<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "
  DELETE FROM notifications
  WHERE
    (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
    OR LOWER(TRIM(target_user_type)) = 'member'
    OR LOWER(TRIM(target_user_type)) = 'all'
";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'Prepare failed']); exit; }
$stmt->bind_param('i', $user_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok]);
