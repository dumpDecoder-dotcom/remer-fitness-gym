<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// Guard: members only
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'Member')) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* --- Member info --- */
$user = null;
if ($stmtU = $conn->prepare("
    SELECT fname, lname, membership_type, membership_start, membership_end, status
    FROM `user` WHERE user_id = ?
")) {
    $stmtU->bind_param('i', $user_id);
    $stmtU->execute();
    $resU = $stmtU->get_result();
    $user  = $resU ? $resU->fetch_assoc() : null;
    $stmtU->close();
}

/* --- Payment history (NO schema-sensitive columns in SQL) --- */
$pay_rows = [];
if ($stmt = $conn->prepare("
    SELECT *
    FROM transactions
    WHERE user_id = ?
    ORDER BY transaction_id DESC
")) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        // amount: prefer total_amount, else amount if present, else 0
        if (array_key_exists('total_amount', $row) && $row['total_amount'] !== null) {
            $row['__amount__'] = (float)$row['total_amount'];
        } elseif (array_key_exists('amount', $row) && $row['amount'] !== null) {
            $row['__amount__'] = (float)$row['amount'];
        } else {
            $row['__amount__'] = 0.00;
        }

        // created_at: prefer created_at, else date, else null
        if (!empty($row['created_at'])) {
            $row['__created__'] = $row['created_at'];
        } elseif (!empty($row['date'])) {
            $row['__created__'] = $row['date'];
        } else {
            $row['__created__'] = null;
        }

        $pay_rows[] = $row;
    }
    $stmt->close();
}

/* --- Flash --- */
$success_message = $_SESSION['success'] ?? null;
$error_message   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Remer Fitness Gym</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <style>

        #qrcode{border-radius:16px;border:8px solid black;width:min(400px,100%);aspect-ratio:1/1;background:url('Assets/Graphics/qrcode_fordebug.png');background-size:cover;background-position:center}
        .payment-card{border-radius:20px;background:#fff;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
        .status-badge{display:inline-block;padding:8px 16px;border-radius:20px;font-weight:600;font-size:14px}
        .status-active{background:#d4edda;color:#155724}
        .status-inactive{background:#f8d7da;color:#721c24}

        .payment-history-box {
    max-height: 350px;      /* fixed height */
    overflow-y: auto;       /* enable vertical scrolling */
    border-radius: 10px;
    border: 1px solid #ddd;
        }

        /* Sticky table header */
        .payment-history-box thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;  /* same as table-light background */
            z-index: 10;
        }

        /* Optional: scrollbar styling */
        .payment-history-box::-webkit-scrollbar {
            width: 8px;
        }
        .payment-history-box::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.3);
            border-radius: 4px;
        }
        .payment-history-box::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0,0,0,0.5);
        }

    </style>
</head>
<body class="d-flex flex-column gap-3 bg-base pb-3">

<nav class="navbar navbar-expand-lg bg-transparent">
    <div class="container px-md-4 px-lg-5 py-1" style="border-bottom:1px solid var(--text-clr);">
        <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
            <ul class="navbar-nav ms-2">
                <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="member_shop.php">Shop</a></li>
                <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
                <li class="nav-item"><a class="nav-link" href="trackprogress.php">Track Progress</a></li>
                <li class="nav-item"><a class="nav-link active" href="member_payment.php">Payment</a></li>
                <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index:1055;width:90%;max-width:500px" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="w-100">
        <a href="#" onclick="event.preventDefault();window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width:40px;height:40px">
            <i class="ri-arrow-go-back-line"></i>
        </a>
    </div>

    <!-- Membership Status -->
    <div class="payment-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 fw-bold">💳 Membership Status</h5>
            <?php if (!empty($user) && ($user['status'] ?? '') === 'Active'): ?>
                <span class="status-badge status-active">✅ Active</span>
            <?php else: ?>
                <span class="status-badge status-inactive">❌ Inactive</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($user) && ($user['status'] ?? '') === 'Active'): ?>
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted">Membership Type</small>
                    <p class="fw-bold mb-2"><?= htmlspecialchars(ucfirst($user['membership_type'] ?? 'N/A')) ?></p>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Start Date</small>
                    <p class="fw-bold mb-2"><?= !empty($user['membership_start']) ? date('M d, Y', strtotime($user['membership_start'])) : 'N/A' ?></p>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Valid Until</small>
                    <p class="fw-bold mb-2 text-primary"><?= !empty($user['membership_end']) ? date('M d, Y', strtotime($user['membership_end'])) : 'N/A' ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-0">
                <i class="ri-alert-line"></i> Your membership is inactive. Please renew to continue using gym facilities.
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Section -->
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="payment-card text-center h-100">
                <h5 class="fw-bold mb-3">📱 Pay via GCash</h5>
                <div id="qrcode" class="mx-auto mb-3"></div>
                <p class="fw-bold fs-5 mb-2">JO*N RU***L O.</p>
                <small class="text-muted">Scan QR code with GCash app</small>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="payment-card h-100">
                <h5 class="fw-bold mb-4">💰 Submit Payment Details</h5>

                <div id="alertBox" class="alert d-none" role="alert"></div>

                <form id="paymentForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Membership Type <span class="text-danger">*</span></label>
                        <select name="membership_type" id="membershipType" class="form-select" required>
                            <option value="">-- Select Membership --</option>
                            <option value="monthly">Monthly Membership - ₱1,800</option>
                            <option value="daily_student">Daily Pass (Student) - ₱100</option>
                            <option value="daily_regular">Daily Pass (Regular) - ₱120</option>
                        </select>
                    </div>

                    <input type="hidden" name="transaction_type" value="membership">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" readonly required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="">-- Select Payment Method --</option>
                            <option value="GCash">GCash</option>
                            <option value="PayMaya">PayMaya</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash (Counter)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference Number <span class="text-danger">*</span></label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g., GC123456789" required>
                        <small class="text-muted">Enter your GCash reference number</small>
                    </div>

                   <div class="mb-4">
    <label class="form-label fw-semibold">Proof of Payment <span class="text-danger">*</span></label>
    <input type="file" name="proof_image" class="form-control" accept="image/jpeg, image/png" required>
    <small class="text-muted">Upload screenshot/receipt (Max 5MB, JPG or PNG only)</small>
