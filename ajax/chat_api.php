<?php
// ajax/chat_api.php
// Strict One-to-One Private Chat API (V4)

require_once '../config/db.php';

// 1. Security & Session Check
if (session_status() === PHP_SESSION_NONE)
    session_start();

$my_id = null;
$my_type = null;

if (isset($_SESSION['admin_id'])) {
    $my_id = $_SESSION['admin_id'];
    $my_type = 'admin';
} elseif (isset($_SESSION['employee_db_id'])) {
    $my_id = $_SESSION['employee_db_id'];
    $my_type = 'employee';
} else {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// 2. Action Router
try {
    switch ($action) {

        // A. FETCH LIST of Active Conversations
        case 'fetch_list':
            // Logic: Find all conversations where I am User One OR User Two
            // Join with other user's name
            // Get last message info

            // Subquery for unread count
            // Subquery for last message
            $sql = "
                SELECT 
                    c.id as conversation_id,
                    c.user_one_id, c.user_one_type,
                    c.user_two_id, c.user_two_type,
                    
                    -- Determine who the 'Other' person is
                    CASE 
                        WHEN c.user_one_id = ? AND c.user_one_type = ? THEN c.user_two_id
                        ELSE c.user_one_id
                    END as other_id,
                    CASE 
                        WHEN c.user_one_id = ? AND c.user_one_type = ? THEN c.user_two_type
                        ELSE c.user_one_type
                    END as other_type,
                    
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND NOT (m.sender_id = ? AND m.sender_type = ?)) as unread_count,
                    
                    (SELECT message_text FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_msg,
                    (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_time
                    
                FROM conversations c
                WHERE 
                    (c.user_one_id = ? AND c.user_one_type = ?) 
                    OR 
                    (c.user_two_id = ? AND c.user_two_type = ?)
                ORDER BY c.updated_at DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $my_id,
                $my_type, // For Case ID
                $my_id,
                $my_type, // For Case Type
                $my_id,
                $my_type, // For Unread Count (Not sent by me)
                $my_id,
                $my_type, // Where One
                $my_id,
                $my_type  // Where Two
            ]);
            $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enrich with Names (N+1 query but safe for small lists, optimize later if needed)
            foreach ($convs as &$c) {
                if ($c['other_type'] == 'admin') {
                    $u = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
                    $u->execute([$c['other_id']]);
                    $res = $u->fetch();
                    $c['name'] = $res ? "Admin: " . $res['username'] : "Unknown Admin";
                    $c['avatar_initial'] = "A";
                } else {
                    $u = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE id = ?");
                    $u->execute([$c['other_id']]);
                    $res = $u->fetch();
                    $c['name'] = $res ? $res['first_name'] . " " . $res['last_name'] : "Unknown Employee";
                    $c['avatar_initial'] = substr($c['name'], 0, 1);
                }
            }

            echo json_encode(['status' => 'success', 'data' => $convs]);
            break;

        // B. FETCH ALL USERS (To start a new chat)
        case 'fetch_users':
            $users = [];

            // 1. Admins
            $stmtA = $pdo->query("SELECT id, username FROM admins");
            while ($row = $stmtA->fetch()) {
                if ($my_type === 'admin' && $row['id'] == $my_id)
                    continue;
                $users[] = ['id' => $row['id'], 'type' => 'admin', 'name' => "Admin: " . $row['username']];
            }

            // 2. Employees(All)
            $stmtE = $pdo->query("SELECT id, first_name, last_name FROM employees");
            while ($row = $stmtE->fetch()) {
                if ($my_type === 'employee' && $row['id'] == $my_id)
                    continue;
                $users[] = ['id' => $row['id'], 'type' => 'employee', 'name' => $row['first_name'] . " " . $row['last_name']];
            }

            echo json_encode(['status' => 'success', 'data' => $users]);
            break;

        // C. START (OR GET) CONVERSATION
        case 'start_chat':
            $target_id = $_POST['target_id'];
            $target_type = $_POST['target_type'];

            // Check if exists
            // We must check both (Me, Them) and (Them, Me)
            $sql = "SELECT id FROM conversations WHERE 
                    (user_one_id = ? AND user_one_type = ? AND user_two_id = ? AND user_two_type = ?)
                    OR
                    (user_one_id = ? AND user_one_type = ? AND user_two_id = ? AND user_two_type = ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $my_id,
                $my_type,
                $target_id,
                $target_type,
                $target_id,
                $target_type,
                $my_id,
                $my_type
            ]);
            $exist = $stmt->fetch();

            if ($exist) {
                echo json_encode(['status' => 'success', 'conversation_id' => $exist['id']]);
            } else {
                // Create New
                $ins = $pdo->prepare("INSERT INTO conversations (user_one_id, user_one_type, user_two_id, user_two_type) VALUES (?, ?, ?, ?)");
                $ins->execute([$my_id, $my_type, $target_id, $target_type]);
                echo json_encode(['status' => 'success', 'conversation_id' => $pdo->lastInsertId()]);
            }
            break;

        // D. FETCH MESSAGES
        case 'fetch_messages':
            $conv_id = (int) $_POST['conversation_id'];

            // SECURITY: Ensure I am part of this conversation
            $chk = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND ((user_one_id = ? AND user_one_type = ?) OR (user_two_id = ? AND user_two_type = ?))");
            $chk->execute([$conv_id, $my_id, $my_type, $my_id, $my_type]);
            if (!$chk->fetch()) {
                exit(json_encode(['status' => 'error', 'message' => 'Access Denied']));
            }

            // Mark as read (only messages NOT from me)
            // FEATURE 1: Read Receipts (Update read_at if not set)
            $upd = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE conversation_id = ? AND is_read = 0 AND NOT (sender_id = ? AND sender_type = ?)");
            $upd->execute([$conv_id, $my_id, $my_type]);

            // Get Messages
            $sql = "SELECT id, sender_id, sender_type, message_type, message_text, file_name, file_path, is_read, read_at, created_at, edited_at, is_deleted 
                    FROM messages WHERE conversation_id = ? ORDER BY created_at ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$conv_id]);

            $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mask deleted messages
            foreach ($msgs as &$m) {
                if ($m['is_deleted'] == 1) {
                    $m['message_text'] = "This message was deleted";
                    $m['file_path'] = null; // Hide file if deleted
                    $m['file_name'] = null;
                }
            }

            echo json_encode(['status' => 'success', 'data' => $msgs, 'my_id' => $my_id, 'my_type' => $my_type]);
            break;

        // F. EDIT MESSAGE
        case 'edit_message':
            $msg_id = (int) $_POST['message_id'];
            $new_text = trim($_POST['message_text']);

            if (empty($new_text))
                exit(json_encode(['status' => 'error', 'message' => 'Empty text']));

            // Validate Ownership & Read Status (Edit allowed only if not read)
            $stmt = $pdo->prepare("SELECT is_read, is_deleted FROM messages WHERE id = ? AND sender_id = ? AND sender_type = ?");
            $stmt->execute([$msg_id, $my_id, $my_type]);
            $msg = $stmt->fetch();

            if (!$msg) {
                exit(json_encode(['status' => 'error', 'message' => 'Message not found']));
            }

            if ($msg['is_deleted'] == 1) {
                exit(json_encode(['status' => 'error', 'message' => 'Cannot edit deleted message']));
            }

            if ($msg['is_read'] == 1) {
                exit(json_encode(['status' => 'error', 'message' => 'Cannot edit (Already Read)']));
            }

            $upd = $pdo->prepare("UPDATE messages SET message_text = ?, edited_at = NOW() WHERE id = ?");
            $upd->execute([$new_text, $msg_id]);
            echo json_encode(['status' => 'success']);
            break;

        // I. SEARCH DIRECTORY (Sidebar Search)
        case 'search_directory':
            $term = trim($_POST['term']);
            if (empty($term))
                exit(json_encode(['status' => 'success', 'data' => []]));

            $results = [];

            // Search depending on my role
            if ($my_type === 'admin') {
                // Admin searches Employees
                $sql = "SELECT id, first_name, last_name, 'employee' as type FROM employees 
                        WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 10";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%$term%", "%$term%"]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Employee searches Admins & Other Employees
                // 1. Admins
                $stmt = $pdo->prepare("SELECT id, username as first_name, '' as last_name, 'admin' as type FROM admins WHERE username LIKE ? LIMIT 5");
                $stmt->execute(["%$term%"]);
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 2. Employees (excluding me)
                $stmt = $pdo->prepare("SELECT id, first_name, last_name, 'employee' as type FROM employees WHERE id != ? AND (first_name LIKE ? OR last_name LIKE ?) LIMIT 5");
                $stmt->execute([$my_id, "%$term%", "%$term%"]);
                $emps = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $results = array_merge($admins, $emps);
            }

            // Format name
            foreach ($results as &$r) {
                $r['name'] = trim($r['first_name'] . ' ' . $r['last_name']);
            }

            echo json_encode(['status' => 'success', 'data' => $results]);
            break;

        // G. DELETE MESSAGE (Soft)
        case 'delete_message':
            $msg_id = (int) $_POST['message_id'];

            // Validate Ownership
            $stmt = $pdo->prepare("SELECT id FROM messages WHERE id = ? AND sender_id = ? AND sender_type = ?");
            $stmt->execute([$msg_id, $my_id, $my_type]);

            if ($stmt->fetch()) {
                $del = $pdo->prepare("UPDATE messages SET is_deleted = 1, message_text = NULL, file_path = NULL WHERE id = ?");
                $del->execute([$msg_id]);
                echo json_encode(['status' => 'success']);
            } else {
                exit(json_encode(['status' => 'error', 'message' => 'Access Denied']));
            }
            break;

        // H. SEARCH MESSAGES
        case 'search_messages':
            $conv_id = (int) $_POST['conversation_id'];
            $term = trim($_POST['term']);

            if (empty($term))
                exit(json_encode(['status' => 'success', 'data' => []]));

            // Ensure I am part of conv
            $chk = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND ((user_one_id = ? AND user_one_type = ?) OR (user_two_id = ? AND user_two_type = ?))");
            $chk->execute([$conv_id, $my_id, $my_type, $my_id, $my_type]);
            if (!$chk->fetch())
                exit(json_encode(['status' => 'error', 'message' => 'Access Denied']));

            // Search
            $sql = "SELECT * FROM messages WHERE conversation_id = ? AND is_deleted = 0 AND message_text LIKE ? ORDER BY created_at DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$conv_id, "%$term%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $results]);
            break;

        // E. SEND MESSAGE (Modified slightly to include case break, keeping existing logic)
        case 'send_message':
            $conv_id = (int) $_POST['conversation_id'];
            $text = trim($_POST['message']); // Nullable if file

            // SECURITY CHECK
            $chk = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND ((user_one_id = ? AND user_one_type = ?) OR (user_two_id = ? AND user_two_type = ?))");
            $chk->execute([$conv_id, $my_id, $my_type, $my_id, $my_type]);
            if (!$chk->fetch()) {
                exit(json_encode(['status' => 'error', 'message' => 'Access Denied']));
            }

            // File Upload Logic
            $file_path = null;
            $file_name = null;
            $msg_type = 'text';

            if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                // 10MB Check
                if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
                    exit(json_encode(['status' => 'error', 'message' => 'File too large (Max 10MB)']));
                }

                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];

                if (in_array($ext, $allowed)) {
                    $cleanName = uniqid() . '.' . $ext;
                    $uploadDir = '../uploads/chat_files/';
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $cleanName)) {
                        $file_path = 'uploads/chat_files/' . $cleanName; // Store relative for frontend
                        $file_name = $_FILES['file']['name'];
                        $msg_type = 'file';
                    }
                }
            }

            if (empty($text) && empty($file_path)) {
                exit(json_encode(['status' => 'error', 'message' => 'Empty message']));
            }

            $sql = "INSERT INTO messages (conversation_id, sender_id, sender_type, message_type, message_text, file_name, file_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$conv_id, $my_id, $my_type, $msg_type, $text, $file_name, $file_path]);

            // Update Conversation Timestamp (for sorting)
            $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conv_id]);

            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>