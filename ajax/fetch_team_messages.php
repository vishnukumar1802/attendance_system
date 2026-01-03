<?php
// ajax/fetch_team_messages.php
ob_start(); // Start buffering
require_once '../config/db.php';

// session_start(); // db.php already starts session if needed, but double check
if (session_status() === PHP_SESSION_NONE)
    session_start();

ob_clean(); // Clear any previous output (e.g. from db.php)
header('Content-Type: application/json');

if (!isset($_SESSION['employee_db_id'])) {
    exit(json_encode(['messages' => []]));
}

$emp_id = $_SESSION['employee_db_id'];
$team_id = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0; // Fix: was using undefined $last_id

try {
    // Verify membership
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ? AND emp_id = ?");
    $stmt->execute([$team_id, $emp_id]);
    if ($stmt->fetchColumn() == 0) {
        exit(json_encode(['messages' => []]));
    }

    // Fetch Messages
    $sql = "SELECT tc.*, e.first_name 
            FROM team_chat tc 
            JOIN employees e ON tc.sender_id = e.id 
            WHERE tc.team_id = ? AND tc.id > ? 
            ORDER BY tc.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$team_id, $last_id]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['messages' => $msgs]);

} catch (Exception $e) {
    echo json_encode(['messages' => [], 'error' => $e->getMessage()]);
}
?>