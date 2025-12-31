<?php
// ajax/send_message.php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_id = ($_SESSION['user_role'] == 'admin') ? $_SESSION['admin_id'] : $_SESSION['employee_db_id'];
    $sender_role = $_SESSION['user_role'];
    $receiver_id = (int) $_POST['receiver_id']; // For employee this is likely 1 (Admin)
    $msg = trim($_POST['message']);

    if (!empty($msg) && $receiver_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, sender_role, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$sender_id, $sender_role, $receiver_id, $msg]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Empty message or invalid receiver']);
    }
}
?>