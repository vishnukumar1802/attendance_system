<?php
// employee/dashboard.php
require_once '../config/db.php';
require_once '../includes/emp_header.php';

$emp_id = $_SESSION['employee_db_id'];
$today = date('Y-m-d');
$message = '';
$error = '';

// Check today's status
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = :eid AND date = :date");
$stmt->execute(['eid' => $emp_id, 'date' => $today]);
$attendance = $stmt->fetch();

// Handle Check In
if (isset($_POST['check_in'])) {
    if (!$attendance) {
        $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (:eid, :date, NOW(), 'pending')");
        if ($stmt->execute(['eid' => $emp_id, 'date' => $today])) {
            header("Refresh:0"); // Reload to update state
            exit;
        } else {
            $error = "Failed to check in.";
        }
    }
}

// Handle Check Out (Work Submission)
if (isset($_POST['submit_work'])) {
    $description = clean_input($_POST['work_description']);
    $link = clean_input($_POST['work_link']);

    // Validate submission linked to attendance
    if ($attendance && !$attendance['check_out_time']) {
        if (empty($description)) {
            $error = "Work description is required.";
        } else {
            $pdo->beginTransaction();
            try {
                // 1. Update Attendance
                $upd = $pdo->prepare("UPDATE attendance SET check_out_time = NOW() WHERE id = :id");
                $upd->execute(['id' => $attendance['id']]);

                // 2. Insert Work Submission
                $ins = $pdo->prepare("INSERT INTO work_submissions (attendance_id, description, link) VALUES (:aid, :desc, :link)");
                $ins->execute(['aid' => $attendance['id'], 'desc' => $description, 'link' => $link]);

                $pdo->commit();
                $message = "Checked out successfully. Good job!";
                header("Refresh:0");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error submitting work: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">

        <?php if (!$attendance): ?>
            <!-- STATE: NOT CHECKED IN -->
            <div class="attendance-action-card">
                <div class="mb-4">
                    <i class="bi bi-geo-alt fs-1 text-primary"></i>
                    <h3>Not Checked In</h3>
                    <p class="text-muted">Please check in to start your work day.</p>
                </div>
                <form method="post">
                    <button type="submit" name="check_in" class="btn btn-primary btn-lg px-5 rounded-pill shadow">
                        <i class="bi bi-box-arrow-in-right me-2"></i> CHECK IN NOW
                    </button>
                </form>
            </div>

        <?php elseif ($attendance['check_in_time'] && !$attendance['check_out_time']): ?>
            <!-- STATE: CHECKED IN (Working) -->
            <div class="attendance-action-card border-primary">
                <div class="mb-4">
                    <span class="badge bg-success mb-2 px-3 py-2">CURRENTLY WORKING</span>
                    <br>
                    <i class="bi bi-clock-history fs-1 text-primary"></i>
                    <div class="timer-display" id="clock">00:00:00</div>
                    <p class="text-muted">Started at <?php echo date('h:i A', strtotime($attendance['check_in_time'])); ?>
                    </p>
                </div>

                <!-- Open Modal for Work Submission -->
                <button type="button" class="btn btn-danger btn-lg px-5 rounded-pill shadow" data-bs-toggle="modal"
                    data-bs-target="#checkoutModal">
                    <i class="bi bi-box-arrow-right me-2"></i> CHECK OUT
                </button>
            </div>

            <!-- Checkout Modal -->
            <div class="modal fade" id="checkoutModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Daily Work Submission</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <p class="text-muted small mb-3">You must submit your work summary to check out.</p>

                                <div class="mb-3">
                                    <label class="form-label">Work Description <span class="text-danger">*</span></label>
                                    <textarea name="work_description" class="form-control" rows="4"
                                        placeholder="What did you work on today?" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Project Link (Optional)</label>
                                    <input type="url" name="work_link" class="form-control"
                                        placeholder="https://drive.google.com/...">
                                    <div class="form-text">Link to Drive, GitHub, or documents.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="submit_work" class="btn btn-danger">Submit & Check Out</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- STATE: COMPLETED -->
            <div class="attendance-action-card">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill fs-1 text-success"></i>
                    <h3 class="text-success mt-3">Day Completed!</h3>
                    <p class="text-muted">You have checked out for today.</p>
                </div>
                <div class="card bg-light border-0 p-3 text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Check In:</strong>
                        <span><?php echo date('h:i A', strtotime($attendance['check_in_time'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>Check Out:</strong>
                        <span><?php echo date('h:i A', strtotime($attendance['check_out_time'])); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    // Simple Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        const clockElement = document.getElementById('clock');
        if (clockElement) {
            clockElement.textContent = timeString;
        }
    }

    // Update every second if clock element exists
    if (document.getElementById('clock')) {
        setInterval(updateClock, 1000);
        updateClock();
    }
</script>

<?php require_once '../includes/emp_footer.php'; ?>