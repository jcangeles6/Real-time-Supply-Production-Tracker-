<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // Uncomment if using HTTPS AND FOR DEPLOYING

session_start();
include 'db.php';

// Pages that do NOT require login
$no_redirect_pages = ['login.php','register.php','forgot_password.php','reset_password.php'];

if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), $no_redirect_pages)) {
    header("Location: login.php");
    exit();
}
function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header("Location: home.php");
        exit();
    }
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
