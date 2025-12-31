<?php
// employee/request_access.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$message = '';
$error = '';

if (isset($_POST['request_access'])) {
    $reason = clean_input($_POST['reason']);
    $date = $_POST['requested_till'];

    // Validate Date (Must not be past - strictly speaking, user might request access FOR today, which is not past.
    // "Past dates must NOT be selectable". So >= Today.
    if ($date < date('Y-m-d')) {
        $error = "Date cannot be in the past.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO temp_access_requests (emp_id, reason, requested_till) VALUES (?, ?, ?)");
        if ($stmt->execute([$emp_id, $reason, $date])) {
            $message = "Request submitted. Proceed to profile completion in the meantime.";
        } else {
            $error = "Failed to submit request.";
        }
    }
}

// Fetch Status
$stmt = $pdo->prepare("SELECT * FROM temp_access_requests WHERE emp_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$emp_id]);
$requests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Request Temporary Access</h1>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> If you cannot complete your profile immediately, you can request temporary
    attendance access.
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Why is your profile incomplete?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Request Access Until</label>
                        <input type="date" name="requested_till" class="form-control" min="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>
                    <button type="submit" name="request_access" class="btn btn-warning w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <h5 class="mb-3">My Requests</h5>
        <div class="list-group">
            <?php foreach ($requests as $r): ?>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">Till: <?php echo date('M d, Y', strtotime($r['requested_till'])); ?></h6>
                        <small>
                            <?php
                            $cls = match ($r['status']) {
                                'approved' => 'text-success',
                                'rejected' => 'text-danger',
                                default => 'text-muted'
                            };
                            echo "<span class='$cls fw-bold'>" . ucfirst($r['status']) . "</span>";
                            ?>
                        </small>
                    </div>
                    <p class="mb-1 small"><?php echo htmlspecialchars($r['reason']); ?></p>
                    <?php if ($r['admin_response']): ?>
                        <small class="text-muted d-block border-top mt-2 pt-1">Admin:
                            <?php echo htmlspecialchars($r['admin_response']); ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/emp_footer.php'; ?>