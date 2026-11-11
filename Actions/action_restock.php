<?php
session_start();
include 'dbconnect.php';

if (isset($_GET['id']) && isset($_GET['quantity'])) {
    $product_id = intval($_GET['id']);
    $change_quantity = intval($_GET['quantity']); // can be + or -

    if ($change_quantity == 0) {
        $_SESSION['error'] = "Quantity change cannot be 0.";
        header("Location: ../product-list.php");
        exit;
    }

    // Get current quantity
    $get_query = "SELECT quantity FROM product WHERE product_id = $product_id";
    $result = mysqli_query($conn, $get_query);

    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $current_quantity = intval($row['quantity']);
        $new_quantity = $current_quantity + $change_quantity;

        // Prevent negative stock
        if ($new_quantity < 0) {
            $_SESSION['error'] = "Cannot subtract more than current stock ($current_quantity pcs).";
            header("Location: ../product-list.php");
            exit;
        }

        // Update status automatically based on new quantity
        $new_status = ($new_quantity > 0) ? 'Available' : 'Out of Stock';

        $update_query = "UPDATE product 
                         SET quantity = $new_quantity, status = '$new_status' 
                         WHERE product_id = $product_id";

        if (mysqli_query($conn, $update_query)) {
            if ($change_quantity > 0) {
                $_SESSION['success'] = "Product restocked by +" . $change_quantity . " successfully.";
            } else {
                $_SESSION['success'] = "Product stock decreased by " . abs($change_quantity) . " successfully.";
            }
        } else {
            $_SESSION['error'] = "Failed to update product quantity.";
        }
    } else {
        $_SESSION['error'] = "Product not found.";
    }

    header("Location: ../product-list.php");
    exit;
} else {
    $_SESSION['error'] = "Invalid request. Please try again.";
    header("Location: ../product-list.php");
    exit;
}
?>
