<?php
// ajax/send_team_message.php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();

require_once '../config/db.php';
// db.php handles session_start check

// Clear any buffered output
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['employee_db_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_id = $_SESSION['employee_db_id'];
    $team_id = isset($_POST['team_id']) ? (int) $_POST['team_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($team_id <= 0 || (empty($message) && empty($_FILES['attachment']['name']))) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }

    try {
        // Validate Team Membership
        $chk = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND emp_id = ?");
        $chk->execute([$team_id, $sender_id]);

        if ($chk->rowCount() > 0) {
            $attachment_path = null;
            $attachment_name = null;

            // Handle File Upload
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/chat_attachments/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_name = basename($_FILES['attachment']['name']);
                $target_file = $upload_dir . uniqid() . '_' . $file_name;

                // Allow all file types for now as per requirement "send the document"
                // But maybe block dangerous executables?
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $blocked = ['php', 'exe', 'bat', 'sh'];

                if (!in_array($ext, $blocked)) {
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                        $attachment_path = $target_file; // Store relative path or full path? 
                        // It's better to store path relative to root or consistently. 
                        // The existing code uses '../ajax' so let's stick to relative for now or just the filename?
                        // Let's store 'uploads/chat_attachments/...' (relative to project root)
                        $attachment_path = 'uploads/chat_attachments/' . basename($target_file);
                        $attachment_name = $file_name;
                    }
                }
            }

            $stmt = $pdo->prepare("INSERT INTO team_chat (team_id, sender_id, message, attachment, attachment_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$team_id, $sender_id, $message, $attachment_path, $attachment_name]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not a member']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    }
}
?>