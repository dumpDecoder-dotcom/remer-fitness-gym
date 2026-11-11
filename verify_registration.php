<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

/** Check if a column exists using INFORMATION_SCHEMA (preparable) */
function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    $sql = "
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ";
    if (!$stmt = $conn->prepare($sql)) { return false; }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = ($stmt->num_rows > 0);
    $stmt->close();
    return $exists;
}

$emailFromQuery = $_GET['email'] ?? '';
$emailSession   = $_SESSION['reg_otp_email'] ?? '';
$email = $emailFromQuery ?: $emailSession;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code  = preg_replace('/\D/', '', $_POST['otp'] ?? '');

    if (!isset($_SESSION['reg_otp'], $_SESSION['reg_otp_user_id'], $_SESSION['reg_otp_expires_at'])) {
        $_SESSION['error'] = "No pending verification found. Please register again.";
        header("Location: register.php"); exit;
    }
    if ($email !== ($_SESSION['reg_otp_email'] ?? '')) {
        $_SESSION['error'] = "Email mismatch for verification.";
        header("Location: verify_registration.php?email=" . urlencode($email)); exit;
    }
    if (time() > ($_SESSION['reg_otp_expires_at'] ?? 0)) {
        $_SESSION['error'] = "Code expired. Please resend a new code.";
        header("Location: verify_registration.php?email=" . urlencode($email)); exit;
    }
    if ($code !== ($_SESSION['reg_otp'] ?? '')) {
        $_SESSION['error'] = "Incorrect code. Please try again.";
        header("Location: verify_registration.php?email=" . urlencode($email)); exit;
    }

    // Success: activate account (+ set is_email_verified if column exists)
    $uid = (int)($_SESSION['reg_otp_user_id']);

    $stmt = $conn->prepare("UPDATE user SET status='Active' WHERE user_id=?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();

    if (tableHasColumn($conn, 'user', 'is_email_verified')) {
        $stmt = $conn->prepare("UPDATE user SET is_email_verified=1 WHERE user_id=?");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
    }

    // Clear session OTP
    unset($_SESSION['reg_otp'], $_SESSION['reg_otp_email'], $_SESSION['reg_otp_user_id'], $_SESSION['reg_otp_expires_at']);

    $_SESSION['success'] = "Your email has been verified. You can log in now.";
    header("Location: login.php"); exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verify your email</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container" style="max-width:520px;margin:3rem auto;">
    <h2 class="mb-3">Verify your email</h2>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?=htmlspecialchars($_SESSION['error']); unset($_SESSION['error']);?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success"><?=htmlspecialchars($_SESSION['success']); unset($_SESSION['success']);?></div>
    <?php endif; ?>

    <form method="post" class="card card-body">
      <label>Email</label>
      <input name="email" type="email" required class="form-control" value="<?=htmlspecialchars($email)?>">
      <label class="mt-3">6-digit code</label>
      <input name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" required class="form-control" placeholder="Enter code">
      <button class="btn btn-primary mt-3" type="submit">Verify</button>
    </form>

    <form class="mt-3 text-center" method="post" action="resend_reg_code.php">
      <input type="hidden" name="email" value="<?=htmlspecialchars($email)?>">
      <button class="btn btn-link" type="submit">Resend code</button>
    </form>
  </main>
</body>
</html>
