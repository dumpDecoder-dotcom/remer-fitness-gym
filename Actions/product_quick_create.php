<?php
// ================================
// Actions/product_quick_create.php
// ================================
session_start();
require_once __DIR__ . '/dbconnect.php';

// Gate
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'Admin')) {
  $_SESSION['error'] = "Not authorized.";
  header('Location: ../product-list.php'); exit;
}

// Gather
$name   = trim($_POST['name'] ?? '');
$cat    = trim($_POST['category'] ?? '');
$status = $_POST['status'] ?? 'available';
$price  = isset($_POST['price']) ? floatval($_POST['price']) : null;
$stock  = isset($_POST['stock']) ? intval($_POST['stock']) : null;
$low    = (isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '')
            ? intval($_POST['low_stock_threshold']) : null;
$imgUrl = trim($_POST['image_url'] ?? '');
$desc   = trim($_POST['description'] ?? '');

// Validate
if ($name === '' || $price === null || $stock === null) {
  $_SESSION['error'] = "Please fill in required fields.";
  header('Location: ../product-list.php'); exit;
}

// Image (URL vs Upload)
$finalImage = $imgUrl;

// Handle file upload if present
if (!empty($_FILES['image_file']['name'])) {
  $up = $_FILES['image_file'];
  if ($up['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($up['name'], PATHINFO_EXTENSION));
    $safe = 'prod_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $destAbs = __DIR__ . '/../uploads/' . $safe;
    $destRel = 'uploads/' . $safe; // public path used in HTML
    if (!is_dir(__DIR__ . '/../uploads')) { @mkdir(__DIR__ . '/../uploads', 0775, true); }
    if (move_uploaded_file($up['tmp_name'], $destAbs)) {
      $finalImage = $destRel;
    }
  }
}

/*
Ensure your products table has columns (adjust as needed):
  products(
    id INT PK AI,
    name VARCHAR(255),
    category VARCHAR(100) NULL,
    price DECIMAL(10,2),
    stock INT,
    low_stock_threshold INT NULL,
    status ENUM('available','unavailable','discontinued') DEFAULT 'available',
    image VARCHAR(500) NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )
*/

$sql = "INSERT INTO products
        (name, category, price, stock, low_stock_threshold, status, image, description, created_at)
        VALUES (?,?,?,?,?,?,?,?,NOW())";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  $_SESSION['error'] = "DB error (prepare)."; header('Location: ../product-list.php'); exit;
}
$stmt->bind_param(
  'ssdiisss',
  $name, $cat, $price, $stock, $low, $status, $finalImage, $desc
);

if ($stmt->execute()) {
  $_SESSION['success'] = "Product “{$name}” added.";
} else {
  $_SESSION['error'] = "Failed to save product.";
}
$stmt->close();

header('Location: ../product-list.php'); // match your list filename
exit;
