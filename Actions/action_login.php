<?php
session_start();
include 'dbconnect.php';

if (isset($_POST['submit'])) {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM user WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            if (password_verify($password, $row['password'])) {

                $role    = $row['role'];
                $user_id = (int)$row['user_id'];
                $fname   = $row['fname'];
                $lname   = $row['lname'];

                $_SESSION['role']    = $role;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['success'] = "Logged in successfully";

                // 🔔 (Optional) Create a personal welcome notification ONLY for the user who logged in.
                //    If you don't want ANY login notifications at all, just comment out this line.
                createPersonalWelcome($conn, $user_id, $fname, $role);

                // Redirect by role
                if ($role === 'Admin') {
                    header("Location: ../dashboard.php");
                } elseif ($role === 'Staff') {
                    header("Location: ../staff_class.php");
                } elseif ($role === 'Member') {
                    header("Location: ../member_dashboard.php");
                } else {
                    $_SESSION['error'] = "Unknown user role.";
                    header("Location: ../login.php");
                }
                exit;

            } else {
                $_SESSION['error'] = "Incorrect password.";
                header("Location: ../login.php"); exit;
            }
        } else {
            $_SESSION['error'] = "Email not found.";
            header("Location: ../login.php"); exit;
        }
    } else {
        $_SESSION['error'] = "Please fill up all fields.";
        header("Location: ../login.php"); exit;
    }
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../login.php"); exit;
}

/**
 * 🔔 PERSONAL WELCOME ONLY (no admin/staff alerts)
 * This no longer notifies Admin/Staff about logins.
 * Change 'type' to 'welcome' so it won't be picked up by any "login" filters/popups.
 */
function createPersonalWelcome(mysqli $conn, int $logged_user_id, string $fname, string $role): void
{
    // If you want to disable even this personal welcome, return early:
    // return;

    $welcome_by_role = [
        'Admin'  => "Welcome back, Admin! Ready to manage the gym? 💼",
        'Staff'  => "Welcome back, Coach! Ready to train members? 💪",
        'Member' => "Welcome to REMER Fitness Gym! Ready to crush your workout? 🔥",
    ];

    $welcome_title   = "Welcome back, $fname!";
    $welcome_message = $welcome_by_role[$role] ?? "You have successfully logged in.";

    $sql = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, is_sent, created_at)
            VALUES (?, 'welcome', ?, ?, NULL, 0, 0, NOW())";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('iss', $logged_user_id, $welcome_title, $welcome_message);
        $stmt->execute();
        $stmt->close();
    }
}
