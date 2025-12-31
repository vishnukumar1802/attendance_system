<?php
// employee/notifications.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];

// Mark all as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$emp_id]);

// Fetch All
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$emp_id]);
$list = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Notifications</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        <?php if (count($list) > 0): ?>
            <?php foreach ($list as $n): ?>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?php echo htmlspecialchars($n['message']); ?></h6>
                        <small class="text-muted"><?php echo date('M d, h:i A', strtotime($n['created_at'])); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="list-group-item text-center py-4 text-muted">No notifications.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/emp_footer.php'; ?>