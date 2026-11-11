<?php
session_start();
include 'dbconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn, "
    SELECT SUM(quantity) AS total_items 
    FROM cart 
    WHERE user_id = '$user_id' AND status = 'On Cart'
");
$data = mysqli_fetch_assoc($query);
$count = (int) $data['total_items'];

// If cart is empty, ensure count is 0
if ($count <= 0) {
    $count = 0;
}

echo json_encode(['count' => $count]);
exit;
?>
