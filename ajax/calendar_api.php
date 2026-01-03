<?php
// ajax/calendar_api.php
require_once '../config/db.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$admin_logged = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$emp_logged = isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true;

if (!$admin_logged && !$emp_logged) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 1. Fetch Year Data (Open to all allowed users)
if ($action === 'fetch_year') {
    $year = $_POST['year'] ?? date('Y');

    // Holidays - Alias fields for frontend consistency
    $stmt = $pdo->prepare("SELECT id, date, name as title, type, 'holiday' as category FROM holidays WHERE YEAR(date) = ?");
    $stmt->execute([$year]);
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Events - Alias fields
    $stmt = $pdo->prepare("SELECT id, event_date as date, event_title as title, event_type as type, event_description as description, 'event' as category FROM calendar_events WHERE YEAR(event_date) = ?");
    $stmt->execute([$year]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => array_merge($holidays, $events)]);
    exit;
}

// --- ADMIN ONLY ACTIONS BELOW ---
if (!$admin_logged) {
    echo json_encode(['status' => 'error', 'message' => 'Admin required']);
    exit;
}
$admin_id = $_SESSION['admin_id'] ?? 0;

if ($action === 'save_holiday') {
    $id = $_POST['id'] ?? null;
    $date = $_POST['date'];
    $name = $_POST['name'];
    $type = $_POST['type'];

    if ($id) {
        $upd = $pdo->prepare("UPDATE holidays SET name=?, type=?, date=? WHERE id=?");
        $upd->execute([$name, $type, $date, $id]);
    } else {
        // Check dupe date for holidays? Usually yes.
        $chk = $pdo->prepare("SELECT id FROM holidays WHERE date = ?");
        $chk->execute([$date]);
        if ($chk->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Holiday already exists on this date']);
            exit;
        }
        $ins = $pdo->prepare("INSERT INTO holidays (date, name, type, created_by) VALUES (?, ?, ?, ?)");
        $ins->execute([$date, $name, $type, $admin_id]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'save_event') {
    $id = $_POST['id'] ?? null;
    $date = $_POST['date'];
    $title = $_POST['title'];
    $desc = $_POST['description'] ?? '';
    $type = $_POST['type'];

    if ($id) {
        $upd = $pdo->prepare("UPDATE calendar_events SET event_title=?, event_description=?, event_type=?, event_date=? WHERE id=?");
        $upd->execute([$title, $desc, $type, $date, $id]);
    } else {
        $ins = $pdo->prepare("INSERT INTO calendar_events (event_date, event_title, event_description, event_type, created_by) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$date, $title, $desc, $type, $admin_id]);
    }
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'delete_item') {
    $id = $_POST['id'];
    $cat = $_POST['category'];

    if ($cat === 'holiday') {
        $del = $pdo->prepare("DELETE FROM holidays WHERE id = ?");
    } else {
        $del = $pdo->prepare("DELETE FROM calendar_events WHERE id = ?");
    }
    $del->execute([$id]);
    echo json_encode(['status' => 'success']);
    exit;
}
?>