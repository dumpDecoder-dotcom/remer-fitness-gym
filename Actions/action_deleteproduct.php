<?php
session_start();
include 'dbconnect.php';

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    // Update product status
    $update_query = "UPDATE product SET status = 'Discontinued' WHERE product_id = $product_id";

    if (mysqli_query($conn, $update_query)) {
        // ✅ Updated message
        $_SESSION['success'] = "Product marked as Unavailable.";
    } else {
        $_SESSION['error'] = "Failed to update product status.";
    }

    header("Location: ../product-list.php");
    exit;
} else {
    $_SESSION['error'] = "Invalid request. Please try again.";
    header("Location: ../product-list.php");
    exit;
}
?>
