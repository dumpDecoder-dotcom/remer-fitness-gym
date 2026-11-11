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
        <title>Member | Remer Fitness Gym</title>
        <link rel="icon" href="Assets/Graphics/Remer Fitness Gym Logo.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
        <link rel="stylesheet" href="style.css">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    </head>
    <body class="d-flex flex-column gap-3 bg-base pb-3">
        <nav class="navbar navbar-expand-lg bg-transparent">
            <div class="container px-md-4 px-lg-5 py-1" style="border-bottom: 1px solid var(--text-clr);"   >
                <a class="navbar-brand" href="#"><img src="Assets/Graphics/Remer Fitness Gym Logo.png" alt="Logo" class="logo-img"></a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>

                <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
                    <ul class="navbar-nav ms-2">
                         <li class="nav-item"><a class="nav-link" href="member_dashboard.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link " href="member_shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link active" href="member_bookclass.php">Book Classes</a></li>
                        <li class="nav-item"><a class="nav-link" href="trackprogress.php">Track Progress</a></li>
                        <li class="nav-item"><a class="nav-link" href="member_payment.php">Payment</a></li>
                        <li class="nav-item"><a class="nav-link" href="my-profile.php">Profile</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container cont-rad flex-grow-1 w-100 bg-dark-48 d-flex flex-column p-4 gap-3">
            <div class="w-100">
                <a href="#" onclick="event.preventDefault(); window.history.back();" class="btn btn-light rounded-circle fw-bold shadow text-nowrap" style="width: 40px; height: 40px;"><i class="ri-arrow-go-back-line"></i></a>
            </div>
            <div class="w-100 flex-grow-1 d-flex justify-content-center align-items-center gap-2">
                
                <div class="rounded-5 bg-white p-4 d-flex flex-column gap-3 w-100" style="height: 690px">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                        <h2 class="lh-1 m-0 display-6 fw-bold">Book your Classes</h2>
                    </div>
                    <div class="w-100 flex-grow-1" style="overflow-x: auto;">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <td>Class Name</td>
                                    <td>Date</td>
                                    <td>Time</td>
                                    <td>Registered</td>
                                    <td>Actions</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $sql = "
                                        SELECT * FROM class
                                        WHERE CONCAT(end_date, ' ', end_time) >= NOW()
                                        AND status IN ('Upcoming', 'Ongoing', 'Active')
                                        ORDER BY start_date ASC, start_time ASC
                                    ";

                                    $result = mysqli_query($conn, $sql); 
                                    
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $class_id = $row['id'];

                                            $count_sql = "SELECT COUNT(*) AS total FROM member_class_registration WHERE class_id = $class_id";
                                            $count_result = mysqli_query($conn, $count_sql);
                                            $count_row = mysqli_fetch_assoc($count_result);
                                            $participant_count = $count_row['total'];

                                            $user_id = $_SESSION['user_id'];
                                            $check_sql = "SELECT * FROM member_class_registration 
                                                        WHERE class_id = $class_id AND member_id = $user_id";
                                            $check_result = mysqli_query($conn, $check_sql);
                                            $is_registered = mysqli_num_rows($check_result) > 0;

                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['class_name']) ?></td>
                                                <td>
                                                    <?= date('M d, Y', strtotime($row['start_date'])) ?> 
                                                    <?php if ($row['end_date'] && $row['end_date'] != $row['start_date']): ?>
                                                        - <?= date('M d, Y', strtotime($row['end_date'])) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?>
                                                </td>
                                                <td><?= $participant_count ?> / <?= $row['participant_limit'] ?></td>
                                                <td>
    <?php if ($is_registered): ?>
        <form method="POST" action="Actions/action_cancelclass.php" onsubmit="return confirm('Are you sure you want to cancel this class?');">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            <button type="submit" class="btn btn-danger">Cancel</button>
        </form>
    <?php else: ?>
        <a href="Actions/action_bookclass.php?id=<?= $user_id ?>&class_id=<?= $class_id ?>" class="btn btn-primary">Join!</a>
    <?php endif; ?>
</td>


                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="5">There are no scheduled classes.</td>
                                        </tr>
                                        <?php
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>  
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
        <script src="script.js"></script>
    </body>
</html>