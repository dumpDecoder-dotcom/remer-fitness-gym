<?php
/**
 * Notification Class - Multi-User Support
 * Handles all notification operations for Remer Fitness Gym
 */
class Notification {
    private $conn;
    
    public function __construct($db_connection) {
        if ($db_connection === null) {
            throw new Exception("Database connection is null. Please check your db_connection.php file.");
        }
        $this->conn = $db_connection;
    }
    
    /**
     * Get unread notification count for a user
     * @param int $user_id - User ID
     * @param string $user_type - User type (member, admin, staff, coach, cashier)
     * @return int - Count of unread notifications
     */
    public function getUnreadCount($user_id, $user_type = null) {
        // Auto-detect user type from session if not provided
        if ($user_type === null) {
            $user_type = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'member';
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? 
                AND is_read = 0 
                AND (target_user_type = 'all' OR target_user_type = ?)
            ");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return 0;
            }
            
            $stmt->bind_param("is", $user_id, $user_type);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user notifications
     * @param int $user_id - User ID
     * @param int $limit - Number of notifications to fetch
     * @param bool $unread_only - Fetch only unread notifications
     * @param string $user_type - User type filter
     * @return array - Array of notifications
     */
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false, $user_type = null) {
        // Auto-detect user type from session if not provided
        if ($user_type === null) {
            $user_type = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'member';
        }
        
        try {
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = ? 
                    AND (target_user_type = ? OR target_user_type = 'all')";
            
            if ($unread_only) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("isi", $user_id, $user_type, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark a single notification as read
     * @param int $notification_id - Notification ID
     * @param int $user_id - User ID (for security)
     * @return bool - Success status
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("ii", $notification_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error marking as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * @param int $user_id - User ID
     * @param string $user_type - User type
     * @return bool - Success status
     */
    public function markAllAsRead($user_id, $user_type = null) {
        if ($user_type === null) {
            $user_type = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'member';
        }
        
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? 
                AND (target_user_type = 'all' OR target_user_type = ?)
            ");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $user_id, $user_type);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error marking all as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a notification
     * @param int $notification_id - Notification ID
     * @param int $user_id - User ID (for security)
     * @return bool - Success status
     */
    public function delete($notification_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("ii", $notification_id, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all read notifications for a user
     * @param int $user_id - User ID
     * @param string $user_type - User type
     * @return bool - Success status
     */
    public function deleteAllRead($user_id, $user_type = null) {
        if ($user_type === null) {
            $user_type = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'member';
        }
        
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE user_id = ? 
                AND is_read = 1 
                AND (target_user_type = 'all' OR target_user_type = ?)
            ");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $user_id, $user_type);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error deleting read notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new notification
     * @param int $user_id - User ID
     * @param string $type - Notification type
     * @param string $title - Notification title
     * @param string $message - Notification message
     * @param string $target_user_type - Target user type (member, admin, staff, etc.)
     * @param int $related_id - Related record ID (optional)
     * @param string $send_date - Send date (optional, defaults to now)
     * @return bool - Success status
     */
    public function create($user_id, $type, $title, $message, $target_user_type = 'all', $related_id = null, $send_date = null) {
        if ($send_date === null) {
            $send_date = date('Y-m-d H:i:s');
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, send_date, created_at, is_read) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0)
            ");
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("issssss", $user_id, $type, $title, $message, $target_user_type, $related_id, $send_date);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Broadcast notification to all users of a specific type
     * @param string $user_type - User type (member, admin, staff, coach, cashier)
     * @param string $type - Notification type
     * @param string $title - Notification title
     * @param string $message - Notification message
     * @return int - Number of notifications sent
     */
    public function broadcastToUserType($user_type, $type, $title, $message) {
        try {
            // Determine which table to query based on user type
            if ($user_type === 'member') {
                $sql = "SELECT id FROM user WHERE user_type = 'member'";
            } else {
                // For admin, staff, coach, cashier - check staff table
                $sql = "SELECT id FROM staff WHERE role = ?";
            }
            
            if ($user_type === 'member') {
                $users_result = $this->conn->query($sql);
            } else {
                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    error_log("Prepare failed: " . $this->conn->error);
                    return 0;
                }
                $stmt->bind_param("s", $user_type);
                $stmt->execute();
                $users_result = $stmt->get_result();
            }
            
            $success_count = 0;
            while ($user = $users_result->fetch_assoc()) {
                if ($this->create($user['id'], $type, $title, $message, $user_type)) {
                    $success_count++;
                }
            }
            
            if (isset($stmt)) {
                $stmt->close();
            }
            
            return $success_count;
        } catch (Exception $e) {
            error_log("Error broadcasting notification: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send notification to all users (broadcast to everyone)
     * @param string $type - Notification type
     * @param string $title - Notification title
     * @param string $message - Notification message
     * @return int - Number of notifications sent
     */
    public function broadcastToAll($type, $title, $message) {
        try {
            $success_count = 0;
            
            // Get all members
            $members = $this->conn->query("SELECT id FROM user WHERE user_type = 'member'");
            while ($user = $members->fetch_assoc()) {
                if ($this->create($user['id'], $type, $title, $message, 'all')) {
                    $success_count++;
                }
            }
            
            // Get all staff (admin, staff, coach, cashier)
            $staff = $this->conn->query("SELECT id FROM staff");
            while ($user = $staff->fetch_assoc()) {
                if ($this->create($user['id'], $type, $title, $message, 'all')) {
                    $success_count++;
                }
            }
            
            return $success_count;
        } catch (Exception $e) {
            error_log("Error broadcasting to all: " . $e->getMessage());
            return 0;
        }
    }
}
?>