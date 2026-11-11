<?php
// ✅ Always start a session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Ensure the session cookie is shared across ALL folders
    ini_set('session.cookie_path', '/');
    session_start();
}

$db_name = "remer_fitness_db";
$servername = "localhost";
$username = "root";
$password = "";
$port = 3307; // ✅ your MySQL port

$conn = mysqli_connect($servername, $username, $password, $db_name, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
