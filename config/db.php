<?php
// config/db.php

$host = 'localhost';
$dbname = 'attendance_db';
$user = 'root';

// Try connecting with different common password combinations for local dev
$passwords = ['', 'root', 'admin', 'password'];
$pdo = null;
$error_msg = '';

foreach ($passwords as $try_pass) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $try_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        break; // Connected successfully
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        continue;
    }
}

if (!$pdo) {
    die("<div style='color:red; font-family:sans-serif; padding:20px; border:1px solid red; background:#fff0f0; margin:20px;'>
            <h3>Database Connection Failed</h3>
            <p>Could not connect to database '<strong>$dbname</strong>' as user '<strong>$user</strong>'.</p>
            <p><strong>Error:</strong> $error_msg</p>
            <p>Please check <code>config/db.php</code> and update the <code>\$pass</code> variable with your MySQL password.</p>
         </div>");
}

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration - CHANGE THIS if your XAMPP folder name is different
define('BASE_URL', 'http://localhost/attendance-system/');

// Helper function for sanitization
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Float formatting for currency
function format_money($amount)
{
    return '₹' . number_format($amount, 2);
}
?>