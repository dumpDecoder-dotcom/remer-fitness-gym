<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

function flash($key){
  if (!empty($_SESSION[$key])) { $m=$_SESSION[$key]; unset($_SESSION[$key]); return $m; }
  return null;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $title = trim($_POST['title'] ?? '');
  $promo_type = $_POST['promo_type'] ?? 'product';
  $discount_type = $_POST['discount_type'] ?? 'percent';
  $discount_value = (float)($_POST['discount_value'] ?? 0);
  $start_date = $_POST['start_date'] ?: null;
  $end_date   = $_POST['end_date'] ?: null;
  $status     = $_POST['status'] ?? 'active';
  $description = trim($_POST['description'] ?? '');

  if ($title!=='' && $discount_value>0) {
    $stmt = $conn->prepare("INSERT INTO promos(title,promo_type,discount_type,discount_value,start_date,end_date,status,description)
                            VALUES(?,?,?,?,?,?,?,?)");
    $stmt->bind_param('ssssssss', $title,$promo_type,$discount_type,$discount_value,$start_date,$end_date,$status,$description);
    $ok = $stmt->execute();
    $stmt->close();
    $_SESSION[$ok?'success':'error'] = $ok ? "Promo created." : "Failed to create promo.";
  } else {
    $_SESSION['error'] = "Please complete required fields.";
  }
  header("Location: promos.php"); exit;
}

$promos=[];
$res = $conn->query("SELECT * FROM promos ORDER BY promo_id DESC");
if ($res) $promos = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin • Promos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0"><i class="ri-gift-fill"></i> Promos (Admin)</h1>
    <div class="d-flex gap-2">
      <a href="product-list.php" class="btn btn-outline-secondary"><i class="ri-arrow-left-line"></i> Back to Products</a>
    </div>
  </div>

  <?php if($m=flash('success')): ?><div class="alert alert-success"><?=htmlspecialchars($m)?></div><?php endif; ?>
  <?php if($m=flash('error')):   ?><div class="alert alert-danger"><?=htmlspecialchars($m)?></div><?php endif; ?>

  <div class="card mb-4">
    <div class="card-header fw-bold">Create Promo</div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Title</label>
          <input name="title" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Promo Type</label>
          <select name="promo_type" class="form-select">
            <option value="product">Product</option>
            <option value="membership">Membership</option>
            <option value="class">Class</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Discount Type</label>
          <select name="discount_type" class="form-select">
            <option value="percent">Percent %</option>
            <option value="fixed">Fixed ₱</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Discount Value</label>
          <input type="number" min="0" step="0.01" name="discount_value" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-primary"><i class="ri-save-3-line"></i> Save Promo</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header fw-bold">All Promos (Source of Truth)</div>
    <div class="card-body p-0">
      <table class="table table-hover m-0">
        <thead>
          <tr>
            <th>Title</th><th>Type</th><th>Discount</th><th>Start</th><th>End</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($promos as $p): ?>
            <tr>
              <td><?=htmlspecialchars($p['title'])?></td>
              <td><?=htmlspecialchars($p['promo_type'])?></td>
              <td>
                <?php
                  if ($p['discount_type']==='percent') {
                    echo rtrim(rtrim($p['discount_value'],'0'),'.') . '%';
                  } else {
                    echo '₱' . number_format($p['discount_value'], 0);
                  }
                ?>
              </td>
              <td><?=htmlspecialchars($p['start_date'])?></td>
              <td><?=htmlspecialchars($p['end_date'])?></td>
              <td><span class="badge <?=$p['status']==='active'?'bg-success':'bg-secondary'?>"><?=htmlspecialchars($p['status'])?></span></td>
            </tr>
          <?php endforeach; if(empty($promos)): ?>
            <tr><td colspan="6" class="text-center text-muted p-4">No promos yet. Create one above.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
