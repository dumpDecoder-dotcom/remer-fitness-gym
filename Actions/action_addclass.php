<?php
session_start();
include 'dbconnect.php';

if (isset($_POST['submit'])) {
    $class_name = mysqli_real_escape_string($conn, trim($_POST['class_name']));
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];
    $max_participants = intval($_POST['max_participants']);
    $min_participants = intval($_POST['min_participants']);
    $instructor_id = isset($_POST['instructor_id']) && $_POST['instructor_id'] !== '' ? intval($_POST['instructor_id']) : null;

    // 🕒 Validate time is within gym hours (6:00 AM - 9:00 PM)
    $start_hour = (int)date('H', strtotime($start_time));
    $start_minute = (int)date('i', strtotime($start_time));
    $end_hour = (int)date('H', strtotime($end_time));
    $end_minute = (int)date('i', strtotime($end_time));

    if ($start_hour < 6) {
        $_SESSION['error'] = "Start time cannot be before 6:00 AM.";
        header("Location: ../add-class.php");
        exit;
    }

    if ($end_hour > 21 || ($end_hour == 21 && $end_minute > 0)) {
        $_SESSION['error'] = "End time cannot be after 9:00 PM.";
        header("Location: ../add-class.php");
        exit;
    }

    if ($start_hour >= 21) {
        $_SESSION['error'] = "Start time must be before 9:00 PM.";
        header("Location: ../add-class.php");
        exit;
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        $_SESSION['error'] = "End time must be later than start time.";
        header("Location: ../add-class.php");
        exit;
    }

    if (strtotime($start_date) > strtotime($end_date)) {
        $_SESSION['error'] = "End date must not be earlier than start date.";
        header("Location: ../add-class.php");
        exit;
    }

    $today = date('Y-m-d');
    if ($start_date < $today) {
        $_SESSION['error'] = "Cannot create classes with past dates.";
        header("Location: ../add-class.php");
        exit;
    }

    if ($min_participants < 1) {
        $_SESSION['error'] = "Minimum participants must be at least 1.";
        header("Location: ../add-class.php");
        exit;
    }

    if ($max_participants < $min_participants) {
        $_SESSION['error'] = "Maximum participants cannot be less than minimum participants.";
        header("Location: ../add-class.php");
        exit;
    }

    // 🔍 Check instructor availability only if instructor is selected
    if (!is_null($instructor_id) && $instructor_id > 0) {
        $check_query = "
            SELECT c.class_name, c.start_date, c.end_date, c.start_time, c.end_time 
            FROM class c
            WHERE c.instructor_id = $instructor_id
            AND c.status IN ('Upcoming', 'Ongoing')
            AND (
                (c.start_date <= '$end_date' AND c.end_date >= '$start_date')
                AND (c.start_time < '$end_time' AND c.end_time > '$start_time')
            )
        ";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $conflict = mysqli_fetch_assoc($check_result);
            $conflict_name = $conflict['class_name'];
            $conflict_date = date('M j, Y', strtotime($conflict['start_date']));
            $conflict_time = date('g:i A', strtotime($conflict['start_time'])) . ' - ' . date('g:i A', strtotime($conflict['end_time']));
            
            $_SESSION['error'] = "Instructor is already assigned to '$conflict_name' on $conflict_date at $conflict_time.";
            header("Location: ../add-class.php");
            exit;
        }
    }

    // 🟢 Auto-set class status
    if ($end_date < $today) {
        $status = 'Completed';
    } elseif ($start_date > $today) {
        $status = 'Upcoming';
    } else {
        $status = 'Ongoing';
    }

    // ✅ Insert class (handle NULL instructor)
    $instructor_value = is_null($instructor_id) ? 'NULL' : $instructor_id;
    $insert_query = "
        INSERT INTO class (class_name, start_date, end_date, start_time, end_time, 
                           participant_limit, min_participants, instructor_id, status)
        VALUES ('$class_name', '$start_date', '$end_date', '$start_time', '$end_time', 
                $max_participants, $min_participants, $instructor_value, '$status')
    ";

    if (mysqli_query($conn, $insert_query)) {
        $class_id = mysqli_insert_id($conn);
        
        // 🟢 Create assignment request only if instructor exists
        if (!is_null($instructor_id) && $instructor_id > 0) {
            $insert_request = "
                INSERT INTO class_assignment_requests (class_id, instructor_id, status, created_at) 
                VALUES ($class_id, $instructor_id, 'Pending', NOW())
            ";
            mysqli_query($conn, $insert_request);
        }

        if (!is_null($instructor_id) && $instructor_id > 0) {
            $instructor_query = "SELECT fname, lname FROM user WHERE user_id = $instructor_id";
            $instructor_result = mysqli_query($conn, $instructor_query);
            $instructor_data = mysqli_fetch_assoc($instructor_result);
            $instructor_name = $instructor_data['fname'] . ' ' . $instructor_data['lname'];
            $_SESSION['success'] = "Class '$class_name' created successfully! Waiting for approval from $instructor_name. (Status: $status)";
        } else {
            $_SESSION['success'] = "Class '$class_name' created successfully! (No instructor assigned yet, Status: $status)";
        }

        header("Location: ../class-list.php");
    } else {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        header("Location: ../add-class.php");
    }

    exit;
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../add-class.php");
    exit;
}
?>
