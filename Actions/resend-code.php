<?php
session_start();
include 'dbconnect.php';
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pending_email'])) {
    header("Location: ../my-profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$new_email = $_SESSION['pending_email'];

// Generate new code
$verification_code = random_int(100000, 999999);

// Delete old record and insert new one
$conn->query("DELETE FROM email_verification WHERE user_id = $user_id");
$conn->query("INSERT INTO email_verification (user_id, new_email, code, created_at)
              VALUES ($user_id, '$new_email', '$verification_code', NOW())");

// Send email again
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = (require __DIR__ . '/mail_config.php')['username']; // replace
    $mail->Password = (require __DIR__ . '/mail_config.php')['password'];   // Gmail app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('yourgmail@gmail.com', 'Remer Fitness Gym');
    $mail->addAddress($new_email);
    $mail->isHTML(true);
    $mail->Subject = 'Your New Verification Code';
    $mail->Body = "
        <p>Hi,</p>
        <p>Here’s your new verification code for changing your email:</p>
        <h2>$verification_code</h2>
        <p>This code will expire in 10 minutes.</p>
    ";

    $mail->send();

    $_SESSION['success'] = "A new verification code was sent to your email.";
    header("Location: ../verify-email.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = "Failed to resend verification email. Try again.";
    header("Location: ../verify-email.php");
    exit();
}
?>
