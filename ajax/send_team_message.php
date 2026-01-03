<?php
// ajax/send_team_message.php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['employee_db_id'])) {
    http_response_code(403);
    exit;
}

$sender_id = $_SESSION['employee_db_id'];
$team_id = $_POST['team_id'];
$message = trim($_POST['message']);

// File Upload
$attachment_path = null;
$attachment_name = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
    if (in_array($ext, $allowed)) {
        $newName = uniqid() . '.' . $ext;
        $dest = '../uploads/chat_files/' . $newName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
            $attachment_path = 'uploads/chat_files/' . $newName;
            $attachment_name = $_FILES['attachment']['name'];
        }
    }
}

if (!empty($message) || $attachment_path) {
    // Verify membership
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ? AND emp_id = ?");
    $stmt->execute([$team_id, $sender_id]);
    if ($stmt->fetchColumn() > 0) {
        $ins = $pdo->prepare("INSERT INTO team_chat (team_id, sender_id, message, attachment_path, attachment_name) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$team_id, $sender_id, $message, $attachment_path, $attachment_name]);
    }
}
?>