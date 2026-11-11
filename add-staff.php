<?php 
    session_start();
    include 'Actions/dbconnect.php';

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
    <title>Administrator | Remer Fitness Gym</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css">
    <style>
        /* --- OPTIONAL UI ENHANCEMENTS --- */
        .form-label {
            color: #333;
            font-weight: 600;
        }

        input.form-control {
            border-radius: 10px;
            border: 1px solid #ccc;
            padding: 10px;
            transition: 0.2s ease;
        }

        input.form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.15rem rgba(0,123,255,0.25);
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 10px;
            transition: 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .form-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 900px;
        }

        body {
            background-color: #1b1b1b;
        }

        .btn-back {
            width: 40px;
            height: 40px;
        }
    </style>
</head>

<body class="d-flex flex-column gap-3 bg-base pb-3">
    <nav class="navbar navbar-expand-lg bg-transparent">
        <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
            <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
                <ul class="navbar-nav ms-2">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link " href="class-list.php">Classes</a></li>
                <li class="nav-item"><a class="nav-link active" href="staff-list.php">Staffs</a></li>
                <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
                <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
                <li class="nav-item"><a class="nav-link " href="product-list.php">Products</a></li>
                 <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
                <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
               
            </ul>
            </div>
        </div>
    </nav>

    <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
        <div class="w-100">
            <a href="staff-list.php" class="btn btn-light rounded-circle fw-bold shadow text-nowrap btn-back">
                <i class="ri-arrow-go-back-line"></i>
            </a>
        </div>

        <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-start mt-3">
            <div class="form-card">
                <h2 class="lh-1 mb-4 fw-bold text-center">Add Staff</h2>

                <form action="Actions/action_addstaff.php" method="POST" class="p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="fname" name="fname" placeholder="e.g. Juan" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lname" name="lname" placeholder="e.g. Dela Cruz" required>
                        </div>

                        <div class="col-md-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" required>
                        </div>
                        <div class="col-md-9">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>

                        <div class="col-md-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="example@example.com" required>
                        </div>

                        <div class="col-md-6">
                            <label for="street" class="form-label">Street</label>
                            <input type="text" class="form-control" id="street" name="street" placeholder="Street">
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="City">
                        </div>

                        <div class="col-md-6">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" class="form-control" id="province" name="province" placeholder="Province">
                        </div>
                        <div class="col-md-6">
                            <label for="postal" class="form-label">Postal</label>
                            <input type="text" class="form-control" id="postal" name="postal" placeholder="Postal">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Position</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="position" id="coach" value="Coach" required>
                                    <label class="form-check-label" for="coach">Coach</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="position" id="cashier" value="Cashier" required>
                                    <label class="form-check-label" for="cashier">Cashier</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter your password">
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <input type="submit" name="submit" id="submit" class="btn btn-primary fw-semibold px-4" value="Create Staff Account">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
