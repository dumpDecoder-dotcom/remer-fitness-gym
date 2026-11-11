<?php
/**
 * Enhanced Notification Class - Complete Gym Management System
 * Handles all notification operations for Remer Fitness Gym
 */
class Notification {
    private $conn;
    
    public function __construct($db_connection) {
        if ($db_connection === null) {
            throw new Exception("Database connection is null");
        }
        $this->conn = $db_connection;
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($user_id, $user_type = null) {
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
     */
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false, $user_type = null) {
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
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            
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
     * Mark all notifications as read
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
     */
    public function delete($notification_id, $user_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
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
     * Create a new notification
     */
    public function create($user_id, $type, $title, $message, $target_user_type = 'member', $related_id = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, created_at, is_read) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)
            ");
            
            $stmt->bind_param("issssi", $user_id, $type, $title, $message, $target_user_type, $related_id);
            $success = $stmt->execute();
            $stmt->close();
            
            return $success;
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Broadcast to all users of a specific type
     */
    public function broadcastToUserType($user_type, $type, $title, $message, $related_id = null) {
        try {
            if ($user_type === 'member') {
                $sql = "SELECT id FROM user WHERE user_type = 'member'";
                $users_result = $this->conn->query($sql);
            } else {
                $stmt = $this->conn->prepare("SELECT id FROM staff WHERE role = ?");
                $stmt->bind_param("s", $user_type);
                $stmt->execute();
                $users_result = $stmt->get_result();
            }
            
            $success_count = 0;
            while ($user = $users_result->fetch_assoc()) {
                if ($this->create($user['id'], $type, $title, $message, $user_type, $related_id)) {
                    $success_count++;
                }
            }
            
            if (isset($stmt)) $stmt->close();
            
            return $success_count;
        } catch (Exception $e) {
            error_log("Error broadcasting: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Broadcast to all users (members + staff)
     */
    public function broadcastToAll($type, $title, $message, $related_id = null) {
        try {
            $success_count = 0;
            
            // Send to all members
            $members = $this->conn->query("SELECT id FROM user WHERE user_type = 'member'");
            while ($user = $members->fetch_assoc()) {
                if ($this->create($user['id'], $type, $title, $message, 'all', $related_id)) {
                    $success_count++;
                }
            }
            
            // Send to all staff
            $staff = $this->conn->query("SELECT id FROM staff");
            while ($user = $staff->fetch_assoc()) {
                if ($this->create($user['id'], $type, $title, $message, 'all', $related_id)) {
                    $success_count++;
                }
            }
            
            return $success_count;
        } catch (Exception $e) {
            error_log("Error broadcasting to all: " . $e->getMessage());
            return 0;
        }
    }
    
    // ============================================
    // 🔥 MEMBER NOTIFICATION METHODS
    // ============================================
    
    /**
     * 1. Membership Expiry Reminder (3 days before)
     */
    public function sendMembershipExpiryReminder($user_id, $expiry_date, $days_left) {
        $title = "⚠️ Membership Expiring Soon!";
        $message = "Hi! Your membership will expire in {$days_left} day(s) on " . 
                   date('F j, Y', strtotime($expiry_date)) . 
                   ". Renew now to continue your workouts!";
        
        return $this->create($user_id, 'membership_expiry', $title, $message, 'member');
    }
    
    /**
     * 2. Payment Due Reminder
     */
    public function sendPaymentDueReminder($user_id, $amount, $due_date) {
        $title = "💰 Payment Due Reminder";
        $message = "Reminder: Your payment of ₱" . number_format($amount, 2) . 
                   " is due on " . date('F j, Y', strtotime($due_date)) . 
                   ". Please settle to avoid service interruption.";
        
        return $this->create($user_id, 'payment_due', $title, $message, 'member');
    }
    
    /**
     * 3. Class Booking Confirmation
     */
    public function sendClassBookingConfirmation($user_id, $class_name, $schedule, $instructor, $booking_id) {
        $title = "✅ Class Booking Confirmed!";
        $message = "You have successfully booked {$class_name} with Coach {$instructor} on " . 
                   date('F j, Y g:i A', strtotime($schedule)) . ". See you there!";
        
        return $this->create($user_id, 'class_booking', $title, $message, 'member', $booking_id);
    }
    
    /**
     * 4. Class Reminder (1 day before)
     */
    public function sendClassReminder($user_id, $class_name, $schedule, $location) {
        $title = "⏰ Class Reminder - Tomorrow!";
        $message = "Reminder: You have {$class_name} tomorrow at " . 
                   date('g:i A', strtotime($schedule)) . " at {$location}. Don't forget!";
        
        return $this->create($user_id, 'class_reminder', $title, $message, 'member');
    }
    
    /**
     * 5. Membership Renewal Confirmation
     */
    public function sendMembershipRenewalConfirmation($user_id, $new_expiry_date) {
        $title = "🎉 Membership Renewed!";
        $message = "Thank you! Your membership has been successfully renewed until " . 
                   date('F j, Y', strtotime($new_expiry_date)) . ". Keep crushing your goals!";
        
        return $this->create($user_id, 'membership_renewal', $title, $message, 'member');
    }
    
    /**
     * 6. Promo Notification
     */
    public function sendPromoNotification($user_id, $promo_title, $promo_description, $promo_expiry) {
        $title = "🎁 {$promo_title}";
        $message = "{$promo_description} Valid until " . 
                   date('F j, Y', strtotime($promo_expiry)) . ". Don't miss out!";
        
        return $this->create($user_id, 'promo', $title, $message, 'member');
    }
    
    /**
     * 7. Class Cancellation
     */
    public function sendClassCancellation($user_id, $class_name, $schedule, $reason = '') {
        $title = "❌ Class Cancelled";
        $message = "Sorry, {$class_name} scheduled on " . 
                   date('F j, Y g:i A', strtotime($schedule)) . " has been cancelled.";
        
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        
        return $this->create($user_id, 'class_cancelled', $title, $message, 'member');
    }
    
    /**
     * 8. Class Rescheduling
     */
    public function sendClassReschedule($user_id, $class_name, $old_schedule, $new_schedule) {
        $title = "📅 Class Rescheduled";
        $message = "Your {$class_name} has been rescheduled from " . 
                   date('M j, g:i A', strtotime($old_schedule)) . " to " . 
                   date('M j, g:i A', strtotime($new_schedule)) . ".";
        
        return $this->create($user_id, 'class_schedule', $title, $message, 'member');
    }
    
    /**
     * 9. New Product Available
     */
    public function sendNewProductNotification($user_id, $product_name, $category, $price) {
        $title = "🛍️ New Product Available!";
        $message = "Check out our new {$category} product: {$product_name}! Only ₱" . 
                   number_format($price, 2) . ". Available now at our shop!";
        
        return $this->create($user_id, 'product', $title, $message, 'member');
    }
    
    /**
     * 10. Check-in Confirmation (Optional)
     */
    public function sendCheckInConfirmation($user_id) {
        $title = "✅ Check-in Successful";
        $message = "You checked in at REMER Fitness Gym on " . date('M j, Y g:i A') . ". Have a great workout!";
        
        return $this->create($user_id, 'check_in', $title, $message, 'member');
    }
    
    // ============================================
    // 🔥 STAFF/COACH NOTIFICATION METHODS
    // ============================================
    
    /**
     * Staff: New Class Assignment Request
     */
    public function sendClassAssignmentRequest($staff_id, $class_name, $schedule, $class_id) {
        $title = "📋 New Class Assignment";
        $message = "You have been assigned to teach {$class_name} on " . 
                   date('F j, Y g:i A', strtotime($schedule)) . ". Please confirm your availability.";
        
        return $this->create($staff_id, 'class_assignment', $title, $message, 'coach', $class_id);
    }
    
    /**
     * Staff: Class Booking Alert
     */
    public function sendNewBookingAlert($staff_id, $member_name, $class_name) {
        $title = "👤 New Class Booking";
        $message = "{$member_name} has booked your {$class_name} class.";
        
        return $this->create($staff_id, 'booking_alert', $title, $message, 'coach');
    }
    
    /**
     * Staff: Payment Confirmation for Session
     */
    public function sendPaymentConfirmation($staff_id, $amount, $member_name) {
        $title = "💵 Payment Received";
        $message = "Payment of ₱" . number_format($amount, 2) . " received from {$member_name}.";
        
        return $this->create($staff_id, 'payment_received', $title, $message, 'coach');
    }
}
?>