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

    if ($team_id <= 0 || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }

    try {
        // Validate Team Membership
        $chk = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND emp_id = ?");
        $chk->execute([$team_id, $sender_id]);

        if ($chk->rowCount() > 0) {
            $stmt = $pdo->prepare("INSERT INTO team_chat (team_id, sender_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$team_id, $sender_id, $message]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not a member']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error']);
    }
}
?>