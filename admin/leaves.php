<?php
// admin/leaves.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$message = '';

// Handle Actions (Approve, Reject, Revoke)
if (isset($_POST['action'])) {
    $leave_id = $_POST['leave_id'];
    $act = $_POST['action']; // approve, reject, revoke
    $response_msg = clean_input($_POST['response_msg'] ?? ''); // Optional for revoke maybe?

    if ($act == 'reject' && empty($response_msg)) {
        $message = "<span class='text-danger'>Rejection reason is mandatory.</span>";
    } else {
        // Fetch Leave Data
        $stmt = $pdo->prepare("SELECT * FROM leaves WHERE id = ?");
        $stmt->execute([$leave_id]);
        $leave = $stmt->fetch();

        if ($leave) {
            $new_status = match ($act) {
                'approve' => 'approved',
                'reject' => 'rejected',
                'revoke' => 'revoked', // New status
                default => 'pending'
            };

            $pdo->beginTransaction();
            try {
                // 1. Update Leave Status
                $upd = $pdo->prepare("UPDATE leaves SET status = :st, admin_response = :resp WHERE id = :id");
                $upd->execute(['st' => $new_status, 'resp' => $response_msg, 'id' => $leave_id]);

                // 2. Logic based on Status
                if ($new_status == 'approved') {
                    // Create Attendance Records
                    $start = new DateTime($leave['start_date']);
                    $end = new DateTime($leave['end_date']);
                    $end->modify('+1 day');
                    $period = new DatePeriod($start, DateInterval::createFromDateString('1 day'), $end);

                    foreach ($period as $dt) {
                        $curr_date = $dt->format("Y-m-d");
                        $ins = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (:eid, :date, 'leave') 
                            ON DUPLICATE KEY UPDATE status = 'leave'");
                        $ins->execute(['eid' => $leave['employee_id'], 'date' => $curr_date]);
                    }
                } elseif ($new_status == 'revoked') {
                    // Start Revocation Logic: Delete or Update Attendance
                    // We need to find attendance records for this employee in this range AND status = 'leave'
                    // If status changed (e.g. they worked?), strictly speaking we shouldn't touch it?
                    // But here we assume it's still 'leave'. Safer to delete only if 'leave'.

                    $start = new DateTime($leave['start_date']);
                    $end = new DateTime($leave['end_date']);
                    $end->modify('+1 day');
                    $period = new DatePeriod($start, DateInterval::createFromDateString('1 day'), $end);

                    foreach ($period as $dt) {
                        $curr_date = $dt->format("Y-m-d");
                        // Only delete if status is 'leave'
                        $del = $pdo->prepare("DELETE FROM attendance WHERE employee_id = ? AND date = ? AND status = 'leave'");
                        $del->execute([$leave['employee_id'], $curr_date]);
                    }
                }

                // 3. Send Notification
                $notif_msg = "Your leave request for " . $leave['start_date'] . " has been " . strtoupper($new_status) . ".";
                if ($response_msg)
                    $notif_msg .= " Admin: " . $response_msg;

                $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                    ->execute([$leave['employee_id'], $notif_msg]);

                $pdo->commit();
                $message = "Leave Request processed (" . ucfirst($new_status) . ").";

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch Pending Leaves
$sql_p = "SELECT l.*, e.first_name, e.last_name FROM leaves l 
        JOIN employees e ON l.employee_id = e.id 
        WHERE l.status = 'pending' 
        ORDER BY l.created_at ASC";
$pending_leaves = $pdo->query($sql_p)->fetchAll();

// Fetch Recent Approved Leaves (For Revocation)
$sql_a = "SELECT l.*, e.first_name, e.last_name FROM leaves l 
        JOIN employees e ON l.employee_id = e.id 
        WHERE l.status = 'approved' 
        ORDER BY l.start_date DESC LIMIT 20";
$approved_leaves = $pdo->query($sql_a)->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Leave Requests</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Pending Requests -->
<h4 class="mb-3 text-primary">Pending Requests</h4>
<div class="row mb-5">
    <?php if (count($pending_leaves) > 0): ?>
        <?php foreach ($pending_leaves as $l): ?>
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm border-primary">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <strong><?php echo htmlspecialchars($l['first_name'] . ' ' . $l['last_name']); ?></strong>
                        <span class="badge bg-info text-dark"><?php echo strtoupper($l['type']); ?></span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($l['subject']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?php echo date('M d', strtotime($l['start_date'])) . ' - ' . date('M d', strtotime($l['end_date'])); ?>
                        </h6>
                        <p class="card-text bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($l['message'])); ?></p>

                        <form method="post">
                            <input type="hidden" name="leave_id" value="<?php echo $l['id']; ?>">
                            <div class="mb-3">
                                <textarea name="response_msg" class="form-control form-control-sm"
                                    placeholder="Reason (Required for Rejection)"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="approve"
                                    class="btn btn-success flex-grow-1">Approve</button>
                                <button type="submit" name="action" value="reject"
                                    class="btn btn-outline-danger flex-grow-1">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-muted">
            <p>No pending requests.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Approved Leaves Management -->
<h4 class="mb-3 text-success border-top pt-4">Approved Leaves (Revoke access)</h4>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Subject</th>
                <th>Dates</th>
                <th>Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($approved_leaves as $al): ?>
                <tr>
                    <td><?php echo htmlspecialchars($al['first_name'] . ' ' . $al['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($al['subject']); ?></td>
                    <td><?php echo date('M d', strtotime($al['start_date'])) . ' - ' . date('M d', strtotime($al['end_date'])); ?>
                    </td>
                    <td><?php echo ucfirst($al['type']); ?></td>
                    <td>
                        <form method="post"
                            onsubmit="return confirm('Are you sure you want to REVOKE this leave? Attendance will be reset.');">
                            <input type="hidden" name="leave_id" value="<?php echo $al['id']; ?>">
                            <input type="hidden" name="response_msg" value="Leave Revoked by Admin">
                            <button type="submit" name="action" value="revoke" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Revoke
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/admin_footer.php'; ?>