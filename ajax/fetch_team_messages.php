<?php
// ajax/fetch_team_messages.php
error_reporting(0); // Disable notices causing JSON errors
ini_set('display_errors', 0);

ob_start();

require_once '../config/db.php';
// db.php handles session_start check

// Clear any buffered output (warnings, whitespace from includes)
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['employee_db_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$team_id = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;
$my_id = $_SESSION['employee_db_id'];

if ($team_id <= 0) {
    echo json_encode(['messages' => []]);
    exit;
}

try {
    // Validate Membership
    $chk = $pdo->prepare("SELECT id FROM team_members WHERE team_id = ? AND emp_id = ?");
    $chk->execute([$team_id, $my_id]);

    if ($chk->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT m.*, e.first_name, e.last_name 
            FROM team_chat m 
            JOIN employees e ON m.sender_id = e.id 
            WHERE m.team_id = ? AND m.id > ? 
            ORDER BY m.id ASC
        ");
        $stmt->execute([$team_id, $last_id]);
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['messages' => $msgs]);
    } else {
        echo json_encode(['error' => 'Unauthorized']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'DB Error']);
}
?>