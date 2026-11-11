<?php
session_start();
include 'dbconnect.php';

if (isset($_POST['cart_id'], $_POST['new_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $new_quantity = (int)$_POST['new_quantity'];

    if ($new_quantity <= 0) {
        // 🗑️ Remove the product if quantity is 0
        $delete_query = "DELETE FROM cart WHERE cart_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $cart_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Product removed from cart.";
    } else {
        // 🔁 Otherwise, just update quantity
        $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ii', $new_quantity, $cart_id);
        $stmt->execute();
        $stmt->close();

    }
}

header("Location: ../view-cart.php");
exit();
?>