</div>


                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-3 fw-bold">
                        <i class="ri-secure-payment-line"></i> Submit Payment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="payment-card mt-3">
        <h5 class="fw-bold mb-3">🧾 Payment History</h5>
        <div class="payment-history-box table-responsive">
    <table class="table table-striped align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th style="width:110px;">ID</th>
                        <th style="width:140px;">Type</th>
                        <th style="width:140px;">Status</th>
                        <th style="width:160px;">Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($pay_rows)): ?>
                    <?php foreach ($pay_rows as $pr): ?>
                        <tr>
                            <td><?= htmlspecialchars($pr['transaction_id']) ?></td>
                            <td><?= htmlspecialchars(ucfirst((string)($pr['transaction_type'] ?? ''))) ?></td>
                            <td>
                                <?php
                                  $stat  = strtolower($pr['status'] ?? '');
                                  $badge = ($stat === 'completed' || $stat === 'verified') ? 'success'
                                         : ($stat === 'pending' ? 'warning'
                                         : ($stat === 'rejected' ? 'danger' : 'secondary'));
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars(ucfirst($pr['status'] ?? '')) ?></span>
                            </td>
                            <td>₱ <?= number_format((float)$pr['__amount__'], 2) ?></td>
                            <td>
                                <?php
                                  $dt = $pr['__created__'];
                                  echo $dt ? htmlspecialchars(date('M d, Y h:i A', strtotime($dt)))
                                           : '<span class="text-muted">N/A</span>';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-muted">No payments yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- /Payment History -->

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script>
// Amount autofill per plan
document.getElementById('membershipType').addEventListener('change', function () {
  const amounts = { monthly:'1800.00', daily_student:'100.00', daily_regular:'120.00' };
  document.getElementById('amount').value = amounts[this.value] || '';
});

// Submit handler
document.getElementById('paymentForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  const submitBtn = document.getElementById('submitBtn');
  const alertBox  = document.getElementById('alertBox');

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Processing Payment...';

  fetch('Actions/action_payment.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alertBox.className = 'alert alert-success';
        alertBox.classList.remove('d-none');
        alertBox.innerHTML = '<i class="ri-checkbox-circle-line"></i> ' + (data.message || 'Payment submitted.') + ' Redirecting...';
        setTimeout(() => window.location.reload(), 1500);
      } else {
        alertBox.className = 'alert alert-danger';
        alertBox.classList.remove('d-none');
        alertBox.innerHTML = '<i class="ri-error-warning-line"></i> ' + (data.message || 'Unable to process payment.');
      }
    })
    .catch(() => {
      alertBox.className = 'alert alert-danger';
      alertBox.classList.remove('d-none');
      alertBox.innerHTML = '<i class="ri-error-warning-line"></i> An unexpected error occurred.';
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="ri-secure-payment-line"></i> Submit Payment';
    });
});
</script>
</body>
</html>
