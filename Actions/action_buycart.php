<?php
session_start();
require_once __DIR__ . '/dbconnect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

function go($p){ header("Location: $p"); exit; }

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'Member')) {
    $_SESSION['error'] = 'Please log in first.';
    go('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    $_SESSION['error'] = 'Invalid request.';
    go('../view-cart.php');
}

$uid     = (int)$_SESSION['user_id'];
$payment = isset($_POST['payment']) ? (float)$_POST['payment'] : 0.0;
if ($payment <= 0) { $_SESSION['error'] = 'Please enter payment amount.'; go('../view-cart.php'); }

/* ---------- Load cart items (original prices) ---------- */
$sql = "
  SELECT c.cart_id, c.product_id, c.quantity AS qty,
         p.product_name, p.price
  FROM cart c
  JOIN product p ON p.product_id = c.product_id
  WHERE c.user_id = ? AND c.status = 'On Cart'
";
$items = [];
$subtotal = 0.0;

if ($st = $conn->prepare($sql)) {
    $st->bind_param('i', $uid);
    $st->execute();
    $res = $st->get_result();
    if (!$res || !$res->num_rows) {
        $_SESSION['error'] = 'Your cart is empty.';
        go('../view-cart.php');
    }
    while ($r = $res->fetch_assoc()) {
        $qty  = (int)$r['qty'];
        if ($qty <= 0) continue;
        $price = (float)$r['price'];                // ORIGINAL price
        $line  = $qty * $price;

        $items[] = [
            'cart_id'    => (int)$r['cart_id'],
            'product_id' => (int)$r['product_id'],
            'name'       => $r['product_name'],
            'qty'        => $qty,
            'unit_price' => $price,                 // save original for receipt
            'line_total' => $line,
        ];
        $subtotal += $line;
    }
    $st->close();
} else {
    $_SESSION['error'] = 'Could not load cart items.';
    go('../view-cart.php');
}

/* ---------- Promo (from session; fallback to POST) ---------- */
$promo = ['title'=>null,'dtype'=>null,'dval'=>0.0];
if (!empty($_SESSION['applied_promo'])) {
    $ap = $_SESSION['applied_promo'];
    $promo['title'] = $ap['title'] ?? 'Promo';
    $promo['dtype'] = strtolower($ap['dtype'] ?? 'percent');
    $promo['dval']  = (float)($ap['dval'] ?? 0);
} elseif (!empty($_POST['promo_dtype']) && isset($_POST['promo_dval'])) {
    $promo['title'] = $_POST['promo_title'] ?? 'Promo';
    $promo['dtype'] = strtolower($_POST['promo_dtype']);
    $promo['dval']  = (float)$_POST['promo_dval'];
}

/* ---------- Discount & total ---------- */
$discount = 0.0;
if ($promo['dtype'] === 'fixed' && $promo['dval'] > 0) {
    $discount = min($subtotal, $promo['dval']);
} elseif ($promo['dtype'] === 'percent' && $promo['dval'] > 0) {
    // accept 0–1 or 0–100
    $rate = ($promo['dval'] <= 1.0) ? $promo['dval'] : ($promo['dval'] / 100.0);
    $discount = $subtotal * $rate;
}
$discount = round($discount, 2);
$total    = max($subtotal - $discount, 0.0);

/* ---------- Validate payment vs discounted total ---------- */
if ($payment < $total) {
    $_SESSION['error'] = 'Payment must be at least ₱' . number_format($total, 2);
    go('../view-cart.php');
}
$change = round($payment - $total, 2);

/* ---------- Create transaction ---------- */
$txn_sql = "INSERT INTO transactions
    (user_id, total_amount, transaction_type, status, payment_method, created_at)
    VALUES (?, ?, 'item', 'pending_verification', 'Cash', NOW())";

if (!($ins = $conn->prepare($txn_sql))) {
    $_SESSION['error'] = 'Could not create transaction.';
    go('../view-cart.php');
}
$ins->bind_param('id', $uid, $total);
$ok = $ins->execute();
$transaction_id = $ok ? $conn->insert_id : 0;   // ← use connection insert_id
$ins->close();

if (!$ok || !$transaction_id) {
    $_SESSION['error'] = 'Could not create transaction.';
    go('../view-cart.php');
}

/* ---------- Save items (ORIGINAL unit_price), clear cart ---------- */
foreach ($items as $it) {
    if ($ti = $conn->prepare("
        INSERT INTO transaction_items (transaction_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ")) {
        $ti->bind_param('iiid', $transaction_id, $it['product_id'], $it['qty'], $it['unit_price']);
        $ti->execute();
        $ti->close();
    }
    if ($dc = $conn->prepare("UPDATE cart SET status = 'Purchased' WHERE cart_id = ?")) {
        $dc->bind_param('i', $it['cart_id']);
        $dc->execute();
        $dc->close();
    }
}

/* ---------- Prepare receipt session (SINGLE SOURCE OF TRUTH) ---------- */
$_SESSION['receipt'] = [
    'transaction_id' => $transaction_id,
    'date'           => date('Y-m-d H:i:s'),
    'subtotal'       => round($subtotal, 2),
    'discount'       => round($discount, 2),
    'total'          => round($total, 2),
    'payment'        => round($payment, 2),
    'change'         => round($change, 2),
    'promo_title'    => $promo['title'],
    'promo_dtype'    => $promo['dtype'],
    'promo_dval'     => $promo['dval'],
];

/* Clear promo so it doesn’t auto-apply next time */
unset($_SESSION['applied_promo']);

session_write_close();
go('../receipt.php?transaction_id=' . $transaction_id);
