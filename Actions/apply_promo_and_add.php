<?php
session_start();
require_once __DIR__ . '/dbconnect.php';

/**
 * Endpoint responsibilities:
 * - Store selected promo in session (normalized)
 * - If a mapping exists (promos_products), add that product to the user's cart (qty from ?qty=…, default 1)
 * - Redirect to view-cart
 *
 * Usage:
 *   /Actions/apply_promo_and_add.php?promo_id=12&title=July%20Sale&type=product&dtype=percent&dval=50&end=2025-07-31
 *   Optionally: &product_id=45&qty=2  (overrides mapping for this click)
 */

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Member') {
  header('Location: ../login.php'); exit;
}
$user_id = (int)$_SESSION['user_id'];

/* ---------- helpers ---------- */
function normalize_percent($v){
  $v = (float)$v;
  if ($v < 0) $v = 0;
  return ($v <= 1.0) ? ($v * 100.0) : $v; // accept 0.5 or 50
}
function money_clean($v){
  $v = (float)$v;
  return $v < 0 ? 0 : $v;
}
function table_exists(mysqli $conn, string $table): bool {
  $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
  if ($st = $conn->prepare($sql)) {
    $st->bind_param('s', $table);
    $st->execute();
    $res = $st->get_result();
    $ok = ($res && $res->num_rows > 0);
    $st->close();
    return $ok;
  }
  return false;
}

/* ---------- capture promo from URL ---------- */
$promo_id = (int)($_GET['promo_id'] ?? 0);
$title    = (string)($_GET['title'] ?? '');
$type     = (string)($_GET['type']  ?? 'product');
$dtype    = strtolower((string)($_GET['dtype'] ?? 'percent')); // percent|fixed
$dval     = (float)($_GET['dval']   ?? 0);
$end      = (string)($_GET['end']   ?? '');
$op       = isset($_GET['op']) ? money_clean($_GET['op']) : null;
$np       = isset($_GET['np']) ? money_clean($_GET['np']) : null;

if ($dtype === 'percent') $dval = normalize_percent($dval);
else                      $dval = money_clean($dval);

/* store in session */
$_SESSION['applied_promo'] = [
  'promo_id' => $promo_id,
  'title'    => $title,
  'type'     => $type,
  'dtype'    => $dtype,
  'dval'     => $dval,     // percent in 0–100, or fixed money
  'end'      => $end,
  'op'       => $op,
  'np'       => $np,
  'ts'       => time(),
];

/* ---------- add mapped product to cart (optional) ---------- */
$qty = max(1, (int)($_GET['qty'] ?? 1));
$forced_product_id = (int)($_GET['product_id'] ?? 0);

$product_id = 0;

/* priority 1: explicit ?product_id= */
if ($forced_product_id > 0) {
  $product_id = $forced_product_id;
}
/* priority 2: lookup from promos_products if exists */
elseif ($promo_id > 0 && table_exists($conn, 'promos_products')) {
  if ($st = $conn->prepare("SELECT product_id FROM promos_products WHERE promo_id = ? LIMIT 1")) {
    $st->bind_param('i', $promo_id);
    if ($st->execute()) {
      $rs = $st->get_result();
      if ($rs && $row = $rs->fetch_assoc()) {
        $product_id = (int)$row['product_id'];
      }
    }
    $st->close();
  }
}

/* If we have a product, add or bump quantity in cart (status='On Cart') */
if ($product_id > 0) {
  // ensure product exists & available (optional)
  if ($st = $conn->prepare("SELECT product_id FROM product WHERE product_id = ? LIMIT 1")) {
    $st->bind_param('i', $product_id);
    $st->execute();
    $rs = $st->get_result();
    if ($rs && $rs->num_rows) {
      // already in cart?
      if ($st2 = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND status = 'On Cart' LIMIT 1")) {
        $st2->bind_param('ii', $user_id, $product_id);
        $st2->execute();
        $rs2 = $st2->get_result();
        if ($rs2 && $row2 = $rs2->fetch_assoc()) {
          $cart_id = (int)$row2['cart_id'];
          $new_q   = max(1, (int)$row2['quantity'] + $qty);
          $st2->close();
          if ($up = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?")) {
            $up->bind_param('ii', $new_q, $cart_id);
            $up->execute();
            $up->close();
          }
        } else {
          $st2->close();
          if ($ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, status) VALUES (?, ?, ?, 'On Cart')")) {
            $ins->bind_param('iii', $user_id, $product_id, $qty);
            $ins->execute();
            $ins->close();
          }
        }
      }
    }
    $st->close();
  }
}

$_SESSION['success'] = 'Promo applied' . ($product_id ? ' and item added to cart.' : '.');
header('Location: ../view-cart.php');
exit;
