<?php
session_start();
include 'Actions/dbconnect.php';

// ✅ Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// ✅ CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Alerts (Success/Error)
if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
        style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow"
        style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php unset($_SESSION['error']); endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
            if($_SESSION['role'] === "Admin") echo "Administrator";
            elseif ($_SESSION['role'] === "Staff") echo "Staff";
            elseif ($_SESSION['role'] === "Member") echo "Member";
        ?> | Remer Fitness Gym
    </title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
</head>

<body class="d-flex flex-column gap-3 bg-base pb-3">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-transparent">
        <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);">
            <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
                <ul class="navbar-nav ms-2">
                    <?php if($_SESSION['role'] === "Admin") { ?> 
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="class-list.php">Classes</a></li>
                        <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
                        <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
                        <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="payment_verification.php">Payments</a></li>
                    <?php } elseif($_SESSION['role'] === "Staff") { ?>
                        <li class="nav-item"><a class="nav-link" href="staff_class.php">Class</a></li>
                        <li class="nav-item"><a class="nav-link" href="staff_attendance.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="staff_schedule.php">Schedule</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Progress</a></li>
                    <?php } elseif($_SESSION['role'] === "Member") { ?>
                        <li class="nav-item"><a class="nav-link" href="member_shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Track Progress</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Payment</a></li>
                    <?php } ?>
                    <li class="nav-item"><a class="nav-link active" href="#">Profile</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
        <div class="w-100">
            <a href="#" onclick="event.preventDefault(); window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;">
                <i class="ri-arrow-go-back-line"></i>
            </a>
        </div>

        <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
<div class="rounded-5 bg-white py-5 px-4 d-flex flex-column gap-5" style="width: 500px;">
                <?php 
                $user_id = $_SESSION['user_id'];

                // ✅ Use prepared statement
                $stmt = $conn->prepare("SELECT * FROM user WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $row = $result->fetch_assoc()): ?>
                    <div class="w-100 d-flex flex-column">
                        <h1 class="lh-2 m-0 p-0 display-6 fw-bold">
                            <span class="text-blue fw-bold display-5 shadow-blur-blue">
                                <?= htmlspecialchars($row['lname']) ?>
                            </span>, <?= htmlspecialchars($row['fname']) ?>
                        </h1>

                        <?php if($_SESSION['role'] === "Admin"): ?>
                            <p class="lh-2 m-0 p-0 fs-6">Administrator</p>
                        <?php elseif($_SESSION['role'] === "Staff"): ?>
                            <p class="lh-2 m-0 p-0 fs-6">Position: 
                                <span class="text-blue fw-bold fs-5"><?= htmlspecialchars($row['staff_position']) ?></span>
                            </p>
                        <?php elseif($_SESSION['role'] === "Member"): ?>
                            <p class="lh-2 m-0 p-0 fs-6">Membership Status: 
                                <span class="text-blue fw-bold fs-5"><?= htmlspecialchars($row['status']) ?></span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Edit Profile Form -->
                    <div class="d-flex flex-column flex-grow-1">
                        <div class="mt-4 d-flex justify-content-center align-items-center w-100 h-100">
                            <form action="Actions/action_editprofile.php" method="POST" class="d-flex flex-column gap-3 h-100">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['user_id']) ?>">

                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="flex-grow-1">
                                        <label for="fname" class="form-label m-0">First name</label>
                                        <input type="text" name="fname" id="fname" class="form-control" 
                                            required value="<?= htmlspecialchars($row['fname']) ?>">
                                    </div>
                                    <div class="flex-grow-1">
                                        <label for="lname" class="form-label m-0">Last name</label>
                                        <input type="text" name="lname" id="lname" class="form-control" 
                                            required value="<?= htmlspecialchars($row['lname']) ?>">
                                    </div>
                                </div>

                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="flex-grow-1">
                                        <label for="age" class="form-label m-0">Age</label>
                                        <input type="number" name="age" id="age" class="form-control"
                                            required value="<?= htmlspecialchars($row['age']) ?>">
                                    </div>
                                    <div class="flex-grow-1">
                                        <label for="phone" class="form-label m-0">Phone</label>
                                        <input type="tel" name="phone" id="phone" class="form-control" 
                                            required value="<?= htmlspecialchars($row['phone']) ?>">
                                    </div>
                                    <div class="w-100">
                                        <label for="email" class="form-label m-0">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" 
                                            required value="<?= htmlspecialchars($row['email']) ?>">
                                        <div class="form-text opacity-50">
                                            Changing your email will require verification.
                                        </div>
                                    </div>
                                    <?php if (isset($row['is_email_verified']) && !$row['is_email_verified']): ?>
                                    <div class="text-danger small">Your email is not verified yet.</div>
                                    <?php endif; ?>


                                </div>

                                <div class="w-100">
                                    <label for="password" class="form-label m-0">Password</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Update password (optional)">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </div>
                                    <div class="form-text opacity-50">Leave blank if you don't wish to change password</div>
                                </div>

                                <div class="w-100">
                                    <label for="confirm_password" class="form-label m-0">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter password (optional)">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                            <i class="ri-eye-line"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end align-items-end flex-grow-1">
                                    <input type="submit" name="submit" class="btn btn-primary px-4" value="Confirm">
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: 
                    $_SESSION['error'] = "An error occurred while retrieving your info. Please log in again.";
                    header("Location: login.php");
                    exit;
                endif; ?>
            </div>  
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        input.type = input.type === "password" ? "text" : "password";
    }
    </script>
</body>
</html>
