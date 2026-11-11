<?php
session_start();
include 'dbconnect.php';
require_once '../notification.php';

if (isset($_POST['submit'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $category     = mysqli_real_escape_string($conn, $_POST['category']);
    $subcategory  = mysqli_real_escape_string($conn, $_POST['subcategory']);
    $price        = intval($_POST['price']);
    $quantity     = intval($_POST['quantity']);

    // ✅ Handle Image Upload
    $upload_dir = "../uploads/products/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Allowed extensions
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['error'] = "Invalid image format. Please upload JPG, PNG, or WEBP.";
            header("Location: ../add-product.php");
            exit;
        }

        // Rename image uniquely
        $new_filename = uniqid('prod_', true) . '.' . $file_ext;
        $target_path  = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $target_path)) {
            $image_path = "uploads/products/" . $new_filename; // relative path for database
        } else {
            $_SESSION['error'] = "Image upload failed.";
            header("Location: ../add-product.php");
            exit;
        }
    }

    // ✅ Insert product into database (include image)
    $insert_query = "
        INSERT INTO product (product_name, description, category, subcategory, price, quantity, image)
        VALUES ('$product_name', '$description', '$category', '$subcategory', $price, $quantity, '$image_path')
    ";

    if (mysqli_query($conn, $insert_query)) {
        $product_id = mysqli_insert_id($conn);

        // 🔔 Send notification to all members
        try {
            $notif = new Notification($conn);
            $title = "🎉 New Product Available!";
            $message = "Check out our new {$category} product: {$product_name}! Only ₱" . number_format($price, 2);
            $sent_count = $notif->broadcastToUserType('member', 'product', $title, $message);

            $_SESSION['success'] = "Product added successfully! {$sent_count} members notified.";
        } catch (Exception $e) {
            $_SESSION['success'] = "Product added successfully, but notification failed.";
            error_log("Notification Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['error'] = "Failed to add product: " . mysqli_error($conn);
    }

    header("Location: ../product-list.php");
    exit;
}
?>
