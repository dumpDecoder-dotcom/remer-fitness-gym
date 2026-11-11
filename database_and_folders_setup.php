<?php
/**
 * 🚀 REMER FITNESS GYM - Complete Setup
 * Run once to setup folders AND database
 */

echo "<h1>🚀 REMER Fitness Gym - Complete Setup</h1>";
echo "<hr>";

// ==========================================
// 1. CREATE FOLDERS
// ==========================================
echo "<h2>📁 Step 1: Creating Upload Folders</h2>";

$base_dir = __DIR__;
$upload_dir = $base_dir . '/uploads/payments/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "✅ Created: uploads/payments/<br>";
} else {
    echo "✓ Already exists: uploads/payments/<br>";
}

$htaccess_path = $upload_dir . '.htaccess';
$htaccess_content = "Options -Indexes\n<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>";

if (!file_exists($htaccess_path)) {
    file_put_contents($htaccess_path, $htaccess_content);
    echo "✅ Created: .htaccess<br>";
} else {
    echo "✓ Already exists: .htaccess<br>";
}

// ==========================================
// 2. UPDATE DATABASE
// ==========================================
echo "<br><h2>🗄️ Step 2: Updating Database</h2>";

include 'Actions/dbconnect.php';

$queries = [
    // Update user table
    "ALTER TABLE `user`
     ADD COLUMN IF NOT EXISTS `membership_type` VARCHAR(20) DEFAULT 'monthly',
     ADD COLUMN IF NOT EXISTS `membership_start` DATE NULL,
     ADD COLUMN IF NOT EXISTS `membership_end` DATE NULL,
     ADD COLUMN IF NOT EXISTS `last_login` TIMESTAMP NULL",
    
    // Update notifications table
    "ALTER TABLE `notifications`
     ADD COLUMN IF NOT EXISTS `related_id` INT(11) NULL,
     ADD COLUMN IF NOT EXISTS `is_sent` TINYINT(1) DEFAULT 0,
     ADD COLUMN IF NOT EXISTS `target_user_type` VARCHAR(20) NULL",
    
    // Create transactions table
    "CREATE TABLE IF NOT EXISTS `transactions` (
      `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
      `user_id` INT(11) NOT NULL,
      `amount` DECIMAL(10,2) NOT NULL,
      `payment_method` VARCHAR(50) NOT NULL,
      `reference_number` VARCHAR(100) NULL,
      `proof_of_payment` VARCHAR(255) NULL,
      `transaction_type` VARCHAR(50) DEFAULT 'membership',
      `status` VARCHAR(20) DEFAULT 'completed',
      `transaction_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `notes` TEXT NULL,
      `processed_by` INT(11) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`transaction_id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $index => $query) {
    if (mysqli_query($conn, $query)) {
        echo "✅ Query " . ($index + 1) . " executed successfully<br>";
    } else {
        echo "⚠️ Query " . ($index + 1) . " skipped or already exists<br>";
    }
}

echo "<br><hr>";
echo "<h2>🎉 Setup Complete!</h2>";
echo "<p><strong style='color: green;'>Your system is ready to use!</strong></p>";
echo "<p style='color: red;'><strong>IMPORTANT: Delete this file now for security!</strong></p>";
?>