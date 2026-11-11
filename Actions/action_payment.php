<?php
session_start();
require_once 'dbconnect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_SESSION['user_id'];
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'online');
    $amount = floatval($_POST['amount'] ?? 0);
    $membership_type = mysqli_real_escape_string($conn, $_POST['membership_type'] ?? 'monthly');
    $reference_number = mysqli_real_escape_string($conn, $_POST['reference_number'] ?? uniqid('PAY-'));
    $transaction_type = mysqli_real_escape_string($conn, $_POST['transaction_type'] ?? 'membership');

    // Validation
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    $valid_types = [
        'monthly' => 1800,
        'daily_student' => 100,
        'daily_regular' => 120
    ];

    if (!array_key_exists($membership_type, $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid membership type']);
        exit;
    }

    if ($amount != $valid_types[$membership_type]) {
        echo json_encode(['success' => false, 'message' => 'Amount does not match membership type']);
        exit;
    }

    // Get member info
    $member_sql = "SELECT fname, lname, email FROM user WHERE user_id = $member_id";
    $member_result = mysqli_query($conn, $member_sql);

    if (!$member_result || mysqli_num_rows($member_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $member = mysqli_fetch_assoc($member_result);
    $full_name = $member['fname'] . ' ' . $member['lname'];

    // Calculate membership expiry date
    $start_date = date('Y-m-d');
    if ($membership_type === 'monthly') {
        $end_date = date('Y-m-d', strtotime('+1 month'));
        $type_display = 'Monthly Membership';
        $duration_text = '1 month';
    } elseif ($membership_type === 'daily_student') {
        $end_date = date('Y-m-d', strtotime('+1 day'));
        $type_display = 'Daily Pass (Student)';
        $duration_text = '1 day';
    } else {
        $end_date = date('Y-m-d', strtotime('+1 day'));
        $type_display = 'Daily Pass (Regular)';
        $duration_text = '1 day';
    }

   
   // Handle proof of payment image upload
$proof_path = null;

if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'C:/xampp/htdocs/Remer Fitness Gym/Remer Fitness Gym/uploads/payments/payment_proofs/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique file name
    $file_ext = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
    $file_name = 'proof_' . time() . '_' . uniqid() . '.' . $file_ext;
    $proof_path = $upload_dir . $file_name;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['proof_image']['tmp_name'], $proof_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload proof of payment.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No proof of payment uploaded.']);
    exit;
}

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Save transaction
        // Hardcoded proof of payment image path
$proof_path = 'uploads/payments/payment_proofs/receipt_proof.png'; // relative to your project root
// (Full path if needed: 'C:/xampp/htdocs/Remer Fitness Gym/Remer Fitness Gym/uploads/payments/payment_proofs/receipt_proof.png')

$trans_sql = "INSERT INTO transactions 
(user_id, total_amount, payment_method, reference_number, proof_of_payment, transaction_type, status, date, created_at) 
VALUES ($member_id, $amount, '$payment_method', '$reference_number', '$proof_path', '$transaction_type', 'pending_verification', NOW(), NOW())";



        if (!mysqli_query($conn, $trans_sql)) {
            throw new Exception('Failed to save transaction');
        }

        $transaction_id = mysqli_insert_id($conn);
        // Handle proof of payment image upload
$proof_path = null;
if (!empty($_FILES['proof_image']['name'])) {
    // Your absolute folder path
    $upload_dir = 'C:/xampp/htdocs/Remer Fitness Gym/Remer Fitness Gym/uploads/payments/payment_proofs/';
    
    // Create folder if it doesn't exist
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    // Prepare file info
    $file_tmp = $_FILES['proof_image']['tmp_name'];
    $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['proof_image']['name']));
    $target_path = $upload_dir . $file_name;

    // Validate and upload
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (in_array($_FILES['proof_image']['type'], $allowed_types)) {
        if (move_uploaded_file($file_tmp, $target_path)) {
            $proof_path = $target_path;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload proof of payment.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG or PNG allowed.']);
        exit;
    }
}

if (!is_dir($receipt_dir)) {
    mkdir($receipt_dir, 0777, true);
}

// File name and full path
$receipt_filename = "receipt_$transaction_id.pdf";
$absolute_path = $receipt_dir . $receipt_filename;

// Save PDF file
file_put_contents($absolute_path, $dompdf->output());

// Web-accessible path (for admin dashboard)
$web_path = 'uploads/payments/payment_proofs/' . $receipt_filename;

// Save the web path in DB
mysqli_query($conn, "UPDATE transactions SET proof_of_payment = '$web_path' WHERE transaction_id = $transaction_id");

        // Update transaction with receipt path
        mysqli_query($conn, "UPDATE transactions SET proof_of_payment = '$receipt_path' WHERE transaction_id = $transaction_id");

        // Mark user status pending
        mysqli_query($conn, "UPDATE user SET status = 'Pending' WHERE user_id = $member_id");

        // Notifications (Member)
        $member_title = "⏳ Payment Submitted";
        $member_message = "Your payment of ₱" . number_format($amount,2) . " for your membership has been received and is pending verification.";
        mysqli_query($conn, "
            INSERT INTO notifications (user_id, type, title, message, related_id, is_read, is_sent, created_at)
            VALUES ($member_id, 'payment', '$member_title', '$member_message', $transaction_id, 0, 0, NOW())
        ");

        // Notification for Admin
        $admin_title = "💰 New Payment to Verify";
        $admin_message = "$full_name submitted a payment of ₱" . number_format($amount,2) . " for $type_display via $payment_method. Reference: $reference_number";
        mysqli_query($conn, "
            INSERT INTO notifications (user_id, type, title, message, target_user_type, related_id, is_read, is_sent, created_at)
            SELECT user_id, 'payment', '$admin_title', '$admin_message', 'Admin', $transaction_id, 0, 0, NOW()
            FROM user WHERE role = 'Admin' AND status = 'Active'
        ");

        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Payment submitted! Your membership is now pending verification.',
            'membership_end' => date('M d, Y', strtotime($end_date)),
            'transaction_id' => $transaction_id,
            'duration' => $duration_text,
            'type' => $type_display
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Payment processing failed. Please try again.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
