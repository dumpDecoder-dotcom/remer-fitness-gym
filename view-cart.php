<?php 
session_start();
include 'Actions/dbconnect.php';

/* -------------------------------------------
   Helpers for money & percent
-------------------------------------------- */
function normalize_percent($v){
  $v = (float)$v;
  if ($v < 0) $v = 0;
  return ($v <= 1.0) ? ($v * 100.0) : $v;
}
function format_percent_text($v){
  $v = normalize_percent($v);
  $s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
  return $s . '% OFF';
}
function money2($v){ return number_format((float)$v, 2); }

/* -------------------------------------------
   APPLY PROMO (from URL) -> store in session
   Supports optional pid=<product_id> to bind.
-------------------------------------------- */
if (isset($_GET['apply_promo'])) {
  $dtype = (string)($_GET['dtype'] ?? 'percent');
  $dval  = (float)($_GET['dval'] ?? 0);

  if (strtolower($dtype) === 'percent') {
    $dval = normalize_percent($dval); // ensure 0–100
  }

  $_SESSION['applied_promo'] = [
    'promo_id' => (int)($_GET['promo_id'] ?? 0),
    'title'    => (string)($_GET['title'] ?? ''),
    'type'     => (string)($_GET['type'] ?? 'product'),
    'dtype'    => $dtype,
    'dval'     => $dval,
    'end'      => (string)($_GET['end'] ?? ''),
    'op'       => isset($_GET['op']) ? (float)$_GET['op'] : null,
    'np'       => isset($_GET['np']) ? (float)$_GET['np'] : null,
    'pid'      => isset($_GET['pid']) ? (int)$_GET['pid'] : null, // <— bind target product
    'ts'       => time(),
  ];
  $_SESSION['success'] = "Promo applied.";
  header('Location: view-cart.php');
  exit;
}

/* -------------------------------------------
   REMOVE PROMO
-------------------------------------------- */
if (isset($_POST['remove_promo']) || isset($_GET['remove_promo'])) {
  unset($_SESSION['applied_promo']);
  $_SESSION['success'] = "Promo removed.";
  header('Location: view-cart.php');
  exit;
}

$activePromo = $_SESSION['applied_promo'] ?? null;

