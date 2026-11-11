<?php
    session_start();
    include 'dbconnect.php';

    if (isset($_POST['submit'])) {
        if (
            isset($_POST['fname']) &&
            isset($_POST['lname']) &&
            isset($_POST['age']) &&
            isset($_POST['phone']) &&
            isset($_POST['email']) &&
            isset($_POST['street']) &&
            isset($_POST['city']) &&
            isset($_POST['province']) &&
            isset($_POST['postal']) &&
            isset($_POST['password']) &&
            isset($_POST['confirm_password']) &&
            isset($_POST['position'])
        ) {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password == $confirm_password) {
                // Sanitize and assign values
                $fname = mysqli_real_escape_string($conn, $_POST['fname']);
                $lname = mysqli_real_escape_string($conn, $_POST['lname']);
                $age = mysqli_real_escape_string($conn, $_POST['age']);
                $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $street = mysqli_real_escape_string($conn, $_POST['street']);
                $city = mysqli_real_escape_string($conn, $_POST['city']);
                $province = mysqli_real_escape_string($conn, $_POST['province']);
                $postal = mysqli_real_escape_string($conn, $_POST['postal']);
                $position = mysqli_real_escape_string($conn, $_POST['position']);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'Staff';

                $check = "SELECT * FROM user WHERE email = '$email'";
                $checkResult = mysqli_query($conn, $check);
                if (mysqli_num_rows($checkResult) > 0) {
                    $_SESSION['error'] = "Email already exists.";
                    header("Location: ../add-staff.php");
                    exit;
                }

                $sql = "INSERT INTO user (fname, lname, age, phone, email, street, city, province, postal, password, role, staff_position)
                        VALUES ('$fname', '$lname', '$age', '$phone', '$email', '$street', '$city', '$province', '$postal', '$hashed_password', '$role', '$position')";

                if (mysqli_query($conn, $sql)) {
                    $_SESSION['success'] = "Staff account successfully added.";
                    header("Location: ../staff-list.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Something went wrong. Please try again.";
                    header("Location: ../add-staff.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Passwords don't match.";
                header("Location: ../add-staff.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Please fill up all fields.";
            header("Location: ../add-staff.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: ../add-staff.php");
        exit;
    }
?>
