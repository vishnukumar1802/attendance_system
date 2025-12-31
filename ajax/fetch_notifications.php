<?php
// ajax/fetch_notifications.php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_role'])) {
    exit(json_encode(['count' => 0, 'notifications' => []]));
}

$my_id = ($_SESSION['user_role'] == 'admin') ? $_SESSION['admin_id'] : $_SESSION['employee_db_id']; // admin table has 'id', employees 'id'? 
// Wait, admin session usually is 'admin_id'. Employee is 'employee_db_id'.
// Need to ensure notifications table uses these IDs correctly.
// Install v2 said: user_id INT. 

// Fetch Count
$cnt_sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
$cnt_stmt = $pdo->prepare($cnt_sql);
$cnt_stmt->execute([$my_id]);
$unread_count = $cnt_stmt->fetchColumn();

// Fetch Latest 5
$fetch_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$fetch_stmt = $pdo->prepare($fetch_sql);
$fetch_stmt->execute([$my_id]);
$notifs = $fetch_stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'count' => $unread_count,
    'notifications' => $notifs
]);
?>