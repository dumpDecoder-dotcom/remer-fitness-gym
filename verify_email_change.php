<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in first.";
    header("Location: login.php");
    exit;
}

// Use session email for verification
$email = $_SESSION['reg_otp_email'] ?? '';
$uid   = $_SESSION['reg_otp_user_id'] ?? 0;
$otp_session = $_SESSION['reg_otp'] ?? '';
$expires_at  = $_SESSION['reg_otp_expires_at'] ?? 0;

// If user submitted the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_email = trim($_POST['email'] ?? '');
    $input_otp   = preg_replace('/\D/', '', $_POST['otp'] ?? '');

    // Basic validations
    if (!$otp_session || !$uid || !$expires_at) {
        $_SESSION['error'] = "No pending email verification. Please change your email again.";
        header("Location: my-profile.php");
        exit;
    }

    if ($input_email !== $email) {
        $_SESSION['error'] = "Email mismatch. Please use the same email you just updated.";
        header("Location: verify_email_change.php");
        exit;
    }

    if (time() > $expires_at) {
        $_SESSION['error'] = "Verification code expired. Please resend a new code.";
        header("Location: my-profile.php");
        exit;
    }

    if ((string)$input_otp !== (string)$otp_session) {
        $_SESSION['error'] = "Incorrect code. Please try again.";
        header("Location: verify_email_change.php");
        exit;
    }

    // Success: mark email as verified
    $stmt = $conn->prepare("UPDATE user SET is_email_verified=1 WHERE user_id=?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();

    // Clear OTP session
    unset($_SESSION['reg_otp'], $_SESSION['reg_otp_email'], $_SESSION['reg_otp_user_id'], $_SESSION['reg_otp_expires_at']);

    $_SESSION['success'] = "Your new email has been verified!";
    header("Location: my-profile.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify Your New Email</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container" style="max-width:520px;margin:3rem auto;">
  <h2 class="mb-3">Verify Your New Email</h2>

  <?php if (!empty($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <form method="post" class="card card-body">
    <label>Email</label>
    <input name="email" type="email" required class="form-control" value="<?=htmlspecialchars($email)?>">
    <label class="mt-3">6-digit code</label>
    <input name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" required class="form-control" placeholder="Enter code">
    <button class="btn btn-primary mt-3" type="submit">Verify</button>
</form>


  <form class="mt-3 text-center" method="post" action="resend_email_change_code.php">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
    <button class="btn btn-link" type="submit">Resend code</button>
</form>

</main>
</body>
</html>
