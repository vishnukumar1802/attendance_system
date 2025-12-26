<?php
// config/db.php

$host = 'localhost';
$dbname = 'attendance_db';
$user = 'root'; // Default XAMPP user
$pass = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
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