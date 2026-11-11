<?php
session_start();
include 'dbconnect.php';

// ---- PHPMailer (adjust if your paths differ)
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';
require __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ------------------------------
   Helpers
------------------------------ */
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}
/** Check if a column exists using INFORMATION_SCHEMA (preparable, works on MySQL/MariaDB) */
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
function sendVerificationEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);
    try {
        // === SMTP CONFIG (Gmail App Password) ===
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = (require __DIR__ . '/mail_config.php')['username'];  // <-- change
        $mail->Password   = (require __DIR__ . '/mail_config.php')['password'];        // <-- change
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('fitnessremer@gmail.com', 'Remer Fitness Gym'); // <-- change
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Verify your email (Registration OTP)';
        $mail->Body = "
            <h2>Welcome to Remer Fitness Gym</h2>
            <p>Your email verification code is:</p>
            <h1 style='letter-spacing:4px;margin:10px 0;'>$otp</h1>
            <p>This code expires in <b>10 minutes</b>.</p>
            <hr>
            <small>If you didn't sign up, you can ignore this email.</small>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Optionally log $mail->ErrorInfo
        return false;
    }
}

/** 🔔 Notify Admins about New Member Registration */
function notifyNewMemberRegistration($conn, $fname, $lname, $email) {
    $full_name = $fname . ' ' . $lname;
    $time = date('h:i A, M j, Y');

    $title = "New Member Registered! 🎉";
    $message = "$full_name ($email) just registered at $time. Account pending activation.";

    $query = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, created_at)
              SELECT 
                  user_id,
                  'staff_welcome',
                  ?,
                  ?,
                  'admin',
                  0,
                  NOW()
              FROM user 
              WHERE role = 'Admin' AND status = 'Active'";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $title, $message);
    $stmt->execute();
}

/* ------------------------------
   Main
------------------------------ */
if (!isset($_POST['submit'])) {
    header("Location: ../register.php");
    exit();
}

$fname            = sanitize($conn, $_POST['fname'] ?? '');
$lname            = sanitize($conn, $_POST['lname'] ?? '');
$age              = intval($_POST['age'] ?? 0);
$phone            = sanitize($conn, $_POST['phone'] ?? '');
$email            = sanitize($conn, $_POST['email'] ?? '');
$street           = sanitize($conn, $_POST['street'] ?? '');
$city             = sanitize($conn, $_POST['city'] ?? '');
$province         = sanitize($conn, $_POST['province'] ?? '');
$postal           = sanitize($conn, $_POST['postal'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$role             = "Member";
$status           = "Inactive";  // stays inactive until email verified

// Basic validations
if (
    empty($fname) || empty($lname) || empty($email) || empty($age) || empty($phone) ||
    empty($street) || empty($postal) || empty($city) || empty($province) ||
    empty($password) || empty($confirm_password)
) {
    $_SESSION['error'] = "Please fill up all fields.";
    header("Location: ../register.php");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: ../register.php");
    exit();
}
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: ../register.php");
    exit();
}

// Check email exists (prepared)
$check_sql = "SELECT user_id FROM user WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['error'] = "Email is already registered.";
    header("Location: ../register.php");
    exit();
}
$stmt->close();

// Insert user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insert_sql = "INSERT INTO user 
    (fname, lname, age, phone, email, password, street, city, province, postal, role, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_sql);
$stmt->bind_param(
    'ssisssssssss',
    $fname, $lname, $age, $phone, $email, $hashedPassword,
    $street, $city, $province, $postal, $role, $status
);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    $_SESSION['error'] = "Error: $err";
    header("Location: ../register.php");
    exit();
}
$new_user_id = $stmt->insert_id;
$stmt->close();

// If the column exists, ensure 0 at start (schema-safe)
if (tableHasColumn($conn, 'user', 'is_email_verified')) {
    $stmt = $conn->prepare("UPDATE user SET is_email_verified = 0 WHERE user_id = ?");
    $stmt->bind_param('i', $new_user_id);
    $stmt->execute();
    $stmt->close();
}

// Generate and store OTP in session (10 min TTL, 120s throttle)
$otp = (string) random_int(100000, 999999);
$_SESSION['reg_otp']             = $otp;
$_SESSION['reg_otp_email']       = $email;
$_SESSION['reg_otp_user_id']     = $new_user_id;
$_SESSION['reg_otp_sent_at']     = time();
$_SESSION['reg_otp_expires_at']  = time() + 600; // 10 minutes

// Send email
$sent = sendVerificationEmail($email, $otp);
if (!$sent) {
    $_SESSION['error'] = "We couldn't send the verification email. You can try 'Resend code' on the next page.";
}

// Notify admins (pending)
notifyNewMemberRegistration($conn, $fname, $lname, $email);

$_SESSION['success'] = "Account created! We've sent a verification code to your email. Verify to activate your account.";
header("Location: ../verify_registration.php?email=" . urlencode($email));
exit();
