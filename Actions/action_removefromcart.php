<?php
session_start();
include 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    // Option 1: PERMANENTLY delete the item from the cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Item removed from cart successfully.";
    } else {
        $_SESSION['error'] = "Failed to remove item from cart.";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../view-cart.php");
exit;
?>
