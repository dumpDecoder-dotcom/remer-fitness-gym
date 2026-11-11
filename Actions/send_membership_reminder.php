<?php
session_start();
include 'dbconnect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila');

// Send reminder 5 days before expiration
$days_before = 5;
$target_date = date('Y-m-d', strtotime("+$days_before days"));

// Get members whose membership will end in 5 days
$query = "
    SELECT user_id, fname, lname, email, member_expiry 
    FROM user 
    WHERE role = 'Member'
      AND status = 'Active'
      AND member_expiry = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $target_date);
$stmt->execute();
$result = $stmt->get_result();

$success_count = 0;
$error_count = 0;
$error_log = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $mail = new PHPMailer(true);

        try {
            // SMTP setup
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = (require __DIR__ . '/mail_config.php')['username'];
            $mail->Password = (require __DIR__ . '/mail_config.php')['password']; // ⚠️ Store in config file!
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom('fitnessremer@gmail.com', 'Remer Fitness Gym');
            $mail->addAddress($row['email'], $row['fname'] . ' ' . $row['lname']);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = '⚠️ Membership Expiry Reminder - Action Required';
            
            $expiry_date = date('F j, Y', strtotime($row['member_expiry']));
            $full_name = htmlspecialchars($row['fname'] . ' ' . $row['lname']);
            
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .expiry-box { background: #fff3cd; border-left: 4px solid #ff6a00; padding: 15px; margin: 20px 0; border-radius: 5px; }
                        .cta-button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🔔 Membership Expiry Reminder</h1>
                        </div>
                        <div class='content'>
                            <h2>Hi {$row['fname']}! 👋</h2>
                            
                            <p>This is a friendly reminder that your membership at <strong>Remer Fitness Gym</strong> is about to expire.</p>
                            
                            <div class='expiry-box'>
                                <strong>⏰ Expiry Date:</strong> {$expiry_date}<br>
                                <strong>📅 Days Remaining:</strong> {$days_before} days
                            </div>
                            
                            <p><strong>Don't let your fitness journey stop!</strong></p>
                            
                            <p>Renew your membership today to continue enjoying:</p>
                            <ul>
                                <li>✅ Full access to gym facilities</li>
                                <li>✅ Professional coaching and classes</li>
                                <li>✅ Personalized workout plans</li>
                                <li>✅ Exclusive member benefits</li>
                            </ul>
                            
                            <center>
                                <a href='https://yourgym.com/renew' class='cta-button'>Renew Membership Now</a>
                            </center>
                            
                            <p>If you have any questions, feel free to contact us at the gym or reply to this email.</p>
                            
                            <p><strong>Stay strong, stay fit! 💪</strong></p>
                            
                            <p>Best regards,<br>
                            <strong>Remer Fitness Gym Team</strong></p>
                        </div>
                        <div class='footer'>
                            <p>This is an automated reminder. Please do not reply to this email.</p>
                            <p>&copy; " . date('Y') . " Remer Fitness Gym. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Plain text alternative
            $mail->AltBody = "Hi {$row['fname']},\n\n"
                . "This is a reminder that your membership at Remer Fitness Gym will expire on {$expiry_date}.\n"
                . "You have {$days_before} days remaining.\n\n"
                . "Please renew your membership to continue enjoying our facilities.\n\n"
                . "Thank you for staying fit with us!\n\n"
                . "Best regards,\n"
                . "Remer Fitness Gym Team";

            $mail->send();
            $success_count++;
            
            // 🔔 CREATE NOTIFICATION FOR THE MEMBER
            createExpiryNotification($conn, $row['user_id'], $days_before, $row['member_expiry']);
            
        } catch (Exception $e) {
            $error_count++;
            $error_log[] = [
                'email' => $row['email'],
                'error' => $mail->ErrorInfo
            ];
        }
    }
}

// 🔔 ALSO NOTIFY ADMINS ABOUT EXPIRING MEMBERSHIPS
if ($success_count > 0) {
    notifyAdminsExpiringMembers($conn, $success_count, $days_before);
}

// Optional: Log results
if ($success_count > 0 || $error_count > 0) {
    error_log("Membership Reminders Sent: {$success_count} success, {$error_count} failed");
}

// Display results (for manual runs)
if (php_sapi_name() !== 'cli') {
    echo "<h2>Membership Reminder Results</h2>";
    echo "<p>✅ Successfully sent: {$success_count}</p>";
    echo "<p>❌ Failed: {$error_count}</p>";
    
    if (!empty($error_log)) {
        echo "<h3>Errors:</h3><ul>";
        foreach ($error_log as $error) {
            echo "<li>{$error['email']}: {$error['error']}</li>";
        }
        echo "</ul>";
    }
}

exit;

/**
 * 🔔 Create notification for member about expiring membership
 */
function createExpiryNotification($conn, $user_id, $days_left, $expiry_date) {
    $title = "Membership Expiring Soon ⚠️";
    $message = "Your membership expires in {$days_left} days (on " . date('F j, Y', strtotime($expiry_date)) . "). Please renew to continue enjoying our services.";
    
    $query = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, created_at) 
              VALUES (?, 'membership_expiry', ?, ?, 'member', 0, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iss', $user_id, $title, $message);
    $stmt->execute();
}

/**
 * 🔔 Notify admins about members with expiring memberships
 */
function notifyAdminsExpiringMembers($conn, $count, $days_left) {
    $title = "Membership Expiry Alert";
    $message = "{$count} member(s) have memberships expiring in {$days_left} days. Follow up on renewals.";
    
    $query = "INSERT INTO notifications (user_id, type, title, message, target_user_type, is_read, created_at)
              SELECT 
                  user_id,
                  'membership_expiry',
                  ?,
                  ?,
                  'admin',
                  0,
                  NOW()
              FROM user 
              WHERE role = 'Admin' AND status = 'Active'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $title, $message);
    $stmt->execute();
}
?>