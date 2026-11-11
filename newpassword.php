<?php
    session_start();

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
    </head>
    <body class="d-flex flex-column gap-3 bg-base py-3">
        <div class="container">
            <div class="d-flex">
                <img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Remer Logo" width="120" height="120" loading="lazy">
            </div>
        </div>
        <div class="container cont-rad flex-grow-1 w-100 bg-main-t d-flex flex-column p-4">
            <div class="w-100">
                <a href="verifypassword.php" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;"><i class="ri-arrow-go-back-line"></i></a>
            </div>
            <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
                <div class="container" style="width: min(90%, 650px);">
                    <form action="Actions/action_newpassword.php" method="POST" class="w-100 flex-grow-1">
                        <h1 class="w-100 text-center text-clr display-4 fw-bold lh-1">NEW PASSWORD</h1>
                        <p class="text-center text-white"> Verified! Please set your <span class="fw-bold">new password</span> below. Make sure to enter it twice to confirm.</p>
                        
                        <label for="password" class="form-label text-clr m-0">New Password</label>
                        <input type="password" name="password" id="password" class="form-control" aria-describedby="passwordHelpBlock" placeholder="Enter your new password" required>
                        
                        <label for="confirm_password" class="form-label text-clr m-0 mt-3">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" aria-describedby="passwordHelpBlock" placeholder="Re-enter your password" required>
                        <div id="passwordHelpBlock" class="form-text text-clr opacity-75">
                            Your password must be 8-20 characters long, contain letters and numbers, and must not contain spaces, special characters, or emoji.
                        </div>
                        <input type="submit" class="btn btn-primary mt-4 w-100 fw-semibold" value="Confirm">
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
        <script src="script.js"></script>
    </body>
</html>