<?php
session_start();
require_once __DIR__ . '/dbconnect.php';
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in first.";
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$uid = $_SESSION['user_id'];

if (!$email || $email !== ($_SESSION['reg_otp_email'] ?? '')) {
    $_SESSION['error'] = "Email mismatch or no pending verification.";
    header("Location: verify_email_change.php");
    exit;
}

// Generate new OTP
$otp = random_int(100000, 999999);
$_SESSION['reg_otp'] = (string)$otp;
$_SESSION['reg_otp_user_id'] = $uid;
$_SESSION['reg_otp_expires_at'] = time() + 300; // 5 min

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = (require __DIR__ . '/Actions/mail_config.php')['username'];
    $mail->Password   = (require __DIR__ . '/Actions/mail_config.php')['password'];
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('noreply@remerfitnessgym.com', 'Remer Fitness Gym');
    $mail->addAddress($email);

    $mail->isHTML(false);
    $mail->Subject = 'Your New Email Verification Code';
    $mail->Body    = "Hello,\n\nYour verification code is: $otp\n\nThis code expires in 5 minutes.";

    $mail->send();
    $_SESSION['success'] = "A new verification code has been sent to your email.";
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to send verification code: {$mail->ErrorInfo}. OTP: $otp"; // Show OTP for localhost
}

header("Location: verify_email_change.php");
exit;
