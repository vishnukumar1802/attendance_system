<?php
// admin/attendance.php
require_once '../config/db.php';
require_once '../includes/admin_header.php';

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$message = '';

// Handle Status Updates (Approve/Reject)
if (isset($_POST['action']) && isset($_POST['attendance_id'])) {
    $att_id = $_POST['attendance_id'];
    $act = $_POST['action'];
    $new_status = ($act == 'approve') ? 'present' : 'rejected';

    // If rejecting, maybe we want to set check_out time to null or keep it?
    // Usually rejected means not valid. 
    // "Approve work before attendance is finalized."

    $stmt = $pdo->prepare("UPDATE attendance SET status = :status WHERE id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $att_id]);
    $message = "Attendance marked as " . ucfirst($new_status);
}

// Fetch Attendance for Date
$sql = "
    SELECT a.*, 
           w.description as work_desc, 
           w.link as work_link, 
           e.first_name, 
           e.last_name, 
           e.employee_id
    FROM attendance a 
    LEFT JOIN work_submissions w ON a.id = w.attendance_id 
    JOIN employees e ON a.employee_id = e.id 
    WHERE a.date = :date 
    ORDER BY a.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['date' => $date]);
$records = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Attendance Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <form class="d-flex align-items-center" method="get">
            <label class="me-2">Date:</label>
            <input type="date" name="date" class="form-control form-control-sm me-2" value="<?php echo $date; ?>"
                onchange="this.form.submit()">
        </form>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Time Log</th>
                        <th>Work Submission</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-placeholder me-2 rounded-circle bg-light d-flex align-items-center justify-content-center text-primary"
                                            style="width: 32px; height: 32px; font-weight:bold;">
                                            <?php echo strtoupper(substr($row['first_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                            </div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($row['employee_id']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-muted">In:</span>
                                        <?php echo $row['check_in_time'] ? date('h:i A', strtotime($row['check_in_time'])) : '-'; ?>
                                        <br>
                                        <span class="text-muted">Out:</span>
                                        <?php echo $row['check_out_time'] ? date('h:i A', strtotime($row['check_out_time'])) : '-'; ?>
                                    </div>
                                </td>
                                <td style="max-width: 300px;">
                                    <?php if ($row['work_desc'] || $row['work_link']): ?>
                                        <?php if ($row['work_desc']): ?>
                                            <div class="text-truncate mb-1" title="<?php echo htmlspecialchars($row['work_desc']); ?>">
                                                <?php echo htmlspecialchars($row['work_desc']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($row['work_link']): ?>
                                            <a href="<?php echo htmlspecialchars($row['work_link']); ?>" target="_blank"
                                                class="btn btn-xs btn-outline-primary py-0" style="font-size: 0.75rem;">
                                                <i class="bi bi-link-45deg"></i> View Work
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">No Submission</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match ($row['status']) {
                                        'present' => 'status-present',
                                        'absent' => 'status-absent',
                                        'pending' => 'status-pending',
                                        'rejected' => 'status-inactive',
                                        default => 'status-pending'
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success"
                                                    title="Approve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"
                                                    title="Reject">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Allow Admin to Modify -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                                Edit
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="post">
                                                        <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="action" value="approve"
                                                            class="dropdown-item">Mark Present</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post">
                                                        <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="action" value="reject"
                                                            class="dropdown-item">Mark Rejected</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No records found for this date.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>