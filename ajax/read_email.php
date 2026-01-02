<?php
// ajax/read_email.php
require_once '../config/db.php';

header('Content-Type: application/json');

if (isset($_SESSION['employee_db_id'])) {
    $my_id = $_SESSION['employee_db_id'];
    $my_type = 'employee';
} elseif (isset($_SESSION['admin_id'])) {
    $my_id = $_SESSION['admin_id'];
    $my_type = 'admin';
} else {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$email_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($email_id <= 0)
    exit;

// 1. Mark as Read (if I am a recipient)
$stmt = $pdo->prepare("UPDATE email_recipients SET is_read = 1 WHERE email_id = ? AND recipient_id = ? AND recipient_type = ?");
$stmt->execute([$email_id, $my_id, $my_type]);

// 2. Fetch Email Content
$stmt = $pdo->prepare("SELECT * FROM emails WHERE id = ?");
$stmt->execute([$email_id]);
$email = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$email) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

// 3. Resolve Sender Name
$sender_name = "Unknown";
if ($email['sender_type'] == 'admin') {
    $u = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
    $u->execute([$email['sender_id']]);
    $res = $u->fetch();
    if ($res)
        $sender_name = "Admin: " . $res['username'];
} else {
    $u = $pdo->prepare("SELECT first_name, last_name, employee_id FROM employees WHERE id = ?");
    $u->execute([$email['sender_id']]);
    $res = $u->fetch();
    if ($res)
        $sender_name = $res['first_name'] . ' ' . $res['last_name'] . ' (' . $res['employee_id'] . ')';
}

// 4. Resolve Recipients (To/CC only, BCC shouldn't be shown to everyone usually, 
//    but in a simple system, the sender sees BCC, recipients don't see BCC.
//    However, I am the viewer.
//    If I am the Sender, I see everything.
//    If I am a Recipient, I see To and CC. I should NOT see BCC unless I AM the BCC target? 
//    Actually standard email: To/CC are public header. BCC is hidden.
//    Let's just show To and CC for now.

function getRecipients($pdo, $email_id, $group)
{
    $stmt = $pdo->prepare("SELECT recipient_id, recipient_type FROM email_recipients WHERE email_id = ? AND recipient_group = ?");
    $stmt->execute([$email_id, $group]);
    $rows = $stmt->fetchAll();
    $names = [];
    foreach ($rows as $r) {
        if ($r['recipient_type'] == 'admin') {
            $u = $pdo->query("SELECT username FROM admins WHERE id = " . $r['recipient_id'])->fetch();
            if ($u)
                $names[] = "Admin: " . $u['username'];
        } else {
            $u = $pdo->query("SELECT first_name, last_name FROM employees WHERE id = " . $r['recipient_id'])->fetch();
            if ($u)
                $names[] = $u['first_name'] . ' ' . $u['last_name'];
        }
    }
    return implode(', ', $names);
}

$to_list = getRecipients($pdo, $email_id, 'to');
$cc_list = getRecipients($pdo, $email_id, 'cc');

echo json_encode([
    'id' => $email['id'],
    'subject' => $email['subject'],
    'body' => $email['body'],
    'attachment' => $email['attachment'],
    'attachment_name' => $email['attachment_name'],
    'created_at' => date('M d, Y h:i A', strtotime($email['created_at'])),
    'sender_id' => $email['sender_id'],
    'sender_type' => $email['sender_type'],
    'sender_name' => $sender_name,
    'to_recipients' => $to_list,
    'cc_recipients' => $cc_list
]);
?>