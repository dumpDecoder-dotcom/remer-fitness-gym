<?php
    session_start();
    include 'dbconnect.php';

    if (!isset($_SESSION['user_email'])) {
        $_SESSION['error'] = "Session expired. Please try the password reset process again.";
        header("Location: ../forgotpassword.php");
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $newPassword = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        if (empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = "Please fill out both password fields.";
            header("Location: ../newpassword.php");
            exit();
        }
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: ../newpassword.php");
            exit();
        }
        if (!preg_match('/^[A-Za-z0-9]{8,20}$/', $newPassword)) {
            $_SESSION['error'] = "Password must be 8-20 characters and contain only letters and numbers.";
            header("Location: ../newpassword.php");
            exit();
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        
        $email = $_SESSION['user_email'];
        $sql = "UPDATE user SET password = '$hashedPassword' WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            unset($_SESSION['user_email']);
            $_SESSION['success'] = "Password updated successfully. You can now log in.";
            header("Location: ../login.php");
            exit;
        } 
        else {
            $_SESSION['error'] = "Something went wrong. Please try again.";
            header("Location: ../newpassword.php");
        }
    } 
    else {
        $_SESSION['error'] = "Invalid request.";
    }

    header("Location: ../newpassword.php");
    exit();
?>
