<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_GET['id'])) {
        $product_id = intval($_GET['id']);

        $check_query = "SELECT quantity FROM product WHERE product_id = $product_id";
        $result = mysqli_query($conn, $check_query);

        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            $quantity = intval($product['quantity']);

            $new_status = ($quantity > 0) ? 'Available' : 'Out of Stock';

            $update_query = "UPDATE product SET status = '$new_status' WHERE product_id = $product_id";

            if (mysqli_query($conn, $update_query)) {
                $_SESSION['success'] = "Product restored as '$new_status'.";
            } else {
                $_SESSION['error'] = "Failed to restore product.";
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
