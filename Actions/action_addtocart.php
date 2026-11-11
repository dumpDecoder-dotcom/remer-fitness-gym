<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_POST['product_id'], $_POST['quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $user_id = $_POST['user_id'];

        $query = "SELECT quantity FROM product WHERE product_id = $product_id AND status != 'Out of stock'";
        $check_product = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($check_product) === 1) {
            $product = mysqli_fetch_assoc($check_product);

            if ($product['quantity'] < $quantity) {
                $_SESSION['error'] = "Insufficient stock";
                header("Location: ../member_shop.php");
                exit;
            }

            $check_cart = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id AND status = 'On Cart'");
            
            if (mysqli_num_rows($check_cart) > 0) {
                $existing = mysqli_fetch_assoc($check_cart);
                $new_quantity = $existing['quantity'] + $quantity;

                mysqli_query($conn, "UPDATE cart SET quantity = $new_quantity WHERE cart_id = {$existing['cart_id']}");
            } 
            else {
                mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity, date_added, status) 
                    VALUES ($user_id, $product_id, $quantity, NOW(), 'On Cart')");
            }

            $_SESSION['success'] = "Added to cart";
            header("Location: ../member_shop.php");
            exit;

        } else {
            $_SESSION['error'] = "Product not found or Out of stock.";
            header("Location: ../member_shop.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: ../member_shop.php");
        exit;
    }
?>
