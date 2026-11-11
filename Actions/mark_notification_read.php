<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

$mode = $_GET['mode'] ?? 'single';

if ($mode === 'all') {
    // Mark ALL notifications visible to this user as read
    $sql = "
        UPDATE notifications 
        SET is_read = 1 
        WHERE (
                (user_id = ? AND (target_user_type IS NULL OR target_user_type = ''))
                OR target_user_type = ?
                OR target_user_type = 'all'
              )
          AND is_read = 0
    ";
    if (!$stmt = $conn->prepare($sql)) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed']);
        exit;
    }
    $stmt->bind_param('is', $user_id, $user_role);
    $ok = $stmt->execute();

    echo json_encode([
        'success' => (bool)$ok,
        'updated' => $ok ? $stmt->affected_rows : 0
    ]);
    exit;
}

// Default: mark ONE notification read
$payload = json_decode(file_get_contents('php://input'), true);
$notif_id = (int)($payload['notification_id'] ?? 0);

if ($notif_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification_id']);
    exit;
}

/*
 NOTE:
 If you use broadcast rows (target_user_type='all' or role) without a user_id,
 updating is_read here will mark it read globally. If you want per-user read
 tracking for broadcasts, store one row per user, or keep a separate join table.
*/

$sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
if (!$stmt = $conn->prepare($sql)) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed']);
    exit;
}
$stmt->bind_param('ii', $notif_id, $user_id);
$ok = $stmt->execute();

echo json_encode([
    'success' => (bool)$ok,
    'updated' => $ok ? $stmt->affected_rows : 0
]);
