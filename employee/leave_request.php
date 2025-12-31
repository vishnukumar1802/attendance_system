<?php
// employee/leave_request.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';
$error = '';

// Handle Submission
if (isset($_POST['apply_leave'])) {
    $type = $_POST['type'];
    $subject = clean_input($_POST['subject']);
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $msg = clean_input($_POST['message']);

    // Validate Dates
    $today = date('Y-m-d');
    if ($start < $today) {
        $error = "Start date cannot be in the past.";
    } elseif (strtotime($end) < strtotime($start)) {
        $error = "End date cannot be before start date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO leaves (employee_id, type, subject, message, start_date, end_date) VALUES (:eid, :typ, :sub, :msg, :sd, :ed)");
        if ($stmt->execute(['eid' => $emp_id, 'typ' => $type, 'sub' => $subject, 'msg' => $msg, 'sd' => $start, 'ed' => $end])) {
            $message = "Leave request submitted successfully.";
        } else {
            $error = "Failed to submit request.";
        }
    }
}

// Fetch My Leaves
$stmt = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$emp_id]);
$leaves = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Leave Requests</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">Apply for Leave / WFH</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="leave">Leave (Time Off)</option>
                            <option value="wfh">Work From Home</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Sick Leave" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dates</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text">From</span>
                            <input type="date" name="start_date" class="form-control" min="<?php echo date('Y-m-d'); ?>"
                                required>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">To</span>
                            <input type="date" name="end_date" class="form-control" min="<?php echo date('Y-m-d'); ?>"
                                required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Reason..."
                            required></textarea>
                    </div>
                    <button type="submit" name="apply_leave" class="btn btn-primary w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Subject</th>
                                <th>Dates</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Admin Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leaves) > 0): ?>
                                <?php foreach ($leaves as $l): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($l['subject']); ?></div>
                                            <div class="small text-muted">
                                                <?php echo date('M d, Y', strtotime($l['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('M d', strtotime($l['start_date'])) . ' - ' . date('M d', strtotime($l['end_date'])); ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper($l['type']); ?></span></td>
                                        <td>
                                            <?php
                                            $stClass = match ($l['status']) {
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                default => 'bg-warning text-dark'
                                            };
                                            ?>
                                            <span
                                                class="badge <?php echo $stClass; ?>"><?php echo ucfirst($l['status']); ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo htmlspecialchars($l['admin_response'] ?? '-'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No entries found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/emp_footer.php'; ?>