/* ------------ flash helpers --------------- */
function flash($k){
  if(!empty($_SESSION[$k])){
    $m = $_SESSION[$k]; unset($_SESSION[$k]); return $m;
  }
  return null;
}
$success = flash('success');
$error   = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Member | Remer Fitness Gym</title>
<link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
<style>
  .promo-banner{border-left:6px solid #0f5132}
  .quantity-controls button{min-width:30px;height:30px;display:flex;align-items:center;justify-content:center}
</style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

<?php if ($success): ?>
  <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px" role="alert">
    <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px" role="alert">
    <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg bg-transparent">
  <div class="container px-md-4 px-lg-5 py-1" style="border-bottom:1px solid var(--text-clr);">
    <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" class="logo-img" alt="Logo"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
      <ul class="navbar-nav ms-2">
        <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Home</a></li>
        <li class="nav-item"><a class="nav-link active" href="member_shop.php">Shop</a></li>
        <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Track Progress</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Payment</a></li>
        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
  <div class="w-100">
    <a href="member_shop.php" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width:40px;height:40px;"><i class="ri-arrow-go-back-line"></i></a>
  </div>

  <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
    <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100" style="height:690px">
      <div class="w-100 d-flex justify-content-between align-items-center">
        <h2 class="lh-1 m-0 display-6 fw-bold">Your Cart</h2>
      </div>

      <?php if ($activePromo): ?>
        <div class="alert alert-success promo-banner d-flex align-items-center justify-content-between">
          <div>
            <strong>Promo applied:</strong>
            <?= htmlspecialchars($activePromo['title'] ?: 'Promo') ?> —
            <?php
              if (strtolower($activePromo['dtype']) === 'fixed') {
                $txt = '₱' . money2($activePromo['dval']) . ' OFF';
              } else {
                $txt = format_percent_text($activePromo['dval']);
              }
              echo htmlspecialchars($txt);
            ?>
            <?php if(!empty($activePromo['end'])): ?>
              <small class="text-muted"> (valid until <?= htmlspecialchars($activePromo['end']) ?>)</small>
            <?php endif; ?>
          </div>
          <form method="post" class="m-0 d-inline">
            <input type="hidden" name="remove_promo" value="1">
            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
          </form>
        </div>
      <?php endif; ?>

      <div class="w-100 flex-grow-1" style="overflow-x:auto;">
        <table class="table table-striped table-hover" style="max-width:100%;">
          <thead>
            <tr>
              <td>Name</td>
              <td>Price</td>
              <td>Quantity</td>
              <td>Sub Total</td>
              <td>Actions</td>
            </tr>
          </thead>
          <tbody>
            <?php
              $uid = (int)($_SESSION['user_id'] ?? 0);
              $grand_total = 0.00;
              $cart_pids = []; // <— track product ids currently in cart

              $sql = "SELECT 
                        c.cart_id,
                        c.product_id,
                        c.quantity,
                        p.product_name AS name,
                        p.price,
                        p.quantity AS stock
                      FROM cart c
                      JOIN product p ON c.product_id = p.product_id
                      WHERE c.user_id = ? AND c.status = 'On Cart'";
              if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('i', $uid);
                $stmt->execute(); 
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    $pid  = (int)$row['product_id'];
                    $qty  = (int)$row['quantity'];
                    $price = (float)$row['price'];
                    $stock = (int)$row['stock'];
                    $subtotal = $qty * $price;
                    $grand_total += $subtotal;
                    $cart_pids[$pid] = true;

                    $canAdd = $qty < $stock;
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($row['name']) ?></td>
                      <td><?= money2($price) ?> php</td>
                      <td>
                        <div class="d-flex align-items-center justify-content-start gap-2 quantity-controls">
                          <!-- Decrease -->
                          <form action="Actions/action_updatecart.php" method="POST" class="d-inline">
                            <input type="hidden" name="cart_id" value="<?= (int)$row['cart_id'] ?>">
                            <input type="hidden" name="new_quantity" value="<?= max(0, $qty - 1) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1"
                              onclick="return <?= $qty === 1 ? 'confirm(\'Remove this item from cart?\')' : 'true' ?>;">
                              <i class="ri-subtract-line"></i>
                            </button>
                          </form>

                          <span class="fw-bold"><?= $qty ?> pcs</span>

                          <!-- Increase -->
                          <form action="Actions/action_updatecart.php" method="POST" class="d-inline">
                            <input type="hidden" name="cart_id" value="<?= (int)$row['cart_id'] ?>">
                            <input type="hidden" name="new_quantity" value="<?= $qty + 1 ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1"
                              <?= $canAdd ? '' : 'disabled title="Out of stock or max quantity reached"' ?>>
                              <i class="ri-add-line"></i>
                            </button>
                          </form>
                        </div>
                        <?php if (!$canAdd): ?>
                          <small class="text-danger">Max stock reached (<?= $stock ?> pcs)</small>
                        <?php endif; ?>
                      </td>
                      <td><?= money2($subtotal) ?> php</td>
                      <td>
                        <form action="Actions/action_removefromcart.php" method="POST" class="d-inline">
                          <input type="hidden" name="cart_id" value="<?= (int)$row['cart_id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm"><i class="ri-delete-bin-line"></i></button>
                        </form>
                      </td>
                    </tr>
                  <?php }
                } else { ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">No products found.</td></tr>
                <?php }
                $stmt->close();
              } else {
                echo '<tr><td colspan="5" class="text-danger">Query error.</td></tr>';
              }

              /* ---------- Auto-remove promo if not applicable ---------- */
              if ($activePromo) {
                $boundPid = $activePromo['pid'] ?? null;

                // 1) If cart is empty, drop promo
                if ($grand_total <= 0) {
                  unset($_SESSION['applied_promo']);
                  $activePromo = null;
                  $_SESSION['success'] = 'Promo removed (cart is empty).';
                }
                // 2) If promo was bound to a product and that product is no longer in cart, drop promo
                elseif ($boundPid && empty($cart_pids[(int)$boundPid])) {
                  unset($_SESSION['applied_promo']);
                  $activePromo = null;
                  $_SESSION['success'] = 'Promo removed (item no longer in cart).';
                }
              }

              /* -------- totals with promo ---------- */
              $discount_amount = 0.00;
              if ($activePromo) {
                if (strtolower($activePromo['dtype']) === 'fixed') {
                  $discount_amount = min($grand_total, (float)$activePromo['dval']);
                } else {
                  $pct = normalize_percent($activePromo['dval']);
                  $discount_amount = $grand_total * ($pct / 100.0);
                }
              }
              $final_total = max($grand_total - $discount_amount, 0);
            ?>
          </tbody>
        </table>
      </div>

      <div class="w-100 d-flex flex-column justify-content-start align-items-end mt-3">
        <div class="text-end mb-3">
          <p class="fw-semibold mb-1">Subtotal: <span class="ms-3"><?= money2($grand_total) ?> php</span></p>
          <p class="fw-semibold mb-1">Discount: <span class="ms-3 text-success">- <?= money2($discount_amount) ?> php</span></p>
          <p class="fw-bold fs-2">Total: <span class="fw-normal ms-3"><?= money2($final_total) ?> php</span></p>
        </div>

        <form action="Actions/action_buycart.php" method="POST" class="d-flex flex-wrap justify-content-end align-items-center gap-2 w-100" style="max-width:500px;">
          <label for="payment" class="fw-semibold me-2">Payment:</label>
          <input type="number" name="payment" id="payment" class="form-control flex-grow-1" placeholder="Enter payment amount" step="0.01" min="0" required>

          <input type="hidden" name="cart_subtotal" value="<?= htmlspecialchars(number_format($grand_total, 2, '.', '')) ?>">
          <input type="hidden" name="cart_discount" value="<?= htmlspecialchars(number_format($discount_amount, 2, '.', '')) ?>">
          <input type="hidden" name="cart_total" value="<?= htmlspecialchars(number_format($final_total, 2, '.', '')) ?>">

          <?php if ($activePromo): ?>
            <?php
              $promo_dtype = strtolower($activePromo['dtype']);
              $promo_dval  = (float)$activePromo['dval'];
              if ($promo_dtype === 'percent') $promo_dval = normalize_percent($promo_dval);
            ?>
            <input type="hidden" name="promo_id" value="<?= (int)$activePromo['promo_id'] ?>">
            <input type="hidden" name="promo_title" value="<?= htmlspecialchars($activePromo['title']) ?>">
            <input type="hidden" name="promo_dtype" value="<?= htmlspecialchars($promo_dtype) ?>">
            <input type="hidden" name="promo_dval"  value="<?= htmlspecialchars($promo_dval) ?>">
          <?php endif; ?>

          <input type="submit" name="submit" id="submit" class="btn btn-success px-4 py-2" value="Buy">
        </form>
      </div>

    </div>  
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
