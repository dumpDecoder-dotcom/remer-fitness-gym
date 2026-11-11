<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Admin, Staff, Member, Cashier

// Determine request type: 'popup' or 'list'
$mode = $_GET['mode'] ?? 'popup'; // Default: popup mode

if ($mode === 'popup') {
    // ==========================================
    // MODE: AUTO-POPUP (NEW NOTIFICATIONS ONLY)
    // ==========================================
    
    // Get latest UNSENT notification
    $sql = "SELECT * FROM notifications 
            WHERE ((user_id = ? AND (target_user_type IS NULL OR target_user_type = '')) 
                   OR target_user_type = ? 
                   OR target_user_type = 'all')
            AND is_sent = 0
            ORDER BY created_at DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $user_role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = ['new_notifications' => 0];
    
    if ($result->num_rows > 0) {
        $notification = $result->fetch_assoc();
        
        // Mark as SENT (popup shown once)
        $update_sql = "UPDATE notifications SET is_sent = 1 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $notification['id']);
        $update_stmt->execute();
        
        $response['new_notifications'] = 1;
        $response['latest_notification'] = $notification;
    }
    
    echo json_encode($response);
    
} elseif ($mode === 'list') {
    // ==========================================
    // MODE: DROPDOWN LIST (ALL NOTIFICATIONS)
    // ==========================================
    
    // Get all notifications (last 50)
    $sql = "SELECT * FROM notifications 
            WHERE ((user_id = ? AND (target_user_type IS NULL OR target_user_type = '')) 
                   OR target_user_type = ? 
                   OR target_user_type = 'all')
            ORDER BY created_at DESC 
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $user_role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    // Count unread
    $count_sql = "SELECT COUNT(*) as total FROM notifications 
                  WHERE ((user_id = ? AND (target_user_type IS NULL OR target_user_type = '')) 
                         OR target_user_type = ? 
                         OR target_user_type = 'all')
                  AND is_read = 0";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("is", $user_id, $user_role);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    
    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => $count_row['total']
    ]);
}
?>