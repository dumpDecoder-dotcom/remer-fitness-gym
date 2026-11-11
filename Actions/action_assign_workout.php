<?php
session_start();
require_once 'dbconnect.php';
require_once 'notification.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_SESSION['user_id'];
    $member_id = $_POST['member_id'];
    $workout_plan_id = $_POST['workout_plan_id'];
    $custom_notes = $_POST['notes'] ?? '';
    
    // Get staff and member info
    $staff_sql = "SELECT fname, lname FROM user WHERE user_id = ?";
    $staff_stmt = $conn->prepare($staff_sql);
    $staff_stmt->bind_param("i", $staff_id);
    $staff_stmt->execute();
    $staff = $staff_stmt->get_result()->fetch_assoc();
    $staff_name = $staff['fname'] . ' ' . $staff['lname'];
    
    $member_sql = "SELECT fname, lname FROM user WHERE user_id = ?";
    $member_stmt = $conn->prepare($member_sql);
    $member_stmt->bind_param("i", $member_id);
    $member_stmt->execute();
    $member = $member_stmt->get_result()->fetch_assoc();
    $member_name = $member['fname'] . ' ' . $member['lname'];
    
    // Get workout plan details
    $workout_sql = "SELECT name, description, duration FROM workout_plans WHERE id = ?";
    $workout_stmt = $conn->prepare($workout_sql);
    $workout_stmt->bind_param("i", $workout_plan_id);
    $workout_stmt->execute();
    $workout = $workout_stmt->get_result()->fetch_assoc();
    
    // Assign workout to member
    $assign_sql = "INSERT INTO member_workouts (member_id, workout_plan_id, assigned_by, notes, assigned_date, status) 
                   VALUES (?, ?, ?, ?, NOW(), 'active')";
    $assign_stmt = $conn->prepare($assign_sql);
    $assign_stmt->bind_param("iiis", $member_id, $workout_plan_id, $staff_id, $custom_notes);
    
    if ($assign_stmt->execute()) {
        $assignment_id = $conn->insert_id;
        
        $notif = new Notification($conn);
        
        // 1. Notify Member
        $notif->create(
            $staff_id,
            'workout',
            '💪 New Workout Plan Assigned!',
            "Coach $staff_name assigned you a workout plan: \"" . $workout['name'] . "\". Duration: " . $workout['duration'] . ". Let's crush those goals! 🔥",
            $member_id,
            null,
            $assignment_id,
        );
        
        // 2. Notify Admin
        $notif->create(
            $staff_id,
            'workout',
            '📋 Workout Plan Assigned',
            "Coach $staff_name assigned \"" . $workout['name'] . "\" to $member_name",
            null,
            null,
            $assignment_id,
        );
        
        echo json_encode(['success' => true, 'message' => 'Workout plan assigned successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign workout']);
    }
}
?>