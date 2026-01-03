<?php
// ajax/fetch_notifications.php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
require_once '../config/db.php';
// db.php handles session_start check
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_role'])) {
    exit(json_encode(['count' => 0, 'notifications' => []]));
}

if (isset($_SESSION['admin_id'])) {
    $my_id = $_SESSION['admin_id'];
} elseif (isset($_SESSION['employee_db_id'])) {
    $my_id = $_SESSION['employee_db_id'];
} else {
    exit(json_encode(['count' => 0, 'notifications' => []]));
}
session_write_close();
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