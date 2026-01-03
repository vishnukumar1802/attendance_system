<?php
// config/db.php

$host = 'localhost';
$dbname = 'attendance_db';
$user = 'root';
$pass = ''; // EDIT THIS LINE: Add your XAMPP/MySQL password if you have one. Default is usually empty.

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Check for Access Denied (Error 1045)
    if ($e->getCode() == 1045) {
        die("<div style='color:red; font-family:sans-serif; padding:20px; border:1px solid red; background:#fff0f0; margin:20px;'>
                <h3>Access Denied</h3>
                <p><strong>Message:</strong> " . $e->getMessage() . "</p>
                <p>It appears your MySQL 'root' user has a password set.</p>
                <p>Please open <code>config/db.php</code> and set the <code>\$pass</code> variable to your actual password.</p>
             </div>");
    }
    // Other errors (e.g., database not found, server down)
    die("ERROR: Could not connect to database. " . $e->getMessage());
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