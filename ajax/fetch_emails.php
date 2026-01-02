<?php
// ajax/fetch_emails.php
require_once '../config/db.php';

header('Content-Type: application/json');

if (isset($_SESSION['employee_db_id'])) {
    $my_id = $_SESSION['employee_db_id'];
    $my_type = 'employee';
} elseif (isset($_SESSION['admin_id'])) {
    $my_id = $_SESSION['admin_id'];
    $my_type = 'admin';
} else {
    echo json_encode([]);
    exit;
}

$folder = isset($_GET['folder']) ? $_GET['folder'] : 'inbox';

$data = [];

if ($folder === 'inbox') {
    // Get emails where I am a recipient (To, CC, BCC)
    $sql = "SELECT e.id, e.subject, e.created_at, e.sender_id, e.sender_type,
                   er.is_read
            FROM email_recipients er
            JOIN emails e ON er.email_id = e.id
            WHERE er.recipient_id = ? AND er.recipient_type = ? AND er.is_deleted = 0
            ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$my_id, $my_type]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        // Resolve Sender Name
        $name = "Unknown";
        if ($r['sender_type'] == 'admin') {
            $u = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
            $u->execute([$r['sender_id']]);
            $res = $u->fetch();
            if ($res)
                $name = "Admin: " . $res['username'];
        } else {
            $u = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE id = ?");
            $u->execute([$r['sender_id']]);
            $res = $u->fetch();
            if ($res)
                $name = $res['first_name'] . ' ' . $res['last_name'];
        }

        $data[] = [
            'id' => $r['id'],
            'subject' => $r['subject'],
            'created_at' => date('M d, H:i', strtotime($r['created_at'])),
            'sender_name' => $name,
            'is_read' => $r['is_read'],
            'snippet' => '...' // Could fetch body substring if needed, keeping it light
        ];
    }
} elseif ($folder === 'sent') {
    // Get emails I sent
    $sql = "SELECT id, subject, created_at FROM emails WHERE sender_id = ? AND sender_type = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$my_id, $my_type]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        // Fetch Recipients Summary (Just 2-3 names)
        // This is a bit expensive in loop, but okay for low volume
        $recips = $pdo->prepare("SELECT recipient_id, recipient_type 
                                 FROM email_recipients 
                                 WHERE email_id = ? AND recipient_group = 'to' LIMIT 2");
        $recips->execute([$r['id']]);
        $toList = $recips->fetchAll();

        $names = [];
        foreach ($toList as $rec) {
            if ($rec['recipient_type'] == 'admin') {
                $names[] = "Admin"; // Simplify
            } else {
                $u = $pdo->prepare("SELECT first_name FROM employees WHERE id = ?");
                $u->execute([$rec['recipient_id']]);
                $res = $u->fetch();
                if ($res)
                    $names[] = $res['first_name'];
            }
        }
        $recipient_summary = implode(', ', $names);
        if (count($names) < count($toList))
            $recipient_summary .= "...";

        $data[] = [
            'id' => $r['id'],
            'subject' => $r['subject'],
            'created_at' => date('M d, H:i', strtotime($r['created_at'])),
            'recipient_summary' => $recipient_summary ?: '(No-body)',
            'snippet' => '...'
        ];
    }
}

echo json_encode($data);
?>