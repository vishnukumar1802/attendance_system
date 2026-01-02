<?php
// ajax/fetch_messages.php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
require_once '../config/db.php';
// db.php handles session_start check
ob_clean();

header('Content-Type: application/json');

// Validations
if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$my_id = ($_SESSION['user_role'] == 'admin') ? $_SESSION['admin_id'] : $_SESSION['employee_db_id'];
$my_role = $_SESSION['user_role']; // 'admin' or 'employee'

// Parameters
$last_id = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;
$chat_partner_id = isset($_GET['partner_id']) ? (int) $_GET['partner_id'] : 0;

// Admin chats with Employee (partner is emp_id)
// Employee chats with Admin (partner is admin_id - usually only 1 admin, ID 1?)
// Let's assume Employee always chats with Admin ID 1 for now, or the system supports multiple admins.
// Prompt says "Admin <-> Employee". I'll implement generic ID.

if ($chat_partner_id == 0 && $my_role == 'employee') {
    $chat_partner_id = 1; // Default to Admin ID 1
}

$partner_role = ($my_role == 'admin') ? 'employee' : 'admin';

// Fetch Logic
// Get messages where:
// (Me -> Them) OR (Them -> Me)
// AND id > $last_id
// ORDER BY id ASC (Chronological)

$sql = "SELECT * FROM messages 
        WHERE id > ? 
        AND (
            (sender_id = ? AND sender_role = ? AND receiver_id = ?) 
            OR 
            (sender_id = ? AND sender_role = ? AND receiver_id = ?)
        )
        ORDER BY id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $last_id,
    $my_id,
    $my_role,
    $chat_partner_id,
    $chat_partner_id,
    $partner_role,
    $my_id
]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as Read (Only those sent TO me)
if (count($messages) > 0) {
    $read_ids = [];
    foreach ($messages as $m) {
        if ($m['receiver_id'] == $my_id && $m['is_read'] == 0) {
            $read_ids[] = $m['id'];
        }
    }
    if (!empty($read_ids)) {
        $in = str_repeat('?,', count($read_ids) - 1) . '?';
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id IN ($in)")->execute($read_ids);
    }
}

// Return JSON
header('Content-Type: application/json');
echo json_encode(['messages' => $messages]);
?>