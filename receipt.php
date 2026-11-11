<?php
session_start();
include 'Actions/dbconnect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Require a finished checkout with stored totals */
if (!isset($_GET['transaction_id']) || empty($_SESSION['receipt'])) {
    header("Location: view-cart.php"); exit;
}

$transaction_id = (int)$_GET['transaction_id'];
$rcpt           = $_SESSION['receipt'];   // single source of truth
$user_id        = (int)($_SESSION['user_id'] ?? 0);

/* ------------- User (for display) ------------- */
$user = ['fname'=>'','lname'=>''];
if ($user_id > 0 && ($uq = $conn->prepare("SELECT fname, lname FROM user WHERE user_id = ?"))) {
    $uq->bind_param('i', $user_id);
    $uq->execute();
    if ($res = $uq->get_result()) { if ($res->num_rows) $user = $res->fetch_assoc(); }
    $uq->close();
}

/* ------------- Items (table only; do NOT recompute totals here) ------------- */
$items = [];
if ($iq = $conn->prepare("
    SELECT p.product_name, ti.quantity, ti.unit_price
    FROM transaction_items ti
    JOIN product p ON ti.product_id = p.product_id
    WHERE ti.transaction_id = ?
")) {
    $iq->bind_param('i', $transaction_id);
    $iq->execute();
    $ires = $iq->get_result();
    while ($row = $ires->fetch_assoc()) {
        $qty   = (int)$row['quantity'];
        $price = (float)$row['unit_price']; // original unit price saved at checkout
        $items[] = [
            'product_name' => $row['product_name'],
            'quantity'     => $qty,
            'unit_price'   => $price,
            'line_total'   => $qty * $price,
        ];
    }
    $iq->close();
}

/* ------------- ONE source of truth: values saved by checkout ------------- */
$subtotal = (float)($rcpt['subtotal'] ?? 0.0);
$discount = (float)($rcpt['discount'] ?? 0.0);
$total    = (float)($rcpt['total']    ?? max($subtotal - $discount, 0.0));
$payment  = (float)($rcpt['payment']  ?? 0.0);
$change   = (float)($rcpt['change']   ?? max($payment - $total, 0.0));

/* Optional promo label (display only) */
$promo_label = null;
if (!empty($rcpt['promo_title']) && !empty($rcpt['promo_dtype']) && isset($rcpt['promo_dval']) && $discount > 0) {
    $title = $rcpt['promo_title'];
    $dtype = strtolower($rcpt['promo_dtype']);
    $dval  = (float)$rcpt['promo_dval'];
    if ($dtype === 'percent') {
        $pct = ($dval <= 1.0) ? ($dval * 100.0) : $dval;
        $pct = rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.');
        $promo_label = $title . " ({$pct}% OFF)";
    } elseif ($dtype === 'fixed') {
        $promo_label = $title . " (₱" . number_format($dval, 2) . " OFF)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt - Remer Fitness Gym</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f7f7f7; }
  .receipt-container{ background:#fff; padding:30px; border-radius:15px; max-width:600px; margin:50px auto; box-shadow:0 0 10px rgba(0,0,0,0.1); }
  .receipt-header{ text-align:center; border-bottom:2px solid #000; margin-bottom:20px; }
  .receipt-header h2{ font-weight:bold; }
  .totals .row{ margin-bottom:.35rem; }
  .totals .label{ font-weight:600; }
  .totals .value{ min-width:160px; text-align:right; }
  .grand{ font-size:1.25rem; font-weight:700; border-top:2px solid #000; padding-top:.5rem; margin-top:.35rem; }
  @media print {.no-print{ display:none; }}
</style>
</head>
<body>
<div class="receipt-container">
  <div class="receipt-header">
    <h2>Remer Fitness Gym</h2>
    <p><strong>Transaction Receipt</strong></p>
  </div>

  <p><strong>Transaction ID:</strong> <?= htmlspecialchars($rcpt['transaction_id'] ?? $transaction_id) ?></p>
  <p><strong>Date:</strong> <?= htmlspecialchars($rcpt['date'] ?? date('Y-m-d H:i:s')) ?></p>
  <p><strong>Customer:</strong> <?= htmlspecialchars(($user['fname'] ?? '').' '.($user['lname'] ?? '')) ?></p>

  <table class="table table-bordered mt-3">
    <thead class="table-dark">
      <tr>
        <th>Item</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($items): foreach ($items as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['product_name']) ?></td>
          <td><?= (int)$row['quantity'] ?></td>
          <td>₱<?= number_format($row['unit_price'], 2) ?></td>
          <td>₱<?= number_format($row['line_total'], 2) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="4" class="text-center text-muted">No items found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="totals mt-3">
    <div class="row">
      <div class="col label">Subtotal:</div>
      <div class="col-auto value">₱<?= number_format($subtotal, 2) ?></div>
    </div>

    <?php if ($discount > 0): ?>
      <div class="row">
        <div class="col label text-success">
          Discount<?= $promo_label ? ' — ' . htmlspecialchars($promo_label) : '' ?>:
        </div>
        <div class="col-auto value text-success">− ₱<?= number_format($discount, 2) ?></div>
      </div>
    <?php endif; ?>

    <div class="row grand">
      <div class="col label">Total:</div>
      <div class="col-auto value">₱<?= number_format($total, 2) ?></div>
    </div>

    <div class="row mt-2">
      <div class="col label">Payment:</div>
      <div class="col-auto value">₱<?= number_format($payment, 2) ?></div>
    </div>
    <div class="row">
      <div class="col label">Change:</div>
      <div class="col-auto value">₱<?= number_format($change, 2) ?></div>
    </div>
  </div>

  <div class="text-center mt-4">
    <p class="mb-3">Thank you for your purchase!</p>
    <div class="w-100 d-flex flex-row justify-content-end align-items-center gap-2">
      <a href="member_shop.php" class="btn btn-primary m-0">Back</a>
      <button onclick="window.print()" class="btn btn-success m-0 no-print">🖨️ Print Receipt</button>
    </div>
  </div>
</div>
</body>
</html>
