<?php
session_start();
require_once '../Actions/dbconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
} else {
    echo json_encode(['error' => 'Failed to update']);
}
?>