<?php
    session_start();
    include 'dbconnect.php';

    require '../PHPMailer/PHPMailer.php';
    require '../PHPMailer/SMTP.php';
    require '../PHPMailer/Exception.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    if (!isset($_SESSION['user_email'])) {
        $_SESSION['error'] = "Session expired. Please start the password reset again.";
        header("Location: ../forgotpassword.php");
        exit;
    }

    if (isset($_SESSION['otp_sent_time']) && (time() - $_SESSION['otp_sent_time']) < 120) {
        $remaining = 120 - (time() - $_SESSION['otp_sent_time']);
        $_SESSION['error'] = "Please wait {$remaining} seconds before resending the code.";
        header("Location: ../verifypassword.php");
        exit;
    }

    $email = $_SESSION['user_email'];
    $newOtp = rand(100000, 999999);

    $_SESSION['verification_code'] = $newOtp;
    $_SESSION['otp_sent_time'] = time();

    // Send Email
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = (require __DIR__ . '/mail_config.php')['username']; 
        $mail->Password = (require __DIR__ . '/mail_config.php')['password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('fitnessremer@gmail.com', 'Remer Fitness Gym');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'New OTP for Password Reset';
        $mail->Body = "
            <h2>New Verification Code</h2>
            <p>Your new verification code is:</p>
            <h1 style='color:#007bff;'>$newOtp</h1>
            <p>Please use this to continue resetting your password.</p>
            <hr>
            <small>If you did not request this, you can ignore the email.</small>
        ";

        $mail->send();
        $_SESSION['success'] = "A new OTP was sent to your email.";
    } 
    catch (Exception $e) {
        $_SESSION['error'] = "Failed to resend OTP: {$mail->ErrorInfo}";
    }

    header("Location: ../verifypassword.php");
    exit;
?>