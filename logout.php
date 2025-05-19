<?php
session_start();

// Log the logout action if user is logged in
if(isset($_SESSION["user_id"])) {
    require_once 'config.php';
    
    $user_id = $_SESSION["user_id"];
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO access_logs (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $ip, $user_agent);
    mysqli_stmt_execute($stmt);
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("location: index.php");
exit;
?> 