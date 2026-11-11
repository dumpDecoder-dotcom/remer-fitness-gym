<?php
// Actions/member_notifs_mark_read.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/dbconnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
    echo json_encode(['success'=>false]); exit;
}
$uid = (int)$_SESSION['user_id'];

$payload = json_decode(file_get_contents('php://input'), true);
$id = (int)($payload['id'] ?? 0);

if ($id > 0) {
    $sql = "UPDATE member_notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    if ($st = $conn->prepare($sql)) {
        $st->bind_param('ii', $id, $uid);
        $st->execute();
        echo json_encode(['success'=>true]); exit;
    }
}
echo json_encode(['success'=>false]);
