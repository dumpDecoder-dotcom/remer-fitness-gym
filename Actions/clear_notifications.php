<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']); exit;
}

$user_id = (int)$_SESSION['user_id'];
$role    = strtolower(trim($_SESSION['role'] ?? ''));

// Delete notifications that are visible to this viewer (personal, role, all)
$sql = "
    DELETE FROM notifications
    WHERE
      (user_id = ? AND (target_user_type IS NULL OR TRIM(target_user_type) = ''))
      OR LOWER(TRIM(target_user_type)) = ?
      OR LOWER(TRIM(target_user_type)) = 'all'
";
if (!$stmt = $conn->prepare($sql)) { echo json_encode(['error' => 'Prepare failed']); exit; }
$stmt->bind_param('is', $user_id, $role);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok]);
