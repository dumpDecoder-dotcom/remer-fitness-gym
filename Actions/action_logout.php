<?php
session_start();
include 'dbconnect.php';

/*
 * LOGOUT: no notifications created.
 * (If you need an internal audit trail later, see the optional snippet below.)
 */

// (Optional) If you want to record an audit entry WITHOUT using the notifications table,
// uncomment this block and make sure you have an `audit_log` table.
/*
if (isset($_SESSION['user_id'])) {
    $uid  = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'] ?? '';
    if ($stmt = $conn->prepare("INSERT INTO audit_log (user_id, role, action, created_at) VALUES (?, ?, 'logout', NOW())")) {
        $stmt->bind_param('is', $uid, $role);
        $stmt->execute();
        $stmt->close();
    }
}
*/

// Destroy session data
$_SESSION = [];
session_destroy();

// Remove session cookie if present
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Redirect to homepage
header("Location: ../homepage.php?logout=success");
exit;
