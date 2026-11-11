<?php
session_start();
require_once __DIR__ . '/Actions/dbconnect.php';

// ---- PHPMailer
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = (require __DIR__ . '/Actions/mail_config.php')['username'];  // <-- change
        $mail->Password   = (require __DIR__ . '/Actions/mail_config.php')['password'];        // <-- change
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('YOUR_GMAIL_ADDRESS@gmail.com', 'Remer Fitness Gym'); // <-- change
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your new verification code';
        $mail->Body = "<h2>New Verification Code</h2>
                       <h1 style='letter-spacing:4px;'>$otp</h1>
                       <p>Valid for 10 minutes.</p>";
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: register.php"); exit; }

$email = trim($_POST['email'] ?? '');
if ($email !== ($_SESSION['reg_otp_email'] ?? '')) {
    $_SESSION['error'] = "No pending verification for that email.";
    header("Location: verify_registration.php?email=" . urlencode($email)); exit;
}

// throttle: 120 seconds
$last = $_SESSION['reg_otp_sent_at'] ?? 0;
if ((time() - $last) < 120) {
    $remaining = 120 - (time() - $last);
    $_SESSION['error'] = "Please wait {$remaining} seconds before resending.";
    header("Location: verify_registration.php?email=" . urlencode($email)); exit;
}

// new OTP
$otp = (string) random_int(100000, 999999);
$_SESSION['reg_otp']             = $otp;
$_SESSION['reg_otp_sent_at']     = time();
$_SESSION['reg_otp_expires_at']  = time() + 600; // 10 minutes

if (sendVerificationEmail($email, $otp)) {
    $_SESSION['success'] = "A new code was sent to your email.";
} else {
    $_SESSION['error'] = "Failed to send a new code. Try again.";
}

header("Location: verify_registration.php?email=" . urlencode($email));
exit;
