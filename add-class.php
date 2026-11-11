<?php 
session_start();
include 'Actions/dbconnect.php';

if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" 
         style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" 
         style="z-index: 1055; width: 90%; max-width: 500px;" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php unset($_SESSION['error']); endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Class | Admin</title>
    <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css">
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
                <li class="nav-item"><a class="nav-link  active" href="class-list.php">Classes</a></li>
                <li class="nav-item"><a class="nav-link" href="staff-list.php">Staffs</a></li>
                <li class="nav-item"><a class="nav-link" href="member-list.php">Members</a></li>
                <li class="nav-item"><a class="nav-link" href="progress.php">Progress</a></li>
                <li class="nav-item"><a class="nav-link" href="product-list.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="payment-verification.php">Payments</a></li>
                <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
            </ul>
            </div>
        </div>
    </nav>

    <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
        <div class="w-100">
            <a href="class-list.php" class="btn btn-light rounded-circle fw-bold shadow" style="width: 40px; height: 40px;">
                <i class="ri-arrow-go-back-line"></i>
            </a>
        </div>

        <div class="rounded-5 bg-white p-4 d-flex flex-column gap-5 w-100">
            <div>
                <h2 class="lh-1 m-0 display-6 fw-bold">Add Class</h2>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="ri-information-line"></i>
                    <strong>Gym Operating Hours:</strong> Monday - Sunday, 6:00 AM - 9:00 PM
                </div>
            </div>

            <form action="Actions/action_addclass.php" method="POST" class="d-flex flex-column gap-3" id="classForm">
                
                <!-- Class Name -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Class Name</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" name="class_name" placeholder="e.g. Zumba" required>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Date Range</label>
                    <div class="col-sm-5">
                        <label class="form-label small text-muted">Start Date</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" required>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label small text-muted">End Date</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" required>
                    </div>
                </div>

                <!-- Time with Dropdown -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Time</label>
                    <div class="col-sm-5">
                        <label class="form-label small text-muted">Start Time</label>
                        <select class="form-select" name="start_time" id="start_time" required>
                            <option value="" selected disabled>Select Start Time</option>
                            <?php
                                // Generate time options from 8:00 AM to 9:00 PM
                                for ($hour = 8; $hour <= 21; $hour++) {
                                    for ($minute = 0; $minute < 60; $minute += 30) {
                                        $time_24 = sprintf("%02d:%02d", $hour, $minute);
                                        
                                        // Don't show 9:30 PM (21:30)
                                        if ($hour == 21 && $minute > 0) break;
                                        
                                        // Convert to 12-hour format for display
                                        $time_12 = date("g:i A", strtotime($time_24));
                                        
                                        echo "<option value='$time_24'>$time_12</option>";
                                    }
                                }
                            ?>
                        </select>
                        <small class="text-muted">Available: 8:00 AM - 9:00 PM</small>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label small text-muted">End Time</label>
                        <select class="form-select" name="end_time" id="end_time" required>
                            <option value="" selected disabled>Select End Time</option>
                            <?php
                                // Generate time options from 8:00 AM to 9:00 PM
                                for ($hour = 8; $hour <= 21; $hour++) {
                                    for ($minute = 0; $minute < 60; $minute += 30) {
                                        $time_24 = sprintf("%02d:%02d", $hour, $minute);
                                        
                                        // Don't show 9:30 PM (21:30)
                                        if ($hour == 21 && $minute > 0) break;
                                        
                                        // Convert to 12-hour format for display
                                        $time_12 = date("g:i A", strtotime($time_24));
                                        
                                        echo "<option value='$time_24'>$time_12</option>";
                                    }
                                }
                            ?>
                        </select>
                        <small class="text-muted">Available: 8:00 AM - 9:00 PM</small>
                    </div>
                </div>

                <!-- Schedule Days Info -->
                <div class="row mb-3">
                    <div class="col-sm-2"></div>
                    <div class="col-sm-10">
                        <div class="alert alert-light border">
                            <i class="ri-calendar-check-line"></i>
                            <strong>Schedule Days:</strong> This class will run Monday to Sunday within the selected date range.
                        </div>
                    </div>
                </div>

                <!-- Instructor -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Instructor</label>
                    <div class="col-sm-10">
                        <select class="form-select" name="instructor_id" required>
                            <option disabled selected>Select Instructor</option>
                            <?php
                                $instructors = mysqli_query($conn, "SELECT * FROM user WHERE role='Staff' AND status='Active'");
                                while ($inst = mysqli_fetch_assoc($instructors)) {
                                    echo "<option value='{$inst['user_id']}'>{$inst['fname']} {$inst['lname']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Min Participants -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Minimum Participants</label>
                    <div class="col-sm-10">
                        <input type="number" class="form-control" name="min_participants" min="1" placeholder="e.g. 5" required>
                    </div>
                </div>

                <!-- Max Participants -->
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">Maximum Participants</label>
                    <div class="col-sm-10">
                        <input type="number" class="form-control" name="max_participants" min="1" placeholder="e.g. 20" required>
                    </div>
                </div>

                <div class="text-end">
                    <input type="submit" name="submit" class="btn btn-primary px-4 fw-semibold" value="Create Class">
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Client-side validation for time and date
        document.getElementById('classForm').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            // Validate dates
            if (new Date(endDate) < new Date(startDate)) {
                e.preventDefault();
                alert('End date must be after or equal to start date!');
                return false;
            }
            
            // Validate times
            if (startTime && endTime) {
                const start = new Date('2000-01-01 ' + startTime);
                const end = new Date('2000-01-01 ' + endTime);
                
                if (end <= start) {
                    e.preventDefault();
                    alert('End time must be after start time!');
                    return false;
                }
            }
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').setAttribute('min', today);
        document.getElementById('end_date').setAttribute('min', today);
        
        // Update end date minimum when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').setAttribute('min', this.value);
        });
    </script>
</body>
</html>