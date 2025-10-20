<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // Uncomment if using HTTPS AND FOR DEPLOYING

include __DIR__ . '/../db.php'; // ensures it always points to root db.php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function handle_error($user_message = "Something went wrong. Please try again later.", $error_detail = null) {
    // Log the detailed error for developers
    if ($error_detail) {
        error_log("[" . date('Y-m-d H:i:s') . "] ERROR: " . $error_detail . "\n", 3, __DIR__ . "/error_log.txt");
    }

    // Detect if it's an AJAX request
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        echo json_encode([
            "status" => "error",
            "message" => "🌼 Oops! $user_message Our team is already looking into it."
        ]);
    } else {
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>🌸 BloomTrack | System Notice</title>
            <style>
                body {
                    font-family: 'Poppins', sans-serif;
                    background: #faf8f5;
                    color: #3f2a1c;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .error-box {
                    background: #fff;
                    padding: 40px 50px;
                    border-radius: 20px;
                    text-align: center;
                    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
                    max-width: 480px;
                    border: 2px solid #e0c3a3;
                }
                h1 {
                    font-size: 28px;
                    margin-bottom: 10px;
                    color: #5c4033;
                }
                p {
                    margin: 10px 0;
                    font-size: 16px;
                    color: #6b4e3d;
                }
                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background: #a77255;
                    color: #fff;
                    border-radius: 20px;
                    text-decoration: none;
                    transition: 0.3s;
                }
                a:hover {
                    background: #d19a6e;
                    transform: scale(1.05);
                }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h1>🌷 Oops! Something went wrong</h1>
                <p>$user_message</p>
                <p>We’re working to fix the issue. Please try again shortly.</p>
                <a href='home.php'>⬅ Return to Dashboard</a>
            </div>
        </body>
        </html>";
    }

    exit;
}

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
