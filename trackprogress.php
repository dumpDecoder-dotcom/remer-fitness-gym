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

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Add Progress
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $exercise = mysqli_real_escape_string($conn, $_POST['exercise']);
        $sets = intval($_POST['sets']);
        $reps = intval($_POST['reps']);

        $query = "INSERT INTO progress (user_id, category, exercise, sets, reps)
                VALUES ('$user_id', '$category', '$exercise', '$sets', '$reps')";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Progress successfully logged!";
        } 
        else {
            $_SESSION['error'] = "Failed to save progress. Please try again.";
        }

        header("Location: trackprogress.php");
        exit;
    }

    $result = mysqli_query($conn, "SELECT * FROM progress WHERE user_id = $user_id ORDER BY date_logged DESC");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Member | Remer Fitness Gym</title>
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
                        <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link " href="member_shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="member_bookclass.php">Book Classes</a></li>
                        <li class="nav-item"><a class="nav-link active" href="trackprogress.php">Track Progress</a></li>
                        <li class="nav-item"><a class="nav-link" href="member_payment.php">Payment</a></li>
                        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
            <div class="w-100">
                <a href="#" onclick="event.preventDefault(); window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;">
                    <i class="ri-arrow-go-back-line"></i>
                </a>
            </div>

            <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
                <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100" style="height: 690px; overflow-y: auto;">
                    <div class="w-100 d-flex justify-content-between align-items-center mb-3">
                        <h2 class="lh-1 m-0 display-6 fw-bold">Track Progress</h2>
                    </div>

                    <form method="POST" class="bg-light rounded-4 p-3 shadow-sm mb-4">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" id="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option>Strength Training</option>
                                    <option>Cardio Training</option>
                                    <option>Functional Training</option>
                                    <option>Combat Sports / Martial Arts</option>
                                    <option>Group Fitness Classes</option>
                                    <option>Flexibility & Recovery Training</option>
                                    <option>Specialty Training</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Exercise</label>
                                <select name="exercise" id="exercise" class="form-select" required>
                                    <option value="">Select Exercise</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sets</label>
                                <input type="number" name="sets" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Reps</label>
                                <input type="number" name="reps" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-success w-100">Add Progress</button>
                            </div>
                        </div>
                    </form>

                    <!-- Progress Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Exercise</th>
                                    <th>Sets</th>
                                    <th>Reps</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= date('M d, Y h:i A', strtotime($row['date_logged'])) ?></td>
                                            <td><?= htmlspecialchars($row['category']) ?></td>
                                            <td><?= htmlspecialchars($row['exercise']) ?></td>
                                            <td><?= $row['sets'] ?></td>
                                            <td><?= $row['reps'] ?></td>
                                            <td>
                                                <?php if (!empty($row['remarks'])): ?>
                                                    <!-- Button trigger modal -->
                                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#remarkModal<?= $row['id'] ?>">
                                                        View Remark
                                                    </button>

                                                    <!-- Modal -->
                                                    <div class="modal fade" id="remarkModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Admin Remark</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p><?= nl2br(htmlspecialchars($row['remarks'])) ?></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">No progress logged yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>  
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
        <script src="script.js"></script>
        <script>
            const exercises = {
                "Strength Training": ["Bench Press", "Push-Ups", "Deadlifts", "Squats", "Overhead Press", "Barbell Curl"],
                "Cardio Training": ["Treadmill Running", "Jump Rope", "Stationary Bike", "Rowing Machine", "HIIT Circuits"],
                "Functional Training": ["CrossFit WOD", "Battle Ropes", "Kettlebell Swings", "Box Jumps", "Plyometrics"],
                "Combat Sports / Martial Arts": ["Boxing", "Kickboxing", "MMA", "Sparring", "Shadow Boxing"],
                "Group Fitness Classes": ["Zumba", "BodyPump", "Spin Class", "Step Aerobics", "Yoga"],
                "Flexibility & Recovery Training": ["Yoga", "Static Stretching", "Foam Rolling", "Mobility Drills"],
                "Specialty Training": ["Bodybuilding", "Powerlifting", "Rehabilitation", "Strongman Training"]
            };

            document.getElementById('category').addEventListener('change', function() {
                const category = this.value;
                const exerciseSelect = document.getElementById('exercise');
                exerciseSelect.innerHTML = '<option value="">Select Exercise</option>';

                if (exercises[category]) {
                    exercises[category].forEach(ex => {
                        const option = document.createElement('option');
                        option.value = ex;
                        option.textContent = ex;
                        exerciseSelect.appendChild(option);
                    });
                }
            });
        </script>
    </body>
</html>