<?php
    session_start();
    $showSuccess = false;
    $showError = false;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $input_code = strval(trim($_POST['code']));
        $stored_code = strval($_SESSION['verification_code'] ?? '');
        $otp_time = $_SESSION['otp_sent_time'] ?? 0;
        $now = time();

        if (empty($input_code)) {
            $_SESSION['error'] = "Verification code is required.";
        } 
        elseif ($now - $otp_time > 120) {
            $_SESSION['error'] = "Verification code has expired. Please request a new one.";
        } 
        elseif ($input_code != $stored_code) {
            $_SESSION['error'] = "Incorrect verification code.";
        } 
        else {
            $_SESSION['verified'] = true;
            header("Location: newpassword.php");
            exit;
        }
    }

    if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        unset($_SESSION['success']); 
    endif;

    if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
        unset($_SESSION['error']); 
    endif;


    $otp_duration = 120; // seconds
    $otp_sent_time = $_SESSION['otp_sent_time'] ?? 0;
    $time_remaining = max(0, $otp_duration - (time() - $otp_sent_time));
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Remer Fitness Gym</title>
        <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
        <style>
            .disabled-link {
                pointer-events: none;
                color: grey;
                text-decoration: none;
            }
        </style>
    </head>
    <body class="d-flex flex-column gap-3 bg-base py-3">
        <div class="container">
            <div class="d-flex">
                <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" width="120" height="120" loading="lazy">
            </div>
        </div>
        <div class="container cont-rad flex-grow-1 w-100 bg-main-t d-flex flex-column p-4">
            <div class="w-100">
                <a href="forgotpassword.php" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;"><i class="ri-arrow-go-back-line"></i></a>
            </div>
            <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
                <div class="container" style="width: min(90%, 650px);">
                    <form action="" method="POST" class="w-100 flex-grow-1">
                        <h1 class="w-100 text-center text-clr display-4 fw-bold lh-1">VERIFY CODE</h1>
                        <p class="text-center text-white">We've sent a <span class="fw-bold">6-digit verification code</span> to your email. Please enter the code below to continue resetting your password.</p>
                        <div class="mb-3 mt-5">
                            <label for="code" class="form-label text-clr m-0">Please enter the code</label>
                            <input type="number" name="code" class="form-control m-0" id="code" placeholder="X X X X X X" required>
                        </div>
                        <input type="submit" class="btn btn-primary mt-2 w-100 fw-semibold" value="Confirm">
                        <p class="text-clr mt-4 text-end">
                            Didn't get the code? Check your spam or junk folder, or request a new one. 
                            <a href="Actions/action_resendotp.php" id="resendLink" class="text-clr disabled-link">Resend</a> 
                            <span class="fw-bold" id="countdown"></span>s
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
        <script src="script.js"></script>
        <script>
            let timeLeft = <?= $time_remaining ?>;
            const countdownEl = document.getElementById('countdown');
            const resendLink = document.getElementById('resendLink');

            countdownEl.textContent = timeLeft;

            const interval = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(interval);
                    resendLink.classList.remove('disabled-link');
                    resendLink.setAttribute('href', 'Actions/action_resendotp.php');
                    countdownEl.textContent = "0";
                }
            }, 1000);
        </script>
    </body>
</html>