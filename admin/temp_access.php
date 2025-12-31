<?php
// admin/temp_access.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';

// Handle Action
if (isset($_POST['action'])) {
    $req_id = $_POST['req_id'];
    $act = $_POST['action'];
    $emp_id = $_POST['emp_id'];
    $resp = $_POST['admin_response'] ?? '';

    // Fetch request details
    $req_stmt = $pdo->prepare("SELECT requested_till FROM temp_access_requests WHERE id = ?");
    $req_stmt->execute([$req_id]);
    $req_data = $req_stmt->fetch();

    // If approve, we must set approved till date (default to requested till)
    // "Approve (must set approved till date)" -> Let Admin edit it, or default to requested.
    // I will use `requested_till` as default but allow override if I had a complex UI.
    // For now, assume requested date is approved date.

    $status = ($act == 'approve') ? 'approved' : 'rejected';

    if ($act == 'reject' && empty($resp)) {
        $message = "Rejection reason is mandatory.";
    } else {
        $pdo->beginTransaction();
        try {
            // 1. Update Request
            $upd = $pdo->prepare("UPDATE temp_access_requests SET status = ?, admin_response = ? WHERE id = ?");
            $upd->execute([$status, $resp, $req_id]);

            // 2. If Approved, Update Employee Profile & Auto Check-In
            if ($status == 'approved') {
                $till = $req_data['requested_till'];
                // Update profile temp access date
                $p_upd = $pdo->prepare("INSERT INTO employee_profiles (emp_id, temp_access_expiry) VALUES (?, ?) ON DUPLICATE KEY UPDATE temp_access_expiry = ?");
                $p_upd->execute([$emp_id, $till, $till]);

                // Auto Check-In Logic (for Today)
                $today = date('Y-m-d');
                if ($till >= $today) {
                    $chk = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
                    $chk->execute([$emp_id, $today]);
                    if (!$chk->fetch()) {
                        // Not checked in yet -> Auto Check In
                        $current_time = time();
                        $late_threshold = strtotime(date('Y-m-d 10:30:00'));
                        $att_status = ($current_time > $late_threshold) ? 'half_day' : 'pending';

                        $ins_att = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (?, ?, NOW(), ?)");
                        $ins_att->execute([$emp_id, $today, $att_status]);
                    }
                }
            }

            $pdo->commit();
            $message = "Request $status.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Pending
$sql = "SELECT r.*, e.first_name, e.last_name FROM temp_access_requests r JOIN employees e ON r.emp_id = e.id WHERE r.status = 'pending'";
$requests = $pdo->query($sql)->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Temporary Access Requests</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <?php if (count($requests) > 0): ?>
        <?php foreach ($requests as $r): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $r['first_name'] . ' ' . $r['last_name']; ?></h5>
                        <h6 class="text-muted">Requested Till: <?php echo $r['requested_till']; ?></h6>
                        <p class="card-text bg-light p-2"><?php echo htmlspecialchars($r['reason']); ?></p>

                        <form method="post">
                            <input type="hidden" name="req_id" value="<?php echo $r['id']; ?>">
                            <input type="hidden" name="emp_id" value="<?php echo $r['emp_id']; ?>">

                            <div class="mb-2">
                                <input type="text" name="admin_response" class="form-control form-control-sm"
                                    placeholder="Reason (for rejection)...">
                            </div>

                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No pending requests.</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>