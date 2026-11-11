<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';  // <-- fixes $conn undefined

// flash helpers (optional)
function flash($key){
  if (!empty($_SESSION[$key])) {
    $msg = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $msg;
  }
  return null;
}

// Build query
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
if ($filter === 'lowstock') {
  $sql = "SELECT * FROM product WHERE quantity <= 5 AND (status IS NULL OR status <> 'Discontinued') ORDER BY quantity ASC";
} else {
  $sql = "SELECT * FROM product ORDER BY product_id DESC";
}

$result = mysqli_query($conn, $sql);
$rows   = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator | Remer Fitness Gym</title>
  <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
  <style>
    .prod-thumb{width:64px;height:64px;object-fit:cover;border-radius:8px;background:#f3f3f3}
  </style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

<nav class="navbar navbar-expand-lg bg-transparent">
  <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
        <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Products</a></li>
        <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
  <div class="w-100">
    <a href="" onclick="event.preventDefault(); window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;">
      <i class="ri-arrow-go-back-line"></i>
    </a>
  </div>

  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
    <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100" style="height: 690px">
      <div class="w-100 d-flex justify-content-between align-items-center">
        <h2 class="lh-1 m-0 display-6 fw-bold">Product List</h2>
        <div class="d-flex gap-2">
          <!-- NEW: Promos buttons -->
          <a href="promos.php" class="btn btn-info fw-semibold">
            <i class="ri-gift-line"></i> Promos
          </a>
          <a href="all-promos.php" target="_blank" class="btn btn-outline-primary fw-semibold">
            <i class="ri-external-link-line"></i> View All Promos
          </a>
          <!-- Existing -->
          <a href="product-list.php" class="btn btn-secondary fw-semibold">All Products</a>
          <a href="product-list.php?filter=lowstock" class="btn btn-warning fw-semibold">Low Stock</a>
          <a href="add-product.php" class="btn btn-primary fw-semibold">Add Product</a>
        </div>
      </div>

      <div class="w-100 flex-grow-1" style="overflow-x: auto;">
        <table class="table table-striped table-hover align-middle" style="max-width: 100%;">
          <thead>
            <tr>
              <td>Image</td>
              <td>Name</td>
              <td>Description</td>
              <td>Category</td>
              <td>Price</td>
              <td>Quantity</td>
              <td>Status</td>
              <td>Restocking & Delete</td>
            </tr>
          </thead>
          <tbody>
          <?php if ($rows): ?>
            <?php foreach ($rows as $row):
              $isDiscontinued = (isset($row['status']) && $row['status'] === "Discontinued");
              // thumbnail path if you added image_path column
              $thumb = !empty($row['image']) ? htmlspecialchars($row['image']) : 'Assets/Graphics/product-placeholder.png';
            ?>
              <tr class="<?= ($row['quantity'] <= 5 && !$isDiscontinued) ? 'table-warning' : '' ?>">
                <td><img src="<?= htmlspecialchars($thumb) ?>" class="prod-thumb" alt=""></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['category']) . " | " . htmlspecialchars($row['subcategory']) ?></td>
                <td>
                  <?php
                    $price = floatval($row['price']);
                    echo (fmod($price, 1) == 0) ? intval($price) . ' php' : number_format($price, 2) . ' php';
                  ?>
                </td>
                <td><?= (int)$row['quantity'] ?> pcs</td>
                <td>
                  <?php if ($isDiscontinued): ?>
                    <span class="fw-bold text-secondary">Discontinued</span>
                  <?php elseif ($row['quantity'] <= 0): ?>
                    <span class="fw-bold text-danger">Unavailable</span>
                  <?php elseif ($row['quantity'] <= 5): ?>
                    <span class="fw-bold text-warning">Low Stock</span>
                  <?php else: ?>
                    <span class="fw-bold text-success">Available</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($isDiscontinued): ?>
                    <div class="text-muted small mb-2">
                      <i class="ri-forbid-line"></i> Cannot restock discontinued items
                    </div>
                    <a href="Actions/action_deleteproduct.php?id=<?= (int)$row['product_id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Delete this discontinued product?');">
                       <i class="ri-delete-bin-2-line"></i> Delete
                    </a>
                  <?php else: ?>
                    <form action="Actions/action_restock.php" method="get" class="d-flex align-items-center gap-1">
                      <input type="hidden" name="id" value="<?= (int)$row['product_id'] ?>">
                      <input type="number" name="quantity" class="form-control form-control-sm text-center"
                             style="width: 70px;" placeholder="+/-" min="-999" max="999" required>
                      <button type="submit" class="btn btn-primary btn-sm fw-semibold">Apply</button>
                    </form>

                    <a href="Actions/action_restock.php?id=<?= (int)$row['product_id'] ?>&quantity=1"
                       class="btn btn-success btn-sm mt-1" title="Add 1"><i class="ri-add-line"></i></a>

                    <?php if ($row['quantity'] > 0): ?>
                      <a href="Actions/action_restock.php?id=<?= (int)$row['product_id'] ?>&quantity=-1"
                         class="btn btn-danger btn-sm mt-1" title="Subtract 1"><i class="ri-subtract-line"></i></a>
                    <?php else: ?>
                      <button class="btn btn-danger btn-sm mt-1" disabled title="Cannot subtract below zero">
                        <i class="ri-subtract-line"></i>
                      </button>
                    <?php endif; ?>

                    <a href="Actions/action_deleteproduct.php?id=<?= (int)$row['product_id'] ?>"
                       class="btn btn-danger btn-sm mt-1"
                       onclick="return confirm('Delete this product?');">
                      <i class="ri-delete-bin-2-line"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="8">No products found. Please add some to get started.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
</body>
</html>
