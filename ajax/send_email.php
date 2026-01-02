<?php
// ajax/send_email.php
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_logged_in']) && !isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Determine Sender
if (isset($_SESSION['employee_db_id'])) {
    $sender_id = $_SESSION['employee_db_id'];
    $sender_type = 'employee';
} else {
    $sender_id = $_SESSION['admin_id'];
    $sender_type = 'admin';
}

$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '(No Subject)';
$body = isset($_POST['body']) ? trim($_POST['body']) : '';

// Validation
if (empty($_POST['to'])) {
    echo json_encode(['status' => 'error', 'message' => 'Recipient required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Handle Attachment
    $attachment_path = null;
    $attachment_name = null;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/email_attachments/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_name = basename($_FILES['attachment']['name']);
        $target = $upload_dir . uniqid() . '_' . $file_name;

        // Allowed extensions (block executables)
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'exe', 'bat', 'sh'])) {
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
                $attachment_path = 'uploads/email_attachments/' . basename($target);
                $attachment_name = $file_name;
            }
        }
    }

    // 2. Insert into emails
    $stmt = $pdo->prepare("INSERT INTO emails (sender_id, sender_type, subject, body, attachment, attachment_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sender_id, $sender_type, $subject, $body, $attachment_path, $attachment_name]);
    $email_id = $pdo->lastInsertId();

    // 3. Process Recipients
    function addRecipients($pdo, $email_id, $list, $type_enum)
    {
        if (!$list)
            return;
        if (!is_array($list))
            $list = [$list];

        $stmt = $pdo->prepare("INSERT INTO email_recipients (email_id, recipient_id, recipient_type, recipient_group) VALUES (?, ?, ?, ?)");

        foreach ($list as $val) {
            // Value format: "admin_1", "employee_5"
            $parts = explode('_', $val);
            if (count($parts) == 2) {
                $r_type = $parts[0];
                $r_id = (int) $parts[1];
                $stmt->execute([$email_id, $r_id, $r_type, $type_enum]);
            }
        }
    }

    addRecipients($pdo, $email_id, $_POST['to'], 'to');
    if (isset($_POST['cc']))
        addRecipients($pdo, $email_id, $_POST['cc'], 'cc');
    if (isset($_POST['bcc']))
        addRecipients($pdo, $email_id, $_POST['bcc'], 'bcc');

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>