<?php
session_start();
include 'dbconnect.php';
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = $_POST['user_id'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $age = (int)$_POST['age'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Fetch current email
    $stmt = $conn->prepare("SELECT email FROM user WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_email);
    $stmt->fetch();
    $stmt->close();

    $email_changed = ($email !== $current_email);

    // Check if email is already used by another user
    $stmt = $conn->prepare("SELECT user_id FROM user WHERE email=? AND user_id<>?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "This email is already in use by another account.";
        header("Location: ../profile.php");
        exit;
    }
    $stmt->close();

    // Update base info
    if (!empty($password) && $password === $confirm_password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET fname=?, lname=?, age=?, phone=?, email=?, password=? WHERE user_id=?");
        $stmt->bind_param("ssisssi", $fname, $lname, $age, $phone, $email, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE user SET fname=?, lname=?, age=?, phone=?, email=? WHERE user_id=?");
        $stmt->bind_param("ssissi", $fname, $lname, $age, $phone, $email, $user_id);
    }
    $stmt->execute();
    $stmt->close();

    // If email changed → send OTP
    if ($email_changed) {
        // Mark as unverified
        $conn->query("UPDATE user SET is_email_verified=0 WHERE user_id=$user_id");

        // Generate OTP
        $otp = random_int(100000, 999999);
        $_SESSION['reg_otp'] = (string)$otp;
        $_SESSION['reg_otp_email'] = $email;
        $_SESSION['reg_otp_user_id'] = $user_id;
        $_SESSION['reg_otp_expires_at'] = time() + 300; // 5 min

        // PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = (require __DIR__ . '/mail_config.php')['username']; // your Gmail
            $mail->Password   = (require __DIR__ . '/mail_config.php')['password'];       // Gmail App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('noreply@remerfitnessgym.com', 'Remer Fitness Gym');
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = 'Verify Your New Email';
            $mail->Body    = "Hello $fname,\n\nYour verification code is: $otp\n\nThis code expires in 5 minutes.";

            $mail->send();
            $_SESSION['success'] = "We sent a verification code to your new email.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send verification code: {$mail->ErrorInfo}. OTP: $otp"; // show OTP on localhost
        }

        header("Location: ../verify_email_change.php");
        exit;
    }

    $_SESSION['success'] = "Profile updated successfully.";
    header("Location: ../my-profile.php");
    exit;
}
?>
