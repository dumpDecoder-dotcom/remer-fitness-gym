<?php
require_once 'notification.php';

class NotificationGenerator {
    private $conn;
    private $notif;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->notif = new Notification($db_connection);
    }
    
    // 1. MEMBERSHIP EXPIRY ALERTS
    public function checkMembershipExpiry() {
        // Check memberships expiring in 7 days
        $stmt = $this->conn->query("
            SELECT m.id, m.user_id, m.expiry_date, u.name, u.email 
            FROM memberships m
            JOIN members u ON m.user_id = u.id
            WHERE m.status = 'active' 
            AND m.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.user_id = m.user_id 
                AND n.type = 'membership_expiry' 
                AND n.related_id = m.id 
                AND DATE(n.created_at) = CURDATE()
            )
        ");
        
        while ($row = $stmt->fetch_assoc()) {
            $days_left = ceil((strtotime($row['expiry_date']) - time()) / 86400);
            
            $title = "Membership Expiring Soon!";
            $message = "Hi {$row['name']}, your membership will expire in {$days_left} day(s) on " . 
                       date('F j, Y', strtotime($row['expiry_date'])) . ". Renew now to continue enjoying our services!";
            
            $this->notif->create($row['user_id'], 'membership_expiry', $title, $message, $row['id']);
            
            // Send email (optional)
            $this->sendEmail($row['email'], $title, $message);
        }
    }
    
    // 2. PAYMENT DUE REMINDERS
    public function checkPaymentDue() {
        // Check unpaid invoices
        $stmt = $this->conn->query("
            SELECT i.id, i.user_id, i.amount, i.due_date, u.name, u.email 
            FROM invoices i
            JOIN members u ON i.user_id = u.id
            WHERE i.status = 'pending' 
            AND i.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.user_id = i.user_id 
                AND n.type = 'payment_due' 
                AND n.related_id = i.id 
                AND DATE(n.created_at) = CURDATE()
            )
        ");
        
        while ($row = $stmt->fetch_assoc()) {
            $days_left = ceil((strtotime($row['due_date']) - time()) / 86400);
            
            $title = "Payment Due Reminder";
            $message = "Hi {$row['name']}, you have a payment of ₱" . number_format($row['amount'], 2) . 
                       " due in {$days_left} day(s). Please settle your payment to avoid service interruption.";
            
            $this->notif->create($row['user_id'], 'payment_due', $title, $message, $row['id']);
            
            // Send email
            $this->sendEmail($row['email'], $title, $message);
        }
    }
    
    // 3. CLASS BOOKING CONFIRMATIONS
    public function sendBookingConfirmation($booking_id) {
        $stmt = $this->conn->prepare("
            SELECT b.id, b.user_id, b.class_id, c.name as class_name, 
                   c.schedule, c.instructor, u.name as user_name, u.email
            FROM class_bookings b
            JOIN classes c ON b.class_id = c.id
            JOIN members u ON b.user_id = u.id
            WHERE b.id = ?
        ");
        
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        if ($row) {
            $title = "Class Booking Confirmed!";
            $message = "Hi {$row['user_name']}, your booking for {$row['class_name']} " .
                       "on " . date('F j, Y g:i A', strtotime($row['schedule'])) . 
                       " with {$row['instructor']} has been confirmed!";
            
            $this->notif->create($row['user_id'], 'class_booking', $title, $message, $booking_id);
            
            // Send email
            $this->sendEmail($row['email'], $title, $message);
        }
    }
    
    // 4. CLASS REMINDERS (1 day before)
    public function sendClassReminders() {
        $stmt = $this->conn->query("
            SELECT b.id, b.user_id, b.class_id, c.name as class_name, 
                   c.schedule, c.location, u.name as user_name, u.email
            FROM class_bookings b
            JOIN classes c ON b.class_id = c.id
            JOIN members u ON b.user_id = u.id
            WHERE b.status = 'confirmed'
            AND c.schedule BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) 
                              AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
            AND NOT EXISTS (
                SELECT 1 FROM notifications n 
                WHERE n.user_id = b.user_id 
                AND n.type = 'class_reminder' 
                AND n.related_id = b.id 
                AND DATE(n.created_at) = CURDATE()
            )
        ");
        
        while ($row = $stmt->fetch_assoc()) {
            $title = "Class Reminder - Tomorrow!";
            $message = "Hi {$row['user_name']}, reminder that you have {$row['class_name']} " .
                       "tomorrow at " . date('g:i A', strtotime($row['schedule'])) . 
                       " at {$row['location']}. See you there!";
            
            $this->notif->create($row['user_id'], 'class_reminder', $title, $message, $row['id']);
            
            // Send email
            $this->sendEmail($row['email'], $title, $message);
        }
    }
    
    // 5. PROMO NOTIFICATIONS
    public function sendPromoNotification($promo_id, $user_ids = null) {
        // Get promo details
        $stmt = $this->conn->prepare("SELECT * FROM promos WHERE id = ?");
        $stmt->bind_param("i", $promo_id);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();
        
        if (!$promo) return false;
        
        // Get target users
        if ($user_ids === null) {
            // Send to all active members
            $users_stmt = $this->conn->query("
                SELECT id, name, email FROM members WHERE status = 'active'
            ");
        } else {
            // Send to specific users
            $ids = implode(',', array_map('intval', $user_ids));
            $users_stmt = $this->conn->query("
                SELECT id, name, email FROM members WHERE id IN ($ids)
            ");
        }
        
        $title = "🔥 New Promo: {$promo['title']}";
        $message = "Hi! Check out our latest promo: {$promo['description']}. " .
                   "Valid until " . date('F j, Y', strtotime($promo['expiry_date'])) . ". Don't miss out!";
        
        while ($user = $users_stmt->fetch_assoc()) {
            $this->notif->create($user['id'], 'promo', $title, $message, $promo_id);
            
            // Send email
            $this->sendEmail($user['email'], $title, $message);
        }
        
        return true;
    }
    
    // Email sender (you can integrate with PHPMailer or other service)
    private function sendEmail($to, $subject, $message) {
        // Basic PHP mail (replace with PHPMailer for production)
        $headers = "From: Remer Fitness Gym <noreply@remerfitness.com>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $html_message = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center;'>
                    <h2 style='color: white;'>Remer Fitness Gym</h2>
                </div>
                <div style='padding: 20px; background: #f8f9fa;'>
                    <h3>{$subject}</h3>
                    <p>{$message}</p>
                    <a href='https://yoursite.com/notifications' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>View Details</a>
                </div>
                <div style='padding: 10px; text-align: center; background: #333; color: white; font-size: 12px;'>
                    © 2025 Remer Fitness Gym. All rights reserved.
                </div>
            </body>
            </html>
        ";
        
        return mail($to, $subject, $html_message, $headers);
    }
}