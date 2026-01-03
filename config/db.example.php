<?php
// config/db.php
// COPY THIS FILE TO 'db.php' AND EDIT THE SETTINGS

$host = 'localhost';
$dbname = 'attendance_db';
$user = 'root';
$pass = ''; // EDIT THIS LINE: Add your XAMPP/MySQL password here

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("ERROR: Could not connect to database. " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL - Update if your folder name is different
define('BASE_URL', 'http://localhost/attendance-system/');

function clean_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function format_money($amount)
{
    return '₹' . number_format($amount, 2);
}
